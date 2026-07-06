<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$files = [
    'checkoutType' => $root . '/src/Form/CheckoutType.php',
    'cartService' => $root . '/src/Service/DeliveryPointCartService.php',
    'checkoutController' => $root . '/src/Controller/CheckoutController.php',
    'cartTemplate' => $root . '/templates/cart/index.html.twig',
    'css' => $root . '/public/css/style_mobile.css',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        $failures[] = sprintf('Fichier manquant : %s (%s)', $label, $path);
    }
}

$read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

$checkoutType = $read($files['checkoutType']);
$cartService = $read($files['cartService']);
$checkoutController = $read($files['checkoutController']);
$cartTemplate = $read($files['cartTemplate']);
$css = $read($files['css']);

$mustContain = [
    'CheckoutType passe l’heure en champ caché' => [$checkoutType, "deliveryPointRequestedTime', HiddenType::class"],
    'CheckoutType ne propose plus un TimeType libre' => [$checkoutType, "data-delivery-point-time-input"],
    'Service définit le pas de 30 minutes' => [$cartService, 'public const SLOT_INTERVAL_MINUTES = 30'],
    'Service refuse une heure qui finit après la plage' => [$cartService, '($requestedMinutes + self::SLOT_INTERVAL_MINUTES) <= $endMinutes'],
    'Service valide les départs alignés sur 30 minutes' => [$cartService, '($minutes % self::SLOT_INTERVAL_MINUTES) === 0'],
    'CheckoutController valide avec id de plage choisi' => [$checkoutController, '$selectedTimeWindowId > 0 ? $selectedTimeWindowId : null'],
    'Template affiche un select de créneau' => [$cartTemplate, 'data-delivery-point-slot-select'],
    'Template rend le select dans un bloc visible' => [$cartTemplate, 'delivery-point-slot-control'],
    'Template affiche un état initial explicite' => [$cartTemplate, 'Choisis d’abord une date'],
    'Template stocke l’id de plage côté DOM' => [$cartTemplate, 'data-window-id="{{ window.id }}"'],
    'JS génère les créneaux par demi-heure' => [$cartTemplate, 'for (let minute = start; minute + 30 <= end; minute += 30)'],
    'JS synchronise le champ caché horaire' => [$cartTemplate, 'data-delivery-point-time-input'],
    'CSS cible le select de créneau' => [$css, 'data-delivery-point-slot-select'],
    'CSS force le select à rester visible' => [$css, 'display: block !important'],
    'CSS garde un select désactivé lisible' => [$css, 'cursor: not-allowed'],
    'Template harmonise le bloc date/créneau' => [$cartTemplate, 'delivery-point-appointment-grid'],
    'Template applique une classe dédiée au champ date' => [$cartTemplate, 'delivery-point-date-input'],
    'Template expose un hook date explicite' => [$cartTemplate, 'data-delivery-point-date-input'],
    'CSS harmonise date et select' => [$css, 'J5Y-B-ter — date et créneau de remise harmonisés'],
    'CSS donne une hauteur commune aux champs' => [$css, 'min-height: 52px'],
    'CSS stylise le calendrier natif' => [$css, '::-webkit-calendar-picker-indicator'],
];

foreach ($mustContain as $label => [$haystack, $needle]) {
    if (!str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif absent `%s`.', $label, $needle);
    }
}

$mustNotContain = [
    'Pas de champ heure libre TimeType' => [$checkoutType, "deliveryPointRequestedTime', TimeType::class"],
    'Pas de validation permissive à heure de fin incluse' => [$cartService, '$requestedMinutes > $endMinutes'],
    'Pas de nouveau calcul livraison dans le template' => [$cartTemplate, 'DeliveryLogisticsService'],
    'Pas de modification du calendrier standard' => [$cartTemplate, 'DeliveryScheduleService'],
];

foreach ($mustNotContain as $label => [$haystack, $needle]) {
    if (str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif interdit détecté `%s`.', $label, $needle);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[J5Y-B][KO] Créneaux point de remise par demi-heure non conformes.\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[J5Y-B-ter][OK] Date et select créneau harmonisés : champs visibles, lisibles, créneaux demi-heure et validation serveur conservée.\n";
