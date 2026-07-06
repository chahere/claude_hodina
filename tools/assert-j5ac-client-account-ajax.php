<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$requiredFiles = [
    'templates/client/_account_ajax.html.twig',
    'templates/client/account/index.html.twig',
    'templates/client/orders/index.html.twig',
    'templates/client/orders/show.html.twig',
    'templates/client/profile/edit.html.twig',
    'templates/client/security/password.html.twig',
    'public/css/style_mobile.css',
];

$failures = [];

$read = static function (string $relative) use ($root, &$failures): string {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        $failures[] = "Fichier manquant : {$relative}";
        return '';
    }

    return (string) file_get_contents($path);
};

foreach ($requiredFiles as $file) {
    $read($file);
}

$ajax = $read('templates/client/_account_ajax.html.twig');
$css = $read('public/css/style_mobile.css');

$expectContains = static function (string $content, string $needle, string $message) use (&$failures): void {
    if (!str_contains($content, $needle)) {
        $failures[] = $message;
    }
};

$expectNotContains = static function (string $content, string $needle, string $message) use (&$failures): void {
    if (str_contains($content, $needle)) {
        $failures[] = $message;
    }
};

$expectContains($ajax, 'window.HodinaClientAccountAjaxReady', 'Le script AJAX client doit être idempotent.');
$expectContains($ajax, "const pageSelector = '[data-client-account-page]'", 'Le script AJAX doit cibler uniquement le portail client.');
$expectContains($ajax, "url.pathname.startsWith('/mon-compte')", 'Le script AJAX doit rester limité aux routes /mon-compte.');
$expectContains($ajax, "'X-Requested-With': 'XMLHttpRequest'", 'Les appels AJAX doivent envoyer X-Requested-With.');
$expectContains($ajax, "'Accept': 'text/html'", 'Les appels AJAX doivent demander du HTML progressif.');
$expectContains($ajax, 'new DOMParser()', 'Le script doit parser la réponse HTML pour remplacer seulement le contenu client.');
$expectContains($ajax, 'page.replaceWith(nextPage)', 'Le script doit remplacer la page client sans reload complet.');
$expectContains($ajax, 'markTrigger(trigger, true)', 'Le feedback AJAX doit être porté par le lien ou bouton déclencheur.');
$expectContains($ajax, 'keepViewport: true', 'La navigation interne doit conserver le viewport pour éviter une sensation de reload.');
$expectContains($ajax, 'window.history.pushState', 'La navigation interne doit conserver l’historique navigateur.');
$expectContains($ajax, 'window.history.replaceState', 'Les soumissions POST doivent mettre à jour l’URL sans reload complet.');
$expectContains($ajax, 'popstate', 'Le bouton retour navigateur doit être géré.');
$expectContains($ajax, 'data-confirm-message', 'La confirmation annulation commande doit être conservée.');
$expectContains($ajax, 'new FormData(form)', 'Les formulaires client doivent être soumis en AJAX progressif.');
$expectNotContains($ajax, 'application/json', 'J5AC-B ne doit pas imposer de contrat JSON spécifique.');

foreach ([
    'templates/client/account/index.html.twig',
    'templates/client/orders/index.html.twig',
    'templates/client/orders/show.html.twig',
    'templates/client/profile/edit.html.twig',
    'templates/client/security/password.html.twig',
] as $template) {
    $content = $read($template);
    $expectContains($content, 'data-client-account-page', "{$template} doit exposer le conteneur AJAX du portail client.");
    $expectContains($content, "client/_account_ajax.html.twig", "{$template} doit charger le JS AJAX du portail client.");
}

$profile = $read('templates/client/profile/edit.html.twig');
$password = $read('templates/client/security/password.html.twig');
$show = $read('templates/client/orders/show.html.twig');

$expectContains($profile, "data-client-account-form': 'profile'", 'Le formulaire profil doit être marqué pour AJAX.');
$expectContains($password, "data-client-account-form': 'password'", 'Le formulaire mot de passe doit être marqué pour AJAX.');
$expectContains($password, 'data-client-account-form="reset-link"', 'Le formulaire reset connecté doit être marqué pour AJAX.');
$expectContains($show, 'data-client-account-form="cancel-order"', 'Le formulaire annulation commande doit être marqué pour AJAX.');
$expectContains($show, 'data-confirm-message=', 'La confirmation annulation commande doit rester présente.');

$expectContains($css, '.account-page.is-loading', 'Le CSS doit conserver un état AJAX accessible sans barre globale.');
$expectContains($css, '.is-ajax-pending', 'Le CSS doit fournir un feedback discret sur le déclencheur AJAX.');
$expectContains($css, '.account-ajax-toast', 'Le CSS doit fournir un toast AJAX client.');
$expectNotContains($css, '.account-page.is-loading::before', 'J5AC-B-bis ne doit plus afficher de barre de chargement globale.');
$expectNotContains($css, '@keyframes accountAjaxProgress', 'J5AC-B-bis ne doit plus animer une barre globale.');
$expectNotContains($ajax, 'window.scrollTo({top: 0, behavior:', 'J5AC-B-bis doit éviter le scroll automatique façon rechargement.');

if ($failures !== []) {
    fwrite(STDERR, "[J5AC-B][ERREUR] Portail client AJAX non conforme :\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[J5AC-B][OK] Portail client AJAX progressif discret : navigation /mon-compte sans reload complet, feedback déclencheur, pas de barre globale, fallback naturel conservé.\n";
