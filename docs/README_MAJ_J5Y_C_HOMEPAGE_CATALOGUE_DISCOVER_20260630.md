# README mise à jour — J5Y-C catalogue en homepage

Date : 2026-06-30

## Fichiers modifiés

- `src/Controller/ProductController.php`
- `src/Controller/HomeController.php`
- `templates/base.html.twig`
- `templates/product/catalogue.html.twig`
- `templates/blog/decouvrir_hodina.html.twig`
- `templates/home/index.html.twig`
- `public/css/style_mobile.css`
- `tools/assert-j5y-c-homepage-catalogue-discover.php`

## Routes attendues

- `/` : catalogue public Hodina.
- `/catalogue` : redirection permanente vers `/`.
- `/blog/decouvrir-hodina` : page éditoriale Découvrir Hodina.
- `/blog` : redirection permanente vers `/blog/decouvrir-hodina`.

## Tests recommandés

```bash
php -l src/Controller/ProductController.php
php -l src/Controller/HomeController.php
php -l tools/assert-j5y-c-homepage-catalogue-discover.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-c-homepage-catalogue-discover.php
```

## Tests navigateur

- Ouvrir `/` et vérifier que le catalogue s’affiche.
- Vérifier que recherche, catégories, tri et AJAX catalogue fonctionnent toujours.
- Ouvrir `/catalogue` et vérifier la redirection vers `/`.
- Ouvrir `/blog/decouvrir-hodina` et vérifier le rendu mobile.
- Vérifier les ancres : clients, vendeurs, livreurs, rejoindre le pilote.
- Vérifier que le lien `Découvrir Hodina` du header pointe vers la page éditoriale.

## Point d’attention

Cette mise à jour change la première impression produit : le catalogue devient l’entrée principale. Il faut donc tester ensuite J5X et J5Y sur ce nouveau flux, surtout recherche catalogue, ajout panier AJAX, point de remise et commande.

## Ajustement après test visuel

Le lien `Catalogue` a été retiré du header public après passage du catalogue sur `/`. Le logo renvoie déjà vers le catalogue. Le lien principal conservé dans le header public est `Découvrir Hodina`.

## Note de statut 01/07/2026

Ce document décrit un état intermédiaire du lot. L’état opérationnel courant de J5Y est désormais la validation recette `recette-j5y-carnet-livraison-footer-clean-20260701`. Les routes publiques finales sont `/`, `/decouvrir-hodina`, `/carnet` et `/carnet/livraison`, avec `/blog*` uniquement en redirection legacy.
