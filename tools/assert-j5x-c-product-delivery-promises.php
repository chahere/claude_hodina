<?php

declare(strict_types=1);

/*
 * J5X-C guardrail.
 *
 * Usage:
 *   php tools/assert-j5x-c-product-delivery-promises.php
 */

$projectRoot = dirname(__DIR__);

function fail(string $message): never
{
    fwrite(STDERR, '[J5X-C][KO] ' . $message . PHP_EOL);
    exit(1);
}

function ok(string $message): void
{
    fwrite(STDOUT, '[J5X-C][OK] ' . $message . PHP_EOL);
}

function info(string $message): void
{
    fwrite(STDOUT, '[J5X-C][INFO] ' . $message . PHP_EOL);
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
    $product = readProjectFile('src/Entity/Product.php');
    foreach ([
        'deliveryPromiseMode',
        'deliveryPromiseTitle',
        'deliveryPromiseDescription',
        'appointmentDeliveryWeekdays',
        'appointmentTimeWindowStart',
        'appointmentTimeWindowEnd',
        'appointmentCutoffTime',
        'appointmentCutoffDaysBefore',
    ] as $property) {
        if (!str_contains($product, '$' . $property)) {
            fail('Product doit contenir la propriété J5X-C : ' . $property);
        }
    }

    if (!str_contains($product, 'DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE') || !str_contains($product, 'DELIVERY_PROMISE_MODE_APPOINTMENT')) {
        fail('Product doit exposer les modes de promesse secteur et sur créneau.');
    }

    $service = readProjectFile('src/Service/ProductDeliveryPromiseService.php');
    if (!str_contains($service, 'DeliveryScheduleService')) {
        fail('ProductDeliveryPromiseService doit réutiliser DeliveryScheduleService pour la promesse secteur.');
    }
    if (str_contains($service, 'DeliveryLogisticsService')) {
        fail('ProductDeliveryPromiseService ne doit pas recalculer les frais ni dépendre de DeliveryLogisticsService.');
    }
    foreach (['Sur créneau', 'Selon commune'] as $label) {
        if (!str_contains($service, $label)) {
            fail('ProductDeliveryPromiseService doit produire le libellé : ' . $label);
        }
    }

    $productController = readProjectFile('src/Controller/ProductController.php');
    if (!str_contains($productController, 'ProductDeliveryPromiseService')) {
        fail('ProductController doit construire la promesse produit via ProductDeliveryPromiseService.');
    }
    if (!str_contains($productController, 'resolveSelectedPricingZone')) {
        fail('ProductController doit résoudre la zone tarifaire connue pour afficher une promesse pertinente quand possible.');
    }

    $productCrud = readProjectFile('src/Controller/Admin/ProductCrudController.php');
    foreach (['deliveryPromiseMode', 'appointmentDeliveryWeekdays', 'appointmentTimeWindowStart', 'appointmentCutoffTime'] as $field) {
        if (!str_contains($productCrud, $field)) {
            fail('ProductCrudController doit exposer le champ : ' . $field);
        }
    }

    foreach ([
        'Fiche produit — message de livraison client',
        'Type de message affiché',
        'Début de plage indicative',
        'Fin de plage indicative',
        'Avancé — points de remise : associer des points existants',
        'Avancé — points de remise : créer un nouveau point',
        'Avancé — points de remise : plages horaires du nouveau point',
        'Distinct du message de livraison J5X-C',
        'Pour modifier les plages d’un point existant',
    ] as $expectedLabel) {
        if (!str_contains($productCrud, $expectedLabel)) {
            fail('ProductCrudController doit clarifier le formulaire produit : ' . $expectedLabel);
        }
    }

    foreach ([
        'Promesse livraison client — J5X-C',
        "ChoiceField::new('deliveryMode', 'Mode de livraison')",
        "TimeField::new('appointmentTimeWindowStart', 'Début plage créneau')",
        "TimeField::new('appointmentTimeWindowEnd', 'Fin plage créneau')",
        "TextareaField::new('quickDeliveryPointTimeWindows', 'Plages de remise')",
    ] as $ambiguousLabel) {
        if (str_contains($productCrud, $ambiguousLabel)) {
            fail('ProductCrudController contient encore un libellé ambigu : ' . $ambiguousLabel);
        }
    }

    $productShow = readProjectFile('templates/product/show.html.twig');
    if (!str_contains($productShow, 'productDeliveryPromise')) {
        fail('La fiche produit doit utiliser productDeliveryPromise.');
    }
    if (!str_contains($productShow, 'delivery-sector-details')) {
        fail('Commune inconnue : la fiche produit doit afficher le tableau secteur en bloc repliable.');
    }
    foreach (['livraison mardi', 'Grande-Terre : livraison jeudi', 'PETITE_TERRE_LOCAL', 'localPricingZone'] as $forbidden) {
        if (str_contains($productShow, $forbidden)) {
            fail('La fiche produit expose encore une information technique ou obsolète : ' . $forbidden);
        }
    }

    ok('Code statique conforme : promesse produit dédiée, produits sur créneau, fiche produit sans promesse garantie.');
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
    foreach ([
        'delivery_promise_mode',
        'delivery_promise_title',
        'delivery_promise_description',
        'appointment_delivery_weekdays',
        'appointment_time_window_start',
        'appointment_time_window_end',
        'appointment_cutoff_time',
        'appointment_cutoff_days_before',
    ] as $column) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute(['product', $column]);
        if ((int) $stmt->fetchColumn() !== 1) {
            fail('Colonne manquante dans product : ' . $column);
        }
    }

    $invalidCount = (int) $pdo->query("SELECT COUNT(*) FROM product WHERE delivery_promise_mode NOT IN ('SECTOR_SCHEDULE', 'APPOINTMENT')")->fetchColumn();
    if ($invalidCount > 0) {
        fail('Des produits ont une valeur delivery_promise_mode invalide.');
    }

    ok('Base conforme : colonnes J5X-C présentes et modes de promesse valides.');
}

assertStaticCodeGuardrails();
$pdo = createPdoFromDatabaseUrl(loadDatabaseUrl());
assertDatabaseGuardrails($pdo);
ok('J5X-C validé : promesse produit configurable sans dupliquer J5V-A ni la formule logistique.');
