# COMMIT J5S-B — Panier/checkout avec choix point de remise

Date : 26/06/2026
Statut : patch préparé localement, à valider localement puis en recette.

## Objectif

Activer côté client la brique `DeliveryPoint` validée dans J5S-A.

Le client doit pouvoir commander selon le mode configuré sur les produits :

- livraison standard uniquement ;
- point de remise imposé uniquement ;
- livraison standard ou point de remise.

Cas initial : colliers de fleurs remis à l’accueil de la barge de Petite-Terre ou à l’accueil passager de l’aéroport de Pamandzi.

## Périmètre livré

Inclus :

- détection des produits à point de remise dans le panier ;
- choix du mode de remise quand le produit autorise livraison standard + point de remise ;
- obligation de choisir un point quand le produit impose un point de remise ;
- choix d’un `DeliveryPoint` parmi les points autorisés ;
- choix d’une plage horaire `DeliveryPointTimeWindow` active ;
- champ libre client pour préciser l’arrivée, le vol, la barge ou un repère ;
- validation serveur au checkout ;
- utilisation de la commune logistique du point pour le calcul de livraison ;
- snapshot du point/plage/instruction dans `CustomerOrder` ;
- affichage en confirmation commande ;
- affichage dans le détail portail client ;
- affichage dans l’admin commande ;
- affichage dans la fiche opérationnelle admin ;
- affichage dans Djama pour le livreur.

Exclus :

- modification client du point/plage après commande ;
- capacité maximale par créneau ;
- calendrier avec exceptions ou jours indisponibles ;
- tarification spéciale par point ;
- découpage d’une commande en plusieurs livraisons ;
- paiement en ligne.

## Règles métier

### Produit standard

Si tous les produits du panier sont en `STANDARD`, le flux panier/checkout existant reste inchangé.

### Point de remise imposé

Si au moins un produit du panier est en `DELIVERY_POINT_REQUIRED`, le client doit choisir un point de remise et une plage horaire.

Le checkout refuse la validation si :

- aucun point n’est choisi ;
- aucune plage n’est choisie ;
- le point choisi n’est pas autorisé pour les produits contraints ;
- la plage choisie n’appartient pas au point ;
- aucune intersection de points autorisés n’existe entre plusieurs produits imposés.

### Livraison standard + point de remise

Si le panier contient uniquement des produits `STANDARD` ou `DELIVERY_POINT_OPTIONAL`, le client peut conserver la livraison standard ou choisir un point de remise.

### Panier mixte

Si le panier contient un produit imposant un point de remise, toute la commande suit le point choisi.

Cette règle évite de créer plusieurs livraisons dans une seule commande dans le MVP.

## Données snapshotées sur CustomerOrder

J5S-B ajoute un snapshot complet du point de remise choisi :

- `deliveryPoint` ;
- nom, code, type ;
- adresse du point ;
- commune affichée ;
- consignes publiques ;
- consignes livreur ;
- GPS ;
- libellé de plage ;
- jour/plage horaire ;
- instruction libre client.

Règle : une ancienne commande ne doit pas changer d’affichage si le point de remise est modifié plus tard en admin.

## Fichiers principaux

- `src/Service/DeliveryPointCartService.php` ;
- `src/Form/CheckoutType.php` ;
- `src/Controller/CartController.php` ;
- `src/Controller/CheckoutController.php` ;
- `src/Entity/CustomerOrder.php` ;
- `migrations/Version20260626013000.php` ;
- `templates/cart/index.html.twig` ;
- `templates/checkout/confirmation.html.twig` ;
- `templates/client/orders/show.html.twig` ;
- `templates/courier/dashboard.html.twig` ;
- `templates/admin/customer_order/operational_sheet.html.twig` ;
- `src/Controller/Admin/CustomerOrderCrudController.php` ;
- `src/Controller/Courier/CourierDashboardController.php` ;
- `public/css/style_mobile.css`.

## Anti-régression

Ne pas casser :

- panier standard ;
- checkout standard ;
- adresses client ;
- GPS client ;
- calcul livraison PT/GT ;
- snapshots d’adresse existants ;
- admin commande ;
- portail client J5R-A ;
- Djama ;
- SMS/e-mails ;
- annulation client.

## Tests techniques

```bash
php -l src/Service/DeliveryPointCartService.php
php -l src/Form/CheckoutType.php
php -l src/Entity/CustomerOrder.php
php -l src/Controller/CartController.php
php -l src/Controller/CheckoutController.php
php -l src/Controller/Admin/CustomerOrderCrudController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l migrations/Version20260626013000.php

php bin/console lint:twig templates/cart/index.html.twig templates/checkout/confirmation.html.twig templates/client/orders/show.html.twig templates/courier/dashboard.html.twig templates/admin/customer_order/operational_sheet.html.twig
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tests navigateur

- Produit standard seul : panier/checkout inchangés.
- Produit `DELIVERY_POINT_REQUIRED` : point + plage obligatoires.
- Produit `DELIVERY_POINT_OPTIONAL` : livraison standard possible et point de remise possible.
- Panier mixte standard + point imposé : toute la commande suit le point choisi.
- Conflit de points imposés : validation bloquée.
- Instruction client saisie : visible confirmation, admin, Djama et portail client.
- GPS point : lien carte visible si coordonnées renseignées.
