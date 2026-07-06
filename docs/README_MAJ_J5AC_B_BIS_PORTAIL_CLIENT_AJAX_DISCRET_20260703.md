# J5AC-B-bis — Portail client AJAX discret (2026-07-03)

## Objectif

Améliorer la perception UX du portail client après J5AC-B.

Le portail utilisait déjà `fetch()` pour naviguer dans `/mon-compte`, mais la barre de chargement globale et le scroll automatique donnaient une impression de rechargement complet.

## Décision

Conserver l’AJAX progressif, mais rendre le feedback plus naturel :

- suppression de la barre globale `.account-page.is-loading::before` ;
- suppression de l’animation `accountAjaxProgress` ;
- feedback discret sur le lien ou bouton déclencheur via `.is-ajax-pending` ;
- conservation du viewport pendant la navigation interne ;
- scroll seulement si le nouveau bloc est hors écran après une soumission ;
- fallback naturel conservé si JavaScript échoue ou est désactivé.

## Fichiers modifiés

- `templates/client/_account_ajax.html.twig`
- `public/css/style_mobile.css`
- `tools/assert-j5ac-client-account-ajax.php`

## Hors périmètre

- aucune route modifiée ;
- aucune règle métier modifiée ;
- aucune migration ;
- aucun changement sur panier, checkout, catalogue, Djama ou EasyAdmin.

## Tests

```bash
php bin/console lint:twig templates/client/_account_ajax.html.twig templates/client/account/index.html.twig templates/client/profile/edit.html.twig templates/client/security/password.html.twig templates/client/orders/index.html.twig templates/client/orders/show.html.twig templates/base.html.twig
php bin/console lint:container
php tools/assert-j5ac-client-account-ajax.php
```

Contrôle navigateur : Accueil / Commandes / Profil / Sécurité doivent rester en AJAX, sans barre globale et sans mouvement brutal.

## Validation production

État final :

```text
Inclus dans le tag production : prod-j5ac-espace-client-ajax-20260703
Commit final : 0966429
Statut : validé local + recette + production
```

Contrôles validés :

- navigation `Accueil / Commandes / Profil / Sécurité` en AJAX discret ;
- absence de barre globale ;
- absence de reload complet perceptible ;
- fallback sans JavaScript conservé ;
- asserts J5AC/J5AC-B/J5AC-DB OK en production.
