# README MAJ — J5M-C3-ter — Identité vendeur client et nom de structure optionnel

Date : 2026-06-21

## Objectif

Corriger le formulaire de création/édition vendeur pour respecter la règle métier : un vendeur Hodina est aussi un client/utilisateur.

Le formulaire distingue désormais :

- l'identité de la personne vendeuse : prénom + nom ;
- l'identité commerciale affichée : nom de structure optionnel ;
- l'adresse / point de retrait vendeur ;
- la commune logistique calculée depuis la commune de retrait seedée.

## Règles fonctionnelles

- Le prénom vendeur est obligatoire.
- Le nom vendeur est obligatoire.
- Le nom de structure est optionnel.
- Si un nom de structure est renseigné, il est prioritaire pour les affichages vendeur.
- Si aucun nom de structure n'est renseigné :
  - le portail livreur affiche prénom + nom ;
  - la boutique/catalogue affiche le nom de famille.
- Le compte client vendeur est créé ou rattaché automatiquement depuis l'email.
- Le rôle `ROLE_SELLER` est ajouté au compte client lié.
- La commune de retrait reste issue de `DeliveryCommune` seedée.
- `Seller.deliveryCommune` reste la source de vérité logistique pour le coût, les trajets, la barge et le BFS.

## Modifications techniques

### Seller

Ajout du champ :

```text
businessName nullable
```

Ajout de champs de formulaire non persistés :

```text
sellerFirstName
sellerLastName
```

Ajout de méthodes d'affichage :

```text
getCourierDisplayName()
getPublicDisplayName()
```

### SellerCrudController

Le formulaire vendeur affiche désormais :

```text
Identité du vendeur
- Prénom
- Nom
- Téléphone
- Email

Structure / affichage commercial
- Nom de structure optionnel

Adresse / point de retrait vendeur
- Adresse de retrait
- Complément
- Commune de retrait seedée
- Instructions
- Note terrain
- GPS
```

À la sauvegarde :

- le nom/prénom sont synchronisés vers `Customer` ;
- `Seller.businessName` est stocké si renseigné ;
- `Seller.name` est alimenté par compatibilité : structure si renseignée, sinon nom de famille ;
- `Seller.contactName` contient prénom + nom ;
- le compte client vendeur est créé ou rattaché ;
- l'adresse de retrait est créée/mise à jour ;
- la commune logistique vendeur est recalculée.

### Affichages publics et livreur

- Le catalogue, la fiche produit, le panier et la confirmation utilisent `seller.publicDisplayName`.
- Le portail livreur utilise `seller.courierDisplayName`.

## Migration

Nouvelle migration :

```text
migrations/Version20260621215500.php
```

Elle ajoute :

```text
seller.business_name
```

Elle initialise `business_name` avec l'ancien `seller.name` pour conserver l'affichage existant des vendeurs déjà créés.

## Tests recommandés

```powershell
php -l src/Entity/Seller.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/CheckoutController.php
php -l migrations/Version20260621215500.php

php bin/console lint:twig templates/product/catalogue.html.twig templates/product/show.html.twig templates/cart/index.html.twig templates/checkout/confirmation.html.twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
php bin/console cache:clear
php bin/console cache:warmup
```

## Parcours fonctionnel

1. Créer un vendeur avec prénom + nom + nom de structure.
2. Vérifier la création/rattachement du compte client vendeur.
3. Vérifier que la structure apparaît dans le catalogue et le portail livreur.
4. Créer un vendeur sans nom de structure.
5. Vérifier que le portail livreur affiche prénom + nom.
6. Vérifier que le catalogue affiche seulement le nom de famille.
7. Vérifier que la commune de retrait seedée déduit bien le code postal, `Seller.deliveryCommune` et `Seller.deliveryZone`.
8. Vérifier que le garde-fou logistique reste OK.
