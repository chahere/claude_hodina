<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$base = $root . '/templates/base.html.twig';
$style = $root . '/public/css/style_mobile.css';
$headerLogo = $root . '/public/images/logo_hodina_header.png';
$faviconIco = $root . '/public/favicon.ico';
$favicon16 = $root . '/public/images/favicon-16x16.png';
$favicon32 = $root . '/public/images/favicon-32x32.png';
$appleTouch = $root . '/public/images/apple-touch-icon.png';

$fail = static function (string $message): void {
    fwrite(STDERR, "[J5Y-D-ter][KO] {$message}" . PHP_EOL);
    exit(1);
};

foreach ([$base, $style, $headerLogo, $faviconIco, $favicon16, $favicon32, $appleTouch] as $file) {
    if (!is_file($file)) {
        $fail("Fichier attendu absent : {$file}");
    }
}

$baseContent = file_get_contents($base);
$styleContent = file_get_contents($style);

foreach ([
    "asset('favicon.ico') }}?v=j5y-d-ter",
    "asset('images/favicon-16x16.png') }}?v=j5y-d-ter",
    "asset('images/favicon-32x32.png') }}?v=j5y-d-ter",
    "asset('images/apple-touch-icon.png') }}?v=j5y-d-ter",
    'rel="shortcut icon"',
] as $needle) {
    if (!str_contains($baseContent, $needle)) {
        $fail("Balise favicon/cache-busting manquante : {$needle}");
    }
}

foreach ([
    'logo_hodina_header.png',
    'brand-logo',
] as $needle) {
    if (!str_contains($baseContent . $styleContent, $needle)) {
        $fail("Logo header J5Y-D non conservé : {$needle}");
    }
}

$png32 = file_get_contents($favicon32);
if (!str_starts_with($png32, "\x89PNG\r\n\x1a\n")) {
    $fail('favicon-32x32.png doit être un PNG valide.');
}

// PNG IHDR color type : 6 = truecolor + alpha. Cela évite le retour à un carré opaque.
$colorType = ord($png32[25] ?? "\0");
if ($colorType !== 6) {
    $fail('favicon-32x32.png doit conserver un canal alpha transparent.');
}

if (filesize($favicon32) > 5000) {
    $fail('favicon-32x32.png trop lourd pour un favicon.');
}

echo '[J5Y-D-ter][OK] Favicon Hodina transparent, cache-busting actif, logo header conservé.' . PHP_EOL;
