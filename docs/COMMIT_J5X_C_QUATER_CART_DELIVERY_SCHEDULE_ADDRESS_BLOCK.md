# Commit — J5X-C-quater — Planning livraison dans le bloc adresse panier

Date : 2026-06-29
Branche : `develop`

## Objectif

Afficher la promesse de passage livraison au bon endroit dans le panier : sous l'adresse de livraison utilisée, et non dans le bloc total.

## Décision UX

Le client doit comprendre avant validation :

- où il est livré ;
- combien il paie ;
- quand Hodina peut passer.

Le planning appartient donc au bloc `Livraison / Adresse de livraison utilisée`, pas au bloc `Total du panier`.

## Changements

- Déplacement du rendu `data-delivery-schedule` dans la carte adresse standard.
- Rendu JavaScript compatible avec un bloc planning placé hors de `#checkout-logistics-preview`.
- Ajout d'une note légère si le panier contient un produit avec promesse `Sur créneau / rendez-vous`.
- Ajout d'un garde-fou `tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php`.

## Non-changements

- Aucun changement de frais de livraison.
- Aucun changement de `DeliveryLogisticsService`.
- Aucun changement de calendrier secteur J5X-B.
- Aucun changement checkout.
- Aucune sélection de créneau dans le panier.

## Tests attendus

```powershell
php -l src\Controller\CartController.php
php -l tools\assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php tools/assert-j5x-c-product-delivery-promises.php
```

Tests navigateur ciblés :

- `/panier` avec adresse Mamoudzou : planning mercredi/samedi visible dans le bloc adresse ;
- changement vers Labattoir : planning lundi/jeudi ;
- frais inchangés ;
- produit sur créneau : note informative visible, sans sélection de créneau forcée.
