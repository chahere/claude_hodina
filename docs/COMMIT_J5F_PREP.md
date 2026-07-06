# Commit J5F préparation — Clarification barge PT / GT

## Statut

Documentation préparatoire avant application du patch J5F-A.

## Objectif

Clarifier la règle de barge avant de créer les entités et services logistiques.

## Décision métier

La barge est requise uniquement lorsque la commande traverse Petite-Terre / Grande-Terre.

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

## Ce que cette décision évite

Elle évite de facturer ou d'afficher une contrainte barge pour une livraison interne à Petite-Terre ou interne à Grande-Terre.

Exemples sans barge :

```text
Dzaoudzi → Pamandzi
Dzaoudzi → Labattoir
Mamoudzou → Koungou
Sada → Ouangani
```

Exemples avec barge :

```text
Dzaoudzi → Mamoudzou
Mamoudzou → Dzaoudzi
Pamandzi → Koungou
Sada → Labattoir
```

## Conséquence technique

`DeliveryCommune.territory` devient la donnée centrale pour calculer `requiresBarge`.

Les communes voisines ne calculent pas la barge.

Elles servent uniquement à qualifier le message logistique :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
```

Seul `OTHER_TERRITORY` implique la barge.

## Impact sur J5F-A

Le modèle prévu reste valide :

```text
DeliveryPricingZone
DeliveryCommune
Seller.deliveryCommune
```

Mais les aides EasyAdmin et les futures règles de service doivent être explicites.

## Impact sur J5G

`DeliveryLogisticsService` devra calculer :

```text
requiresBarge = clientTerritory !== sellerTerritory pour au moins un vendeur
```

## Tests à prévoir

```text
Client Dzaoudzi PT + vendeur Pamandzi PT → pas de barge
Client Dzaoudzi PT + vendeur Mamoudzou GT → barge
Client Mamoudzou GT + vendeur Dzaoudzi PT → barge
```

## Commandes Git recommandées

```powershell
git status
git add docs
git commit -m "docs(logistics): clarify PT GT barge rule for J5F"
git push
```


---

# Note postérieure — J5F-A et J5F-B réalisés

La préparation J5F a été suivie par deux livraisons :

```text
J5F-A — entités + migration + CRUD EasyAdmin
J5F-B — service métier DeliveryLogisticsService + DTO CartLogisticsPreview
```

La règle barge préparée dans ce fichier a bien été respectée dans le service :

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Les communes voisines ne déclenchent pas la barge.

---

# Note postérieure — la règle barge reste valable malgré le modèle avancé

La préparation J5F avait verrouillé :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

J5G avancé ajoute un calcul de communes traversées, mais ne change pas cette règle.

Une commune éloignée sur le même territoire peut augmenter les frais via le nombre de communes traversées, mais elle ne déclenche pas la barge.
