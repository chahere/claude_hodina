# README MAJ — J5T-C checkout invité avec compte existant — 2026-06-28

## Contexte

Après J5T-A/J5T-A-bis, le checkout invité créait automatiquement un compte client pour un nouvel e-mail. Le cas d’un e-mail déjà connu restait bloquant et affichait l’ancien message : `Un compte existe déjà avec cette adresse e-mail. Connecte-toi...`.

J5T-C remplace ce comportement par une confirmation explicite de rattachement au compte existant.

## Règle métier

Un client invité peut commander avec un e-mail déjà connu si, après soumission complète du checkout, il confirme que la commande sera rattachée au compte Hodina existant.

## Points anti-régression

- Ne pas afficher la popup à la saisie de l’e-mail.
- Ne pas ajouter d’erreur e-mail pour un compte existant dans `CheckoutController`.
- Ne pas appeler `setData()` après `handleRequest()`.
- Ne pas créer de doublon `Customer`.
- Ne pas persister de commande avant la confirmation.
- Ne pas afficher de lien création de mot de passe pour un compte existant.
- Ne pas changer les frais, les points de remise, Djama, SMS ou statuts.

## État au moment de cette documentation

Sources analysées : archive du 28/06/2026 16:18.

- Code J5T-C présent dans les sources.
- Tests locaux repris et annoncés OK.
- Recette validée sous le tag `recette-j5t-c-checkout-existing-account-20260628`.
- Commit : `38f9e23 feat(j5t-c): allow guest checkout with existing account`.
- Production non faite.

## Contrôle anti-régression conservé

1. Vérifier que l’ancien message n’est plus dans `CheckoutController` :

```powershell
Select-String -Path src\Controller\CheckoutController.php -Pattern "Connecte-toi avant de valider ta commande|utilise une autre adresse e-mail" -Context 3,3
```

2. En recette et avant production, rejouer e-mail nouveau, e-mail existant premier clic, confirmation popup, `ORDER_CREATED`, `EmailLog.body`, standard et point de remise.
3. Ne pas acter production tant que ces contrôles ne sont pas rejoués sur le tag exact à promouvoir.

## Addendum 29/06/2026 — Production validée

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Les tests minimum production sont annoncés OK. Cette validation production clôture le cycle recette → production pour le bloc checkout stabilisé. J5W / `DeliveryArea` reste prévu/non codé et ne doit pas modifier les responsabilités `DeliveryZone`.
