<?php

declare(strict_types=1);

/*
 * J5X-D guardrail.
 *
 * Usage:
 *   php tools/assert-j5x-d-catalogue-search-filters.php
 */

$projectRoot = dirname(__DIR__);

function failJ5XD(string $message): never
{
    fwrite(STDERR, '[J5X-D][KO] ' . $message . PHP_EOL);
    exit(1);
}

function okJ5XD(string $message): void
{
    fwrite(STDOUT, '[J5X-D][OK] ' . $message . PHP_EOL);
}

function infoJ5XD(string $message): void
{
    fwrite(STDOUT, '[J5X-D][INFO] ' . $message . PHP_EOL);
}

function readJ5XD(string $relativePath): string
{
    global $projectRoot;
    $path = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        failJ5XD('Fichier introuvable : ' . $relativePath);
    }

    $content = file_get_contents($path);
    if ($content === false) {
        failJ5XD('Impossible de lire : ' . $relativePath);
    }

    return $content;
}

function assertStaticJ5XD(): void
{
    $category = readJ5XD('src/Entity/Category.php');
    foreach (['$displayOrder', '$isFeatured', '$publicDescription'] as $needle) {
        if (!str_contains($category, $needle)) {
            failJ5XD('Category doit contenir : ' . $needle);
        }
    }

    $product = readJ5XD('src/Entity/Product.php');
    foreach (['$displayPriority', '$isFeatured', "OrderBy(['position' => 'ASC', 'id' => 'ASC'])"] as $needle) {
        if (!str_contains($product, $needle)) {
            failJ5XD('Product doit contenir : ' . $needle);
        }
    }

    $productRepository = readJ5XD('src/Repository/ProductRepository.php');
    foreach (['findCatalogueProducts', 'p.isActive = :active', 'c.isActive = :active', 'LOWER(p.name)', 'leftJoin(\'p.images\'', "orderBy('c.isFeatured', 'DESC')", "addOrderBy('c.displayOrder', 'ASC')", "addOrderBy('p.isFeatured', 'DESC')", "addOrderBy('p.displayPriority', 'ASC')"] as $needle) {
        if (!str_contains($productRepository, $needle)) {
            failJ5XD('ProductRepository ne respecte pas le catalogue dédié : ' . $needle);
        }
    }

    $categoryRepository = readJ5XD('src/Repository/CategoryRepository.php');
    foreach (['findActiveForCatalogue', 'findOneActiveBySlug', 'displayOrder', 'isFeatured'] as $needle) {
        if (!str_contains($categoryRepository, $needle)) {
            failJ5XD('CategoryRepository doit contenir : ' . $needle);
        }
    }

    $controller = readJ5XD('src/Controller/ProductController.php');
    foreach (['CategoryRepository', 'findCatalogueProducts', 'findActiveForCatalogue', 'fragment', 'sortProductsByCustomerPrice', 'SORT_DEFAULT'] as $needle) {
        if (!str_contains($controller, $needle)) {
            failJ5XD('ProductController doit piloter le catalogue J5X-D : ' . $needle);
        }
    }
    if (str_contains($controller, "findBy(\n            ['isActive' => true],\n            ['createdAt' => 'DESC']")) {
        failJ5XD('ProductController ne doit plus faire un findBy catalogue brut createdAt DESC.');
    }

    $categoryCrud = readJ5XD('src/Controller/Admin/CategoryCrudController.php');
    foreach (['displayOrder', 'isFeatured', 'publicDescription', 'Visible catalogue', 'Mettre en tête du catalogue', 'Plus le chiffre est faible'] as $needle) {
        if (!str_contains($categoryCrud, $needle)) {
            failJ5XD('CategoryCrudController doit exposer : ' . $needle);
        }
    }

    $productCrud = readJ5XD('src/Controller/Admin/ProductCrudController.php');
    foreach (['Catalogue — ordre éditorial Hodina', 'isFeatured', 'displayPriority', 'Mettre en tête de sa catégorie', 'Plus le chiffre est faible'] as $needle) {
        if (!str_contains($productCrud, $needle)) {
            failJ5XD('ProductCrudController doit exposer : ' . $needle);
        }
    }

    $catalogue = readJ5XD('templates/product/catalogue.html.twig');
    foreach (['data-catalogue-page', 'data-catalogue-filter-form', 'data-catalogue-results', 'fetch(', 'history.pushState'] as $needle) {
        if (!str_contains($catalogue, $needle)) {
            failJ5XD('catalogue.html.twig doit gérer le fallback GET + AJAX progressif : ' . $needle);
        }
    }

    $filters = readJ5XD('templates/product/_catalogue_filters.html.twig');
    foreach (['method="get"', 'name="q"', 'name="categorie"', 'name="tri"', 'Ordre Hodina'] as $needle) {
        if (!str_contains($filters, $needle)) {
            failJ5XD('Le formulaire catalogue doit contenir : ' . $needle);
        }
    }

    foreach ([$controller, $filters] as $content) {
        foreach (["'Mis en avant'", 'value="featured"'] as $forbiddenSort) {
            if (str_contains($content, $forbiddenSort)) {
                failJ5XD('Le tri client ne doit plus exposer “Mis en avant” : ' . $forbiddenSort);
            }
        }
    }

    $card = readJ5XD('templates/product/_catalogue_product_card.html.twig');
    foreach (['data-ajax-cart-form', 'product.isFeatured', 'Mis en avant'] as $needle) {
        if (!str_contains($card, $needle)) {
            failJ5XD('La carte catalogue doit conserver AJAX panier et badge : ' . $needle);
        }
    }

    foreach ([$catalogue, $filters, $card] as $content) {
        foreach (['DeliveryLogisticsService', 'DeliveryScheduleService', 'localPricingZone', 'commune='] as $forbidden) {
            if (str_contains($content, $forbidden)) {
                failJ5XD('J5X-D ne doit pas mélanger catalogue et livraison : ' . $forbidden);
            }
        }
    }

    okJ5XD('Code statique conforme : catalogue filtrable, ordre Hodina par catégories puis produits, sans mélange livraison.');
}

function loadDatabaseUrlJ5XD(): ?string
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
                infoJ5XD('DATABASE_URL retenue : ' . $envFile);
                return $value;
            }
        }
    }

    infoJ5XD('DATABASE_URL introuvable : garde-fou base ignoré, garde-fou statique uniquement.');

    return null;
}

function createPdoJ5XD(string $databaseUrl): PDO
{
    $parts = parse_url($databaseUrl);
    if (!is_array($parts) || !isset($parts['scheme'])) {
        failJ5XD('DATABASE_URL invalide ou non parsable.');
    }

    $scheme = strtolower((string) $parts['scheme']);
    if (!in_array($scheme, ['mysql', 'mariadb'], true)) {
        failJ5XD('Seuls mysql:// et mariadb:// sont supportés. Scheme reçu : ' . $scheme);
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
        failJ5XD('Connexion base impossible : ' . $e->getMessage());
    }
}

function assertDatabaseJ5XD(PDO $pdo): void
{
    $checks = [
        'category' => ['display_order', 'is_featured', 'public_description'],
        'product' => ['display_priority', 'is_featured'],
    ];

    foreach ($checks as $table => $columns) {
        foreach ($columns as $column) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $stmt->execute([$table, $column]);
            if ((int) $stmt->fetchColumn() !== 1) {
                failJ5XD(sprintf('Colonne manquante dans %s : %s', $table, $column));
            }
        }
    }

    okJ5XD('Base conforme : colonnes merchandising catalogue présentes.');
}

assertStaticJ5XD();
$databaseUrl = loadDatabaseUrlJ5XD();
if ($databaseUrl !== null) {
    assertDatabaseJ5XD(createPdoJ5XD($databaseUrl));
}
okJ5XD('J5X-D validé : recherche, filtres, ordre éditorial Hodina et tris client restent séparés de la livraison.');
