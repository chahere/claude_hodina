# README — Mise à jour J5X-D catalogue recherche, filtres, tri, priorité admin

Date : 2026-06-29

## Résumé

J5X-D améliore le catalogue public Hodina pour préparer l’ouverture : le client peut rechercher un produit, filtrer par catégorie, trier les résultats et profiter d’un affichage plus stratégique piloté depuis EasyAdmin.

Cette mise à jour ne change pas la livraison : les frais restent calculés par `DeliveryLogisticsService`, les calendriers restent portés par `DeliveryPricingZone`, et la promesse produit reste portée par J5X-C.

## Fonctionnalités ajoutées

### Catalogue public

- recherche `q` ;
- filtre catégorie `categorie` ;
- tri `tri` ;
- état sans résultat clair ;
- formulaire GET robuste ;
- rafraîchissement AJAX progressif du bloc résultats ;
- carte produit avec badge `Mis en avant` ;
- ajout panier AJAX conservé.

### Back-office

Sur `Category` :

- `displayOrder` ;
- `isFeatured` ;
- `publicDescription` ;
- `isActive` exposé dans EasyAdmin.

Sur `Product` :

- `isFeatured` ;
- `displayPriority` ;
- images ordonnées par `position` puis `id`.

## Règles métier

- Les catégories inactives ne sont pas affichées dans les filtres.
- Les produits inactifs ne sont pas affichés dans le catalogue.
- Le tri par prix utilise le prix client calculé par `ProductPricingService`.
- `displayPriority` : plus petit = plus haut.
- `GT_LOCAL`, `DeliveryPricingZone`, `DeliveryScheduleService` et `DeliveryLogisticsService` ne sont pas modifiés.

## Fichiers principaux

```text
migrations/Version20260629223000.php
src/Entity/Category.php
src/Entity/Product.php
src/Repository/CategoryRepository.php
src/Repository/ProductRepository.php
src/Controller/ProductController.php
src/Controller/Admin/CategoryCrudController.php
src/Controller/Admin/ProductCrudController.php
templates/product/catalogue.html.twig
templates/product/_catalogue_filters.html.twig
templates/product/_catalogue_results.html.twig
templates/product/_catalogue_product_card.html.twig
public/css/style_mobile.css
tools/assert-j5x-d-catalogue-search-filters.php
```

## Commandes de validation

```powershell
php -l migrations\Version20260629223000.php
php -l src\Entity\Product.php
php -l src\Entity\Category.php
php -l src\Repository\ProductRepository.php
php -l src\Repository\CategoryRepository.php
php -l src\Controller\ProductController.php
php -l src\Controller\Admin\ProductCrudController.php
php -l src\Controller\Admin\CategoryCrudController.php
php -l tools\assert-j5x-d-catalogue-search-filters.php

php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container

php tools/assert-j5x-d-catalogue-search-filters.php
```

## Exemples d’URL

```text
/catalogue
/catalogue?q=jasmin
/catalogue?categorie=fleurs
/catalogue?tri=price_asc
/catalogue?q=jasmin&categorie=fleurs&tri=featured
```

## Points non inclus

- filtre par commune ;
- disponibilité produit par commune ;
- tri livraison la plus proche ;
- pagination avancée ;
- moteur de recherche externe ;
- modification panier/checkout/livraison.
