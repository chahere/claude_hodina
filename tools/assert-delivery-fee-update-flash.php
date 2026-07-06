<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    'cart' => $root . '/templates/cart/index.html.twig',
    'css' => $root . '/public/css/style_mobile.css',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, sprintf("[DeliveryFeeUpdateFlash][FAIL] Fichier manquant (%s) : %s\n", $label, $path));
        exit(1);
    }
}

$cart = file_get_contents($files['cart']);
foreach ([
    'data-delivery-fee-update-flash',
    'cart-info-flash--page-top',
    'data-dismiss-delivery-fee-update',
    'Frais de livraison mis à jour',
    'Selon ta nouvelle adresse, Hodina a recalculé les frais. Le détail apparaît sous “Frais de livraison”.',
    'showDeliveryFeeUpdateFlash',
    'hideDeliveryFeeUpdateFlash',
    'buildDeliveryFeeSignature',
    "refreshLogisticsPreview('delivery-address')",
    "refreshLogisticsPreview('delivery-point')",
    "refreshLogisticsPreview('delivery-method')",
    'shouldNotifyFeeUpdate',
] as $needle) {
    if (!str_contains($cart, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeUpdateFlash][FAIL] Panier ne contient pas : %s\n", $needle));
        exit(1);
    }
}

$flashPosition = strpos($cart, 'data-delivery-fee-update-flash');
$cartHeadPosition = strpos($cart, '<div class="cart-head">');
$totalsPosition = strpos($cart, 'cart-step--totals');
if ($flashPosition === false || $cartHeadPosition === false || $flashPosition > $cartHeadPosition) {
    fwrite(STDERR, "[DeliveryFeeUpdateFlash][FAIL] Le message frais recalculés doit être placé en haut du panier, au-dessus de la bannière.\n");
    exit(1);
}
if ($totalsPosition !== false && $flashPosition > $totalsPosition) {
    fwrite(STDERR, "[DeliveryFeeUpdateFlash][FAIL] Le message frais recalculés ne doit pas être caché dans la section Total du panier.\n");
    exit(1);
}

$css = file_get_contents($files['css']);
foreach ([
    '.cart-info-flash',
    '.cart-info-flash[hidden]',
    '.cart-info-flash-close',
    '.cart-info-flash--page-top',
    'background:#f4e6d2;',
    'border:1px solid #d7b98a;',
] as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeUpdateFlash][FAIL] CSS ne contient pas : %s\n", $needle));
        exit(1);
    }
}

echo "[DeliveryFeeUpdateFlash][OK] Message informatif supprimable, opaque et visible en haut du panier quand une nouvelle adresse change les frais de livraison.\n";
