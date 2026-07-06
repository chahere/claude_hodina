<?php

declare(strict_types=1);

/*
 * J5AC DB guardrail.
 * Vérifie que customer.email peut recevoir un index unique nullable.
 * Usage : php tools/assert-j5ac-customer-email-db-readiness.php
 */

$projectRoot = dirname(__DIR__);

function failJ5ACDB(string $message): never
{
    fwrite(STDERR, '[J5AC-DB][KO] ' . $message . PHP_EOL);
    exit(1);
}

function okJ5ACDB(string $message): void
{
    fwrite(STDOUT, '[J5AC-DB][OK] ' . $message . PHP_EOL);
}

function infoJ5ACDB(string $message): void
{
    fwrite(STDOUT, '[J5AC-DB][INFO] ' . $message . PHP_EOL);
}

function loadDatabaseUrlJ5ACDB(): string
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
                infoJ5ACDB('DATABASE_URL retenue : ' . $envFile);
                return $value;
            }
        }
    }

    failJ5ACDB('DATABASE_URL introuvable.');
}

function createPdoJ5ACDB(string $databaseUrl): PDO
{
    $parts = parse_url($databaseUrl);
    if (!is_array($parts) || !isset($parts['scheme'])) {
        failJ5ACDB('DATABASE_URL invalide ou non parsable.');
    }

    $scheme = strtolower((string) $parts['scheme']);
    if (!in_array($scheme, ['mysql', 'mariadb'], true)) {
        failJ5ACDB('Seuls mysql:// et mariadb:// sont supportés. Scheme reçu : ' . $scheme);
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

    try {
        return new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset), $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        failJ5ACDB('Connexion base impossible : ' . $e->getMessage());
    }
}

function countInfoSchemaJ5ACDB(PDO $pdo, string $sql, array $params): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function assertMigrationIsNonTransactionalJ5ACDB(): void
{
    global $projectRoot;

    $migrationPath = $projectRoot . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . 'Version20260703093000.php';
    if (!is_file($migrationPath)) {
        failJ5ACDB('Migration Version20260703093000.php introuvable.');
    }

    $contents = file_get_contents($migrationPath);
    if (!is_string($contents)) {
        failJ5ACDB('Migration Version20260703093000.php illisible.');
    }

    if (!str_contains($contents, 'function isTransactional(): bool') || !preg_match('/function\s+isTransactional\s*\(\)\s*:\s*bool\s*\{\s*return\s+false\s*;/s', $contents)) {
        failJ5ACDB('La migration J5AC doit déclarer isTransactional(): bool avec return false pour éviter le warning MariaDB/MySQL en recette/prod.');
    }

    okJ5ACDB('Migration J5AC non transactionnelle : isTransactional(): false détecté.');
}

assertMigrationIsNonTransactionalJ5ACDB();

$pdo = createPdoJ5ACDB(loadDatabaseUrlJ5ACDB());

if (countInfoSchemaJ5ACDB($pdo, 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', ['customer']) !== 1) {
    failJ5ACDB('Table customer introuvable.');
}

if (countInfoSchemaJ5ACDB($pdo, 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', ['customer', 'email']) !== 1) {
    failJ5ACDB('Colonne customer.email introuvable.');
}

$summary = $pdo->query("SELECT COUNT(*) AS total_customers, SUM(email IS NULL) AS emails_null, SUM(email IS NOT NULL AND TRIM(email) = '') AS emails_vides FROM customer")->fetch();
infoJ5ACDB(sprintf(
    'Customers: %d, emails NULL: %d, emails vides: %d',
    (int) ($summary['total_customers'] ?? 0),
    (int) ($summary['emails_null'] ?? 0),
    (int) ($summary['emails_vides'] ?? 0),
));

$duplicates = $pdo->query("SELECT LOWER(TRIM(email)) AS normalized_email, COUNT(*) AS nb, GROUP_CONCAT(id ORDER BY id) AS ids FROM customer WHERE email IS NOT NULL AND TRIM(email) <> '' GROUP BY LOWER(TRIM(email)) HAVING COUNT(*) > 1")->fetchAll();
if ($duplicates !== []) {
    foreach ($duplicates as $duplicate) {
        fwrite(STDERR, sprintf("[J5AC-DB][DOUBLON] %s => %s compte(s), ids: %s\n", $duplicate['normalized_email'] ?? '(vide)', $duplicate['nb'] ?? '?', $duplicate['ids'] ?? '?'));
    }
    failJ5ACDB('Doublons email normalisés détectés. Corriger avant migration J5AC.');
}
okJ5ACDB('Aucun doublon email normalisé détecté.');

$invalids = $pdo->query("SELECT id, email FROM customer WHERE email IS NOT NULL AND TRIM(email) <> '' AND TRIM(email) NOT REGEXP '^[^@[:space:]]+@[^@[:space:]]+\\.[^@[:space:]]+$'")->fetchAll();
if ($invalids !== []) {
    foreach ($invalids as $invalid) {
        fwrite(STDERR, sprintf("[J5AC-DB][EMAIL INVALIDE] id=%s email=%s\n", $invalid['id'] ?? '?', $invalid['email'] ?? '?'));
    }
    failJ5ACDB('Emails invalides détectés. Corriger avant migration/profil J5AC.');
}
okJ5ACDB('Aucun email invalide simple détecté.');

$emailIndex = countInfoSchemaJ5ACDB($pdo, 'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', ['customer', 'UNIQ_CUSTOMER_EMAIL']);
if ($emailIndex === 1) {
    okJ5ACDB('Index unique nullable UNIQ_CUSTOMER_EMAIL présent.');
} else {
    infoJ5ACDB('Index UNIQ_CUSTOMER_EMAIL absent pour l’instant : normal avant migration J5AC.');
}

$phoneUniqueIndexes = $pdo->query("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer' AND COLUMN_NAME = 'phone' AND NON_UNIQUE = 0")->fetchAll();
if ($phoneUniqueIndexes !== []) {
    failJ5ACDB('Un index unique existe sur customer.phone alors que J5AC l’interdit.');
}
okJ5ACDB('Aucune contrainte unique téléphone détectée.');

okJ5ACDB('Base prête pour J5AC : email unique nullable possible, téléphone non unique.');
