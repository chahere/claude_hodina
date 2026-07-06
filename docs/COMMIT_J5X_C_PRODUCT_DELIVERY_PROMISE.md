# Commit — J5X-C — Promesse produit / produits sur créneau

## Résumé

Ajout d’une promesse livraison configurable au niveau produit pour distinguer :

- produits standard suivant les passages du secteur client ;
- produits sur créneau / rendez-vous comme broche de jasmin, collier de fleurs, accueil aéroport ou événement.

## Fichiers principaux

```text
migrations/Version20260629163000.php
src/Entity/Product.php
src/Dto/ProductDeliveryPromise.php
src/Service/ProductDeliveryPromiseService.php
src/Controller/Admin/ProductCrudController.php
src/Controller/ProductController.php
templates/product/show.html.twig
public/css/style_mobile.css
tools/assert-j5x-c-product-delivery-promises.php
```

## Points métier

- Commune connue : afficher seulement la promesse pertinente.
- Commune inconnue : afficher un résumé et un tableau repliable.
- Produit sur créneau : afficher jours possibles, plage indicative, cutoff et délai produit éventuel.
- La date finale reste confirmée par Hodina.

## Non modifié

- Formule des frais de livraison.
- J5X-A tarifs.
- J5X-B calendrier secteur.
- J5V-A délai minimum produit.
- Checkout.
- Catalogue recherche/filtres/tri.

## Validation locale attendue

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-c-product-delivery-promises.php
```
