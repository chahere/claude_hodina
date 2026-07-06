<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    'cart' => $root . '/templates/cart/index.html.twig',
    'checkout' => $root . '/templates/checkout/index.html.twig',
    'confirmation' => $root . '/templates/checkout/confirmation.html.twig',
    'clientOrder' => $root . '/templates/client/orders/show.html.twig',
    'emailCreated' => $root . '/templates/emails/order_created.html.twig',
    'orderEmailService' => $root . '/src/Service/OrderEmailService.php',
    'checkoutController' => $root . '/src/Controller/CheckoutController.php',
    'cartController' => $root . '/src/Controller/CartController.php',
    'deliveryFeeReasonFormatter' => $root . '/src/Service/DeliveryFeeReasonFormatter.php',
    'css' => $root . '/public/css/style_mobile.css',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] Fichier manquant (%s) : %s\n", $label, $path));
        exit(1);
    }
}

foreach (['cart', 'checkout'] as $label) {
    $content = file_get_contents($files[$label]);
    foreach ([
        'data-logistics-fee-reason',
        'buildDeliveryFeeReason',
        'Inclus : ',
        'landHopCount',
        'commune',
        'traversée',
        'barge',
        "setHidden('[data-logistics-fee-reason]'",
    ] as $needle) {
        if (!str_contains($content, $needle)) {
            fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] %s ne contient pas : %s\n", $label, $needle));
            exit(1);
        }
    }
}

$cart = file_get_contents($files['cart']);
foreach ([
    'payload.deliveryFeeReason',
    'collectionRouteDetails',
    'sellerRouteDetails',
    'maxRouteMetric',
    'routeRequiresBarge',
] as $needle) {
    if (!str_contains($cart, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] cart AJAX ne contient pas : %s\n", $needle));
        exit(1);
    }
}

foreach (['confirmation', 'clientOrder'] as $label) {
    $content = file_get_contents($files[$label]);
    foreach ([
        'order.deliveryLogisticsSnapshot.preview',
        'Inclus : ',
        'landHopCount',
        'commune',
        'traversée',
        'barge',
        'cart-logistics-fee-note',
    ] as $needle) {
        if (!str_contains($content, $needle)) {
            fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] %s ne contient pas : %s\n", $label, $needle));
            exit(1);
        }
    }
}

$deliveryFeeReasonFormatter = file_get_contents($files['deliveryFeeReasonFormatter']);
foreach ([
    'Inclus : ',
    'commune',
    'traversée',
    'barge',
    'formatReasons',
    'reasonsFromPreviewArray',
] as $needle) {
    if (!str_contains($deliveryFeeReasonFormatter, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] DeliveryFeeReasonFormatter ne contient pas : %s\n", $needle));
        exit(1);
    }
}

$orderEmailService = file_get_contents($files['orderEmailService']);
foreach ([
    'DeliveryFeeReasonFormatter',
    'deliveryFeeReason',
    'buildPlainOrderCreatedBody',
] as $needle) {
    if (!str_contains($orderEmailService, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] OrderEmailService ne contient pas : %s\n", $needle));
        exit(1);
    }
}

$emailCreated = file_get_contents($files['emailCreated']);
foreach ([
    'deliveryFeeReason',
    'Frais de livraison',
] as $needle) {
    if (!str_contains($emailCreated, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] Email commande ne contient pas : %s\n", $needle));
        exit(1);
    }
}


$cartController = file_get_contents($files['cartController']);
foreach ([
    'DeliveryFeeReasonFormatter',
    'deliveryFeeReason',
    'jsonLogisticsPreview',
] as $needle) {
    if (!str_contains($cartController, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] CartController aperçu AJAX ne contient pas : %s\n", $needle));
        exit(1);
    }
}

$checkoutController = file_get_contents($files['checkoutController']);
foreach ([
    'DeliveryFeeReasonFormatter',
    'Frais livraison : %s € (%s).',
    'rtrim($deliveryFeeReason',
] as $needle) {
    if (!str_contains($checkoutController, $needle)) {
        fwrite(STDERR, sprintf("[DeliveryFeeReason][FAIL] CheckoutController SMS ne contient pas : %s\n", $needle));
        exit(1);
    }
}

$css = file_get_contents($files['css']);
if (!str_contains($css, '.cart-logistics-fee-note')) {
    fwrite(STDERR, "[DeliveryFeeReason][FAIL] Classe CSS .cart-logistics-fee-note absente.\n");
    exit(1);
}

echo "[DeliveryFeeReason][OK] Justification explicite des frais livraison affichée sous les frais avec communes traversées et barge.\n";
