# README MAJ docs — J5S / J5T-C / J5V-A recette — 2026-06-28

## Objet

Mettre à jour la documentation après les validations recette annoncées le 28/06/2026 :

- J5S-B-ter/quater : checkout point de remise / livraison standard stabilisé ;
- J5T-C : checkout invité avec e-mail existant validé ;
- J5U-A : rappel expéditeur e-mails `commande@hodina.fr` déjà validé recette ;
- J5V-A : délai minimum produit corrigé et revalidé recette après réactivation de la validation serveur checkout par `3b508d0`.

## Validé recette

```text
J5S-B-ter/quater : recette-j5s-b-ter-quater-checkout-point-standard-20260628
J5T-C : recette-j5t-c-checkout-existing-account-20260628
J5T-C commit : 38f9e23 feat(j5t-c): allow guest checkout with existing account
J5U-A : commande@hodina.fr confirmé
J5V-A : régression détectée puis corrigée, validation recette confirmée le 28/06/2026
```

## Production

Aucune validation production n’est actée par cette mise à jour documentaire.

## Incohérence / vigilance J5V-A

L’archive contient bien :

- `Product.minimumOrderLeadTimeHours` ;
- migration `Version20260626194000` ;
- affichage panier du délai ;
- `DeliveryPointCartService::validateMinimumOrderLeadTime()`.

Cette incohérence a été corrigée ensuite : `CheckoutController` appelle désormais `validateMinimumOrderLeadTime()` dans le checkout point de remise. Le correctif `3b508d0` est validé recette sous `recette-j5v-a-checkout-lead-time-fix-20260628`.

## Fichiers modifiés

- `ARCHITECTURE.md`
- `DECISIONS.md`
- `ENTITIES.md`
- `WORKFLOWS.md`
- `TODO.md`
- `ROADMAP.md`
- `PILOT_STATUS_DETAILED.md`
- `DEPLOIEMENT_PREPROD.md`
- `HISTORIQUE.md`
- `README_MAJ_J5T_C_CHECKOUT_EXISTING_ACCOUNT_20260628.md`
- `README_MAJ_J5S_B_TER_QUATER_CHECKOUT_POINT_STANDARD_20260628.md`
- `COMMIT_J5T_C_CHECKOUT_EXISTING_ACCOUNT.md`
- `COMMIT_J5V_A_PRODUCT_LEAD_TIME.md`
- `COMMIT_DOCS_J5S_B_TER_QUATER_20260628.md`
- `COMMIT_DOCS_J5T_C_PAUSE_20260628.md`
- `README_MAJ_DOCS_J5T_J5U_J5V_J5W_20260627.md`
- `README_MAJ_DOCS_J5S_J5T_C_J5V_A_RECETTE_20260628.md`
- `COMMIT_DOCS_J5S_J5T_C_J5V_A_RECETTE_20260628.md`

## Commande de contrôle après application

```powershell
git diff -- docs/ARCHITECTURE.md docs/DECISIONS.md docs/ENTITIES.md docs/WORKFLOWS.md docs/TODO.md docs/ROADMAP.md docs/PILOT_STATUS_DETAILED.md docs/DEPLOIEMENT_PREPROD.md docs/HISTORIQUE.md docs/README_MAJ_J5T_C_CHECKOUT_EXISTING_ACCOUNT_20260628.md docs/README_MAJ_J5S_B_TER_QUATER_CHECKOUT_POINT_STANDARD_20260628.md docs/COMMIT_J5T_C_CHECKOUT_EXISTING_ACCOUNT.md docs/COMMIT_J5V_A_PRODUCT_LEAD_TIME.md docs/COMMIT_DOCS_J5S_B_TER_QUATER_20260628.md docs/COMMIT_DOCS_J5T_C_PAUSE_20260628.md docs/README_MAJ_DOCS_J5T_J5U_J5V_J5W_20260627.md docs/README_MAJ_DOCS_J5S_J5T_C_J5V_A_RECETTE_20260628.md docs/COMMIT_DOCS_J5S_J5T_C_J5V_A_RECETTE_20260628.md
```

## Commit conseillé

```powershell
git add docs/ARCHITECTURE.md `
  docs/DECISIONS.md `
  docs/ENTITIES.md `
  docs/WORKFLOWS.md `
  docs/TODO.md `
  docs/ROADMAP.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/HISTORIQUE.md `
  docs/README_MAJ_J5T_C_CHECKOUT_EXISTING_ACCOUNT_20260628.md `
  docs/README_MAJ_J5S_B_TER_QUATER_CHECKOUT_POINT_STANDARD_20260628.md `
  docs/COMMIT_J5T_C_CHECKOUT_EXISTING_ACCOUNT.md `
  docs/COMMIT_J5V_A_PRODUCT_LEAD_TIME.md `
  docs/COMMIT_DOCS_J5S_B_TER_QUATER_20260628.md `
  docs/COMMIT_DOCS_J5T_C_PAUSE_20260628.md `
  docs/README_MAJ_DOCS_J5T_J5U_J5V_J5W_20260627.md `
  docs/README_MAJ_DOCS_J5S_J5T_C_J5V_A_RECETTE_20260628.md `
  docs/COMMIT_DOCS_J5S_J5T_C_J5V_A_RECETTE_20260628.md

git commit -m "docs(j5s-j5t-j5v): record recette validations and production cautions"
```

## Addendum 28/06/2026 — J5V-A corrigé après régression

La réserve technique initiale J5V-A est levée en recette. Une régression a confirmé que le champ produit et le service existaient sans appel serveur effectif dans le checkout. Le commit `3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout` rebranche l’appel à `DeliveryPointCartService::validateMinimumOrderLeadTime()` dans `CheckoutController`.

Tag recette validé : `recette-j5v-a-checkout-lead-time-fix-20260628`.

État documentaire à retenir : J5V-A est corrigé et validé recette, mais ensuite validé production sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Addendum 29/06/2026 — Production validée

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Les tests minimum production sont annoncés OK. Cette validation production clôture le cycle recette → production pour le bloc checkout stabilisé. J5W / `DeliveryArea` reste prévu/non codé et ne doit pas modifier les responsabilités `DeliveryZone`.
