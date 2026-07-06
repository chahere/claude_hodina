---

# Commit J5G-B1 — Source communes / voisinage validée

## Statut

**Décision documentaire réalisée avant patch de code.**

## Source validée

```text
hodina_communes_voisinage_reference_v1.xlsx
```

Cette source complète et corrige le document initial :

```text
Voisinage commune.pdf
```

## Décision principale

Le fichier validé doit être traduit en base de données modifiable.

```text
Excel validé
→ seed initial
→ données Doctrine
→ correction via EasyAdmin
```

## Décision technique

Ne pas utiliser une table de hashage comme stockage principal.

Modèle retenu :

```text
DeliveryCommune
DeliveryCommuneConnection
```

Puis, côté service :

```text
DeliveryLogisticsService construit une hash map temporaire pour le BFS.
```

## Pourquoi cette étape est importante

Le calcul avancé de livraison dépend du voisinage.

Si le voisinage est codé en dur, Hodina sera difficile à corriger sur le terrain.

Si le voisinage est en base et administrable, Hodina pourra évoluer sans redéploiement.

## Points critiques retenus

- Labattoir est un point logistique utile, mais doit être rattaché administrativement à Dzaoudzi si nécessaire.
- Dzaoudzi ↔ Mamoudzou doit être une liaison `BARGE`.
- Dzaoudzi ↔ Labattoir doit être une liaison `LAND`.
- La barge reste liée à PT / GT.
- Les liaisons typées permettront de calculer `landHopCount` et `bargeHopCount`.

## Suite

```text
J5G-B2 — modèle Doctrine
J5G-B3 — seed initial
J5G-B4 — EasyAdmin
J5G-B5 — BFS
```
