# README — J5X-B — Calendrier de livraison paramétrable par secteur

Date : 2026-06-29
Branche de développement : `develop`
État : livré pour validation locale ciblée, non validé recette, non validé production.

## Objectif

J5X-B ajoute un calendrier de passage configurable par secteur de livraison, porté par `DeliveryPricingZone`, sans modifier la formule de calcul des frais de livraison.

La promesse affichée au client reste volontairement prudente : Hodina affiche un **prochain passage possible**, pas une livraison garantie. La date finale reste confirmée après vérification des vendeurs pendant le pilote.

## Rappel de la formule de livraison préservée

```text
Frais livraison client =
forfait local DeliveryPricingZone de la commune client
+ coûts de liaison DeliveryCommuneConnection LAND/BARGE
+ éventuel supplément multi-vendeurs plafonné
+ application éventuelle du plafond global.
```

J5X-B ne touche pas `DeliveryLogisticsService::calculateDeliveryAmounts()`.

## Calendriers configurés

| Zone tarifaire | Libellé public | Jours de livraison | Cutoff |
|---|---|---|---|
| `PT_LOCAL` | Petite-Terre | lundi, jeudi | 10h J-1 |
| `MAMOUDZOU_LOCAL` | Mamoudzou | mercredi, samedi | 10h J-1 |
| `SUD_LOCAL` | Grande-Terre Sud | mercredi, samedi | 10h J-1 |
| `NORD_LOCAL` | Grande-Terre Nord | mardi, vendredi | 10h J-1 |
| `CENTRE_LOCAL` | Grande-Terre Centre | mardi, vendredi | 10h J-1 |
| `GT_LOCAL` | Grande-Terre fallback | planning inactif | fallback technique |

Convention technique : `1=lundi`, `2=mardi`, `3=mercredi`, `4=jeudi`, `5=vendredi`, `6=samedi`, `7=dimanche`.

## Fichiers principaux

```text
migrations/Version20260629152000.php
src/Entity/DeliveryPricingZone.php
src/Dto/DeliverySchedulePreview.php
src/Service/DeliveryScheduleService.php
src/Controller/Admin/DeliveryPricingZoneCrudController.php
src/Controller/CartController.php
src/Controller/ProductController.php
templates/cart/index.html.twig
templates/product/show.html.twig
public/css/style_mobile.css
tools/assert-j5x-b-delivery-schedules.php
```

## UX client

Panier standard avec commune connue :

```text
Passages à Petite-Terre : lundi et jeudi.
Prochain passage possible : jeudi 2 juillet
Commande avant mercredi 10h
La date finale est confirmée par Hodina après vérification des vendeurs.
```

Fiche produit : suppression de l’ancien bloc pilote statique `Petite-Terre mardi / Grande-Terre jeudi` et affichage d’une information neutre + tableau des secteurs.

## EasyAdmin

`DeliveryPricingZoneCrudController` expose :

```text
Libellé public
Description publique
Jours de livraison
Heure limite de commande
Jours avant passage
Planning actif
```

`GT_LOCAL` reste actif comme zone tarifaire fallback si nécessaire, mais son planning public est inactif.

## Tests locaux ciblés

```powershell
php -l src\Entity\DeliveryPricingZone.php
php -l src\Dto\DeliverySchedulePreview.php
php -l src\Service\DeliveryScheduleService.php
php -l src\Controller\Admin\DeliveryPricingZoneCrudController.php
php -l src\Controller\CartController.php
php -l src\Controller\ProductController.php
php -l migrations\Version20260629152000.php
php -l tools\assert-j5x-b-delivery-schedules.php

php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-b-delivery-schedules.php
```

Tests navigateur minimum :

```text
EasyAdmin > Zones tarifaires : vérifier jours + cutoff.
Panier Labattoir : afficher Petite-Terre lundi/jeudi.
Panier Mamoudzou : afficher Mamoudzou mercredi/samedi.
Changer d’adresse : frais J5X-A conservés + planning rafraîchi en AJAX.
Fiche produit : ancien texte PT mardi / GT jeudi absent.
```

## Hors périmètre

```text
Produits sur créneau : J5X-C.
Recherche/filtres/tri catalogue : J5X-D.
Disponibilité produit par commune : futur J5Y-A.
DeliveryArea : repoussé.
```
