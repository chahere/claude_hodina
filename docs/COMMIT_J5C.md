# Commit J5C — Données livraison et préparation dashboard livreur

## Statut

**Réalisé, testé localement, déployé en préproduction et validé fonctionnellement le 06/06/2026.**

## Branche

```text
pilot/j5b-workflow-service
```

La branche conserve le nom J5B, mais contient désormais aussi le lot J5C.

## Objectif

Préparer techniquement le futur dashboard livreur `/livreur` sans encore créer l'interface.

Objectif métier : permettre qu'une commande prête puisse être prise en charge par un livreur.

Objectif technique : ajouter les champs, relations et transitions nécessaires avant de coder le contrôleur livreur.

## Fichiers principaux

```text
config/packages/security.yaml
src/Entity/CustomerOrder.php
src/Controller/Admin/CustomerOrderCrudController.php
src/Service/CustomerOrderWorkflowService.php
migrations/Version20260606101500.php
migrations/Version20260606091936.php
migrations/Version20260606103000.php
```

## Changements réalisés

### Sécurité

Ajout de la protection future :

```text
/livreur → ROLE_COURIER
```

### CustomerOrder

Ajout de :

```text
assignedCourier
courierAssignedAt
outForDeliveryAt
```

Relation retenue :

```text
assignedCourier -> Customer
```

### EasyAdmin

Affichage en détail commande de :

```text
Livreur assigné
Livreur assigné le
Départ livraison le
```

### CustomerOrderWorkflowService

Ajout des méthodes :

```text
canTakeForDelivery()
takeForDelivery()
canMarkDeliveredByCourier()
markDeliveredByCourier()
```

Règles préparées :

- seule une commande prête peut être prise ;
- une commande déjà assignée ne peut pas être reprise ;
- la prise en charge passe en `OUT_FOR_DELIVERY` ;
- la prise en charge crée un SmsLog ;
- seul le livreur assigné peut livrer côté livreur.

## Migrations

### Version20260606101500

Ajoute les champs livraison et la relation `assignedCourier`.

### Version20260606091936

Migration d'alignement d'index générée avec un timestamp trop ancien.

Correction : transformée en no-op pour compatibilité.

### Version20260606103000

Nouvelle migration sûre d'alignement d'index.

Elle vérifie l'existence de l'ancien et du nouvel index avant de renommer.

## Incident documenté

Erreur rencontrée en préproduction :

```text
Key 'idx_3cf0a31e4b1e148f' doesn't exist in table 'customer_order'
```

Cause : migration corrective exécutée avant la migration qui créait l'index.

Correction : no-op + nouvelle migration postérieure conditionnelle.

## Tests locaux

- [x] Patch appliqué avec `git apply`.
- [x] `cache:clear` OK.
- [x] Migration locale OK.
- [x] `doctrine:schema:validate` OK.
- [x] `lint:container` OK.

## Tests préproduction

- [x] `git pull` OK.
- [x] Migrations appliquées jusqu'à `Version20260606103000`.
- [x] `doctrine:schema:validate --env=prod` OK.
- [x] `cache:clear --env=prod` OK.
- [x] `cache:warmup --env=prod` OK.
- [x] Nouveaux champs visibles dans EasyAdmin.
- [x] Tests fonctionnels confirmés.

## État attendu dans EasyAdmin avant dashboard

```text
Livreur assigné      Null
Livreur assigné le   Null
Départ livraison le  Null
```

Ce comportement est normal tant que `/livreur` n'existe pas.

## Commandes utiles

```powershell
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:container
```

Préproduction :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console doctrine:migrations:migrate --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## Suite

Prochaine étape :

```text
J5D — Dashboard livreur /livreur
```

À créer :

- `CourierDashboardController` ;
- `templates/courier/dashboard.html.twig` ;
- affichage des commandes prêtes ;
- prise en charge ;
- livraisons en cours ;
- marquage livré ;
- liens téléphone / SMS client.


---

# Note de continuité J5F / J5G

J5C a ajouté les champs nécessaires pour suivre la livraison par un livreur.

J5F et J5G ajouteront la dimension économique de la livraison :

- frais payés par le client ;
- rémunération prévue du livreur ;
- marge livraison Hodina ;
- barge requise ou non ;
- zone tarifaire appliquée.

Ces informations devront être figées dans `CustomerOrder` au moment du checkout pour que les commandes anciennes restent cohérentes.


---

# Note postérieure — J5E et cohérence des snapshots

J5C a préparé les champs de livraison sur `CustomerOrder`.

J5E ajoute le même principe côté prix produit : conserver les valeurs importantes au moment de la commande.

Pour J5F / J5G, il faudra figer les frais livraison client, la rémunération livreur, la marge livraison Hodina, la zone tarifaire et la barge requise.

---

# Note postérieure — CustomerOrder devra recevoir le snapshot livraison

J5C a ajouté les champs de suivi livreur.

J5G-E devra ajouter ou compléter les champs économiques de livraison sur `CustomerOrder`.

À figer au checkout :

```text
deliveryFee
courierPayout
deliveryMargin
requiresBarge
logisticsHopCount
logisticsPathSummary
bargeCustomerFee
bargeCourierPayout
communeHopCustomerFee
communeHopCourierPayout
```

But : une ancienne commande doit garder les frais calculés le jour où elle a été passée.
