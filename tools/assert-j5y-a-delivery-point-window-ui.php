<?php

declare(strict_types=1);

/*
 * J5Y-A-bis guardrail.
 *
 * Usage:
 *   php tools/assert-j5y-a-delivery-point-window-ui.php
 */

$projectRoot = dirname(__DIR__);

function failJ5YA(string $message): never
{
    fwrite(STDERR, '[J5Y-A-bis][KO] ' . $message . PHP_EOL);
    exit(1);
}

function okJ5YA(string $message): void
{
    fwrite(STDOUT, '[J5Y-A-bis][OK] ' . $message . PHP_EOL);
}

function readJ5YA(string $relativePath): string
{
    global $projectRoot;
    $path = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        failJ5YA('Fichier introuvable : ' . $relativePath);
    }

    $content = file_get_contents($path);
    if ($content === false) {
        failJ5YA('Impossible de lire : ' . $relativePath);
    }

    return $content;
}

function existsJ5YA(string $relativePath): bool
{
    global $projectRoot;
    return is_file($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
}

$productCrud = readJ5YA('src/Controller/Admin/ProductCrudController.php');

foreach ([
    "TextareaField::new('quickDeliveryPointTimeWindows'",
    "'data-controller' => 'delivery-point-windows'",
    "'data-delivery-point-windows-source' => '1'",
    "Interface guidée J5Y-A-bis",
    'jours ouvrés',
    'jours ouvrables',
    'expandWeekdayPreset',
    'normalizeTextToken',
    'Matin;jours ouvrés;08:00;12:00',
    'Après-midi;jours ouvrés;14:00;18:00',
] as $needle) {
    if (!str_contains($productCrud, $needle)) {
        failJ5YA('ProductCrudController ne contient pas : ' . $needle);
    }
}

foreach ([
    "setTemplatePath('admin/field/quick_delivery_point_time_windows.html.twig')",
    'quick_delivery_point_time_windows.html.twig',
] as $forbidden) {
    if (str_contains($productCrud, $forbidden)) {
        failJ5YA('Le formulaire produit ne doit plus dépendre du template EasyAdmin fragile : ' . $forbidden);
    }
}

if (existsJ5YA('templates/admin/field/quick_delivery_point_time_windows.html.twig')) {
    failJ5YA('Template obsolète encore présent : templates/admin/field/quick_delivery_point_time_windows.html.twig. Supprime-le : il masque le vrai branchement formulaire.');
}

foreach (['DeliveryLogisticsService', 'DeliveryScheduleService', 'CartController', 'CheckoutController'] as $forbidden) {
    if (str_contains($productCrud, $forbidden)) {
        failJ5YA('J5Y-A-bis ne doit pas mélanger le produit admin avec : ' . $forbidden);
    }
}

$controller = readJ5YA('assets/controllers/delivery_point_windows_controller.js');
foreach ([
    'export default class extends Controller',
    "textarea[data-delivery-point-windows-source]",
    'createBuilder()',
    '+ Ajouter une plage horaire',
    "{ label: 'Matin', day: 'jours ouvrés', start: '08:00', end: '12:00' }",
    "{ label: 'Après-midi', day: 'jours ouvrés', start: '14:00', end: '18:00' }",
    'data-delivery-point-windows-row',
    "lines.push([label || 'Plage', day || 'jours ouvrés', start, end].join(';'))",
    "'jours ouvrables': 'jours ouvrables'",
    '.hodina-delivery-windows-builder__source { display: none !important; }',
] as $needle) {
    if (!str_contains($controller, $needle)) {
        failJ5YA('Contrôleur JS plages horaires incomplet : ' . $needle);
    }
}

foreach ([
    '@EasyAdmin/crud/field/textarea.html.twig',
    'data-delivery-point-windows-template',
] as $forbidden) {
    if (str_contains($controller, $forbidden)) {
        failJ5YA('Le contrôleur JS ne doit plus dépendre du template Twig obsolète : ' . $forbidden);
    }
}

$admin = readJ5YA('assets/admin.js');
foreach ([
    "import DeliveryPointWindowsController from './controllers/delivery_point_windows_controller.js';",
    "app.register('delivery-point-windows', DeliveryPointWindowsController);",
] as $needle) {
    if (!str_contains($admin, $needle)) {
        failJ5YA('assets/admin.js ne déclare pas le contrôleur : ' . $needle);
    }
}

foreach (['templates/cart/index.html.twig', 'src/Controller/CartController.php', 'src/Service/DeliveryLogisticsService.php', 'src/Service/DeliveryScheduleService.php'] as $path) {
    $content = readJ5YA($path);
    if (str_contains($content, 'quickDeliveryPointTimeWindows') || str_contains($content, 'quick_delivery_point_time_windows')) {
        failJ5YA('J5Y-A-bis ne doit pas modifier ou dépendre du front client/livraison : ' . $path);
    }
}

okJ5YA('Interface guidée réellement branchée au formulaire EasyAdmin via row_attr + textarea caché, sans template fragile ni impact panier/livraison.');
