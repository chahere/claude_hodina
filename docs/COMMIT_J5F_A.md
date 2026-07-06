# Commit J5F-A — Communes livrées, zones tarifaires et commune logistique vendeur

## Statut

**Réalisé, testé localement, poussé, déployé en préproduction et validé fonctionnellement.**

## Branche

```text
pilot/j5-order-delivery-pricing
```

Cette branche est le nouveau nom de l'ancienne branche `pilot/j5b-workflow-service`.

## Objectif métier

J5F-A crée le socle de paramétrage logistique de Hodina.

Avant J5F-A, Hodina avait des zones de livraison et des communes sous forme plus historique / textuelle.

Après J5F-A, Hodina dispose de vraies entités métier pour :

- définir les frais payés par le client ;
- définir la rémunération prévue du livreur ;
- rattacher une commune à un territoire PT / GT ;
- dire quelle zone tarifaire s'applique en local ;
- dire quelle zone tarifaire s'applique si la barge est nécessaire ;
- rattacher un vendeur à une commune logistique ;
- définir les communes voisines.

## Objectif technique

Préparer le futur calcul automatique de livraison sans encore toucher au panier ni au checkout.

Découpage volontaire :

```text
J5F-A = données + EasyAdmin + migrations
J5F-B = service métier logistique
J5G-A = intégration panier
J5G-B = gel checkout dans CustomerOrder
```

## Fichiers principaux

```text
src/Entity/DeliveryPricingZone.php
src/Entity/DeliveryCommune.php
src/Entity/Seller.php
src/Controller/Admin/DeliveryPricingZoneCrudController.php
src/Controller/Admin/DeliveryCommuneCrudController.php
src/Controller/Admin/SellerCrudController.php
src/Controller/Admin/DashboardController.php
migrations/Version20260607170000.php
migrations/Version20260607173000.php
```

## Entité DeliveryPricingZone

Rôle : définir une zone tarifaire de livraison.

Champs :

```text
name
code
customerDeliveryFee
courierPayout
isActive
internalNote
createdAt
updatedAt
```

Méthode calculée :

```text
getDeliveryMargin()
```

Formule :

```text
marge livraison Hodina = customerDeliveryFee - courierPayout
```

Exemple de test :

```text
PT_LOCAL
client paie 6 €
livreur reçoit 5 €
Hodina garde 1 € sur la livraison
```

## Entité DeliveryCommune

Rôle : représenter une commune de livraison / commune logistique.

Champs / relations :

```text
name
territory
localPricingZone
bargePricingZone
neighboringCommunes
isActive
internalNote
createdAt
updatedAt
```

Territoires :

```text
PT = Petite-Terre
GT = Grande-Terre
```

## Seller.deliveryCommune

Le vendeur peut maintenant être rattaché à une commune logistique.

```text
Seller.deliveryCommune -> DeliveryCommune
```

Le champ `Seller.commune` reste présent, mais il est historique.

Règle pour la suite : utiliser `Seller.deliveryCommune` pour les calculs logistiques.

## EasyAdmin

Ajouts :

- menu Logistique ;
- CRUD Zones tarifaires ;
- CRUD Communes livrées ;
- champ Commune logistique dans le CRUD vendeur ;
- aides pour expliquer zone locale / zone barge / commune historique.

## Migration principale

```text
Version20260607170000
```

Rôle : créer les tables et colonnes J5F-A.

## Migration corrective

```text
Version20260607173000
```

Rôle : aligner la base avec le mapping Doctrine final.

Pourquoi ? Après la migration principale, `doctrine:schema:validate` était rouge.

Diagnostic :

```powershell
php bin/console doctrine:schema:update --dump-sql
```

Doctrine demandait :

- renommage d'index ;
- ajout / alignement de contraintes ;
- index sur `seller.delivery_commune_id` ;
- ajustement `created_at` / `updated_at`.

Décision : migration corrective versionnée, pas `schema:update --force`.

## Incident patch corrigé

Le premier patch correctif était corrompu :

```text
error: corrupt patch at ./j5f_a_fix_schema_alignment.patch:189
```

Correction : utiliser `j5f_a_fix_schema_alignment_v2.patch`.

## Tests locaux validés

```powershell
php -l src\Entity\DeliveryPricingZone.php
php -l src\Entity\DeliveryCommune.php
php -l src\Controller\Admin\DeliveryPricingZoneCrudController.php
php -l src\Controller\Admin\DeliveryCommuneCrudController.php
php -l migrations\Version20260607170000.php
php -l migrations\Version20260607173000.php
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:container
```

Résultat : OK.

## Tests EasyAdmin validés

- [x] menu zones tarifaires visible ;
- [x] menu communes livrées visible ;
- [x] création `PT_LOCAL` ;
- [x] création `GT_LOCAL` ;
- [x] création `Dzaoudzi` en PT ;
- [x] création `Labattoir` en PT ;
- [x] création `Mamoudzou` en GT ;
- [x] association zones locale / barge ;
- [x] association communes voisines ;
- [x] association vendeur `ferme houmadi` à `Mamoudzou`.

## Déploiement préproduction

Commandes :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultat final : OK.

## Jeu de test recette

Validé :

```text
GT_LOCAL → 6 € client / 5 € livreur / 1 € marge livraison
PT_LOCAL → 6 € client / 5 € livreur / 1 € marge livraison

Dzaoudzi  → PT → PT_LOCAL / GT_LOCAL
Labattoir → PT → PT_LOCAL / GT_LOCAL
Mamoudzou → GT → GT_LOCAL / PT_LOCAL

Dzaoudzi ↔ Labattoir
ferme houmadi → Mamoudzou → GT
```

## Incident SQL recette

Une commande SQL utilisait `active`, mais la colonne réelle est `is_active`.

Erreur :

```text
Unknown column 'active' in 'INSERT INTO'
```

Correction : vérifier avec `information_schema.COLUMNS`, puis utiliser `is_active`.

## Point pédagogique

Un développeur débutant doit retenir :

```text
Le mapping PHP et le nom SQL réel ne sont pas toujours identiques.
En cas de doute, vérifier information_schema avant d'écrire du SQL manuel.
```

## Suite

```text
J5F-B — DeliveryLogisticsService
```

---

# Note postérieure — J5G avancé enrichit le rôle des communes voisines

J5F-A a créé les communes livrées et les communes voisines.

À l'origine, les communes voisines servaient surtout à qualifier le message :

```text
même commune
commune voisine
commune éloignée
autre territoire
```

Avec J5G avancé, elles deviennent aussi la base du calcul de chemin.

```text
DeliveryCommune.neighboringCommunes
→ graphe de communes
→ BFS
→ nombre de communes traversées
→ supplément livraison
```

Aucune migration n'est nécessaire pour commencer J5G-B si la relation `neighboringCommunes` existante suffit.
