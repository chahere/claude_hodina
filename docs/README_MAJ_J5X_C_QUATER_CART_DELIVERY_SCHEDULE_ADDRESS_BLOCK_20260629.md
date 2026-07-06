# README MAJ — J5X-C-quater — Affichage planning livraison dans le bloc adresse panier

Date : 2026-06-29

## Résumé

Ce correctif stabilise l'affichage client du planning de livraison dans le panier.

Le backend renvoyait déjà correctement `deliverySchedule` dans `/panier/logistique/apercu`. Le problème était UX/front : le planning était rattaché au bloc total/logistique et pouvait ne pas être visible ou pas mis à jour au bon endroit.

## Choix retenu

Le planning est déplacé dans le bloc `Adresse de livraison utilisée`, car c'est là que le client cherche naturellement l'information de passage.

Message attendu :

```text
Livraison à Petite-Terre
Passages Hodina : lundi et jeudi
Prochain passage possible : jeudi 2 juillet
Commande avant mercredi 1 juillet 10h
La date finale est confirmée par Hodina après vérification des vendeurs.
```

## Produit sur créneau

Si le panier contient un produit configuré en promesse `Sur créneau / rendez-vous`, le panier affiche seulement une note prudente :

```text
Ce panier contient un produit sur créneau.
Hodina confirmera l'heure exacte avec toi après validation.
```

Aucune sélection de créneau n'est ajoutée dans ce lot.

## Périmètre

Inclus :

- `templates/cart/index.html.twig` ;
- `public/css/style_mobile.css` ;
- `src/Controller/CartController.php` pour exposer le signal produit sur créneau ;
- garde-fou `tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php`.

Exclus :

- changement des tarifs ;
- changement de la formule logistique ;
- changement du checkout ;
- recherche/filtres catalogue J5X-D ;
- sélection de créneau client.

## Validation locale

```powershell
php -l src\Controller\CartController.php
php -l tools\assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php tools/assert-j5x-c-product-delivery-promises.php
```
