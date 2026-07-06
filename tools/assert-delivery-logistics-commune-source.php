<?php

declare(strict_types=1);

/*
 * J5M-C2 guardrail.
 *
 * Goal: DeliveryLogisticsService must always compute seller logistics from
 * Seller::deliveryCommune, never from the seller pickup address used only as
 * a field aid for couriers.
 *
 * Cross-platform usage:
 *   php tools/assert-delivery-logistics-commune-source.php
 */

$projectRoot = dirname(__DIR__);
$servicePath = $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'DeliveryLogisticsService.php';

function fail(string $message): never
{
    fwrite(STDERR, "[J5M-C2][KO] {$message}" . PHP_EOL);
    exit(1);
}

function ok(string $message): void
{
    fwrite(STDOUT, "[J5M-C2][OK] {$message}" . PHP_EOL);
}

if (!is_file($servicePath)) {
    fail('Fichier introuvable : ' . $servicePath);
}

$source = file_get_contents($servicePath);
if ($source === false) {
    fail('Impossible de lire : ' . $servicePath);
}

$tokens = token_get_all($source);
$codeWithoutComments = '';
foreach ($tokens as $token) {
    if (is_array($token)) {
        if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        $codeWithoutComments .= $token[1];
        continue;
    }

    $codeWithoutComments .= $token;
}

$forbiddenPatterns = [
    '/->\s*getPickupAddress\s*\(/' => 'getPickupAddress()',
    '/->\s*getEffectivePickupAddress\s*\(/' => 'getEffectivePickupAddress()',
    '/->\s*getCustomerAccount\s*\(/' => 'getCustomerAccount()',
    '/\$pickupAddress\b/' => '$pickupAddress',
    '/\bpickupAddress\b/' => 'pickupAddress',
];

foreach ($forbiddenPatterns as $pattern => $label) {
    if (preg_match($pattern, $codeWithoutComments) === 1) {
        fail(sprintf(
            'DeliveryLogisticsService ne doit pas utiliser %s. Les calculs trajet/coût/barge/BFS doivent rester basés sur Seller::getDeliveryCommune().',
            $label
        ));
    }
}

if (!preg_match('/function\s+resolveSellerLogisticsCommune\s*\([^)]*Product\s+\$product[^)]*\)\s*:\s*\?DeliveryCommune\s*\{(?P<body>.*?)\n\s*\}/s', $codeWithoutComments, $matches)) {
    fail('La méthode resolveSellerLogisticsCommune(Product $product): ?DeliveryCommune est absente ou sa signature a changé.');
}

$normalizedBody = preg_replace('/\s+/', '', $matches['body'] ?? '');
if ($normalizedBody === null || !str_contains($normalizedBody, 'return$product->getSeller()->getDeliveryCommune();')) {
    fail('resolveSellerLogisticsCommune() doit retourner explicitement $product->getSeller()->getDeliveryCommune().');
}

if (!str_contains($codeWithoutComments, 'resolveSellerLogisticsCommune($product)')) {
    fail('Le calcul logistique doit passer par resolveSellerLogisticsCommune($product).');
}

ok('DeliveryLogisticsService reste verrouillé sur Seller::deliveryCommune pour les trajets/coûts/barge/BFS.');
