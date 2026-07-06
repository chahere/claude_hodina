# COMMIT — J5M-C2 à J5M-C3-ter — Collecte vendeurs et identité vendeur

Message conseillé :

```text
feat(j5m): add seller pickup address and logistics commune sync
```

## Contenu fonctionnel

- Ajout du point de retrait vendeur réutilisant `Address`.
- Ajout du lien `Seller.customerAccount` vers `Customer`.
- Ajout du lien `Seller.pickupAddress` vers `Address`.
- Ajout du nom de structure optionnel `Seller.businessName`.
- Création/rattachement automatique du compte client vendeur depuis le formulaire vendeur.
- Ajout automatique du rôle `ROLE_SELLER` au compte vendeur.
- Création / mise à jour de l’adresse de retrait depuis le formulaire vendeur.
- Commune de retrait choisie via `DeliveryCommune`, pas en texte libre.
- Code postal déduit automatiquement depuis `DeliveryCommune`.
- Synchronisation automatique de `Seller.deliveryCommune` et `Seller.deliveryZone`.
- Portail livreur enrichi avec bloc `Collecte vendeurs` : point de retrait, GPS, produits et quantités.
- Catalogue utilisant `Seller::getPublicDisplayName()`.
- Portail livreur utilisant `Seller::getCourierDisplayName()`.

## Contenu technique

Fichiers principaux :

```text
src/Entity/Seller.php
src/Controller/Admin/SellerCrudController.php
src/Controller/Courier/CourierDashboardController.php
src/Controller/CheckoutController.php
src/Service/SellerPickupLogisticsSynchronizer.php
src/Command/J5mC2SyncSellerPickupCommand.php
tools/assert-delivery-logistics-commune-source.php
templates/product/catalogue.html.twig
templates/product/show.html.twig
templates/cart/index.html.twig
templates/checkout/confirmation.html.twig
templates/courier/dashboard.html.twig
migrations/Version20260621143500.php
migrations/Version20260621145500.php
migrations/Version20260621215500.php
```

## Migrations

```text
Version20260621143500
→ seller.customer_account_id
→ seller.pickup_address_id

Version20260621145500
→ migration corrective index/FK pour customer_account_id et pickup_address_id

Version20260621215500
→ seller.business_name
```

## Historique à retenir

- Ne pas dupliquer adresse/GPS dans `Seller`.
- Réutiliser `Address`.
- Ne pas faire calculer les frais depuis `pickupAddress`.
- Garder `Seller.deliveryCommune` comme source de vérité logistique.
- Utiliser un garde-fou PHP portable, pas PowerShell.
- Choisir la commune de retrait depuis `DeliveryCommune`, pas depuis un champ texte.
- Un vendeur est aussi un client : prénom et nom obligatoires.
- Le nom de structure est optionnel et sert d’affichage commercial.

## Tests réalisés

```powershell
php -l src/Entity/Seller.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/CheckoutController.php
php -l src/Service/DeliveryLogisticsService.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php -l src/Command/J5mC2SyncSellerPickupCommand.php
php -l tools/assert-delivery-logistics-commune-source.php
php bin/console lint:twig templates/product/catalogue.html.twig templates/product/show.html.twig templates/cart/index.html.twig templates/checkout/confirmation.html.twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
php bin/console cache:clear
php bin/console cache:warmup
```

## Tests fonctionnels validés localement

- Création vendeur avec prénom, nom, téléphone, email.
- Création/rattachement automatique du compte client vendeur.
- Création de l’adresse de retrait vendeur.
- Choix commune de retrait depuis seed `DeliveryCommune`.
- Synchronisation commune logistique et zone.
- Affichage catalogue correct avec structure ou fallback nom.
- Affichage portail livreur correct avec structure ou fallback prénom + nom.
- Bloc collecte vendeurs correct dans `/livreur`.

## Validation

```text
Local : OK
Recette : à faire
Production : non déployé
```
