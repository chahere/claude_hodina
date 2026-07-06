# Mise à jour documentation Hodina — clarification J5F barge

## Objet

Cette mise à jour documente une clarification métier importante avant application de J5F-A.

## Règle validée

La barge n'est pas liée à la distance entre communes.

La barge est uniquement liée au changement de territoire logistique :

```text
PT = Petite-Terre
GT = Grande-Terre
```

Règle :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

## Pourquoi cette mise à jour

Avant de créer `DeliveryPricingZone`, `DeliveryCommune` et `Seller.deliveryCommune`, il fallait éviter une mauvaise interprétation :

```text
commune éloignée ≠ barge
commune non voisine ≠ barge
absence de voisinage ≠ barge
```

## Documents mis à jour

```text
DECISIONS.md
WORKFLOWS.md
ARCHITECTURE.md
TODO.md
VISION.md
ROADMAP.md
PATCH_GUIDELINES.md
ENTITIES.md
DEPLOIEMENT_PREPROD.md
COMMIT_J5D.md
COMMIT_J5E.md
```

## Conséquence pour le code futur

`DeliveryLogisticsService` devra appliquer :

```text
requiresBarge = true uniquement si clientTerritory !== sellerTerritory
```

Les communes voisines serviront au message client, pas au déclenchement de la barge.

## Tests futurs minimum

```text
Dzaoudzi PT + Pamandzi PT = pas de barge
Dzaoudzi PT + Mamoudzou GT = barge
Mamoudzou GT + Dzaoudzi PT = barge
```

---

# Note corrective — barge et chemin sont deux notions différentes

La documentation J5G avancée conserve la règle de barge :

```text
PT ↔ GT uniquement
```

Mais elle ajoute un calcul séparé :

```text
nombre de communes traversées
```

Donc :

```text
barge
→ dépend de PT / GT

communes traversées
→ dépend du graphe neighboringCommunes
```
