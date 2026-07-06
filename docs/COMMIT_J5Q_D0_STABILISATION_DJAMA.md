# COMMIT J5Q-D0 — Stabilisation Djama avant Portail client MVP

Date : 25/06/2026
Statut : patch préparé localement, validation recette à faire.

## Objectif

Stabiliser le portail livreur `/djama` avant de basculer sur le Portail client MVP, sans recréer d’éléments métier déjà existants.

## Analyse préalable

Le code existant contient déjà les briques nécessaires :

- `Customer` avec `ROLE_COURIER` pour les livreurs ;
- `CustomerOrder.assignedCourier`, `courierAssignedAt`, `outForDeliveryAt`, `deliveredAt` ;
- `CustomerOrder.sellerCollectionSnapshot` pour les collectes vendeurs ;
- `Seller.collectionValidationCode` pour le code vendeur permanent ;
- `CustomerOrder.deliveryValidationCodeEncrypted` pour le code réception client ;
- `CourierPayout` et `CourierPayoutLine` pour les rémunérations livreurs.

Décision : ne pas créer de nouvelle entité `Courier`, `SellerCollection`, `DeliveryCode` ou paiement livreur.

## Fichiers modifiés

```text
src/Service/SellerCollectionCodeService.php
src/Service/CustomerOrderWorkflowService.php
src/Controller/Courier/CourierDashboardController.php
templates/courier/dashboard.html.twig
docs/TODO.md
docs/ROADMAP.md
docs/HISTORIQUE.md
docs/DECISIONS.md
docs/COMMIT_J5Q_D0_STABILISATION_DJAMA.md
```

## Changements techniques

### 1. Correction injection e-mail branding vendeur

`SellerCollectionCodeService` utilisait `EmailBrandingService` sans l’injecter. Le service est maintenant injecté dans le constructeur.

Impact : les e-mails de code collecte vendeur peuvent utiliser le branding J5Q-C-2 sans erreur d’exécution.

### 2. Suppression du SMS générique départ livraison

`CustomerOrderWorkflowService::startDelivery()` ne renvoie plus de `SmsLog` et n’envoie plus le SMS :

```text
customer_order_out_for_delivery
```

Le démarrage livraison conserve uniquement :

- passage statut `OUT_FOR_DELIVERY` ;
- horodatage `outForDeliveryAt` ;
- persistance Doctrine ;
- envoi du code réception client via `CustomerDeliveryCodeService`.

Impact : le client ne reçoit plus deux SMS au même moment.

### 3. Alertes terrain Djama

Le contrôleur livreur construit désormais des alertes sans nouvelle table :

Client :

- téléphone manquant ou placeholder ;
- e-mail manquant ou invalide ;
- GPS livraison absent.

Vendeur :

- téléphone manquant ;
- e-mail manquant ;
- aucun contact vendeur ;
- pas de code permanent ;
- code ponctuel impossible si aucun contact ;
- adresse de retrait manquante ;
- GPS retrait manquant ;
- commune retrait différente de la commune logistique.

Le template Djama affiche ces alertes sous forme de badges et ajoute les raccourcis `Appeler vendeur` / `E-mail vendeur` quand les coordonnées existent.

## Contrôles locaux effectués

```text
php -l src/Service/SellerCollectionCodeService.php
php -l src/Service/CustomerOrderWorkflowService.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Service/CustomerDeliveryCodeService.php
php -l src/Service/CustomerOrderNotificationService.php
```

Résultat : OK.

Le zip de sources fourni ne contient pas `bin/console`, donc `lint:twig` et `doctrine:schema:validate` doivent être rejoués dans l’environnement projet complet.

## Tests recette à effectuer

1. Prendre en charge une commande multi-vendeurs.
2. Valider une collecte vendeur avec code permanent.
3. Valider une collecte vendeur avec code ponctuel.
4. Vérifier affichage alertes vendeur/client.
5. Démarrer livraison.
6. Vérifier qu’il n’y a pas de SMS `customer_order_out_for_delivery`.
7. Vérifier que le SMS/e-mail `customer_delivery_code` part bien.
8. Valider livraison avec bon code client.
9. Vérifier `DELIVERED`, `deliveredAt`, rémunération Djama et logs EasyAdmin.

## Suite immédiate

Après validation recette J5Q-D0, reprendre :

```text
Portail client MVP
```
