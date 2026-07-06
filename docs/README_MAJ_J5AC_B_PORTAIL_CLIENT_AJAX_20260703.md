# J5AC-B — Portail client AJAX progressif

Date : 2026-07-03

## Objectif

Optimiser l’espace client Hodina après J5AC-A sans rouvrir les règles métier : le client doit naviguer et soumettre les actions courantes du portail sans rechargement complet de fenêtre.

## Décision produit

Le portail client reste un espace simple : suivi de commandes, profil, sécurité. J5AC-B n’ajoute aucune fonctionnalité métier. Il améliore seulement la fluidité d’usage mobile-first.

## Périmètre validé

- Navigation interne `/mon-compte/*` en AJAX progressif.
- Formulaire profil en AJAX progressif.
- Formulaire changement mot de passe en AJAX progressif.
- Demande de lien de réinitialisation connecté en AJAX progressif.
- Annulation client conservée avec confirmation puis soumission AJAX progressive.
- Historique navigateur conservé avec `pushState` / `replaceState` / `popstate`.
- Fallback naturel conservé : sans JavaScript, toutes les pages et formulaires continuent de fonctionner en navigation classique.

## Hors périmètre

- Pas de changement des routes.
- Pas de changement de sécurité.
- Pas de changement sur panier, checkout, Djama, admin ou calcul livraison.
- Pas de nouveau contrat JSON : le patch réutilise les réponses HTML Symfony existantes.
- Pas de changement de la règle DB J5AC-A0 : `customer.email` reste unique nullable, `customer.phone` non unique.

## Fichiers principaux

- `templates/client/_account_ajax.html.twig` : script d’amélioration progressive du portail client.
- `templates/client/account/index.html.twig` : conteneur AJAX + inclusion script.
- `templates/client/orders/index.html.twig` : conteneur AJAX + inclusion script.
- `templates/client/orders/show.html.twig` : conteneur AJAX, annulation AJAX avec confirmation.
- `templates/client/profile/edit.html.twig` : formulaire profil marqué AJAX.
- `templates/client/security/password.html.twig` : formulaires sécurité marqués AJAX.
- `public/css/style_mobile.css` : état AJAX discret, toast et feedback déclencheur. La barre de progression globale a été supprimée par J5AC-B-bis.
- `tools/assert-j5ac-client-account-ajax.php` : garde-fou statique du lot.

## Tests attendus

```bash
php bin/console lint:twig templates/client/_account_ajax.html.twig templates/client/account/index.html.twig templates/client/profile/edit.html.twig templates/client/security/password.html.twig templates/client/orders/index.html.twig templates/client/orders/show.html.twig templates/base.html.twig
php bin/console lint:container
php tools/assert-j5ac-client-account-finalization.php
php tools/assert-j5ac-customer-email-db-readiness.php
php tools/assert-j5ac-client-account-ajax.php
php bin/console doctrine:schema:validate
```

## Tests navigateur

- Depuis `/mon-compte`, cliquer Accueil / Commandes / Profil / Sécurité : le contenu change sans reload complet.
- Modifier le profil : succès et erreurs restent dans le portail sans reload complet.
- Modifier le mot de passe : succès et erreurs restent dans le portail sans reload complet.
- Demander un lien de réinitialisation : message de confirmation visible sans reload complet.
- Annuler une commande annulable : confirmation conservée puis mise à jour de la page détail.
- Revenir en arrière avec le bouton navigateur : le contenu du portail reste cohérent.
- Désactiver JavaScript : toutes les routes et formulaires restent fonctionnels.

## Statut final

J5AC-B est validé en production dans le même tag que J5AC :

```text
Tag production : prod-j5ac-espace-client-ajax-20260703
Commit final : 0966429
```

Le lot J5AC-B-bis a ensuite rendu l’AJAX plus discret sans changer les routes ni les règles métier : suppression de la barre globale, feedback sur le déclencheur, moins de mouvement visuel.
