# README MAJ J5Z — Checkout/admin UX validé production — 02/07/2026

## Objet

Cette mise à jour documentaire clôture J5Z, mini-lot de finition checkout/admin validé localement, en recette et en production.

## Périmètre J5Z

- UX formulaire Produit EasyAdmin.
- Phrase catalogue clarifiée.
- Champ indicatif téléphone explicite.
- Commande de rattrapage téléphones legacy.
- Annotation frais livraison `Inclus : ...`.
- Flash frais recalculés visible en haut du panier.
- Correction mobile du champ Date de rendez-vous.
- Correction du champ Indicatif parasite client connecté.
- Cohérence annotation frais au premier affichage et après recalcul AJAX.
- Email / SMS commande enrichis avec annotation frais si nécessaire.

## Tags

```text
Tag recette final : recette-j5z-delivery-fee-reason-refresh-20260702
Tag production final : prod-j5z-delivery-fee-reason-refresh-20260702
```

Tags recette supersédés :

```text
recette-j5z-checkout-admin-ux-20260702
recette-j5z-checkout-admin-ux-fix-mobile-20260702
```

## Décisions clés

- Ne plus réécrire les fonctionnalités validées production ; étendre par petits lots.
- Ne pas déduire le pays depuis le téléphone saisi ; utiliser un indicatif explicite.
- Afficher l’annotation des frais uniquement si elle explique un supplément réel.
- Utiliser `commune traversée`, plus compréhensible que `liaison terrestre`.
- Le flash frais recalculés doit être visible, lisible, supprimable et non alarmant.
- Le cache de preview logistique est versionné pour éviter les sessions anciennes sans annotation.
- `DeliveryFeeReasonFormatter` explique les frais mais ne les calcule pas.

## Validation

Tests locaux : assertions J5Z, J5X-C/D, lint Twig, lint container.

Recette : tag final `recette-j5z-delivery-fee-reason-refresh-20260702` validé après hotfix AJAX.

Production : tests navigateur annoncés OK, incluant les cas annotation présente / absente selon trajet.

## Point de vigilance

Le rattrapage téléphone production n’est pas documenté par un extrait serveur dans cette mise à jour. Ne pas relancer `--apply` sans simulation préalable.
