# README mise à jour documentation — J5T / J5U / J5V / cadrage J5W

Date : 27/06/2026

## Objet

Mettre à jour la documentation après :

- validation recette du checkout invité simplifié J5T-A / J5T-A-bis ;
- validation recette de l’expéditeur e-mails paramétrable J5U-A ;
- présence du code J5V-A dans les sources fournies ;
- cadrage métier des futurs lots J5W autour des communes, sous-zones, planning, express et rendez-vous point de remise.

## Points importants

- J5U-A est validé recette : les e-mails partent avec `commande@hodina.fr`.
- J5V-A était présent dans le code, mais la validation recette n’était pas actée dans cette documentation du 27/06/2026.
- Mise à jour postérieure du 28/06/2026 : J5V-A a connu une régression de branchement serveur ; le correctif `3b508d0` rebranche `validateMinimumOrderLeadTime()` dans `CheckoutController` et la recette est validée sous `recette-j5v-a-checkout-lead-time-fix-20260628`.
- Les besoins J5W sont documentés comme prévus, non codés.
- La future `DeliveryArea` ne doit pas remplacer `DeliveryZone`.
- Les calculs de barge restent basés sur Petite-Terre / Grande-Terre.
- Labattoir fait partie des `DeliveryCommune` seedées et doit être rattaché à la future `DeliveryArea` Petite-Terre.

## Fichiers principaux mis à jour

- `VISION.md`
- `ARCHITECTURE.md`
- `DECISIONS.md`
- `ENTITIES.md`
- `WORKFLOWS.md`
- `TODO.md`
- `ROADMAP.md`
- `PILOT_STATUS_DETAILED.md`
- `DEPLOIEMENT_PREPROD.md`
- `HISTORIQUE.md`
- `COMMIT_J5U_A_EMAIL_SENDER_SETTINGS.md`
- `COMMIT_J5V_A_PRODUCT_LEAD_TIME.md`
- `COMMIT_J5S_B_BIS_DELIVERY_POINT_APPOINTMENT.md`

## Commandes de contrôle après application

```powershell
git diff -- docs/VISION.md docs/ARCHITECTURE.md docs/DECISIONS.md docs/ENTITIES.md docs/WORKFLOWS.md docs/TODO.md docs/ROADMAP.md docs/PILOT_STATUS_DETAILED.md docs/DEPLOIEMENT_PREPROD.md docs/HISTORIQUE.md docs/COMMIT_J5U_A_EMAIL_SENDER_SETTINGS.md docs/COMMIT_J5V_A_PRODUCT_LEAD_TIME.md docs/COMMIT_J5S_B_BIS_DELIVERY_POINT_APPOINTMENT.md docs/README_MAJ_DOCS_J5T_J5U_J5V_J5W_20260627.md docs/COMMIT_DOCS_J5T_J5U_J5V_J5W_20260627.md
```

## Commit conseillé

```powershell
git add docs/VISION.md docs/ARCHITECTURE.md docs/DECISIONS.md docs/ENTITIES.md docs/WORKFLOWS.md docs/TODO.md docs/ROADMAP.md docs/PILOT_STATUS_DETAILED.md docs/DEPLOIEMENT_PREPROD.md docs/HISTORIQUE.md docs/COMMIT_J5U_A_EMAIL_SENDER_SETTINGS.md docs/COMMIT_J5V_A_PRODUCT_LEAD_TIME.md docs/COMMIT_J5S_B_BIS_DELIVERY_POINT_APPOINTMENT.md docs/README_MAJ_DOCS_J5T_J5U_J5V_J5W_20260627.md docs/COMMIT_DOCS_J5T_J5U_J5V_J5W_20260627.md
git commit -m "docs(j5t-j5w): update checkout email lead time and delivery area decisions"
```


## Mise à jour 29/06/2026

Le cadrage J5W initial reste utile comme backlog, mais le nom J5W-A est repris pour le lot validé localement « zones tarifaires locales par secteur ». La restriction produit par commune est déplacée en J5Y-A.
