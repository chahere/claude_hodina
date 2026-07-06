# Commit J5G-B préparation — Livraison avancée par chemin de communes

## Statut

**Décision métier et technique préparée. Code à venir.**

## Pourquoi ce fichier existe

Après J5G-A, l'aperçu panier fonctionne, mais la tarification reste trop simple.

La décision prise est de calculer les frais de livraison avec :

```text
frais local de la commune client
+ supplément par commune traversée
+ supplément barge aller-retour si PT ↔ GT
```

## Objectif J5G-B

Ajouter dans `DeliveryLogisticsService` la capacité à calculer le chemin le plus court entre :

```text
commune vendeur
commune client
```

## Données utilisées

```text
DeliveryCommune
DeliveryCommune.neighboringCommunes
Seller.deliveryCommune
Address.commune
```

## Algorithme retenu

Pour le pilote :

```text
BFS = Breadth First Search = parcours en largeur
```

Pourquoi :

```text
on cherche le plus petit nombre de communes traversées
chaque saut a le même poids
on ne fait pas encore de GPS ni de distance kilométrique
```

## Méthodes recommandées

À ajouter dans `DeliveryLogisticsService` :

```text
findShortestCommunePath()
countCommuneHops()
calculateHopSurcharge()
```

Le nom exact pourra être adapté, mais la responsabilité doit rester dans le service.

## Règle barge inchangée

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Le chemin de communes ne doit pas déclencher la barge.

## Exemple validé

```text
vendeur = Mamoudzou
client = Labattoir
```

Comme Mamoudzou est en GT et Labattoir en PT :

```text
barge requise
```

Frais cible :

```text
frais local Labattoir
+ barge aller-retour
+ supplément traversée Dzaoudzi → Labattoir
```

## Réglages nécessaires ensuite

J5G-C devra ajouter :

```text
delivery_commune_hop_customer_fee
delivery_commune_hop_courier_payout
delivery_barge_round_trip_customer_fee
delivery_barge_round_trip_courier_payout
```

## Tests minimum à prévoir

```text
même commune → hopCount = 0
commune voisine directe → hopCount = 1
deux communes intermédiaires → hopCount > 1
aucun chemin → warning
PT → PT éloigné → pas de barge
PT → GT → barge
```

## Point pédagogique

Un développeur débutant doit retenir :

```text
territory PT/GT
→ calcule la barge

neighboringCommunes
→ calcule le chemin / nombre de communes traversées

HodinaSetting
→ donne les montants financiers
```


---

# Note postérieure — Source Excel validée avant codage BFS

Avant de coder le BFS, le fichier suivant est validé comme source initiale :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

Décision : ne pas lire ce fichier en runtime. Il doit servir à créer un seed initial, puis les données doivent être modifiables en base via EasyAdmin.

Cette décision ajoute une étape avant le BFS :

```text
J5G-B1 — source validée
J5G-B2 — modèle Doctrine modifiable
J5G-B3 — seed
J5G-B4 — EasyAdmin
J5G-B5 — BFS
```

---

# Mise à jour historique — clarification après J5G-B2 / J5G-B3

Le découpage initial indiquait :

```text
J5G-B4 — EasyAdmin
J5G-B5 — BFS
```

Après réalisation, le CRUD EasyAdmin a été intégré directement à J5G-B2.

Raison : créer un modèle modifiable sans interface admin aurait été moins utile pour le pilote.

Découpage opérationnel retenu désormais :

```text
J5G-B2 — modèle Doctrine modifiable + CRUD EasyAdmin
J5G-B3 — seed initial source validée
J5G-B4 — DeliveryLogisticsService lit les liaisons et calcule le chemin
J5G-C  — réglages financiers avancés
J5G-D  — panier détaillé
J5G-E  — snapshot checkout
```
