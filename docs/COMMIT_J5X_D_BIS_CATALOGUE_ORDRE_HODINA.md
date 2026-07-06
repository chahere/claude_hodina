# Commit J5X-D-bis — Ordre éditorial Hodina du catalogue

## Objectif

Corriger la logique de tri par défaut du catalogue public afin qu’elle corresponde au pilotage métier attendu depuis EasyAdmin.

## Besoin fonctionnel validé

- Les catégories cochées **Mettre en tête du catalogue** passent devant les catégories non cochées.
- À priorité équivalente, les catégories sont triées par **ordre d’affichage croissant**.
- Dans chaque catégorie, les produits cochés **Mettre en tête de sa catégorie** passent devant les autres produits de cette catégorie.
- À priorité équivalente, les produits sont triés par **ordre d’affichage croissant**.
- À égalité, les produits les plus récents passent devant.
- Le tri client **Mis en avant** est retiré : la mise en avant est une logique back-office, pas une option client.

## Ordre par défaut

```text
category.isFeatured DESC
category.displayOrder ASC
category.name ASC
product.isFeatured DESC
product.displayPriority ASC
product.createdAt DESC
product.name ASC
```

## Tris client conservés

- Nouveautés
- Prix croissant
- Prix décroissant

## Hors périmètre

- Pas de migration.
- Pas de modification du panier.
- Pas de modification des frais de livraison.
- Pas de modification de DeliveryLogisticsService.
- Pas de modification de DeliveryScheduleService.
- Pas de filtre commune ou disponibilité commune.

## Tests recommandés

```powershell
php -l src\Repository\ProductRepository.php
php -l src\Controller\ProductController.php
php -l src\Controller\Admin\CategoryCrudController.php
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5x-d-catalogue-search-filters.php
php bin\console lint:twig templates
php bin\console lint:container
php bin\console doctrine:schema:validate
php tools\assert-j5x-d-catalogue-search-filters.php
```
