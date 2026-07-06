# README MAJ — J5M-C2 à J5M-C3-ter — Collecte vendeurs, adresse de retrait, identité vendeur

Date : 2026-06-21  
Branche de travail : `main`  
Base propre avant chantier : `j5m-c1-portail-livreur-valide-local-clean`  
Chantier concerné : `J5M — Workflow livreur enrichi / portail livreur terrain`

---

## 1. Résumé exécutif

Cette séquence finalise la partie terrain du portail livreur autour de la collecte vendeur.

Le livreur peut maintenant ouvrir une commande et voir, pour chaque vendeur concerné :

```text
- le nom affiché du vendeur ;
- la commune logistique du vendeur ;
- l’adresse / point de retrait vendeur ;
- le lien GPS si le point de retrait en possède un ;
- les produits et quantités à récupérer chez ce vendeur.
```

Côté backoffice, la création d’un vendeur a été clarifiée :

```text
Un vendeur est aussi un client Hodina.
```

Le formulaire vendeur permet donc maintenant de renseigner :

```text
- prénom obligatoire ;
- nom obligatoire ;
- téléphone ;
- email ;
- nom de structure optionnel ;
- adresse / point de retrait vendeur ;
- commune de retrait issue du seed DeliveryCommune ;
- instructions / notes terrain ;
- GPS éventuel ;
- marge vendeur Hodina éventuelle.
```

À la sauvegarde, Hodina crée ou rattache automatiquement le compte client vendeur, crée ou met à jour l’adresse de retrait, puis déduit la commune logistique et la zone vendeur depuis la commune seedée.

---

## 2. Règle métier finale validée

La règle stabilisée est la suivante :

```text
L’admin renseigne l’adresse de retrait.
Hodina déduit et stocke Seller.deliveryCommune.
DeliveryLogisticsService continue d’utiliser uniquement Seller.deliveryCommune.
```

Cette règle sépare strictement deux notions :

```text
Seller.deliveryCommune
→ source de vérité pour coût, trajet, barge, BFS, zone et snapshot logistique.

Seller.pickupAddress
→ aide terrain pour guider le livreur vers le point réel de collecte.
```

Il ne faut pas remplacer `Seller.deliveryCommune` par `Seller.pickupAddress` dans `DeliveryLogisticsService`.

---

## 3. Historique des rebondissements et corrections

Cette partie est importante pour éviter de refaire les mêmes erreurs.

### 3.1 Première idée rejetée : dupliquer adresse/GPS dans `Seller`

Une première version de J5M-C2 ajoutait directement dans `Seller` :

```text
pickup_address
pickup_gps_latitude
pickup_gps_longitude
pickup_gps_accuracy_meters
```

Cette solution a été rejetée, car le projet possédait déjà une entité `Address` complète avec :

```text
line1
line2
postalCode
commune
notes / instructions
courierNotes
gpsLatitude
gpsLongitude
gpsAccuracyMeters
getGpsMapUrl()
```

Décision finale : ne pas dupliquer. Réutiliser `Address`.

### 3.2 Deuxième correction : lier `Seller` à `Customer` et `Address`

Le vendeur étant aussi un client, la solution retenue est :

```text
Seller.customerAccount → Customer
Seller.pickupAddress   → Address
```

Le point de retrait vendeur est donc une vraie adresse Hodina.

### 3.3 Garde-fou logistique

Risque identifié : un futur développeur pourrait être tenté d’utiliser `pickupAddress.commune` dans `DeliveryLogisticsService` pour calculer les frais.

Décision : ajouter un garde-fou portable Windows / Linux.

Script ajouté :

```text
tools/assert-delivery-logistics-commune-source.php
```

Commande :

```powershell
php tools/assert-delivery-logistics-commune-source.php
```

Résultat attendu :

```text
[J5M-C2][OK] DeliveryLogisticsService reste verrouillé sur Seller::deliveryCommune pour les trajets/coûts/barge/BFS.
```

### 3.4 PowerShell remplacé par PHP

Un premier garde-fou avait été imaginé en PowerShell. Il a été remplacé par un script PHP, car :

```text
- le développement local est sur Windows ;
- la recette et la production sont sur Linux ;
- PHP est disponible partout dans le projet Symfony ;
- un script PHP peut être lancé de manière identique sur tous les environnements.
```

### 3.5 Migration initiale incomplète puis migration corrective

La migration initiale `Version20260621143500` a bien ajouté les colonnes :

```text
seller.customer_account_id
seller.pickup_address_id
```

Mais elle n’a exécuté que 2 requêtes SQL en local, alors que les index et clés étrangères étaient attendus également. La cause probable : les tests d’existence de colonnes étaient évalués avant l’exécution effective des `addSql` précédents.

Une migration corrective a donc été ajoutée :

```text
Version20260621145500
```

Elle ajoute les index et clés étrangères manquants si nécessaire.

Après cette correction :

```text
doctrine:schema:validate → OK
```

### 3.6 Formulaire vendeur : suppression de la sélection d’adresse existante en création

Le sélecteur `Adresse / point de retrait` n’était pas naturel lors de la création d’un vendeur.

Décision : en création vendeur, l’admin saisit directement l’adresse de retrait propre au vendeur.

Hodina crée ensuite automatiquement :

```text
- le compte Customer vendeur ;
- l’Address de retrait ;
- le lien Seller.customerAccount ;
- le lien Seller.pickupAddress.
```

### 3.7 Commune texte libre remplacée par `DeliveryCommune`

Le champ texte `commune` et le champ texte `code postal` dans le formulaire vendeur ont été rejetés, car les communes de retrait sont des communes de Mayotte déjà seedées.

Décision finale :

```text
L’admin choisit une commune de retrait dans la liste DeliveryCommune.
Address.commune et Address.postalCode sont déduits automatiquement.
Seller.deliveryCommune et Seller.deliveryZone sont déduits automatiquement.
```

Cela évite les variantes humaines :

```text
Labattoir / Labatoir / Labattoire
97615 mal saisi
Mamoudzou mal orthographié
```

### 3.8 Identité vendeur : prénom obligatoire + nom de structure optionnel

Retour fonctionnel : un vendeur est un client. Il doit donc avoir :

```text
- prénom obligatoire ;
- nom obligatoire.
```

Le nom de structure est différent de l’identité du vendeur. Il est optionnel.

Règle d’affichage validée :

```text
Si nom de structure renseigné :
- portail livreur : nom de structure ;
- catalogue / boutique : nom de structure.

Si nom de structure vide :
- portail livreur : prénom + nom ;
- catalogue / boutique : nom de famille.
```

---

## 4. Modèle de données final

### 4.1 `Seller`

Champs / relations importants :

```text
Seller.name
→ champ legacy / affichage boutique interne, alimenté automatiquement.

Seller.businessName
→ nom de structure optionnel.

Seller.customerAccount
→ compte Customer lié au vendeur.

Seller.pickupAddress
→ adresse de retrait terrain du vendeur.

Seller.commune
→ champ texte historique conservé pour compatibilité, non saisi manuellement.

Seller.deliveryCommune
→ commune logistique source de vérité calculs.

Seller.deliveryZone
→ zone logistique calculée depuis deliveryCommune.
```

Méthodes utiles :

```text
Seller::getCourierDisplayName()
→ structure si renseignée, sinon prénom + nom.

Seller::getPublicDisplayName()
→ structure si renseignée, sinon nom de famille.

Seller::getEffectivePickupAddress()
→ pickupAddress si renseignée, sinon fallback éventuel vers l’adresse de livraison du compte vendeur.
```

### 4.2 `Address`

L’adresse de retrait vendeur est une `Address` normale.

Elle porte :

```text
line1
line2
postalCode
commune
notes
courierNotes
gpsLatitude
gpsLongitude
gpsAccuracyMeters
```

Elle est créée ou mise à jour depuis le formulaire vendeur.

### 4.3 `DeliveryCommune`

La commune de retrait est choisie via `DeliveryCommune`.

Elle fournit :

```text
name
postalCode
territory
deliveryZone
```

---

## 5. Services et commandes ajoutés

### 5.1 `SellerPickupLogisticsSynchronizer`

Fichier :

```text
src/Service/SellerPickupLogisticsSynchronizer.php
```

Responsabilités :

```text
- créer ou mettre à jour l’adresse de retrait depuis le formulaire vendeur ;
- synchroniser Address.commune depuis DeliveryCommune.name ;
- synchroniser Address.postalCode depuis DeliveryCommune.postalCode ;
- synchroniser Seller.deliveryCommune ;
- synchroniser Seller.deliveryZone ;
- renseigner le champ legacy Seller.commune pour compatibilité ;
- retourner warnings / errors exploités par EasyAdmin ou par la commande CLI.
```

### 5.2 Commande de rattrapage

Fichier :

```text
src/Command/J5mC2SyncSellerPickupCommand.php
```

Commande :

```powershell
php bin/console hodina:j5m:c2:sync-seller-pickup
```

Mode par défaut : simulation.

Commandes utiles :

```powershell
php bin/console hodina:j5m:c2:sync-seller-pickup
php bin/console hodina:j5m:c2:sync-seller-pickup --apply
php bin/console hodina:j5m:c2:sync-seller-pickup --apply --seller-id=12
php bin/console hodina:j5m:c2:sync-seller-pickup --apply --create-missing-pickup-address
```

Résultat obtenu en local :

```text
Vendeurs analysés : 4
0 vendeur(s) modifié(s)
0 adresse(s) créée(s)
0 avertissement(s)
0 erreur(s)
```

---

## 6. Migrations

### 6.1 `Version20260621143500`

Ajoute :

```text
seller.customer_account_id
seller.pickup_address_id
```

### 6.2 `Version20260621145500`

Migration corrective.

Ajoute si nécessaire :

```text
index seller.customer_account_id
index seller.pickup_address_id
foreign key seller.customer_account_id → customer.id
foreign key seller.pickup_address_id → address.id
```

### 6.3 `Version20260621215500`

Ajoute :

```text
seller.business_name
```

Objectif : stocker le nom de structure optionnel sans écraser l’identité du client vendeur.

---

## 7. Workflow final de création vendeur

```text
Admin ouvre EasyAdmin > Vendeurs > Créer
→ renseigne prénom
→ renseigne nom
→ renseigne téléphone
→ renseigne email si disponible
→ renseigne nom de structure si le vendeur a une structure commerciale
→ renseigne l’adresse de retrait
→ choisit une commune de retrait dans DeliveryCommune
→ renseigne instructions / GPS si disponible
→ enregistre
```

À la sauvegarde :

```text
SellerCrudController::prepareSellerBeforeSave()
→ normalise prénom / nom / structure
→ crée ou rattache Customer vendeur via email si possible
→ ajoute ROLE_SELLER
→ crée ou met à jour Address de retrait
→ synchronise Seller.deliveryCommune
→ synchronise Seller.deliveryZone
→ conserve Seller.deliveryCommune comme source logistique
```

---

## 8. Workflow final côté livreur

Dans `/djama`, au dépliage d’une commande :

```text
Client
Adresse client
GPS livraison si renseigné
Commentaire terrain
Collecte vendeurs
Actions
```

Dans `Collecte vendeurs` :

```text
Nom vendeur — Commune logistique
Point de retrait
Lien GPS si disponible
Produits et quantités à récupérer
```

Exemple :

```text
Femmé ALLOUI II — Labattoir
Point de retrait
3 rue Mariam Ali
97615 Labattoir

• Cannes à sucre × 1
```

---

## 9. Tests effectués localement

Tests techniques effectués :

```powershell
php -l src/Entity/Seller.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/CheckoutController.php
php -l src/Service/DeliveryLogisticsService.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php -l src/Command/J5mC2SyncSellerPickupCommand.php
php -l migrations/Version20260621143500.php
php -l migrations/Version20260621145500.php
php -l migrations/Version20260621215500.php
php -l tools/assert-delivery-logistics-commune-source.php
```

Twig :

```powershell
php bin/console lint:twig templates/product/catalogue.html.twig templates/product/show.html.twig templates/cart/index.html.twig templates/checkout/confirmation.html.twig templates/courier/dashboard.html.twig
```

Doctrine :

```powershell
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
```

Garde-fou :

```powershell
php tools/assert-delivery-logistics-commune-source.php
```

Résultat attendu et obtenu :

```text
[J5M-C2][OK] DeliveryLogisticsService reste verrouillé sur Seller::deliveryCommune pour les trajets/coûts/barge/BFS.
```

Tests fonctionnels validés localement :

```text
- création vendeur avec prénom + nom ;
- création/rattachement automatique du compte client vendeur ;
- ajout automatique du rôle ROLE_SELLER ;
- création de l’adresse de retrait propre au vendeur ;
- choix commune de retrait depuis DeliveryCommune ;
- déduction commune logistique et zone ;
- affichage nom structure dans catalogue si renseigné ;
- fallback catalogue sur nom de famille si structure absente ;
- affichage portail livreur avec prénom + nom ou structure selon cas ;
- affichage point de retrait dans Collecte vendeurs ;
- produits et quantités regroupés par vendeur dans le portail livreur.
```

---

## 10. Commandes SQL utiles de vérification

Voir les liens vendeur / compte / point de retrait :

```powershell
php bin/console dbal:run-sql "SELECT s.id, s.name, s.business_name, s.customer_account_id, s.pickup_address_id, s.delivery_commune_id, s.delivery_zone_id FROM seller s ORDER BY s.id;"
```

Voir les communes de retrait :

```powershell
php bin/console dbal:run-sql "SELECT s.id, s.name, s.business_name, a.line1, a.postal_code, a.commune, s.delivery_commune_id FROM seller s LEFT JOIN address a ON a.id = s.pickup_address_id ORDER BY s.id;"
```

---

## 11. Points de vigilance pour la suite

Ne pas faire :

```text
- ne pas utiliser pickupAddress dans DeliveryLogisticsService ;
- ne pas remettre deliveryCommune en saisie manuelle dans le formulaire vendeur ;
- ne pas remettre commune / code postal en champ texte libre ;
- ne pas créer de champs GPS doublons dans Seller ;
- ne pas changer le compte client vendeur à la légère en édition ;
- ne pas exposer au livreur des champs admin inutiles.
```

À faire plus tard seulement :

```text
- checklist persistante de collecte par vendeur ;
- validation produit par produit ;
- preuve photo de collecte ;
- optimisation d’ordre de tournée ;
- portail vendeur complet ;
- gestion autonome du point de retrait par le vendeur.
```

---

## 12. État de validation

```text
J5M-C2      : validé localement après correction migration.
J5M-C2-bis  : validé localement.
J5M-C3      : intégré dans le flux formulaire vendeur.
J5M-C3-bis  : validé localement.
J5M-C3-ter  : validé localement.
Recette     : à faire.
Production  : non déployé.
```
