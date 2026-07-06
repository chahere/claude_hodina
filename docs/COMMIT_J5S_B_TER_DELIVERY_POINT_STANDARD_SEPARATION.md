# COMMIT J5S-B-ter — Séparation stricte point de remise / adresse standard

## Objectif

Corriger le panier/checkout après J5S-B-bis afin de ne plus mélanger l’adresse de livraison client et le point de remise Hodina.

Le besoin métier est de distinguer strictement :

- livraison standard : adresse de livraison choisie par le client ;
- point de remise : point Hodina choisi parmi les points autorisés du panier ;
- produit standard + point : le mode choisi par le client détermine la source de vérité.

## Décisions métier

- En mode point de remise, le bloc `Adresse de livraison utilisée` ne doit pas être affiché au client.
- En mode point de remise, le client voit un bloc `Point de remise choisi` avec le point, la commune et le rendez-vous.
- En mode point de remise, le changement d’adresse client est masqué : le client ne doit pas croire qu’il peut choisir une adresse libre.
- En mode point de remise, les frais de livraison sont calculés avec la commune du `DeliveryPoint` choisi.
- En mode livraison standard, les frais restent calculés avec la commune de l’adresse de livraison client.
- En produit `STANDARD_AND_DELIVERY_POINT`, le mode choisi par le client décide : adresse standard ou point de remise.

## Fichiers modifiés

- `src/Controller/CartController.php`
- `templates/cart/index.html.twig`

## Détails techniques

### CartController

- Le preview logistique `/panier/logistique/apercu` reçoit maintenant :
  - `deliveryMethod` ;
  - `deliveryPointId` ;
  - `commune` ;
  - `address`.
- Si `deliveryMethod = DELIVERY_POINT`, le contrôleur :
  - réanalyse le panier ;
  - vérifie que le point demandé appartient aux points disponibles pour le panier ;
  - construit une adresse technique à partir du `DeliveryPoint` ;
  - calcule les frais sur la commune du point.
- Si `deliveryMethod = STANDARD`, le contrôleur conserve le calcul existant basé sur la commune/adresse client.
- Pour un produit à point imposé, le panier utilise aussi le premier point disponible pour le preview initial.

### Template panier

- Ajout d’un bloc `Point de remise choisi` côté client connecté.
- Masquage du bloc adresse standard et du sélecteur d’adresse quand le mode point de remise est actif.
- Le choix d’un point de remise ne remplace plus visuellement l’adresse client.
- Le JavaScript envoie le mode de livraison et le point choisi au recalcul des frais.
- Le bouton de validation n’exige plus l’adresse standard quand le mode point de remise est actif ; il exige alors point/date/heure.

## Tests recommandés

```powershell
php -l src/Controller/CartController.php
php -l src/Controller/CheckoutController.php
php -l src/Service/DeliveryPointCartService.php
php bin/console lint:twig templates/cart/index.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tests navigateur prioritaires

- Produit à point imposé :
  - le bloc adresse standard est masqué ;
  - le bloc point choisi est affiché ;
  - les frais correspondent à la commune du point ;
  - la commande est validée avec point/date/heure.
- Produit standard :
  - l’adresse client reste visible ;
  - les frais restent calculés avec la commune de l’adresse client.
- Produit standard + point :
  - mode standard : adresse client visible et utilisée ;
  - mode point : adresse client masquée, point utilisé pour les frais ;
  - retour au mode standard : l’adresse client redevient la source de vérité.

## Hors périmètre

- Aucun changement e-mail.
- Aucun changement SMS.
- Aucun changement Djama, sauf affichage indirect via snapshots existants.
- Aucun changement des statuts commande.
- Aucun changement des règles de barge.
