# README — J5X-C-bis — Clarification formulaire produit

Date : 2026-06-29
Branche de développement : `develop`

## Objectif

Clarifier le formulaire EasyAdmin Produit après l’ajout J5X-C pour éviter la confusion entre :

- le mode de remise au client ;
- le message de livraison affiché sur la fiche produit ;
- la création rapide d’un point de remise ;
- les plages horaires d’un point de remise.

## Problème identifié

Dans l’écran d’édition produit, les champs `Début plage créneau`, `Fin plage créneau` et `Points de remise — plages du nouveau point` donnaient l’impression de configurer la même chose.

En réalité :

- les plages indicatives J5X-C servent uniquement à expliquer au client les produits sur créneau ;
- les plages du nouveau point de remise créent des `DeliveryPointTimeWindow` pour un vrai point de remise logistique ;
- les points existants doivent être gérés via les menus dédiés `Points de remise` et `Plages points de remise`.

## Modifications

- `Mode de livraison` devient `Mode de remise au client`.
- `Promesse livraison client — J5X-C` devient `Fiche produit — message de livraison client`.
- `Promesse de livraison` devient `Type de message affiché`.
- `Début plage créneau` devient `Début de plage indicative`.
- `Fin plage créneau` devient `Fin de plage indicative`.
- Les blocs points de remise sont renommés en blocs `Avancé — points de remise`.
- Les aides expliquent que le message J5X-C ne calcule pas les frais, ne crée pas un point de remise et ne garantit pas un créneau.
- L’aide des plages du nouveau point précise qu’elles ne s’appliquent que lors de la création d’un nouveau point avec nom + commune logistique.

## Hors périmètre

- Aucun changement du calcul des frais de livraison.
- Aucun changement du calendrier secteur J5X-B.
- Aucun changement du checkout.
- Aucun changement de validation serveur J5V-A.
- Aucun changement de logique de création rapide des points de remise.

## Validation attendue

```powershell
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5x-c-product-delivery-promises.php
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate
php tools/assert-j5x-c-product-delivery-promises.php
```

Tests navigateur ciblés :

- le formulaire produit distingue clairement le message fiche produit et les points de remise ;
- les blocs points de remise sont dans une zone avancée ;
- un produit standard garde le mode `Suit les passages du secteur client` ;
- un produit sur créneau affiche une plage indicative, sans promettre une livraison garantie ;
- la création rapide d’un point de remise reste disponible mais clairement expliquée.
