# Commit J5G-B4 préparation — DeliveryLogisticsService branché sur la carte Doctrine

## Statut

**À faire. Préparation documentaire réalisée après validation J5G-B3.**

## Objectif

Faire évoluer `DeliveryLogisticsService` pour qu'il n'utilise plus seulement l'ancien voisinage simple `DeliveryCommune.neighboringCommunes`, mais la nouvelle carte modifiable :

```text
DeliveryCommuneConnection
```

## Pourquoi

J5G-B3 a posé la carte en base. J5G-B4 doit apprendre au service à lire cette carte.

Image simple :

```text
J5G-B3 = la carte est imprimée
J5G-B4 = le service apprend à lire la carte
```

## Responsabilités J5G-B4

- charger les communes actives ;
- charger les liaisons actives ;
- construire une hash map en mémoire ;
- respecter `isBidirectional` ;
- trouver le plus court chemin par BFS ;
- compter les hops `LAND` ;
- compter les hops `BARGE` ;
- exposer un résumé exploitable dans `CartLogisticsPreview` ;
- conserver les warnings pour les cas incomplets.

## Hors périmètre J5G-B4

```text
pas de nouveaux champs CustomerOrder
pas de snapshot checkout
pas de politique tarifaire définitive
pas de portail vendeur
pas de portail livreur
```

## Cas de test minimaux

```text
Mamoudzou → Labattoir
→ chemin attendu : Mamoudzou → Dzaoudzi → Labattoir
→ BARGE = 1
→ LAND = 1

Dzaoudzi → Labattoir
→ chemin attendu : Dzaoudzi → Labattoir
→ BARGE = 0
→ LAND = 1

Mamoudzou → Koungou
→ chemin attendu : Mamoudzou → Koungou
→ BARGE = 0
→ LAND = 1

Mamoudzou → Kani-Kéli
→ chemin multi-hop GT
→ BARGE = 0
→ LAND > 1
```

## Règle métier à ne pas casser

La barge reste liée au territoire :

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Le chemin typé permet d'expliquer le trajet, mais il ne doit pas inverser cette règle sans décision métier explicite.

## Stratégie de patch recommandée

Patch court :

```text
src/Service/DeliveryLogisticsService.php
src/Dto/CartLogisticsPreview.php si nécessaire
templates/cart/index.html.twig si affichage minimal nécessaire
```

Ne pas mélanger avec une migration sauf nécessité réelle.

---

# Note de clôture — J5G-B4 livré

Date : **17/06/2026**  
Merge : `10ff512 merge(j5g): integrate BFS delivery logistics rules`

Le jalon préparé dans ce document est maintenant livré dans la branche principale pilote.

Les éléments initialement prévus ont été complétés par des règles nécessaires découvertes pendant les tests :

```text
plafond global des frais client
supplément multicommunes de collecte
correction du comptage par communes distinctes
barge affichée dans le chemin
snapshot logistique commande
page admin Logistique
```

Le document de référence final est :

```text
COMMIT_J5G_B4.md
README_MAJ_J5G_B4.md
```
