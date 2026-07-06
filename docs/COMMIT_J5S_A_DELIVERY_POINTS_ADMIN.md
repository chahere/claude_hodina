# COMMIT J5S-A — Socle DeliveryPoint / points de remise admin

Date : 25/06/2026
Statut : patch préparé localement, à valider localement puis en recette.

## Objectif

Créer une brique logistique réutilisable pour les produits qui ne doivent pas être livrés à une adresse libre du client, mais uniquement dans un ensemble de points de remise précis.

Cas initial visé : vente de colliers de fleurs sur Hodina avec remise possible uniquement :

- à l’accueil de la barge de Petite-Terre ;
- à l’accueil passager de l’aéroport de Pamandzi.

La brique doit rester générique pour pouvoir gérer ensuite : relais pickup, points vendeur, points événementiels, autres points fixes validés par Hodina.

## Périmètre livré dans J5S-A

J5S-A est volontairement un lot **socle admin**.

Inclus :

- entité `DeliveryPoint` ;
- entité `DeliveryPointTimeWindow` ;
- entité `ProductDeliveryPoint` ;
- champ `Product.deliveryMode` ;
- mode produit `STANDARD` ;
- mode produit `DELIVERY_POINT_REQUIRED` ;
- mode produit `DELIVERY_POINT_OPTIONAL` ;
- CRUD EasyAdmin `DeliveryPointCrudController` ;
- CRUD EasyAdmin `DeliveryPointTimeWindowCrudController` ;
- CRUD EasyAdmin `ProductDeliveryPointCrudController` ;
- menu EasyAdmin : `Logistique > Points de remise` ;
- menu EasyAdmin : `Logistique > Plages points de remise` ;
- menu EasyAdmin : `Catalogue > Produits ↔ points de remise` ;
- seed initial des deux points : barge Petite-Terre et aéroport Pamandzi ;
- seed initial de plages génériques : matin 08h-12h, après-midi 14h-18h.

Exclus de J5S-A :

- choix du point par le client dans le panier ;
- validation checkout des points imposés ;
- snapshot du point sur `CustomerOrder` ;
- affichage du point dans Djama ;
- affichage du point dans le portail client ;
- modification du point/plage par le client ;
- capacité maximale par créneau ;
- calendrier avec exceptions, jours fériés ou indisponibilités.

Ces sujets sont repoussés aux lots J5S-B, J5S-C et J5S-D.

## Règles métier introduites

Un produit peut avoir trois modes de livraison :

```text
STANDARD
DELIVERY_POINT_REQUIRED
DELIVERY_POINT_OPTIONAL
```

- `STANDARD` conserve le comportement actuel : adresse client, commune livrée, GPS, calcul livraison existant.
- `DELIVERY_POINT_REQUIRED` indique que le produit devra être remis dans un point Hodina autorisé.
- `DELIVERY_POINT_OPTIONAL` indique que le produit pourra être livré en adresse classique ou remis dans un point Hodina autorisé. Dans J5S-A, ces modes sont seulement paramétrables en admin ; l’application client n’applique pas encore cette contrainte.

Un point de remise est une adresse Hodina/logistique, pas une adresse client.

Un point de remise est rattaché à une `DeliveryCommune`, qui reste la source logistique cohérente avec les règles PT/GT existantes.

## Entités

### DeliveryPoint

Représente un lieu fixe de remise : barge, aéroport, relais pickup, point vendeur, point événementiel.

Champs principaux :

- `name` ;
- `code` unique ;
- `type` ;
- `isActive` ;
- adresse : `line1`, `line2`, `postalCode`, `communeName` ;
- `deliveryCommune` ;
- consigne client ;
- consigne livreur ;
- GPS optionnel ;
- ordre d’affichage.

### DeliveryPointTimeWindow

Représente une plage horaire disponible sur un point de remise.

Champs principaux :

- `deliveryPoint` ;
- `label` ;
- `weekday`, nullable pour “Tous les jours” ;
- `startTime` ;
- `endTime` ;
- `isActive` ;
- `sortOrder`.

### ProductDeliveryPoint

Associe un produit à un point de remise autorisé.

Règle : une paire produit + point est unique.

## Migration

Migration ajoutée :

```text
migrations/Version20260625214500.php
```

Elle crée :

- `delivery_point` ;
- `delivery_point_time_window` ;
- `product_delivery_point` ;
- `product.delivery_mode`.

Elle initialise :

- `BARGE_PETITE_TERRE` ;
- `AEROPORT_PAMANDZI_PASSAGERS` ;
- plages `Matin` et `Après-midi` pour chaque point.

## Anti-régression

J5S-A ne doit pas modifier :

- le panier ;
- le checkout ;
- les adresses client ;
- le GPS client ;
- le calcul de livraison ;
- Djama ;
- le portail client J5R-A ;
- les SMS/e-mails ;
- les statuts commande.

Le mode `DELIVERY_POINT_REQUIRED` n’est pas encore appliqué côté client dans J5S-A.

## Tests techniques à rejouer

```bash
php -l src/Entity/DeliveryPoint.php
php -l src/Entity/DeliveryPointTimeWindow.php
php -l src/Entity/ProductDeliveryPoint.php
php -l src/Repository/DeliveryPointRepository.php
php -l src/Repository/DeliveryPointTimeWindowRepository.php
php -l src/Repository/ProductDeliveryPointRepository.php
php -l src/Controller/Admin/DeliveryPointCrudController.php
php -l src/Controller/Admin/DeliveryPointTimeWindowCrudController.php
php -l src/Controller/Admin/ProductDeliveryPointCrudController.php
php -l src/Controller/Admin/ProductCrudController.php
php -l src/Controller/Admin/DashboardController.php
php -l migrations/Version20260625214500.php

php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console cache:clear
php bin/console cache:warmup
```

Sous Windows si le cache bloque ou manque de mémoire :

```powershell
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tests navigateur admin

- Ouvrir EasyAdmin.
- Vérifier `Logistique > Points de remise`.
- Vérifier présence des points seedés : barge Petite-Terre et aéroport Pamandzi.
- Vérifier `Logistique > Plages points de remise`.
- Vérifier les plages matin / après-midi.
- Modifier un point de remise sans erreur.
- Créer un nouveau point relais pickup de test.
- Ouvrir `Catalogue > Produits` et vérifier le champ `Mode de livraison`.
- Mettre un produit en `Point de remise imposé`.
- Ouvrir `Catalogue > Produits ↔ points de remise`.
- Associer le produit au point barge et/ou aéroport.

## Suite prévue

- J5S-B : activation panier/checkout des points imposés.
- J5S-C : affichage admin commande, Djama et portail client.
- J5S-D : modification client du point/plage avant préparation.


## J5S-A-quater — Ajustement produit multi-options

Correction ajoutée avant validation :

- un produit peut être associé à plusieurs points de remise depuis le formulaire Produit ;
- un produit peut être en livraison standard uniquement ;
- un produit peut imposer uniquement un point de remise ;
- un produit peut laisser le choix entre livraison standard dans une commune livrable et point de remise ;
- l’ajout rapide de points depuis un produit standard bascule automatiquement le produit en `DELIVERY_POINT_OPTIONAL`, pas en point imposé obligatoire.

Aucune migration supplémentaire : `product.delivery_mode` est déjà un `VARCHAR(40)` compatible.
