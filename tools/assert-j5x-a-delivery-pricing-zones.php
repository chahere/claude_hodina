<?php

declare(strict_types=1);

/*
 * J5X-A guardrail.
 *
 * Goal: delivery pricing fee updates must remain data-driven through
 * DeliveryPricingZone and must not alter the J5W-A logistics formula.
 *
 * Cross-platform usage after migration:
 *   php tools/assert-j5x-a-delivery-pricing-zones.php
 */

$projectRoot = dirname(__DIR__);

const EXPECTED_FEES = [
    'PT_LOCAL' => '12.00',
    'MAMOUDZOU_LOCAL' => '12.00',
    'CENTRE_LOCAL' => '17.00',
    'SUD_LOCAL' => '21.00',
    'NORD_LOCAL' => '21.00',
    'GT_LOCAL' => '21.00',
];

const EXPECTED_COMMUNES = [
    'dzaoudzi' => 'PT_LOCAL',
    'labattoir' => 'PT_LOCAL',
    'pamandzi' => 'PT_LOCAL',
    'mamoudzou' => 'MAMOUDZOU_LOCAL',
    'acoua' => 'NORD_LOCAL',
    'bandraboua' => 'NORD_LOCAL',
    'koungou' => 'NORD_LOCAL',
    'm-tsangamouji' => 'NORD_LOCAL',
    'mtsamboro' => 'NORD_LOCAL',
    'chiconi' => 'CENTRE_LOCAL',
    'ouangani' => 'CENTRE_LOCAL',
    'sada' => 'CENTRE_LOCAL',
    'tsingoni' => 'CENTRE_LOCAL',
    'bandrele' => 'SUD_LOCAL',
    'boueni' => 'SUD_LOCAL',
    'chirongui' => 'SUD_LOCAL',
    'dembeni' => 'SUD_LOCAL',
    'kani-keli' => 'SUD_LOCAL',
];

function fail(string $message): never
{
    fwrite(STDERR, '[J5X-A][KO] ' . $message . PHP_EOL);
    exit(1);
}

function ok(string $message): void
{
    fwrite(STDOUT, '[J5X-A][OK] ' . $message . PHP_EOL);
}

function info(string $message): void
{
    fwrite(STDOUT, '[J5X-A][INFO] ' . $message . PHP_EOL);
}

function readProjectFile(string $relativePath): string
{
    global $projectRoot;

    $path = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        fail('Fichier introuvable : ' . $relativePath);
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        fail('Impossible de lire : ' . $relativePath);
    }

    return $contents;
}

function normalizeMoney(mixed $value): string
{
    return number_format((float) $value, 2, '.', '');
}

function assertStaticCodeGuardrails(): void
{
    $logisticsService = readProjectFile('src/Service/DeliveryLogisticsService.php');

    if (!str_contains($logisticsService, '$clientCommune->getLocalPricingZone()')) {
        fail('DeliveryLogisticsService doit continuer à partir de DeliveryCommune::getLocalPricingZone().');
    }

    if (!str_contains($logisticsService, 'DeliveryCommuneConnection::LINK_TYPE_BARGE')) {
        fail('DeliveryLogisticsService doit continuer à utiliser DeliveryCommuneConnection::LINK_TYPE_BARGE.');
    }

    if (preg_match('/(PT_LOCAL|MAMOUDZOU_LOCAL|CENTRE_LOCAL|SUD_LOCAL|NORD_LOCAL|GT_LOCAL).*?(12\.00|17\.00|21\.00)|(12\.00|17\.00|21\.00).*?(PT_LOCAL|MAMOUDZOU_LOCAL|CENTRE_LOCAL|SUD_LOCAL|NORD_LOCAL|GT_LOCAL)/s', $logisticsService) === 1) {
        fail('DeliveryLogisticsService ne doit pas contenir de tarifs J5X-A codés en dur. Les montants doivent rester en base.');
    }

    $migration = readProjectFile('migrations/Version20260629141000.php');
    foreach (EXPECTED_FEES as $code => $fee) {
        if (!str_contains($migration, "'{$code}' => '{$fee}'")) {
            fail(sprintf('Migration J5X-A incomplète : %s doit être mis à %s.', $code, $fee));
        }
    }

    if (str_contains($migration, 'courier_payout')) {
        fail('Migration J5X-A ne doit pas modifier courier_payout.');
    }

    if (str_contains($migration, 'PETITE_TERRE_LOCAL')) {
        fail('Migration J5X-A ne doit ni créer ni manipuler PETITE_TERRE_LOCAL.');
    }

    ok('Code statique conforme : formule logistique préservée et tarifs J5X-A portés par migration de données.');
}

function loadDatabaseUrl(): string
{
    global $projectRoot;

    $envDatabaseUrl = getenv('DATABASE_URL');
    if (is_string($envDatabaseUrl) && trim($envDatabaseUrl) !== '') {
        return trim($envDatabaseUrl);
    }

    foreach (['.env.local', 'prod.env.local', '.env'] as $envFile) {
        $path = $projectRoot . DIRECTORY_SEPARATOR . $envFile;
        if (!is_file($path)) {
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_starts_with($trimmed, 'DATABASE_URL=')) {
                continue;
            }

            $value = trim(substr($trimmed, strlen('DATABASE_URL=')));
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($value !== '') {
                info('DATABASE_URL retenue : ' . $envFile);
                return $value;
            }
        }
    }

    fail('DATABASE_URL introuvable. Lance ce garde-fou depuis un environnement Hodina configuré.');
}

function createPdoFromDatabaseUrl(string $databaseUrl): PDO
{
    $parts = parse_url($databaseUrl);
    if (!is_array($parts) || !isset($parts['scheme'])) {
        fail('DATABASE_URL invalide ou non parsable.');
    }

    $scheme = strtolower((string) $parts['scheme']);
    if (!in_array($scheme, ['mysql', 'mariadb'], true)) {
        fail('Seuls mysql:// et mariadb:// sont supportés par ce garde-fou. Scheme reçu : ' . $scheme);
    }

    $host = $parts['host'] ?? '127.0.0.1';
    $port = isset($parts['port']) ? (int) $parts['port'] : 3306;
    $database = isset($parts['path']) ? ltrim((string) $parts['path'], '/') : '';
    $user = isset($parts['user']) ? urldecode((string) $parts['user']) : '';
    $password = isset($parts['pass']) ? urldecode((string) $parts['pass']) : '';
    $query = [];
    if (isset($parts['query'])) {
        parse_str((string) $parts['query'], $query);
    }
    $charset = isset($query['charset']) && is_string($query['charset']) && $query['charset'] !== ''
        ? $query['charset']
        : 'utf8mb4';

    if ($database === '') {
        fail('Nom de base absent dans DATABASE_URL.');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

    try {
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        fail('Connexion base impossible : ' . $e->getMessage());
    }
}

function assertDatabaseGuardrails(PDO $pdo): void
{
    $codes = array_keys(EXPECTED_FEES);
    $placeholders = implode(',', array_fill(0, count($codes), '?'));

    $stmt = $pdo->prepare("SELECT code, customer_delivery_fee, courier_payout FROM delivery_pricing_zone WHERE code IN ({$placeholders}) ORDER BY code");
    $stmt->execute($codes);
    $rows = $stmt->fetchAll();

    $byCode = [];
    foreach ($rows as $row) {
        $byCode[(string) $row['code']] = $row;
    }

    foreach (EXPECTED_FEES as $code => $expectedFee) {
        if (!isset($byCode[$code])) {
            fail('Zone tarifaire manquante en base : ' . $code);
        }

        $actualFee = normalizeMoney($byCode[$code]['customer_delivery_fee']);
        if ($actualFee !== $expectedFee) {
            fail(sprintf('%s doit valoir %s côté client, valeur actuelle : %s.', $code, $expectedFee, $actualFee));
        }
    }

    $duplicateCount = (int) $pdo->query("SELECT COUNT(*) FROM delivery_pricing_zone WHERE code = 'PETITE_TERRE_LOCAL'")->fetchColumn();
    if ($duplicateCount > 0) {
        fail('PETITE_TERRE_LOCAL ne doit pas exister. Petite-Terre doit rester sur PT_LOCAL.');
    }

    $stmt = $pdo->query(<<<'SQL'
SELECT c.slug, z.code AS pricing_zone_code
FROM delivery_commune c
INNER JOIN delivery_pricing_zone z ON z.id = c.local_pricing_zone_id
SQL);
    $communes = $stmt->fetchAll();
    $pricingZoneBySlug = [];
    foreach ($communes as $row) {
        $pricingZoneBySlug[(string) $row['slug']] = (string) $row['pricing_zone_code'];
    }

    foreach (EXPECTED_COMMUNES as $slug => $expectedCode) {
        if (!isset($pricingZoneBySlug[$slug])) {
            fail('Commune livrée manquante ou non rattachée : ' . $slug);
        }

        if ($pricingZoneBySlug[$slug] !== $expectedCode) {
            fail(sprintf('Commune %s doit rester rattachée à %s, valeur actuelle : %s.', $slug, $expectedCode, $pricingZoneBySlug[$slug]));
        }
    }

    ok('Base conforme : tarifs J5X-A appliqués, PETITE_TERRE_LOCAL absent, rattachements communes préservés.');

    foreach (EXPECTED_FEES as $code => $expectedFee) {
        $courierPayout = isset($byCode[$code]['courier_payout']) ? normalizeMoney($byCode[$code]['courier_payout']) : 'n/a';
        info(sprintf('%s : frais client %s €, rémunération livreur %s €.', $code, $expectedFee, $courierPayout));
    }
}

assertStaticCodeGuardrails();
$pdo = createPdoFromDatabaseUrl(loadDatabaseUrl());
assertDatabaseGuardrails($pdo);

ok('J5X-A validé : mise à jour tarifaire par zone sans régression sur la formule logistique.');
