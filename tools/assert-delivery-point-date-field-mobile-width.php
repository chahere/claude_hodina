<?php

declare(strict_types=1);

$cssPath = __DIR__ . '/../public/css/style_mobile.css';
$css = file_get_contents($cssPath);

if ($css === false) {
    fwrite(STDERR, "[DeliveryPointDateWidth][FAIL] Impossible de lire public/css/style_mobile.css\n");
    exit(1);
}

$requiredSnippets = [
    '.delivery-point-date-control,',
    '.delivery-point-slot-control {',
    'max-width: 100%;',
    'min-width: 0;',
    'box-sizing: border-box;',
    '[data-delivery-point-date-input]',
    '[data-delivery-point-slot-select]',
    'Correctif mobile Safari',
    '.cart-step--delivery .delivery-point-appointment-grid,',
    '.cart-step--delivery input.delivery-point-date-input,',
    '.cart-step--delivery input[data-delivery-point-date-input],',
    '.cart-step--guest-checkout input.delivery-point-date-input,',
    '.cart-step--guest-checkout input[data-delivery-point-date-input]',
    'width:100% !important;',
    'max-width:100% !important;',
    'min-width:0 !important;',
    'box-sizing:border-box !important;',
    '-webkit-appearance:none;',
    '::-webkit-date-and-time-value',
];

foreach ($requiredSnippets as $snippet) {
    if (!str_contains($css, $snippet)) {
        fwrite(STDERR, sprintf("[DeliveryPointDateWidth][FAIL] Règle CSS manquante : %s\n", $snippet));
        exit(1);
    }
}

$dateInputPos = strpos($css, '.delivery-point-date-input,');
$boxSizingPos = strpos($css, 'box-sizing: border-box;', $dateInputPos === false ? 0 : $dateInputPos);

if ($dateInputPos === false || $boxSizingPos === false) {
    fwrite(STDERR, "[DeliveryPointDateWidth][FAIL] Le champ date de rendez-vous ne protège pas encore son calcul de largeur.\n");
    exit(1);
}

fwrite(STDOUT, "[DeliveryPointDateWidth][OK] Champ Date de rendez-vous contraint dans sa carte sur mobile pour client connecté et invité, y compris Safari/iPhone.\n");
