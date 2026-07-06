# COMMIT J5S-B-quater-bis — Masquage points optionnels et affichage unité produit

## Objectif

Corriger deux irritants constatés pendant les tests mobiles du panier et du catalogue :

1. pour un produit qui permet à la fois la livraison standard et le point de remise, masquer les points de remise quand le client choisit `Livraison à mon adresse` ;
2. afficher clairement l’unité de vente des produits côté client : catalogue, fiche produit et panier.

## Périmètre

- Panier : masquage du panneau point de remise si le mode actif est `STANDARD`.
- Panier : réaffichage du panneau point de remise si le mode actif est `DELIVERY_POINT`.
- Produit : ajout d’un libellé métier `Product::getUnitLabel()`.
- Catalogue : affichage de l’unité de vente dans la carte produit et près du prix.
- Fiche produit : affichage de l’unité de vente dans les métadonnées et près du prix.
- Panier : affichage de l’unité près du prix unitaire.

## Hors périmètre

- Aucune migration.
- Aucun changement de calcul de frais.
- Aucun changement e-mail/SMS.
- Aucun changement Djama.
- Aucun changement de stock ou de prix.

## Règles métier

- En mode `Livraison à mon adresse`, les points de remise ne doivent pas être visibles pour ne pas créer de confusion.
- En mode `Point de remise`, les points, horaires, date, heure et instruction de remise doivent être visibles.
- L’unité de vente affichée vient du champ `Product.unit` déjà existant.
- Si l’unité est absente ou inconnue, l’affichage retombe sur `À l’unité`.

## Tests recommandés

```powershell
php -l src/Entity/Product.php
php -l src/Controller/Admin/ProductCrudController.php
php bin/console lint:twig templates/cart/index.html.twig templates/product/catalogue.html.twig templates/product/show.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Tests navigateur :

- produit standard + point : choisir `Livraison à mon adresse` masque les points de remise ;
- produit standard + point : choisir `Point de remise` affiche les points/date/heure ;
- catalogue : l’unité est visible ;
- fiche produit : l’unité est visible ;
- panier : le prix unitaire affiche l’unité.
