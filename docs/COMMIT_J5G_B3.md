# Commit J5G-B3 — Seed initial communes et liaisons logistiques

## Statut

**Terminé, testé localement, commité, déployé sur recette et validé.**

## Objectif

Insérer en base les données initiales issues de la source validée :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

Le seed prépare la carte logistique utilisée plus tard par `DeliveryLogisticsService`.

## Migration

```text
migrations/Version20260607220000.php
```

## Données insérées

### Points logistiques

18 points logistiques :

```text
Dzaoudzi
Labattoir
Pamandzi
Acoua
Bandraboua
Bandrélé
Bouéni
Chiconi
Chirongui
Dembéni
Kani-Kéli
Koungou
M'Tsangamouji
Mamoudzou
Mtsamboro
Ouangani
Sada
Tsingoni
```

### Labattoir

Décision confirmée : Labattoir est un point logistique Hodina, rattaché administrativement à Dzaoudzi.

```text
slug = labattoir
postalCode = 97615
inseeCode = null
parentInseeCode = 97608
territory = PT
isLogisticsPoint = true
```

### Liaisons

23 liaisons :

```text
22 LAND
1 BARGE
```

Liaison barge :

```text
Dzaoudzi → Mamoudzou
linkType = BARGE
isBidirectional = true
hopCount = 1
```

Exemples de liaisons terrestres :

```text
Dzaoudzi → Labattoir
Dzaoudzi → Pamandzi
Labattoir → Pamandzi
Mamoudzou → Koungou
Mamoudzou → Dembéni
Mamoudzou → Ouangani
```

## Validation locale

Contrôles réalisés :

```text
migration présente
migration exécutée
schema:validate OK
lint:container OK
requêtes SQL de contrôle OK
```

## Validation recette

Contrôles réalisés :

```text
git pull OK
migration Version20260607220000 exécutée
schema:validate OK
cache clear OK
cache warmup OK
lint:container OK
requêtes SQL métier OK
git status propre
```

## Point critique

En recette, Doctrine a affiché :

```text
Migration ... was executed but did not result in any SQL statements.
```

Ce message n'a pas été considéré suffisant pour conclure. Les données ont été vérifiées directement dans :

```text
delivery_commune
delivery_commune_connection
```

Résultat : les données étaient bien présentes.

## Ce que J5G-B3 ne fait pas

```text
pas de BFS
pas de calcul de prix avancé
pas d'utilisation de customerExtraFee/courierExtraPayout
pas de modification panier
pas de snapshot commande
```

## Suite

```text
J5G-B4 — brancher DeliveryLogisticsService sur DeliveryCommuneConnection
```
