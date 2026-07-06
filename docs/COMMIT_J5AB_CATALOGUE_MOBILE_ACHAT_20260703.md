# COMMIT — J5AB catalogue mobile orienté achat

Date : 2026-07-03

## Commit

```text
bab469e feat(j5ab): compact mobile catalogue filters
```

## Tags

```text
recette-j5ab-catalogue-mobile-achat-20260703
prod-j5ab-catalogue-mobile-achat-20260703
```

## Résumé

Le catalogue public `/` devient plus orienté achat sur mobile : recherche + loupe + `Filtres` sur une ligne, panneau catégorie/tri repliable, compteur produits rapproché et suppression du bloc institutionnel haut.

## Fichiers principaux

- `templates/product/catalogue.html.twig`
- `templates/product/_catalogue_filters.html.twig`
- `public/css/style_mobile.css`
- `tools/assert-j5ab-catalogue-mobile-buy-first.php`
- `tools/assert-j5y-c-homepage-catalogue-discover.php`
- `docs/README_MAJ_J5AB_CATALOGUE_MOBILE_ORIENTE_ACHAT_20260703.md`

## Hors périmètre

- moteur catalogue ;
- routes ;
- livraison ;
- panier / checkout ;
- Djama ;
- EasyAdmin ;
- pagination.

## Validation

Statut : validé local, recette et production.

Contrôles : lint Twig catalogue, asserts J5X-D/J5Y/J5AB, HTTP `/`, `/catalogue`, `/decouvrir-hodina`, validation mobile.
