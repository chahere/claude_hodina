# J5AB — Catalogue mobile orienté achat

Date : 2026-07-03  
Statut : validé localement, validé recette, validé production le 03/07/2026.

## Objectif

Rendre la page catalogue plus orientée achat sur mobile : après le header Hodina, le client voit immédiatement une barre de recherche compacte, un bouton `Filtres`, puis le compteur et les produits.

## Décision produit

La page catalogue n’est plus utilisée comme page institutionnelle. Le contenu pédagogique Hodina reste disponible ailleurs :

- page `/decouvrir-hodina` ;
- footer ;
- Carnet / Infos livraison si utile.

Le haut du catalogue ne doit plus contenir le bloc : `Marketplace locale de Mayotte`, `Produits locaux de Mayotte`, texte explicatif long, CTA `Découvrir Hodina`.

## Périmètre technique

Modifié :

- `templates/product/catalogue.html.twig` ;
- `templates/product/_catalogue_filters.html.twig` ;
- `public/css/style_mobile.css` ;
- `tools/assert-j5ab-catalogue-mobile-buy-first.php` ;
- `tools/assert-j5y-c-homepage-catalogue-discover.php`.

Non modifié :

- routes catalogue ;
- moteur de recherche ;
- repository produit ;
- calcul des frais ;
- panier / checkout ;
- Djama ;
- EasyAdmin ;
- migrations.

## Rendu cible

```text
Header Hodina

[ Rechercher un produit, un vendeur… 🔍 ] [ Filtres ]

si filtres ouverts :
  Catégorie
  Trier
  [Appliquer]
  [Réinitialiser si actif]

X produits trouvés

Produit 1
Produit 2
Produit 10

footer actuel
```

Note : aucune pagination n’a été ajoutée dans J5AB, car elle n’existe pas dans le catalogue actuel. Ce lot ne reconstruit pas le moteur catalogue.

## Comportement JS / fallback

- Avec JS : recherche AJAX progressive, changements de catégorie/tri en AJAX, URL GET conservée via `history.pushState`.
- Sans JS : le formulaire reste en GET, et la loupe reste un vrai bouton `submit`.
- Le panneau `Filtres` est replié au chargement si aucun filtre avancé n’est actif.
- Le panneau reste ouvert au chargement si une catégorie ou un tri est déjà actif.

## Tests locaux recommandés

```bash
php bin/console lint:twig templates/product/catalogue.html.twig templates/product/_catalogue_filters.html.twig templates/product/_catalogue_results.html.twig templates/product/_catalogue_product_card.html.twig
php tools/assert-j5x-d-catalogue-search-filters.php
php tools/assert-j5y-c-homepage-catalogue-discover.php
php tools/assert-j5ab-catalogue-mobile-buy-first.php
```

Contrôles navigateur :

- `/` ;
- `/catalogue` ;
- `/decouvrir-hodina`.

À vérifier manuellement : recherche produit, recherche vendeur, catégorie, tri, paramètres GET, compteur produits, cartes produits, ajout panier, header/footer, rendu mobile.

## Commit conseillé

Ne pas utiliser `git add .`.

```bash
git status --short

git add templates/product/catalogue.html.twig
git add templates/product/_catalogue_filters.html.twig
git add public/css/style_mobile.css
git add tools/assert-j5ab-catalogue-mobile-buy-first.php
git add tools/assert-j5y-c-homepage-catalogue-discover.php
git add docs/README_MAJ_J5AB_CATALOGUE_MOBILE_ORIENTE_ACHAT_20260703.md

git commit -m "feat(j5ab): compact mobile catalogue filters"
```

## Validation recette / production

État final du lot :

```text
Commit : bab469e feat(j5ab): compact mobile catalogue filters
Tag recette : recette-j5ab-catalogue-mobile-achat-20260703
Tag production : prod-j5ab-catalogue-mobile-achat-20260703
Statut : validé local + recette + production
```

Contrôles validés :

- `php bin/console lint:twig` sur les templates catalogue ;
- `php tools/assert-j5x-d-catalogue-search-filters.php` ;
- `php tools/assert-j5y-c-homepage-catalogue-discover.php` ;
- `php tools/assert-j5ab-catalogue-mobile-buy-first.php` ;
- `/` en HTTP 200 ;
- `/catalogue` en redirection 301 vers `/` ;
- `/decouvrir-hodina` en HTTP 200 ;
- validation visuelle mobile : recherche + loupe + `Filtres` sur une ligne, produits rapprochés, footer conservé.

Règle anti-régression : J5AB ne doit pas être rouvert sauf bug bloquant. Toute future amélioration catalogue doit rester séparée du panier, du checkout, de Djama et du calcul livraison.
