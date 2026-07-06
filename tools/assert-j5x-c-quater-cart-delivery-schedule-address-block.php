<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$template = $root . '/templates/cart/index.html.twig';
$controller = $root . '/src/Controller/CartController.php';
$css = $root . '/public/css/style_mobile.css';

$failures = [];

$read = static function (string $path) use (&$failures): string {
    if (!is_file($path)) {
        $failures[] = sprintf('Fichier introuvable : %s', $path);
        return '';
    }

    return (string) file_get_contents($path);
};

$templateContent = $read($template);
$controllerContent = $read($controller);
$cssContent = $read($css);

$expectations = [
    [$templateContent, 'data-standard-delivery-summary', 'Le bloc adresse standard doit rester la zone UX de livraison.'],
    [$templateContent, 'cart-delivery-schedule--address', 'Le planning doit être affiché dans le bloc adresse de livraison.'],
    [$templateContent, 'Planning de livraison', 'Le planning doit avoir un libellé client explicite.'],
    [$templateContent, 'data-cart-appointment-promise-note', 'Le panier doit pouvoir signaler un produit sur créneau sans créer de sélection de créneau.'],
    [$templateContent, "document.querySelectorAll('[data-delivery-schedule]')", 'Le rendu JS doit viser les blocs planning hors du seul total panier.'],
    [$controllerContent, 'cartHasAppointmentDeliveryPromise', 'Le contrôleur doit fournir le signal produit sur créneau au template.'],
    [$controllerContent, 'isAppointmentDeliveryPromise()', 'Le signal produit sur créneau doit provenir de Product::isAppointmentDeliveryPromise().'],
    [$cssContent, 'cart-delivery-schedule--address', 'Le CSS doit styler le planning dans le bloc adresse.'],
    [$cssContent, 'cart-appointment-promise-note', 'Le CSS doit styler la note produit sur créneau.'],
];

foreach ($expectations as [$content, $needle, $message]) {
    if (!str_contains($content, $needle)) {
        $failures[] = $message;
    }
}

$totalBlockPosition = strpos($templateContent, 'id="checkout-logistics-preview"');
$addressBlockPosition = strpos($templateContent, 'data-standard-delivery-summary');
$scheduleAddressPosition = strpos($templateContent, 'cart-delivery-schedule--address');

if ($totalBlockPosition === false || $addressBlockPosition === false || $scheduleAddressPosition === false) {
    $failures[] = 'Impossible de vérifier la position du planning dans le template panier.';
} elseif ($scheduleAddressPosition < $addressBlockPosition) {
    $failures[] = 'Le planning doit apparaître dans le bloc adresse, pas avant celui-ci.';
}

if (preg_match('/id="checkout-logistics-preview"[\s\S]{0,1800}cart-delivery-schedule--address/', $templateContent)) {
    $failures[] = 'Le planning adresse ne doit pas rester dans le bloc Total du panier.';
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[J5X-C-quater][KO] ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo '[J5X-C-quater][OK] Planning livraison affiché dans le bloc adresse panier et compatible AJAX.' . PHP_EOL;
