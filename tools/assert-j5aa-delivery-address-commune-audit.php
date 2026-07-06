<?php

declare(strict_types=1);

/*
 * J5AA-0 guardrail.
 *
 * Goal: audit DELIVERY addresses around Address.commune without changing the
 * application. Address.commune remains the central address field; for a
 * DELIVERY address it must be a canonical Hodina livrable commune, validated
 * against DeliveryCommune.
 *
 * Cross-platform usage:
 *   php tools/assert-j5aa-delivery-address-commune-audit.php
 *
 * Optional:
 *   APP_ENV=dev php tools/assert-j5aa-delivery-address-commune-audit.php
 */

use App\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, '[J5AA-0][KO] vendor/autoload.php introuvable. Lance Composer puis rejoue cet audit depuis la racine du projet.' . PHP_EOL);
    exit(1);
}

require $autoloadPath;

if (class_exists(Dotenv::class) && is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
    (new Dotenv())->bootEnv($projectRoot . DIRECTORY_SEPARATOR . '.env');
}

// Le container Hodina utilise DEFAULT_URI pour le router/request context.
// Les scripts tools/ ne passent pas par bin/console : on pose donc un fallback
// CLI local uniquement si la variable n'est pas déjà fournie par .env/.env.local.
if (($_SERVER['DEFAULT_URI'] ?? $_ENV['DEFAULT_URI'] ?? getenv('DEFAULT_URI')) === false
    || ($_SERVER['DEFAULT_URI'] ?? $_ENV['DEFAULT_URI'] ?? getenv('DEFAULT_URI')) === null
    || trim((string) ($_SERVER['DEFAULT_URI'] ?? $_ENV['DEFAULT_URI'] ?? getenv('DEFAULT_URI'))) === ''
) {
    $_SERVER['DEFAULT_URI'] = 'http://localhost';
    $_ENV['DEFAULT_URI'] = 'http://localhost';
    putenv('DEFAULT_URI=http://localhost');
}

if (!class_exists(Kernel::class)) {
    fwrite(STDERR, '[J5AA-0][KO] Kernel Symfony introuvable. Vérifie que le script est lancé depuis un projet Hodina complet.' . PHP_EOL);
    exit(1);
}

$env = (string) ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'dev');
$debugValue = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
$debug = $debugValue === false || $debugValue === null ? $env !== 'prod' : !in_array((string) $debugValue, ['0', 'false', 'off'], true);

$kernel = new Kernel($env, $debug);

try {
    $kernel->boot();

    /** @var ManagerRegistry $doctrine */
    $doctrine = $kernel->getContainer()->get('doctrine');
    $connection = $doctrine->getConnection();

    if (!$connection instanceof Connection) {
        throw new RuntimeException('Le service doctrine ne retourne pas une connexion DBAL valide.');
    }

    $audit = runAudit($connection);
    printReport($audit, $env);

    exit($audit['blockingIssues'] === [] ? 0 : 1);
} catch (Throwable $exception) {
    fwrite(STDERR, '[J5AA-0][KO] Audit impossible : ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if (isset($kernel)) {
        $kernel->shutdown();
    }
}

/**
 * @return array{
 *     addressesTotal: int,
 *     deliveryTotal: int,
 *     billingTotal: int,
 *     unknownTypeTotal: int,
 *     activeLogisticsCommunesTotal: int,
 *     deliveryOk: list<array<string, mixed>>,
 *     billingOtherIgnored: list<array<string, mixed>>,
 *     blockingIssues: list<array<string, mixed>>,
 * }
 */
function runAudit(Connection $connection): array
{
    /** @var list<array<string, mixed>> $addresses */
    $addresses = $connection->fetchAllAssociative(<<<'SQL'
        SELECT
            a.id,
            a.type,
            a.postal_code,
            a.commune,
            a.delivery_zone_id,
            z.code AS zone_code,
            z.name AS zone_name,
            z.is_active AS zone_is_active
        FROM address a
        LEFT JOIN delivery_zone z ON z.id = a.delivery_zone_id
        ORDER BY a.id ASC
        SQL);

    /** @var list<array<string, mixed>> $communes */
    $communes = $connection->fetchAllAssociative(<<<'SQL'
        SELECT
            id,
            name,
            slug,
            postal_code,
            territory,
            is_active,
            is_logistics_point
        FROM delivery_commune
        ORDER BY name ASC
        SQL);

    $activeLogisticsCommunes = array_values(array_filter(
        $communes,
        static fn (array $commune): bool => isTruthy($commune['is_active'] ?? false) && isTruthy($commune['is_logistics_point'] ?? false)
    ));

    $deliveryOk = [];
    $billingOtherIgnored = [];
    $blockingIssues = [];
    $deliveryTotal = 0;
    $billingTotal = 0;
    $unknownTypeTotal = 0;

    foreach ($addresses as $address) {
        $type = strtoupper(trim((string) ($address['type'] ?? '')));

        if ($type === 'DELIVERY') {
            ++$deliveryTotal;
            $result = auditDeliveryAddress($address, $activeLogisticsCommunes);

            if ($result['status'] === 'OK') {
                $deliveryOk[] = $result;
            } else {
                $blockingIssues[] = $result;
            }

            continue;
        }

        if ($type === 'BILLING') {
            ++$billingTotal;

            if (strtoupper(trim((string) ($address['zone_code'] ?? ''))) === 'AUTRE') {
                $billingOtherIgnored[] = [
                    'id' => (int) $address['id'],
                    'type' => $type,
                    'postalCode' => trim((string) ($address['postal_code'] ?? '')),
                    'commune' => trim((string) ($address['commune'] ?? '')),
                    'zoneCode' => trim((string) ($address['zone_code'] ?? '')),
                    'reason' => 'Facturation hors zone Hodina : ignorée volontairement par J5AA-0.',
                ];
            }

            continue;
        }

        ++$unknownTypeTotal;
    }

    return [
        'addressesTotal' => count($addresses),
        'deliveryTotal' => $deliveryTotal,
        'billingTotal' => $billingTotal,
        'unknownTypeTotal' => $unknownTypeTotal,
        'activeLogisticsCommunesTotal' => count($activeLogisticsCommunes),
        'deliveryOk' => $deliveryOk,
        'billingOtherIgnored' => $billingOtherIgnored,
        'blockingIssues' => $blockingIssues,
    ];
}

/**
 * @param array<string, mixed> $address
 * @param list<array<string, mixed>> $activeLogisticsCommunes
 * @return array<string, mixed>
 */
function auditDeliveryAddress(array $address, array $activeLogisticsCommunes): array
{
    $id = (int) $address['id'];
    $postalCode = trim((string) ($address['postal_code'] ?? ''));
    $communeName = trim((string) ($address['commune'] ?? ''));
    $zoneCode = strtoupper(trim((string) ($address['zone_code'] ?? '')));
    $zoneActive = isTruthy($address['zone_is_active'] ?? false);

    $base = [
        'id' => $id,
        'type' => 'DELIVERY',
        'postalCode' => $postalCode,
        'commune' => $communeName,
        'zoneCode' => $zoneCode,
        'status' => 'OK',
        'reason' => '',
        'matchMode' => null,
        'matchedCommune' => null,
        'candidates' => [],
    ];

    if ($communeName === '') {
        return issue($base, 'MISSING_COMMUNE', 'Commune vide : une adresse de livraison doit porter une commune livrable Hodina.');
    }

    if ($postalCode === '') {
        return issue($base, 'MISSING_POSTAL_CODE', 'Code postal vide : une adresse de livraison doit être contrôlable avec le référentiel DeliveryCommune.');
    }

    if (preg_match('/^\d{5}$/', $postalCode) !== 1) {
        return issue($base, 'INVALID_POSTAL_CODE', 'Code postal invalide : format 5 chiffres attendu pour une adresse de livraison.');
    }

    if ($zoneCode === '') {
        return issue($base, 'MISSING_DELIVERY_ZONE', 'Zone absente : une adresse de livraison doit être rattachée à PT ou GT.');
    }

    if ($zoneCode === 'AUTRE') {
        return issue($base, 'DELIVERY_ZONE_AUTRE', 'Zone AUTRE interdite pour une adresse de livraison. AUTRE est réservé aux usages hors livraison, notamment facturation.');
    }

    if (!$zoneActive) {
        return issue($base, 'INACTIVE_DELIVERY_ZONE', sprintf('Zone %s inactive : une adresse de livraison doit utiliser une zone active.', $zoneCode));
    }

    $exactCandidates = findExactCandidates($communeName, $activeLogisticsCommunes);

    if (count($exactCandidates) > 1) {
        return issue(
            withCandidates($base, $exactCandidates),
            'AMBIGUOUS_EXACT_COMMUNE',
            'Plusieurs DeliveryCommune actives correspondent exactement à cette commune. Le référentiel doit être dédoublonné.'
        );
    }

    if (count($exactCandidates) === 1) {
        $candidate = $exactCandidates[0];
        $candidatePostalCode = trim((string) ($candidate['postal_code'] ?? ''));
        $candidateTerritory = strtoupper(trim((string) ($candidate['territory'] ?? '')));

        if ($candidatePostalCode === '') {
            return issue(withCandidates($base, [$candidate]), 'MATCHED_COMMUNE_WITHOUT_POSTAL_CODE', 'La DeliveryCommune correspondante n’a pas de code postal seedé.');
        }

        if ($postalCode !== $candidatePostalCode) {
            return issue(
                withCandidates($base, [$candidate]),
                'POSTAL_CODE_MISMATCH',
                sprintf('Code postal incohérent : %s reçu, %s attendu pour %s.', $postalCode, $candidatePostalCode, (string) $candidate['name'])
            );
        }

        if ($zoneCode !== $candidateTerritory) {
            return issue(
                withCandidates($base, [$candidate]),
                'DELIVERY_ZONE_MISMATCH',
                sprintf('Zone incohérente : %s reçue, %s attendue pour %s.', $zoneCode, $candidateTerritory, (string) $candidate['name'])
            );
        }

        $base['matchMode'] = 'EXACT';
        $base['matchedCommune'] = formatCommuneCandidate($candidate);
        $base['reason'] = 'Commune canonique, code postal et zone cohérents avec DeliveryCommune.';

        return $base;
    }

    $fuzzyCandidates = findFuzzyCandidates($communeName, $activeLogisticsCommunes);

    if (count($fuzzyCandidates) > 1) {
        return issue(
            withCandidates($base, $fuzzyCandidates),
            'AMBIGUOUS_FUZZY_COMMUNE',
            'Commune non canonique et ambiguë : plusieurs DeliveryCommune actives peuvent correspondre. Corriger manuellement vers une commune unique.'
        );
    }

    if (count($fuzzyCandidates) === 1) {
        return issue(
            withCandidates($base, $fuzzyCandidates),
            'FUZZY_ONLY_COMMUNE',
            'Commune résoluble seulement par matching souple. Pour J5AA, une adresse DELIVERY doit stocker le nom canonique exact de DeliveryCommune.'
        );
    }

    return issue($base, 'UNRESOLVED_COMMUNE', 'Commune introuvable dans les DeliveryCommune actives/logistiques.');
}

/** @param array<string, mixed> $base */
function issue(array $base, string $status, string $reason): array
{
    $base['status'] = $status;
    $base['reason'] = $reason;

    return $base;
}

/**
 * @param array<string, mixed> $base
 * @param list<array<string, mixed>> $candidates
 * @return array<string, mixed>
 */
function withCandidates(array $base, array $candidates): array
{
    $base['candidates'] = array_map('formatCommuneCandidate', $candidates);

    return $base;
}

/**
 * @param list<array<string, mixed>> $communes
 * @return list<array<string, mixed>>
 */
function findExactCandidates(string $communeName, array $communes): array
{
    $normalizedInput = normalizeForAudit($communeName);

    if ($normalizedInput === '') {
        return [];
    }

    return array_values(array_filter($communes, static function (array $commune) use ($normalizedInput): bool {
        $normalizedName = normalizeForAudit((string) ($commune['name'] ?? ''));
        $normalizedSlug = normalizeForAudit((string) ($commune['slug'] ?? ''));

        return $normalizedInput === $normalizedName || ($normalizedSlug !== '' && $normalizedInput === $normalizedSlug);
    }));
}

/**
 * @param list<array<string, mixed>> $communes
 * @return list<array<string, mixed>>
 */
function findFuzzyCandidates(string $communeName, array $communes): array
{
    $normalizedInput = normalizeForAudit($communeName);

    if ($normalizedInput === '') {
        return [];
    }

    return array_values(array_filter($communes, static function (array $commune) use ($normalizedInput): bool {
        foreach ([(string) ($commune['name'] ?? ''), (string) ($commune['slug'] ?? '')] as $candidateName) {
            $normalizedCandidate = normalizeForAudit($candidateName);

            if ($normalizedCandidate === '') {
                continue;
            }

            if ($normalizedInput === $normalizedCandidate) {
                return false;
            }

            if (str_contains($normalizedInput, $normalizedCandidate) || str_contains($normalizedCandidate, $normalizedInput)) {
                return true;
            }

            if (str_contains($normalizedInput, 'labattoir') && str_contains($normalizedCandidate, 'labattoir')) {
                return true;
            }
        }

        return false;
    }));
}

/** @param array<string, mixed> $candidate */
function formatCommuneCandidate(array $candidate): string
{
    return sprintf(
        '#%d %s [%s / %s]',
        (int) ($candidate['id'] ?? 0),
        trim((string) ($candidate['name'] ?? '')),
        trim((string) ($candidate['postal_code'] ?? 'CP ?')),
        trim((string) ($candidate['territory'] ?? 'Zone ?'))
    );
}

function normalizeForAudit(string $value): string
{
    $value = trim(mb_strtolower($value));

    $replacements = [
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'î' => 'i',
        'ï' => 'i',
        'ô' => 'o',
        'ö' => 'o',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        "'" => ' ',
        '’' => ' ',
        '-' => ' ',
        '_' => ' ',
    ];

    $value = strtr($value, $replacements);
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
}

function isTruthy(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value === 1;
    }

    if (is_string($value)) {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

/** @param array<string, mixed> $audit */
function printReport(array $audit, string $env): void
{
    $issuesByStatus = [];
    foreach ($audit['blockingIssues'] as $issue) {
        $status = (string) ($issue['status'] ?? 'UNKNOWN');
        $issuesByStatus[$status] = ($issuesByStatus[$status] ?? 0) + 1;
    }

    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, '================================================================================' . PHP_EOL);
    fwrite(STDOUT, 'J5AA-0 — Audit strict des adresses DELIVERY autour de Address.commune' . PHP_EOL);
    fwrite(STDOUT, '================================================================================' . PHP_EOL);
    fwrite(STDOUT, sprintf('Environnement Symfony : %s', $env) . PHP_EOL);
    fwrite(STDOUT, sprintf('Adresses analysées : %d', $audit['addressesTotal']) . PHP_EOL);
    fwrite(STDOUT, sprintf('- DELIVERY : %d', $audit['deliveryTotal']) . PHP_EOL);
    fwrite(STDOUT, sprintf('- BILLING  : %d', $audit['billingTotal']) . PHP_EOL);
    fwrite(STDOUT, sprintf('- Type inconnu : %d', $audit['unknownTypeTotal']) . PHP_EOL);
    fwrite(STDOUT, sprintf('DeliveryCommune actives/logistiques : %d', $audit['activeLogisticsCommunesTotal']) . PHP_EOL);
    fwrite(STDOUT, PHP_EOL);
    fwrite(STDOUT, sprintf('DELIVERY OK : %d', count($audit['deliveryOk'])) . PHP_EOL);
    fwrite(STDOUT, sprintf('DELIVERY bloquantes : %d', count($audit['blockingIssues'])) . PHP_EOL);

    foreach ($issuesByStatus as $status => $count) {
        fwrite(STDOUT, sprintf('- %s : %d', $status, $count) . PHP_EOL);
    }

    fwrite(STDOUT, sprintf('BILLING AUTRE ignorées volontairement : %d', count($audit['billingOtherIgnored'])) . PHP_EOL);
    fwrite(STDOUT, PHP_EOL);

    if ($audit['blockingIssues'] !== []) {
        fwrite(STDOUT, '[DELIVERY][KO] Anomalies bloquantes' . PHP_EOL);
        foreach ($audit['blockingIssues'] as $issue) {
            fwrite(STDOUT, PHP_EOL . formatAddressLine($issue) . PHP_EOL);
            fwrite(STDOUT, 'Motif : ' . (string) $issue['reason'] . PHP_EOL);

            if (($issue['candidates'] ?? []) !== []) {
                fwrite(STDOUT, 'Candidats possibles : ' . implode(', ', $issue['candidates']) . PHP_EOL);
            }
        }
        fwrite(STDOUT, PHP_EOL);
    }

    if ($audit['billingOtherIgnored'] !== []) {
        fwrite(STDOUT, '[BILLING][INFO] Facturations AUTRE ignorées volontairement' . PHP_EOL);
        foreach ($audit['billingOtherIgnored'] as $billing) {
            fwrite(STDOUT, '- ' . formatAddressLine($billing) . ' — ' . (string) $billing['reason'] . PHP_EOL);
        }
        fwrite(STDOUT, PHP_EOL);
    }

    if ($audit['blockingIssues'] === []) {
        fwrite(STDOUT, '[J5AA-0][OK] Toutes les adresses DELIVERY portent une commune canonique, un code postal et une zone cohérents.' . PHP_EOL);
        return;
    }

    fwrite(STDOUT, '[J5AA-0][KO] Corriger les adresses DELIVERY ci-dessus avant de s’appuyer sur la donnée commune pour J5AA-A/J5AA-B.' . PHP_EOL);
}

/** @param array<string, mixed> $address */
function formatAddressLine(array $address): string
{
    return sprintf(
        'Address #%d type=%s postalCode="%s" commune="%s" zone="%s"',
        (int) ($address['id'] ?? 0),
        (string) ($address['type'] ?? '?'),
        (string) ($address['postalCode'] ?? ''),
        (string) ($address['commune'] ?? ''),
        (string) ($address['zoneCode'] ?? '')
    );
}
