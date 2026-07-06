# README mise à jour J5G-B4 — Calcul logistique BFS, plafonds et snapshot

Date : **17/06/2026**  
Merge final : `10ff512 merge(j5g): integrate BFS delivery logistics rules`  
Branche source : `pilot/j5g-b4-bfs-link-costs`  
Branche cible : `pilot/j5j-commerce-mode-role-tester`

---

## Pourquoi cette mise à jour existe

Après la validation production de J5G-E1 → J5G-E2-bis-A, Hodina avait une base saine :

```text
commune livrée = source de vérité
code postal prérempli
zone déduite côté serveur
frais recalculés en AJAX
total verrouillé avant validation
panier = écran contractuel pendant le paiement manuel
```

J5G-B4 ajoute la partie logistique avancée sans revenir sur cette base.

Objectif : calculer un prix de livraison plus réaliste à partir des communes de collecte et de livraison, tout en gardant une tarification compréhensible pour le client.

---

## Règle métier finale

```text
Frais client = forfait local de la commune livrée
             + coût du trajet de collecte le plus contraignant
             + supplément multicommunes de collecte plafonné

Puis : application éventuelle du plafond global des frais client.
```

Le système ne fait pas :

```text
livraison vendeur A + livraison vendeur B + livraison vendeur C
```

Ce choix évite de rendre les paniers multivendeurs invendables.

---

## Paramètres globaux Hodina à vérifier

Dans EasyAdmin > Réglages Hodina :

```text
global_commune_crossing_customer_fee
global_commune_crossing_courier_payout
global_delivery_customer_fee_cap
global_multi_seller_extra_customer_fee
global_multi_seller_extra_customer_fee_cap
```

Valeurs pilotes conseillées pendant les tests :

```text
global_commune_crossing_customer_fee = 3
global_commune_crossing_courier_payout = 3
global_delivery_customer_fee_cap = 40
global_multi_seller_extra_customer_fee = 2
global_multi_seller_extra_customer_fee_cap = 4
```

Ne pas saisir le symbole `€` dans les valeurs. Préférer `3`, `3.00`, `40` ou `40.00`.

---

## Exemple de calcul validé

Panier livré à Mamoudzou :

```text
Commune livrée : Mamoudzou (GT)
Forfait local GT : 15 €
Communes de collecte : Dzaoudzi, Labattoir, Mtsamboro
Supplément commune traversée : 3 €
Barge : 8 €
Supplément commune de collecte supplémentaire : 2 €, plafonné à 4 €
```

Trajets affichés :

```text
Dzaoudzi — Ferme combo : Dzaoudzi -barge-> Mamoudzou
Labattoir — ferme Abdallah : Labattoir → Dzaoudzi -barge-> Mamoudzou
Mtsamboro — Ferme allaoui : Mtsamboro → Bandraboua → Koungou → Mamoudzou
```

Le trajet le plus contraignant est :

```text
Labattoir → Dzaoudzi -barge-> Mamoudzou
```

Calcul :

```text
15 € forfait local GT
+ 3 € liaison LAND Labattoir → Dzaoudzi
+ 8 € barge Dzaoudzi → Mamoudzou
+ 4 € supplément multicommunes de collecte
= 30 €
```

Plafond global `40 €` non appliqué.

---

## Snapshot logistique

Les nouvelles commandes contiennent un snapshot JSON :

```text
CustomerOrder.deliveryLogisticsSnapshot
```

Il permet de conserver :

```text
commune livrée
territoire
vendeurs
communes de collecte
trajets
forfait local
coût trajet
supplément multicommunes
plafond
frais final client
payout livreur estimé
marge livraison
lignes produits
```

Cette donnée est essentielle pour analyser plus tard les commandes avec ChatGPT ou avec un export SQL.

---

## Page admin Logistique

Depuis EasyAdmin > Commandes, une action `Logistique` permet de consulter le détail.

La page affiche :

```text
Résumé logistique
Calcul des frais client
Estimation livreur / marge Hodina
Détail des trajets de collecte
Données brutes conservées pour analyse
```

Pour une commande créée avant J5G-B4, le snapshot peut être absent. Dans ce cas, le service peut recalculer dynamiquement avec les paramètres actuels, mais ce recalcul ne représente pas forcément le prix historique vu par le client.

---

## Checklist recette / production

Avant déploiement :

```text
Backup DB
Vérifier la branche pilot/j5j-commerce-mode-role-tester
Vérifier que le commit 10ff512 est présent
```

Déploiement :

```powershell
git pull origin pilot/j5j-commerce-mode-role-tester
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:migrations:status --env=prod
```

Tests fonctionnels :

```text
1 vendeur / 1 commune sans barge
1 vendeur / 1 commune avec barge
2 vendeurs même commune
2 communes de collecte
3 communes de collecte
trajet dépassant 40 €
commande créée
confirmation commande
admin logistique
```

---

## Remise à zéro des commandes avant passage snapshot

Pour repartir proprement en recette ou production, après backup :

```powershell
php bin/console doctrine:query:sql "DELETE FROM sms_log WHERE customer_order_id IS NOT NULL;"
php bin/console doctrine:query:sql "DELETE FROM email_log WHERE customer_order_id IS NOT NULL;"
php bin/console doctrine:query:sql "DELETE FROM order_item;"
php bin/console doctrine:query:sql "DELETE FROM customer_order;"
```

Ne pas supprimer les produits, clients, vendeurs, communes, zones ni réglages.

---

## Point de vigilance

J5G-B4 améliore le calcul logistique, mais ne remplace pas une vraie optimisation de tournée.

Ce qui est livré :

```text
commune vendeur → commune livrée
trajet le plus contraignant
supplément multicommunes
snapshot et analyse
```

Ce qui reste futur :

```text
optimisation de tournée livreur
ordre réel de collecte entre plusieurs vendeurs
multi-livreurs
créneaux logistiques avancés
```

## Complément 18/06/2026 — scripts de MEP versionnés

Après validation recette J5G-B4, les scripts opérationnels suivants sont ajoutés au dépôt :

```text
tools/deploy-hodina-by-tag.sh
tools/reset-commandes-hodina.ps1
```

Ils documentent et automatisent la MEP par tag Git issu de `main`, la protection des fichiers d'environnement locaux, les migrations, le cache, le cron Messenger et le nettoyage optionnel des anciennes commandes.

Voir aussi :

```text
docs/README_MAJ_DEPLOIEMENT_TAGS_TOOLS.md
docs/COMMIT_DEVOPS_DEPLOY_TOOLS.md
```

## Mise à jour 18/06/2026 — tag production v7

J5G-B4 est maintenant en production via :

```text
j5g-b4-20260618-v7
```

À vérifier après MEP :

- panier simple sans barge ;
- panier avec barge ;
- panier multi-communes vendeurs ;
- validation commande ;
- Admin > Commandes > Logistique ;
- snapshot logistique ;
- images produits uploadées ;
- aperçu image dans édition produit ;
- cron Messenger prod après 1 à 2 minutes.

Le script de déploiement v7 est documenté dans :

```text
docs/COMMIT_DEVOPS_DEPLOY_TAG_V7.md
docs/README_MAJ_PROD_J5G_B4_V7.md
```

## Mise à jour 19/06/2026 — tag final v11

Tag final validé :

```text
j5g-b4-20260618-v11
```

Ce tag contient :

- calcul logistique J5G-B4 ;
- snapshot admin ;
- déploiement par tag stabilisé ;
- `asset-map:compile` automatique ;
- `public/assets` généré et autorisé ;
- miniatures EasyAdmin ;
- menu admin repliable ;
- correction du cas `Utilisateurs` ;
- Ajax ajout produit au panier ;
- validation e-mail commande réel après configuration SMTP.

Checklist finale validée :

- [x] Recette OK.
- [x] Production OK.
- [x] Admin mobile OK.
- [x] Catalogue + fiche produit Ajax OK.
- [x] Miniatures produit OK.
- [x] E-mail commande reçu.
- [x] Pas de plantage admin hors MEP.

Prochaine étape : dette technique courte puis J5K GPS livraison.
