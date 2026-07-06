<?php

declare(strict_types=1);

/*
 * Guardrail UX admin produit.
 *
 * Usage:
 *   php tools/assert-admin-product-form-order.php
 */

$projectRoot = dirname(__DIR__);

function failAdminProductOrder(string $message): never
{
    fwrite(STDERR, '[AdminProductOrder][KO] ' . $message . PHP_EOL);
    exit(1);
}

function okAdminProductOrder(string $message): void
{
    fwrite(STDOUT, '[AdminProductOrder][OK] ' . $message . PHP_EOL);
}

function readAdminProductOrder(string $relativePath): string
{
    global $projectRoot;

    $path = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        failAdminProductOrder('Fichier introuvable : ' . $relativePath);
    }

    $content = file_get_contents($path);
    if ($content === false) {
        failAdminProductOrder('Impossible de lire : ' . $relativePath);
    }

    return $content;
}

function positionAdminProductOrder(string $content, string $needle): int
{
    $position = strpos($content, $needle);
    if ($position === false) {
        failAdminProductOrder('ProductCrudController ne contient pas : ' . $needle);
    }

    return $position;
}

$productCrud = readAdminProductOrder('src/Controller/Admin/ProductCrudController.php');

$expectedOrder = [
    "NumberField::new('marginRate', 'Marge produit Hodina (%)')",
    "BooleanField::new('isUnlimitedStock', 'Stock illimité')",
    "IntegerField::new('stockQty', 'Stock')",
    "ChoiceField::new('unit', 'Unité de vente')",
    "TextareaField::new('description', 'Description')",
    "BooleanField::new('isPreorder', 'Précommande')",
    "IntegerField::new('manufacturingDays', 'Jours fabrication')",
    "ChoiceField::new('deliveryMode', 'Mode de remise au client')",
    "IntegerField::new('deliveryDays', 'Jours livraison')",
    "IntegerField::new('minimumOrderLeadTimeHours', 'Délai minimum avant remise/livraison (h)')",
];

$previousPosition = -1;
foreach ($expectedOrder as $needle) {
    $position = positionAdminProductOrder($productCrud, $needle);
    if ($position <= $previousPosition) {
        failAdminProductOrder('Ordre formulaire produit incorrect autour de : ' . $needle);
    }

    $previousPosition = $position;
}

foreach ([
    "MoneyField::new('price', 'Ancien prix / compatibilité (€)')",
    "BooleanField::new('isActive', 'Actif')",
    "FormField::addFieldset('Catalogue — ordre éditorial Hodina')",
] as $needle) {
    $position = positionAdminProductOrder($productCrud, $needle);
    if ($position <= $previousPosition) {
        failAdminProductOrder($needle . ' doit rester après les champs opérationnels de création produit.');
    }
}

okAdminProductOrder('Formulaire produit EasyAdmin : stock, unité, description, précommande, mode de remise et délais apparaissent juste après la marge produit Hodina.');
