# Commit — J5X-C-bis — Clarification formulaire produit

## Résumé

Clarifie l’écran EasyAdmin Produit après J5X-C pour éviter la confusion entre la promesse affichée sur la fiche produit et la gestion logistique des points de remise.

## Fichiers concernés

- `src/Controller/Admin/ProductCrudController.php`
- `tools/assert-j5x-c-product-delivery-promises.php`
- `docs/README_MAJ_J5X_C_BIS_CLARIFICATION_FORMULAIRE_PRODUIT_20260629.md`

## Décisions

- Le bloc J5X-C est un bloc de message client, pas un moteur de créneau.
- Les plages indicatives des produits sur créneau ne créent pas de point de remise.
- Les plages horaires des points de remise restent une fonctionnalité logistique avancée.
- La création rapide d’un point depuis un produit est conservée, mais présentée comme avancée.

## Validation

```powershell
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5x-c-product-delivery-promises.php
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate
php tools/assert-j5x-c-product-delivery-promises.php
```
