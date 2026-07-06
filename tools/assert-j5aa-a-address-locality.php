<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$checks = [
    'src/Entity/AddressLocality.php' => [
        'class AddressLocality',
        'private ?DeliveryCommune $deliveryCommune',
        'private ?string $postalCode',
        'private bool $isActive',
    ],
    'src/Repository/AddressLocalityRepository.php' => [
        'findActiveForCheckout',
        'findOneActiveCompatible',
    ],
    'src/Entity/Address.php' => [
        'private ?AddressLocality $addressLocality',
        'private ?string $localityText',
        'getLocalityLabel',
    ],
    'src/Entity/CustomerOrder.php' => [
        'private ?string $deliveryAddressLocalityName',
        'getDeliveryAddressLocalityName',
        'getLocalityLabel',
    ],
    'src/Command/SeedAddressLocalitiesCommand.php' => [
        'hodina:address-localities:seed',
        'Acoua',
        'Labattoir',
        'Kavani',
        'Miréréni',
        'Tsingoni',
    ],
    'src/Controller/Admin/AddressLocalityCrudController.php' => [
        'Localités',
        'Village / quartier / lieu-dit',
    ],
    'src/Controller/CheckoutController.php' => [
        'resolveSubmittedAddressLocality',
        'setAddressLocality($deliveryAddressLocality)',
        'setLocalityText($deliveryLocalityText)',
    ],
    'templates/cart/index.html.twig' => [
        'Village / quartier / lieu-dit',
        'checkout-address-locality-suggestions',
        'data-address-locality-suggestions',
        'address-locality-suggestion-button',
        'data-delivery-current-locality',
    ],
    'migrations/Version20260704210000.php' => [
        'address_locality',
        'address_locality_id',
        'locality_text',
        'delivery_address_locality_name',
    ],
];

$failures = [];

foreach ($checks as $relativePath => $needles) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        $failures[] = sprintf('%s introuvable.', $relativePath);
        continue;
    }

    $contents = (string) file_get_contents($path);
    foreach ($needles as $needle) {
        if (!str_contains($contents, $needle)) {
            $failures[] = sprintf('%s ne contient pas « %s ».', $relativePath, $needle);
        }
    }
}


$seedContents = (string) file_get_contents($root . '/src/Command/SeedAddressLocalitiesCommand.php');
$seededLocalities = substr_count($seedContents, "'name' =>");
if ($seededLocalities < 72) {
    $failures[] = sprintf('Le seed AddressLocality doit couvrir les villages de Mayotte. Trouvé : %d entrée(s), attendu au moins 72.', $seededLocalities);
}

$addressContents = (string) file_get_contents($root . '/src/Entity/Address.php');
if (str_contains($addressContents, 'private ?DeliveryCommune $deliveryCommune')) {
    $failures[] = 'Address ne doit pas ajouter de champ deliveryCommune en J5AA-A.';
}

$logisticsContents = is_file($root . '/src/Service/DeliveryLogisticsService.php')
    ? (string) file_get_contents($root . '/src/Service/DeliveryLogisticsService.php')
    : '';
if (str_contains($logisticsContents, 'AddressLocality') || str_contains($logisticsContents, 'getLocality')) {
    $failures[] = 'DeliveryLogisticsService ne doit pas dépendre de AddressLocality : la localité ne calcule pas les frais.';
}

if ($failures !== []) {
    echo "\n================================================================================\n";
    echo "J5AA-A — Assert AddressLocality\n";
    echo "================================================================================\n";
    foreach ($failures as $failure) {
        echo '[KO] ' . $failure . "\n";
    }
    echo "\n[J5AA-A][KO] AddressLocality n’est pas correctement cadrée.\n";
    exit(1);
}

echo "\n================================================================================\n";
echo "J5AA-A — Assert AddressLocality\n";
echo "================================================================================\n";
echo "[J5AA-A][OK] AddressLocality précise l’adresse, Address.commune reste central, aucun Address.deliveryCommune ni calcul de frais par localité.\n";

exit(0);
