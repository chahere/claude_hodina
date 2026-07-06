# README MAJ — J5X-D-bis — Catalogue ordre éditorial Hodina

Date : 2026-06-29

## Résumé

J5X-D-bis ajuste le tri par défaut du catalogue pour respecter le besoin métier réel : Hodina doit pouvoir piloter la vitrine depuis EasyAdmin par catégorie puis par produit.

## Règle métier

Par défaut, le catalogue utilise l’**ordre Hodina** :

```text
1. Catégories mises en tête.
2. Ordre d’affichage catégorie, du plus petit au plus grand.
3. Nom de catégorie, en secours.
4. Produits mis en tête dans leur catégorie.
5. Ordre d’affichage produit, du plus petit au plus grand.
6. Nouveautés, en secours.
```

## Traduction technique

```text
category.isFeatured DESC
category.displayOrder ASC
category.name ASC
product.isFeatured DESC
product.displayPriority ASC
product.createdAt DESC
product.name ASC
```

## Interface catalogue

La liste de tri client ne contient plus “Mis en avant”.

Tris conservés :

```text
- Ordre Hodina, par défaut
- Nouveautés
- Prix croissant
- Prix décroissant
```

## Libellés EasyAdmin clarifiés

Catégories :

```text
Mettre en tête du catalogue
Si coché, cette catégorie passe devant les catégories non cochées dans l’ordre Hodina du catalogue.

Ordre d’affichage catégorie
Plus le chiffre est faible, plus la catégorie remonte. Exemple : 0 avant 10.
```

Produits :

```text
Mettre en tête de sa catégorie
Si coché, ce produit passe devant les produits non cochés de la même catégorie dans l’ordre Hodina.

Ordre d’affichage produit
Plus le chiffre est faible, plus le produit remonte dans sa catégorie. Exemple : 0 avant 10.
```

## Points de vigilance

Quand un client choisit explicitement un tri prix ou nouveautés, son choix doit rester prioritaire. L’ordre Hodina ne doit pas rendre un tri prix trompeur.

## Validation attendue

- Fruits et légumes cochée “Mettre en tête” + ordre 0 remonte devant Fleurs/Jasmin.
- Les produits fruits et légumes sont ordonnés par mise en tête puis ordre d’affichage.
- Le menu de tri n’affiche plus “Mis en avant”.
- Le panier et la livraison ne sont pas modifiés.
