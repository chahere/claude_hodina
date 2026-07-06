<?php

declare(strict_types=1);

/**
 * J5AA-B guardrail.
 *
 * Vérifie statiquement que le checkout utilise un code postal sélectionné depuis
 * le référentiel DeliveryCommune et que le serveur contrôle le couple
 * code postal + commune sans ajouter Address.deliveryCommune.
 */

$projectDir = dirname(__DIR__);

$checks = [
    [
        'file' => 'src/Form/CheckoutType.php',
        'label' => 'CheckoutType utilise un ChoiceType pour postalCode',
        'needles' => ["->add('postalCode', ChoiceType::class", 'buildDeliveryPostalCodeChoices'],
    ],
    [
        'file' => 'src/Form/CheckoutType.php',
        'label' => 'CheckoutType conserve commune comme choix DeliveryCommune',
        'needles' => ["->add('commune', ChoiceType::class", 'data-delivery-commune-select', 'data-postal-code'],
    ],
    [
        'file' => 'src/Controller/CheckoutController.php',
        'label' => 'CheckoutController valide le couple postalCode + commune côté serveur',
        'needles' => ['string $postalCode', 'resolveCanonicalActiveLogisticsCommune($commune, $postalCode)', 'Le code postal %s ne correspond pas à la commune %s', '$postalCode !== $expectedPostalCode'],
    ],
    [
        'file' => 'src/Controller/CheckoutController.php',
        'label' => 'CheckoutController persiste toujours Address.commune depuis DeliveryCommune.name',
        'needles' => ['->setPostalCode($deliveryPostalCode)', '->setCommune($deliveryCommuneName)'],
    ],
    [
        'file' => 'src/Controller/CartController.php',
        'label' => 'Aperçu logistique AJAX contrôle aussi le couple postalCode + commune',
        'needles' => ["request->request->get('postalCode'", 'resolveCanonicalActiveLogisticsCommune($communeName, $postalCode)', 'Choisis un code postal Hodina valide', 'Le code postal %s ne correspond pas à la commune %s'],
    ],
    [
        'file' => 'src/Service/DeliveryCommuneMatcherService.php',
        'label' => 'DeliveryCommuneMatcherService expose une résolution canonique stricte sans changer le fuzzy existant',
        'needles' => ['resolveCanonicalActiveLogisticsCommune', 'if ($normalizedCommune !== $this->normalize((string) $candidateName))'],
    ],
    [
        'file' => 'templates/cart/index.html.twig',
        'label' => 'Template affiche code postal sélectionnable et commune filtrable',
        'needles' => ['Choisis un code postal connu par Hodina', 'applyPostalCodeFilter', "body.set('postalCode'"],
    ],
];

$errors = [];

foreach ($checks as $check) {
    $path = $projectDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $check['file']);

    if (!is_file($path)) {
        $errors[] = sprintf('[KO] %s — fichier introuvable : %s', $check['label'], $check['file']);
        continue;
    }

    $content = file_get_contents($path);
    if (!is_string($content)) {
        $errors[] = sprintf('[KO] %s — impossible de lire : %s', $check['label'], $check['file']);
        continue;
    }

    foreach ($check['needles'] as $needle) {
        if (!str_contains($content, $needle)) {
            $errors[] = sprintf('[KO] %s — motif absent dans %s : %s', $check['label'], $check['file'], $needle);
        }
    }
}

$addressPath = $projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR . 'Address.php';
$addressContent = is_file($addressPath) ? (string) file_get_contents($addressPath) : '';

if (preg_match('/private\s+\??DeliveryCommune\s+\$deliveryCommune/', $addressContent) === 1) {
    $errors[] = '[KO] Address.deliveryCommune détecté : hors périmètre J5AA-B.';
}

$forbiddenFiles = glob($projectDir . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '*J5AA*.php') ?: [];
if ($forbiddenFiles !== []) {
    $errors[] = '[KO] Migration J5AA détectée : J5AA-B ne doit pas créer de migration.';
}

echo PHP_EOL;
echo '================================================================================' . PHP_EOL;
echo 'J5AA-B — Assert checkout code postal + commune' . PHP_EOL;
echo '================================================================================' . PHP_EOL;

if ($errors !== []) {
    foreach ($errors as $error) {
        echo $error . PHP_EOL;
    }

    echo PHP_EOL . '[J5AA-B][KO] Garde-fou échoué.' . PHP_EOL;
    exit(1);
}

echo '[J5AA-B][OK] Checkout livraison : code postal seedé, commune DeliveryCommune, contrôle serveur, sans Address.deliveryCommune ni migration.' . PHP_EOL;

exit(0);
