# COMMIT J5G-B4 — BFS, coûts logistiques, plafonds et snapshot admin

Date : **17/06/2026**  
Branche de développement : `pilot/j5g-b4-bfs-link-costs`  
Branche consolidée pilote : `pilot/j5j-commerce-mode-role-tester`  
Merge final : `10ff512 merge(j5g): integrate BFS delivery logistics rules`

---

## Résumé exécutif

J5G-B4 clôture la reprise du calcul logistique avancé après la mise en production de J5G-E1 → J5G-E2-bis-A.

Le jalon ajoute le calcul du plus court chemin entre communes à partir de la carte existante `DeliveryCommuneConnection`, applique les coûts de traversées terrestres, conserve la règle de barge PT ↔ GT, plafonne les frais client, ajoute un supplément pour paniers avec plusieurs communes de collecte, et fige le détail logistique dans la commande pour analyse future.

Principe central : **ne pas recréer la logistique existante**.

```text
DeliveryCommune = commune livrée / commune logistique source
DeliveryCommuneConnection = graphe des liaisons LAND / BARGE
DeliveryLogisticsService = service unique de calcul
CustomerOrder.deliveryLogisticsSnapshot = trace figée du calcul au moment de la commande
```

---

## Historique Git

Branche créée depuis la branche principale pilote validée :

```powershell
git checkout pilot/j5j-commerce-mode-role-tester
git pull
git checkout -b pilot/j5g-b4-bfs-link-costs
```

Commits / repères connus :

```text
3bbbb71 feat(j5g): add BFS delivery caps and multi-seller fee rules
ada7fcc feat(j5g): persist and display delivery logistics calculation snapshot
c87d4a4 chore(products): add test product image for delivery validation
10ff512 merge(j5g): integrate BFS delivery logistics rules
```

La branche `pilot/j5g-b4-bfs-link-costs` a été poussée sur GitHub, puis fusionnée en ligne de commande dans `pilot/j5j-commerce-mode-role-tester` :

```powershell
git checkout pilot/j5j-commerce-mode-role-tester
git pull origin pilot/j5j-commerce-mode-role-tester
git merge --no-ff pilot/j5g-b4-bfs-link-costs -m "merge(j5g): integrate BFS delivery logistics rules"
git commit --amend
git push origin pilot/j5j-commerce-mode-role-tester
```

Le message final du merge contient la description fonctionnelle :

```text
Ajout du calcul logistique J5G-B4 :
- chemin BFS entre communes ;
- coûts de traversée LAND ;
- coût barge intégré au trajet ;
- plafond global des frais client ;
- supplément multicommunes vendeurs ;
- affichage panier plus clair ;
- snapshot logistique sur commande ;
- page admin Logistique pour analyse.
```

---

## Périmètre livré

### Calcul du plus court chemin

- Lecture des liaisons actives `DeliveryCommuneConnection`.
- Construction d'une carte en mémoire.
- Prise en compte des liaisons bidirectionnelles.
- Calcul du chemin par BFS entre commune de collecte vendeur et commune livrée.
- Détection des hops `LAND` et `BARGE`.
- Affichage de la barge directement dans le chemin : `Dzaoudzi -barge-> Mamoudzou`.

### Coûts LAND

Pour chaque liaison terrestre `LAND` du trajet retenu :

```text
si DeliveryCommuneConnection.customerExtraFee est renseigné
→ utiliser ce coût spécifique

si customerExtraFee est null / vide
→ utiliser global_commune_crossing_customer_fee

si customerExtraFee vaut 0
→ forcer 0 €, sans fallback global
```

Même logique côté estimation livreur avec :

```text
DeliveryCommuneConnection.courierExtraPayout
global_commune_crossing_courier_payout
```

### Coût BARGE

La barge reste une règle métier territoriale :

```text
PT → GT = barge requise
GT → PT = barge requise
PT → PT = pas de barge
GT → GT = pas de barge
```

Le coût de barge est porté par la liaison `BARGE` correspondante. Le coût global de traversée de commune ne s'applique pas à la barge.

### Trajet vendeur le plus contraignant

En panier multivendeur / multicommunes, Hodina ne somme pas tous les trajets vendeurs.

Règle pilote retenue :

```text
Frais client = tarif local de la commune livrée
+ coût du trajet de collecte le plus contraignant
+ supplément multicommunes vendeurs plafonné
puis plafonnement global des frais client
```

Cette règle évite de facturer plusieurs livraisons complètes au client tout en tenant compte de la complexité logistique.

### Supplément multicommunes vendeurs

Le supplément ne compte pas les articles. Il ne compte pas non plus les vendeurs bruts.

Il compte les **communes de collecte distinctes**.

```text
Plusieurs articles du même vendeur
→ 1 commune de collecte

Plusieurs vendeurs dans la même commune
→ 1 commune de collecte

Vendeurs dans 2 communes différentes
→ 2 communes de collecte distinctes
→ 1 supplément

Vendeurs dans 3 communes différentes
→ 3 communes de collecte distinctes
→ 2 suppléments, puis plafond du supplément
```

Formule :

```text
supplément multicommunes = max(0, nombreCommunesCollecteDistinctes - 1)
                           × global_multi_seller_extra_customer_fee
```

Puis :

```text
si global_multi_seller_extra_customer_fee_cap > 0
→ supplément multicommunes plafonné à ce montant
```

### Plafond global frais client

Après calcul complet, les frais client peuvent être plafonnés :

```text
si global_delivery_customer_fee_cap > 0
et fraisClientAvantPlafond > global_delivery_customer_fee_cap
→ fraisClientFinal = global_delivery_customer_fee_cap
```

Le plafond protège la conversion client. Il ne plafonne pas automatiquement l'estimation livreur.

### Snapshot logistique commande

Un champ JSON est ajouté sur `CustomerOrder` :

```text
deliveryLogisticsSnapshot
```

Il fige les données de calcul au moment de la commande.

Objectif : pouvoir analyser une commande plus tard même si les réglages, communes, liaisons ou tarifs ont changé.

---

## Formule finale J5G-B4

### Client

```text
fraisClientAvantPlafond = forfaitLocalCommuneLivrée
                         + coûtTrajetCollecteLePlusContraignant
                         + supplémentMulticommunesCollecte

fraisClientFinal = min(fraisClientAvantPlafond, plafondGlobalClient) si plafond > 0
```

### Trajet avec barge

Exemple :

```text
Mtsamboro → Bandraboua → Koungou → Mamoudzou → Dzaoudzi → Pamandzi
```

Si la commune livrée est `Pamandzi`, le calcul est :

```text
tarif local PT
+ coût LAND Mtsamboro → Bandraboua
+ coût LAND Bandraboua → Koungou
+ coût LAND Koungou → Mamoudzou
+ coût BARGE Mamoudzou → Dzaoudzi
+ coût LAND Dzaoudzi → Pamandzi
+ supplément multicommunes si plusieurs communes de collecte
puis plafond global frais client si nécessaire
```

### Livreurs / marge

L'estimation livreur reste séparée du prix client.

```text
payoutLivreurEstimé = payoutLocalCommuneLivrée
                    + suppléments trajet livreur
```

La marge livraison affichée côté admin est :

```text
margeLivraison = fraisClientFinal - payoutLivreurEstimé
```

---

## Settings globaux ajoutés

### `global_commune_crossing_customer_fee`

Coût client global par traversée terrestre `LAND` lorsque la liaison n'a pas de coût spécifique.

Exemple : `3`.

### `global_commune_crossing_courier_payout`

Supplément livreur global par traversée terrestre `LAND` lorsque la liaison n'a pas de payout spécifique.

Exemple : `3`.

### `global_delivery_customer_fee_cap`

Plafond global des frais de livraison facturés au client.

Exemple pilote : `40`.

Valeur `0` ou vide : pas de plafond.

### `global_multi_seller_extra_customer_fee`

Supplément client par commune de collecte supplémentaire.

Exemple : `2`.

### `global_multi_seller_extra_customer_fee_cap`

Plafond du supplément multicommunes vendeurs.

Exemple : `4`.

Valeur `0` ou vide : pas de plafond spécifique du supplément.

---

## Affichage panier validé

Le bloc logistique du panier affiche maintenant une synthèse plus pertinente :

```text
Nombre de vendeurs : 4
Communes de collecte : 3 distincte(s)
Liaisons : 3 terrestre(s) et 1 barge
Frais livraison estimés : 30,00 €
```

Le détail des trajets de collecte affiche la barge au bon endroit :

```text
Dzaoudzi — Ferme combo : Dzaoudzi -barge-> Mamoudzou
Labattoir — ferme Abdallah : Labattoir → Dzaoudzi -barge-> Mamoudzou
Mtsamboro — Ferme allaoui : Mtsamboro → Bandraboua → Koungou → Mamoudzou
```

---

## Affichage admin validé

Une page admin `Logistique` est disponible depuis les commandes.

Elle permet de voir :

```text
Résumé logistique
Calcul des frais client
Estimation livreur / marge Hodina
Détail des trajets de collecte
Données brutes conservées pour analyse
```

Cette page utilise le snapshot s'il existe. Pour les anciennes commandes sans snapshot, elle peut recalculer dynamiquement avec les paramètres actuels.

---

## Fichiers principaux modifiés

```text
migrations/Version20260617143000.php
migrations/Version20260617150000.php
migrations/Version20260617153000.php
migrations/Version20260617162000.php
src/Controller/Admin/CustomerOrderCrudController.php
src/Controller/Admin/DeliveryCommuneConnectionCrudController.php
src/Controller/CheckoutController.php
src/Dto/CartLogisticsPreview.php
src/Entity/CustomerOrder.php
src/Entity/DeliveryCommuneConnection.php
src/Entity/HodinaSetting.php
src/Service/DeliveryLogisticsService.php
templates/admin/customer_order/logistics_details.html.twig
templates/cart/index.html.twig
public/uploads/products/20260617-f32aa8457768a72907c7e9749943148f8a031465.png
```

---

## Migrations ajoutées

```text
Version20260617143000
Version20260617150000
Version20260617153000
Version20260617162000
```

Observations pendant les tests locaux : certaines migrations de settings peuvent afficher `0 SQL queries` si les réglages existent déjà. Ce n'est pas bloquant si `doctrine:migrations:status` indique ensuite `New = 0` et si le schema est synchronisé.

---

## Tests techniques validés localement

Commandes validées pendant le jalon :

```powershell
php -l src/Service/DeliveryLogisticsService.php
php -l src/Entity/HodinaSetting.php
php -l src/Entity/DeliveryCommuneConnection.php
php -l src/Controller/Admin/DeliveryCommuneConnectionCrudController.php
php -l src/Entity/CustomerOrder.php
php -l src/Controller/CheckoutController.php
php -l src/Controller/Admin/CustomerOrderCrudController.php
php -l src/Dto/CartLogisticsPreview.php
php bin/console cache:clear
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
```

Résultat après merge :

```text
cache:clear : OK
doctrine:schema:validate : OK
migrations:status : Current = Version20260617162000, New = 0
```

---

## Tests métier validés

### Sans barge

Exemple :

```text
Mtsamboro → Bandraboua → Koungou → Mamoudzou
```

Avec :

```text
forfait local GT Mamoudzou = 15 €
3 liaisons LAND × 3 € = 9 €
```

Résultat attendu :

```text
frais livraison = 24 €
```

### Avec barge

Exemple :

```text
Dzaoudzi → Mamoudzou
```

Avec :

```text
forfait local GT Mamoudzou = 15 €
barge = 8 €
```

Résultat attendu :

```text
frais livraison = 23 €
```

### Multicommunes avec trajet le plus contraignant

Exemple :

```text
Dzaoudzi → Mamoudzou
Labattoir → Dzaoudzi → Mamoudzou
Mtsamboro → Bandraboua → Koungou → Mamoudzou
```

Le trajet le plus contraignant est celui avec la barge + 1 terrestre :

```text
Labattoir → Dzaoudzi -barge-> Mamoudzou
```

Calcul observé :

```text
15 € forfait local GT
+ 11 € trajet le plus contraignant
+ 4 € supplément communes de collecte
= 30 € avant plafond
```

Avec plafond global `40 €`, le plafond ne s'applique pas.

### Snapshot admin

Validation observée côté admin :

```text
Frais client : 30 €
Payout livreur estimé : 19 €
Marge livraison Hodina : 11 €
```

Le détail logistique est visible dans la page admin `Logistique`.

---

## Décisions à retenir

- Le panier multivendeur ne facture pas une livraison complète par vendeur.
- Le supplément est basé sur les communes de collecte distinctes, pas sur le nombre d'articles.
- Le coût global de traversée s'applique aux hops `LAND`, y compris avant et après une barge.
- Le coût `BARGE` reste spécifique à la liaison barge.
- Le plafond global s'applique après tous les suppléments client.
- L'estimation livreur n'est pas plafonnée par le plafond client.
- Le snapshot logistique est obligatoire pour les nouvelles commandes analysables.
- Les anciennes commandes sans snapshot peuvent être supprimées en recette/prod si l'objectif est de repartir sur une base propre.

---

## Commandes de nettoyage commandes avant bascule snapshot

À utiliser avec prudence, après backup, si l'on veut repartir sans anciennes commandes non snapshotées :

```powershell
php bin/console doctrine:query:sql "DELETE FROM sms_log WHERE customer_order_id IS NOT NULL;"
php bin/console doctrine:query:sql "DELETE FROM email_log WHERE customer_order_id IS NOT NULL;"
php bin/console doctrine:query:sql "DELETE FROM order_item;"
php bin/console doctrine:query:sql "DELETE FROM customer_order;"
```

Cette opération ne supprime pas les clients, vendeurs, produits, communes, zones ni settings Hodina.

---

## Déploiement recommandé recette / production

```powershell
git checkout pilot/j5j-commerce-mode-role-tester
git pull origin pilot/j5j-commerce-mode-role-tester
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:migrations:status --env=prod
```

Tests à refaire après déploiement :

```text
Panier simple sans barge
Panier avec barge
Panier avec plusieurs articles du même vendeur
Panier avec plusieurs vendeurs dans la même commune
Panier avec plusieurs communes de collecte
Panier dépassant le plafond global
Création commande
Page confirmation
Admin > Commandes > Logistique
```

## Complément DevOps du 18/06/2026

Les tests recette J5G-B4 étant validés, la préparation production introduit un outillage DevOps versionné :

```text
tools/deploy-hodina-by-tag.sh
tools/reset-commandes-hodina.ps1
```

Le déploiement cible se fait désormais par tag issu de `main`. Le script Bash contrôle le tag, le remote SSH, les fichiers env locaux, les migrations, le cache, Doctrine et le cron Messenger. Le script PowerShell nettoie les anciennes commandes en local/dev avec `dbal:run-sql`.

## Mise à jour 18/06/2026 — Production via tag v7

Après le merge fonctionnel `10ff512`, J5G-B4 a été consolidé dans `main`, puis livré par tags successifs jusqu'à la version de production :

```text
j5g-b4-20260618-v7
```

Le tag v7 contient le jalon fonctionnel J5G-B4 et l'outillage de MEP stabilisé.

Production validée :

```text
Projet : /home/vopu3712/hodina.fr
Avant MEP : 36cc357
Déployé : a888a90
Base : vopu3712_hodina_db
Migrations : 29 → 33
Latest : Version20260617162000
Backup DB : OK via /bin/mariadb-dump
Cron Messenger prod : ajouté
Doctrine schema : OK
```

Correctifs intégrés après le merge initial :

- aperçu stable des images produit EasyAdmin ;
- cache prod no-warmup + warmup ;
- protection uploads runtime ;
- backup DB via mariadb-dump ;
- cohérence base dumpée / Doctrine ;
- résolution des binaires au début du script.

## Complément 19/06/2026 — v11 validée en production

La séquence J5G-B4 a continué après le tag v7 pour stabiliser l'exploitation réelle.

Tags successifs :

```text
v7  : prod initiale robuste
v8  : compilation AssetMapper automatique
v9  : public/assets accepté comme dossier généré
v10 : Ajax ajout panier
v11 : correctif menu Utilisateurs et validation finale
```

Le tag final à retenir est :

```text
j5g-b4-20260618-v11
```

Validation fonctionnelle :

- miniatures produit EasyAdmin OK ;
- admin mobile OK ;
- Ajax panier OK ;
- e-mail commande reçu ;
- admin stable après test hors MEP ;
- schema Doctrine OK ;
- cron Messenger actif.

La suite ne doit plus enrichir J5G-B4 sauf correction. Le prochain jalon métier est J5K GPS livraison.
