<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$read = static function (string $relative) use ($root, &$failures): string {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        $failures[] = "Fichier manquant : {$relative}";
        return '';
    }

    return (string) file_get_contents($path);
};

$assertContains = static function (string $haystack, string $needle, string $message) use (&$failures): void {
    if (!str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};

$assertNotContains = static function (string $haystack, string $needle, string $message) use (&$failures): void {
    if (str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};

$customer = $read('src/Entity/Customer.php');
$migration = $read('migrations/Version20260703093000.php');
$security = $read('config/packages/security.yaml');
$accountController = $read('src/Controller/Client/AccountController.php');
$profileController = $read('src/Controller/Client/ProfileController.php');
$passwordController = $read('src/Controller/Client/PasswordController.php');
$profileType = $read('src/Form/ClientProfileType.php');
$changePasswordType = $read('src/Form/ClientChangePasswordType.php');
$resetService = $read('src/Service/CustomerPasswordResetLinkService.php');
$baseTemplate = $read('templates/base.html.twig');
$accountTemplate = $read('templates/client/account/index.html.twig');
$profileTemplate = $read('templates/client/profile/edit.html.twig');
$passwordTemplate = $read('templates/client/security/password.html.twig');
$ordersTemplate = $read('templates/client/orders/index.html.twig');
$orderShowTemplate = $read('templates/client/orders/show.html.twig');
$css = $read('public/css/style_mobile.css');
$dbAssert = $read('tools/assert-j5ac-customer-email-db-readiness.php');

$assertContains($customer, "#[ORM\\UniqueConstraint(name: 'UNIQ_CUSTOMER_EMAIL', columns: ['email'])]", 'Customer doit déclarer la contrainte unique nullable UNIQ_CUSTOMER_EMAIL en attribut Doctrine reconnu par SchemaTool.');
$assertContains($customer, '#[ORM\Column(length: 180, nullable: true)]', 'customer.email doit rester nullable dans J5AC.');

$assertContains($migration, 'UPDATE customer SET email = NULL', 'La migration J5AC doit convertir les emails vides en NULL.');
$assertContains($migration, 'UPDATE customer SET email = LOWER(TRIM(email))', 'La migration J5AC doit normaliser les emails existants.');
$assertContains($migration, 'HAVING COUNT(*) > 1', 'La migration J5AC doit bloquer les doublons normalisés avant index unique.');
$assertContains($migration, 'CREATE UNIQUE INDEX UNIQ_CUSTOMER_EMAIL ON customer (email)', 'La migration J5AC doit créer un index unique nullable sur customer.email.');
$assertNotContains($migration, 'UNIQUE INDEX UNIQ_CUSTOMER_PHONE', 'J5AC ne doit pas ajouter de contrainte unique sur customer.phone.');
$assertNotContains($migration, 'phone)', 'J5AC ne doit pas créer d’index unique téléphone.');

$assertContains($security, 'path: ^/mon-compte, roles: ROLE_USER', 'Le portail client doit rester protégé par ROLE_USER.');

$assertContains($accountController, "#[Route('', name: 'client_account_index'", '/mon-compte doit être une page hub, pas seulement une redirection.');
$assertContains($accountController, "client/account/index.html.twig", 'AccountController doit rendre le dashboard compte.');
$assertContains($accountController, 'o.customer = :customer', 'Les commandes client doivent rester filtrées par propriétaire.');
$assertContains($accountController, 'o.status != :draft', 'Les commandes DRAFT doivent rester exclues du portail client.');

$assertContains($profileController, "#[Route('/mon-compte/profil')]", 'La route profil client doit exister.');
$assertContains($profileController, 'PhoneNumberNormalizer', 'Le profil doit réutiliser PhoneNumberNormalizer.');
$assertContains($profileController, 'LOWER(customer.email) = :email', 'Le profil doit contrôler les doublons email côté applicatif.');
$assertContains($profileController, "customer.id != :currentCustomerId", 'Le contrôle doublon email doit exclure le compte courant.');
$assertContains($profileController, 'setEmail($normalizedEmail)', 'Le profil doit enregistrer un email normalisé.');
$assertContains($profileController, 'setPhone($normalizedPhone)', 'Le profil doit enregistrer un téléphone normalisé.');

$assertContains($passwordController, "#[Route('/mon-compte/mot-de-passe')]", 'La route sécurité/mot de passe client doit exister.');
$assertContains($passwordController, 'isPasswordValid($customer, $currentPassword)', 'Le changement de mot de passe doit vérifier l’ancien mot de passe.');
$assertContains($passwordController, 'hashPassword($customer, $plainPassword)', 'Le nouveau mot de passe doit être hashé.');
$assertContains($passwordController, "client_password_reset_link", 'La demande de lien reset connecté doit être protégée par CSRF.');
$assertContains($passwordController, 'CustomerPasswordResetLinkService', 'La génération reset connecté doit passer par le service dédié.');

$assertContains($profileType, 'phoneCountryCode', 'Le formulaire profil doit conserver un indicatif téléphone explicite.');
$assertContains($profileType, 'PhoneNumberNormalizer::dialCodeChoices()', 'Le formulaire profil doit réutiliser les indicatifs J5Z.');
$assertContains($changePasswordType, 'currentPassword', 'Le formulaire mot de passe doit demander l’ancien mot de passe.');
$assertContains($changePasswordType, 'plainPassword', 'Le formulaire mot de passe doit demander le nouveau mot de passe.');

$assertContains($resetService, 'setResetPasswordToken($token)', 'Le service reset doit écrire le token sur Customer.');
$assertContains($resetService, "setContext('customer_password_reset_link')", 'Le service reset doit créer un SmsLog compatible pilote.');
$assertContains($resetService, "UrlGeneratorInterface::ABSOLUTE_URL", 'Le lien reset doit être absolu pour SMS.');

$assertContains($baseTemplate, "path('client_account_index')", 'Le menu header doit pointer vers le hub Mon compte.');
$assertContains($accountTemplate, "client/_account_nav.html.twig", 'Le dashboard compte doit afficher la navigation client.');
$assertContains($profileTemplate, "profileForm.email", 'Le template profil doit exposer l’email.');
$assertContains($passwordTemplate, "passwordForm.currentPassword", 'Le template sécurité doit exposer l’ancien mot de passe.');
$assertContains($passwordTemplate, "client_security_reset_link_request", 'Le template sécurité doit proposer la demande de lien reset connecté.');
$assertContains($ordersTemplate, "client/_account_nav.html.twig", 'La liste commandes doit afficher la navigation compte.');
$assertContains($orderShowTemplate, "client/_account_nav.html.twig", 'Le détail commande doit afficher la navigation compte.');
$assertNotContains($orderShowTemplate, 'deliveryValidationCode }}</', 'Le code de réception ne doit pas être affiché en clair côté client.');

$assertContains($css, 'J5AC — Espace client finalisé', 'Le CSS J5AC doit être isolé et identifiable.');
$assertContains($css, '.account-nav', 'La navigation compte mobile doit être stylée.');
$assertContains($css, '.account-dashboard-grid', 'Le dashboard compte doit être stylé.');
$assertContains($dbAssert, 'HAVING COUNT(*) > 1', 'L’assert DB J5AC doit vérifier les doublons email normalisés.');
$assertContains($dbAssert, 'UNIQ_CUSTOMER_EMAIL', 'L’assert DB J5AC doit vérifier l’index email unique nullable.');
$assertContains($dbAssert, "COLUMN_NAME = 'phone'", 'L’assert DB J5AC doit contrôler qu’aucun unique téléphone n’est présent.');

if ($failures !== []) {
    fwrite(STDERR, "[J5AC][ERREUR] Espace client finalisation non conforme :\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[J5AC][OK] Espace client finalisé : hub /mon-compte, profil avec email unique nullable DB, téléphone normalisé, mot de passe sécurisé, reset connecté via SmsLog, commandes protégées.\n";
