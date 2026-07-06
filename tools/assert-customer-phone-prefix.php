<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'service' => $root . '/src/Service/PhoneNumberNormalizer.php',
    'command' => $root . '/src/Command/NormalizeCustomerPhonesCommand.php',
    'checkoutController' => $root . '/src/Controller/CheckoutController.php',
    'cartController' => $root . '/src/Controller/CartController.php',
    'registrationController' => $root . '/src/Controller/RegistrationController.php',
    'checkoutType' => $root . '/src/Form/CheckoutType.php',
    'registrationType' => $root . '/src/Form/RegistrationFormType.php',
    'cart' => $root . '/templates/cart/index.html.twig',
    'checkout' => $root . '/templates/checkout/index.html.twig',
    'registration' => $root . '/templates/registration/register.html.twig',
    'css' => $root . '/public/css/style_mobile.css',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, sprintf("[PhonePrefix][FAIL] Fichier manquant (%s) : %s\n", $label, $path));
        exit(1);
    }
}

require_once $files['service'];

$normalizer = new App\Service\PhoneNumberNormalizer();
$explicitCases = [
    ['+262', '0639 12 34 56', '+262639123456'],
    ['+262', '0269 60 00 00', '+262269600000'],
    ['+33', '06 12 34 56 78', '+33612345678'],
    ['+33', '02 99 00 00 00', '+33299000000'],
    ['+269', '773 00 00', '+2697730000'],
    ['+261', '034 12 345 67', '+261341234567'],
    ['+262', '+33 (0)6 12 34 56 78', '+33612345678'],
];

foreach ($explicitCases as [$dialCode, $input, $expected]) {
    $actual = $normalizer->normalizeWithDialCode($dialCode, $input);
    if ($actual !== $expected) {
        fwrite(STDERR, sprintf("[PhonePrefix][FAIL] %s + %s => %s, attendu %s\n", $dialCode, $input, $actual, $expected));
        exit(1);
    }
}

$legacyCases = [
    ['0639123456', '+262639123456'],
    ['0269600000', '+262269600000'],
    ['0612345678', '+33612345678'],
    ['0299000000', '+33299000000'],
    ['+2620639123456', '+262639123456'],
    ['0033261000000', '+33261000000'],
];

foreach ($legacyCases as [$input, $expected]) {
    $actual = $normalizer->normalizeLegacy($input);
    if ($actual !== $expected) {
        fwrite(STDERR, sprintf("[PhonePrefix][FAIL] legacy %s => %s, attendu %s\n", $input, $actual, $expected));
        exit(1);
    }
}

$checks = [
    'choix Mayotte en premier' => [$files['service'], "'Mayotte / La Réunion (+262)' => '+262'"],
    'choix métropole' => [$files['service'], "'France métropolitaine (+33)' => '+33'"],
    'pas de datalist devinée' => [$files['service'], 'normalizeWithDialCode'],
    'rattrapage séparé' => [$files['service'], 'normalizeLegacy'],
    'commande rattrapage' => [$files['command'], 'hodina:customers:normalize-phones'],
    'checkout champ indicatif' => [$files['checkoutType'], "->add('phoneCountryCode'"],
    'registration champ indicatif' => [$files['registrationType'], "->add('phoneCountryCode'"],
    'checkout assemble indicatif' => [$files['checkoutController'], 'normalizeWithDialCode'],
    'cart prépare indicatif' => [$files['cartController'], 'splitForForm'],
    'registration assemble indicatif' => [$files['registrationController'], 'normalizeWithDialCode'],
    'cart affiche indicatif avant téléphone' => [$files['cart'], 'form.phoneCountryCode'],
    'cart cache indicatif client connecté' => [$files['cart'], '{{ form_row(form.phoneCountryCode) }}'],
    'registration affiche indicatif avant téléphone' => [$files['registration'], 'registrationForm.phoneCountryCode'],
    'css ligne téléphone' => [$files['css'], '.phone-input-row'],
];

foreach ($checks as $label => [$path, $needle]) {
    if (!str_contains(file_get_contents($path), $needle)) {
        fwrite(STDERR, sprintf("[PhonePrefix][FAIL] Contrôle manquant : %s (%s)\n", $label, $needle));
        exit(1);
    }
}

echo "[PhonePrefix][OK] Formulaires client : champ indicatif explicite avant téléphone, assemblage international et rattrapage legacy séparé conformes.\n";
