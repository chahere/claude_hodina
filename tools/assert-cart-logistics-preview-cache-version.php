<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cartControllerPath = $root . '/src/Controller/CartController.php';
$previewDtoPath = $root . '/src/Dto/CartLogisticsPreview.php';

if (!is_file($cartControllerPath)) {
    fwrite(STDERR, "[CartLogisticsCache][FAIL] CartController introuvable.\n");
    exit(1);
}

if (!is_file($previewDtoPath)) {
    fwrite(STDERR, "[CartLogisticsCache][FAIL] CartLogisticsPreview introuvable.\n");
    exit(1);
}

$cartController = file_get_contents($cartControllerPath);
$previewDto = file_get_contents($previewDtoPath);

foreach ([
    'LOGISTICS_PREVIEW_CACHE_VERSION',
    "'version' => self::LOGISTICS_PREVIEW_CACHE_VERSION",
    "($cached['version'] ?? null) === self::LOGISTICS_PREVIEW_CACHE_VERSION",
] as $needle) {
    if (!str_contains($cartController, $needle)) {
        fwrite(STDERR, sprintf("[CartLogisticsCache][FAIL] CartController ne contient pas : %s\n", $needle));
        exit(1);
    }
}

foreach ([
    'landHopCount',
    'bargeHopCount',
    'collectionPointCount',
] as $needle) {
    if (!str_contains($previewDto, $needle)) {
        fwrite(STDERR, sprintf("[CartLogisticsCache][FAIL] CartLogisticsPreview ne contient pas : %s\n", $needle));
        exit(1);
    }
}

echo "[CartLogisticsCache][OK] Cache logistique panier versionné pour forcer le recalcul des annotations frais livraison après déploiement.\n";
