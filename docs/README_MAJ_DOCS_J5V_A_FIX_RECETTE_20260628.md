# README MAJ docs — J5V-A correctif checkout lead time — 2026-06-28

## Objectif

Mettre à jour la documentation après correction et revalidation recette de J5V-A.

## État réel acté

- J5V-A avait une régression : le champ `Product.minimumOrderLeadTimeHours` et le service `DeliveryPointCartService::validateMinimumOrderLeadTime()` existaient, mais l’appel serveur n’était plus branché dans le checkout.
- Correctif code : `3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout`.
- Tag recette : `recette-j5v-a-checkout-lead-time-fix-20260628`.
- Recette validée : produit à délai 48 h, rendez-vous trop proche refusé, message global affiché, panier conservé.
- Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Fichiers documentaires concernés

- `ARCHITECTURE.md` : branchement technique CheckoutController → DeliveryPointCartService.
- `DECISIONS.md` : serveur source de vérité, pas de contournement JS.
- `ENTITIES.md` : champ produit et migration, état recette/production.
- `WORKFLOWS.md` : parcours client point de remise avec délai minimum.
- `TODO.md` : J5V-A corrigé/revalidé recette, production non cochée.
- `ROADMAP.md` : J5V-A n’est plus à réconcilier techniquement, mais reste à promouvoir production.
- `PILOT_STATUS_DETAILED.md` : statut pilote.
- `DEPLOIEMENT_PREPROD.md` : tag recette et contrôles.
- `HISTORIQUE.md` : chronologie de la régression et du correctif.
- `COMMIT_J5V_A_PRODUCT_LEAD_TIME.md` : addendum correctif.

## Commandes de contrôle recommandées

```powershell
cd E:\hodina\hodina.fr
git diff -- docs/ARCHITECTURE.md docs/DECISIONS.md docs/ENTITIES.md docs/WORKFLOWS.md docs/TODO.md docs/ROADMAP.md docs/PILOT_STATUS_DETAILED.md docs/DEPLOIEMENT_PREPROD.md docs/HISTORIQUE.md docs/COMMIT_J5V_A_PRODUCT_LEAD_TIME.md
```

## Commit conseillé

```powershell
git commit -m "docs(j5v-a): record checkout lead time fix recette validation"
```

## Addendum 29/06/2026 — Production validée

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Les tests minimum production sont annoncés OK. Cette validation production clôture le cycle recette → production pour le bloc checkout stabilisé. J5W / `DeliveryArea` reste prévu/non codé et ne doit pas modifier les responsabilités `DeliveryZone`.
