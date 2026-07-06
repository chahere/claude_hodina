<?php

declare(strict_types=1);

/*
 * J5X-B guardrail.
 *
 * Usage:
 *   php tools/assert-j5x-b-delivery-schedules.php
 */

$projectRoot = dirname(__DIR__);

const EXPECTED_SCHEDULES = [
    'PT_LOCAL' => ['label' => 'Petite-Terre', 'weekdays' => [1, 4], 'active' => 1],
    'MAMOUDZOU_LOCAL' => ['label' => 'Mamoudzou', 'weekdays' => [3, 6], 'active' => 1],
    'SUD_LOCAL' => ['label' => 'Grande-Terre Sud', 'weekdays' => [3, 6], 'active' => 1],
    'NORD_LOCAL' => ['label' => 'Grande-Terre Nord', 'weekdays' => [2, 5], 'active' => 1],
    'CENTRE_LOCAL' => ['label' => 'Grande-Terre Centre', 'weekdays' => [2, 5], 'active' => 1],
    'GT_LOCAL' => ['label' => 'Grande-Terre fallback', 'weekdays' => [], 'active' => 0],
];

function fail(string $message): never
{
    fwrite(STDERR, '[J5X-B][KO] ' . $message . PHP_EOL);
    exit(1);
}

function ok(string $message): void
{
    fwrite(STDOUT, '[J5X-B][OK] ' . $message . PHP_EOL);
}

function info(string $message): void
{
    fwrite(STDOUT, '[J5X-B][INFO] ' . $message . PHP_EOL);
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

function assertStaticCodeGuardrails(): void
{
    $entity = readProjectFile('src/Entity/DeliveryPricingZone.php');
    foreach (['publicLabel', 'publicDescription', 'deliveryWeekdays', 'cutoffTime', 'cutoffDaysBefore', 'isDeliveryScheduleActive'] as $property) {
        if (!str_contains($entity, '$' . $property)) {
            fail('DeliveryPricingZone doit contenir la propriété : ' . $property);
        }
    }

    $service = readProjectFile('src/Service/DeliveryScheduleService.php');
    if (!str_contains($service, 'Indian/Mayotte')) {
        fail('DeliveryScheduleService doit calculer les passages en timezone Indian/Mayotte.');
    }
    if (!str_contains($service, 'prochain passage possible') && !str_contains($service, 'Passages à')) {
        fail('DeliveryScheduleService doit produire une promesse prudente, pas une garantie.');
    }

    $cartController = readProjectFile('src/Controller/CartController.php');
    if (!str_contains($cartController, 'DeliveryScheduleService')) {
        fail('CartController doit enrichir l’aperçu AJAX avec DeliveryScheduleService.');
    }

    $productShow = readProjectFile('templates/product/show.html.twig');
    foreach (['livraison mardi', 'livraison jeudi (commandes avant mardi 23h)', 'Petit-Terre : livraison mardi', 'Grande-Terre : livraison jeudi'] as $obsolete) {
        if (str_contains($productShow, $obsolete)) {
            fail('La fiche produit contient encore l’ancien planning pilote : ' . $obsolete);
        }
    }
    if (preg_match('/new Date|DateTime|modify\(|setDate\(|setTime\(/i', $productShow) === 1) {
        fail('La fiche produit ne doit pas calculer le calendrier directement dans Twig.');
    }

    $cartTwig = readProjectFile('templates/cart/index.html.twig');
    if (!str_contains($cartTwig, 'data-delivery-schedule')) {
        fail('Le panier doit afficher un bloc de planning livraison rafraîchissable.');
    }

    ok('Code statique conforme : calendrier porté par DeliveryPricingZone, service dédié, Twig sans ancien planning pilote.');
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
    $charset = isset($query['charset']) && is_string($query['charset']) && $query['charset'] !== '' ? $query['charset'] : 'utf8mb4';

    if ($database === '') {
        fail('Nom de base absent dans DATABASE_URL.');
    }

    try {
        return new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset), $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        fail('Connexion base impossible : ' . $e->getMessage());
    }
}

function assertDatabaseGuardrails(PDO $pdo): void
{
    foreach (['public_label', 'public_description', 'delivery_weekdays', 'cutoff_time', 'cutoff_days_before', 'is_delivery_schedule_active'] as $column) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute(['delivery_pricing_zone', $column]);
        if ((int) $stmt->fetchColumn() !== 1) {
            fail('Colonne manquante dans delivery_pricing_zone : ' . $column);
        }
    }

    $codes = array_keys(EXPECTED_SCHEDULES);
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("SELECT code, public_label, delivery_weekdays, cutoff_time, cutoff_days_before, is_delivery_schedule_active FROM delivery_pricing_zone WHERE code IN ({$placeholders}) ORDER BY code");
    $stmt->execute($codes);
    $rows = $stmt->fetchAll();

    $byCode = [];
    foreach ($rows as $row) {
        $byCode[(string) $row['code']] = $row;
    }

    foreach (EXPECTED_SCHEDULES as $code => $expected) {
        if (!isset($byCode[$code])) {
            fail('Zone tarifaire manquante en base : ' . $code);
        }

        $row = $byCode[$code];
        if ((string) $row['public_label'] !== $expected['label']) {
            fail(sprintf('%s doit avoir le libellé public "%s", valeur actuelle : "%s".', $code, $expected['label'], $row['public_label']));
        }

        $weekdays = $row['delivery_weekdays'] !== null && $row['delivery_weekdays'] !== ''
            ? json_decode((string) $row['delivery_weekdays'], true)
            : [];
        if (!is_array($weekdays)) {
            fail('delivery_weekdays invalide pour ' . $code);
        }
        $weekdays = array_values(array_map('intval', $weekdays));
        if ($weekdays !== $expected['weekdays']) {
            fail(sprintf('%s doit avoir les jours [%s], valeur actuelle [%s].', $code, implode(',', $expected['weekdays']), implode(',', $weekdays)));
        }

        if ((string) $row['cutoff_time'] !== '10:00:00') {
            fail(sprintf('%s doit avoir cutoff_time=10:00:00, valeur actuelle : %s.', $code, $row['cutoff_time']));
        }
        if ((int) $row['cutoff_days_before'] !== 1) {
            fail(sprintf('%s doit avoir cutoff_days_before=1.', $code));
        }
        if ((int) $row['is_delivery_schedule_active'] !== $expected['active']) {
            fail(sprintf('%s doit avoir is_delivery_schedule_active=%d.', $code, $expected['active']));
        }
    }

    $duplicateCount = (int) $pdo->query("SELECT COUNT(*) FROM delivery_pricing_zone WHERE code = 'PETITE_TERRE_LOCAL'")->fetchColumn();
    if ($duplicateCount > 0) {
        fail('PETITE_TERRE_LOCAL ne doit pas exister.');
    }

    ok('Base conforme : calendriers J5X-B configurés, cutoff 10h J-1, fallback GT_LOCAL neutre.');
}

assertStaticCodeGuardrails();
$pdo = createPdoFromDatabaseUrl(loadDatabaseUrl());
assertDatabaseGuardrails($pdo);
ok('J5X-B validé : calendrier livraison paramétrable par secteur sans promesse garantie.');
