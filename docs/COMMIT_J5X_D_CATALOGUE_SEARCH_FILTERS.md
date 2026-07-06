# Commit J5X-D — Catalogue recherche, filtres, tri et priorité admin

Date : 2026-06-29
Branche de développement : `develop`

## Objectif

Rendre le catalogue Hodina plus exploitable avant ouverture publique, sans toucher à la logique livraison J5X-A/B/C.

J5X-D ajoute :

- recherche produit/vendeur/catégorie ;
- filtre par catégorie ;
- tri `Mis en avant`, `Nouveautés`, `Prix croissant`, `Prix décroissant` ;
- priorité d’affichage administrable côté produit ;
- ordre d’affichage administrable côté catégorie ;
- rendu initial SSR Twig avec fallback GET ;
- amélioration AJAX progressive par fragment HTML ;
- garde-fou `tools/assert-j5x-d-catalogue-search-filters.php`.

## Décisions

- Le catalogue reste séparé de la livraison : aucun calcul de frais, calendrier ou disponibilité commune n’est ajouté ici.
- Le tri par prix est fait en PHP après calcul `ProductPricingService`, pour éviter de trier sur l’ancien champ `Product.price`.
- Les catégories et produits inactifs ne doivent pas être visibles dans le catalogue public.
- L’ajout panier AJAX existant est conservé via `data-ajax-cart-form`.
- Le filtre commune, la disponibilité produit par commune et le tri “livraison la plus proche” sont repoussés.

## Tests locaux recommandés

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

## Tests navigateur ciblés

- `/catalogue` sans filtre OK ;
- recherche `jasmin` OK ;
- recherche sans résultat OK ;
- filtre catégorie OK ;
- tri `Mis en avant` OK ;
- tri `Nouveautés` OK ;
- tri `Prix croissant` / `Prix décroissant` OK ;
- catégorie inactive non affichée ;
- produit inactif non affiché ;
- produit mis en avant remonte ;
- priorité admin respectée ;
- ajout panier AJAX toujours OK ;
- fallback GET fonctionnel ;
- mobile lisible.

## Statut

- Local : à valider après application du patch.
- Recette : non déployé.
- Production : non déployé.
