<?php

declare(strict_types=1);

/*
 * J5W-A guardrail.
 *
 * Goal: local pricing zones by sector must not replace PT/GT territories and
 * must not move barge pricing out of logistics connections.
 *
 * Cross-platform usage:
 *   php tools/assert-j5w-a-local-pricing-zones.php
 */

$projectRoot = dirname(__DIR__);

function fail(string $message): never
{
    fwrite(STDERR, "[J5W-A][KO] {$message}" . PHP_EOL);
    exit(1);
}

function ok(string $message): void
{
    fwrite(STDOUT, "[J5W-A][OK] {$message}" . PHP_EOL);
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

$deliveryCommune = readProjectFile('src/Entity/DeliveryCommune.php');
foreach (["public const TERRITORY_PT = 'PT';", "public const TERRITORY_GT = 'GT';"] as $requiredLine) {
    if (!str_contains($deliveryCommune, $requiredLine)) {
        fail('DeliveryCommune doit conserver les territoires techniques PT/GT. Ligne manquante : ' . $requiredLine);
    }
}
ok('DeliveryCommune conserve les territoires techniques PT/GT.');

$logisticsService = readProjectFile('src/Service/DeliveryLogisticsService.php');
if (!str_contains($logisticsService, '$clientCommune->getLocalPricingZone()')) {
    fail('DeliveryLogisticsService doit continuer à calculer le forfait de base depuis DeliveryCommune::getLocalPricingZone().');
}
if (!str_contains($logisticsService, 'DeliveryCommuneConnection::LINK_TYPE_BARGE')) {
    fail('DeliveryLogisticsService doit continuer à connaître les liaisons logistiques BARGE.');
}
ok('DeliveryLogisticsService conserve localPricingZone comme forfait de base et les liaisons BARGE comme source trajet.');

$migration = readProjectFile('migrations/Version20260629083000.php');
foreach (['MAMOUDZOU_LOCAL', 'NORD_LOCAL', 'CENTRE_LOCAL', 'SUD_LOCAL', 'PT_LOCAL'] as $code) {
    if (!str_contains($migration, $code)) {
        fail('Migration J5W-A incomplète : code zone absent ' . $code);
    }
}
$forbiddenPetiteTerreCreationPatterns = [
    "'code' => 'PETITE_TERRE_LOCAL'",
    "=> 'PETITE_TERRE_LOCAL'",
    "PETITE_TERRE_LOCAL' =>",
];

foreach ($forbiddenPetiteTerreCreationPatterns as $pattern) {
    if (str_contains($migration, $pattern)) {
        fail('Migration J5W-A ne doit pas créer/rattacher PETITE_TERRE_LOCAL : Petite-Terre doit réutiliser PT_LOCAL. Motif interdit : ' . $pattern);
    }
}
foreach (['dzaoudzi', 'labattoir', 'pamandzi'] as $slug) {
    if (!str_contains($migration, "'{$slug}' => 'PT_LOCAL'")) {
        fail('Petite-Terre incomplète : ' . $slug . ' doit rester rattachée à PT_LOCAL.');
    }
}
ok('Migration J5W-A crée 4 zones Grande-Terre et conserve Dzaoudzi, Labattoir, Pamandzi sur PT_LOCAL.');
