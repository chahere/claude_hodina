# COMMIT J5T-C — Checkout invité avec compte existant

## Statut

Validé localement et validé recette le 28/06/2026.

```text
Commit : 38f9e23 feat(j5t-c): allow guest checkout with existing account
Tag recette : recette-j5t-c-checkout-existing-account-20260628
Production : non actée
```

## Objectif

Permettre à un client invité de terminer une commande même si l’e-mail renseigné correspond déjà à un compte Hodina, sans créer de doublon client et sans forcer la connexion.

## Périmètre

- Champs techniques non mappés dans `CheckoutType` : `confirmExistingAccount`, `confirmedExistingAccountEmail`.
- Popup de confirmation dans `templates/cart/index.html.twig`.
- Rattachement au `Customer` existant dans `CheckoutController` après confirmation.
- Mention conditionnelle dans `ORDER_CREATED` : `Cette commande a été rattachée à ton espace client Hodina.`
- Corps `EmailLog` enrichi via `OrderEmailService`.
- Aucune migration.
- Aucun changement de frais, Djama, SMS ou statuts.

## Correction importante

L’ancien bloc de checkout qui ajoutait une erreur e-mail `Un compte existe déjà... Connecte-toi...` ne doit plus être présent dans `CheckoutController`. Ce comportement reste normal dans l’inscription classique, mais pas dans le checkout invité.

## État final recette

- Test e-mail nouveau annoncé OK.
- Test e-mail existant annoncé OK : popup au premier clic, aucune commande avant confirmation, aucun doublon `Customer`.
- Confirmation popup annoncée OK : commande rattachée au compte existant.
- `ORDER_CREATED` / `EmailLog.body` contiennent la mention de rattachement.
- Recette validée.
- Production non faite.

## Tests techniques recommandés

```powershell
php -l src/Controller/CheckoutController.php
php -l src/Form/CheckoutType.php
php -l src/Service/OrderEmailService.php
php bin/console lint:twig templates/cart/index.html.twig templates/emails/order_created.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tests fonctionnels obligatoires

1. E-mail nouveau : commande créée, compte créé, lien mot de passe dans `ORDER_CREATED`.
2. E-mail existant : premier clic affiche le popup, aucune commande créée, aucun doublon `Customer`.
3. Confirmation popup : commande créée et rattachée au compte existant.
4. `ORDER_CREATED` et `EmailLog.body` contiennent la mention de rattachement.
5. Point de remise : point/date/heure conservés après popup.
6. Livraison standard : adresse/commune conservées après popup.
7. `Modifier mon e-mail` ferme le popup sans créer de commande.

## Commit réalisé

```powershell
git add src/Controller/CheckoutController.php `
  src/Form/CheckoutType.php `
  src/Service/OrderEmailService.php `
  templates/cart/index.html.twig `
  templates/emails/order_created.html.twig `
  public/css/style_mobile.css `
  docs/ARCHITECTURE.md `
  docs/DECISIONS.md `
  docs/WORKFLOWS.md `
  docs/TODO.md `
  docs/HISTORIQUE.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/ENTITIES.md `
  docs/ROADMAP.md `
  docs/COMMIT_J5T_C_CHECKOUT_EXISTING_ACCOUNT.md

git commit -m "feat(j5t-c): allow guest checkout with existing account"
# Commit réalisé : 38f9e23
# Tag recette validé : recette-j5t-c-checkout-existing-account-20260628
```

## Production 29/06/2026 — J5T-C validé

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

J5T-C est validé production : le checkout invité accepte un e-mail existant avec popup de confirmation, sans créer de commande avant confirmation et sans créer de doublon `Customer`. Après confirmation, la commande est rattachée au compte existant et `ORDER_CREATED` mentionne le rattachement.
