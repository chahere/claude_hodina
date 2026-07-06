# README MAJ — J5W-A recette validée

Cette mise à jour documentaire acte la recette de J5W-A.

## Résumé

J5W-A introduit des zones tarifaires locales par secteur sans remplacer les territoires techniques PT/GT.

La recette est validée sous :

- tag : `recette-j5w-a-local-pricing-zones-20260629` ;
- commit : `162fcb4 merge(j5w-a): local pricing zones by sector`.

## Contrôles validés en recette

- dépôt propre ;
- schéma Doctrine OK ;
- migration `Version20260629083000` exécutée/current/latest ;
- garde-fou J5W-A OK ;
- `PETITE_TERRE_LOCAL` absent ;
- `PT_LOCAL` conservé pour Dzaoudzi, Labattoir et Pamandzi ;
- Grande-Terre découpée en `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL` ;
- tests fonctionnels recette annoncés OK.

## Production

Production actée ensuite sous `prod-j5w-a-local-pricing-zones-20260629` sur le commit `cea4d19`. Voir `README_MAJ_J5W_A_PROD_VALIDEE_20260629.md` et `COMMIT_PROD_J5W_A_LOCAL_PRICING_ZONES_20260629.md`.

## Règle anti-régression

J5W-A ne doit pas transformer `DeliveryPricingZone` en source de vérité pour la barge. La barge reste portée par les liaisons logistiques et les territoires techniques PT/GT.
