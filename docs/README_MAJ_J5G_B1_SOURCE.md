# Mise à jour documentation Hodina — J5G-B1 source communes / voisinage validée

## Objet

Cette mise à jour documente la validation de la source :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

comme source initiale de données pour les communes, points logistiques, codes postaux, territoires et voisinages.

## Décision majeure

Le fichier doit être traduit en base de données modifiable.

```text
Excel validé
→ seed initial
→ base Doctrine
→ EasyAdmin
→ DeliveryLogisticsService
```

## Correction critique

L'idée de table de hashage est conservée uniquement côté PHP, pour le calcul rapide.

En base, le modèle doit rester relationnel :

```text
DeliveryCommune
DeliveryCommuneConnection
```

## Documents mis à jour

```text
ARCHITECTURE.md
DECISIONS.md
DEPLOIEMENT_PREPROD.md
ENTITIES.md
PATCH_GUIDELINES.md
ROADMAP.md
TODO.md
VISION.md
WORKFLOWS.md
COMMIT_J5G_B_PREP.md
```

## Nouveau fichier

```text
COMMIT_J5G_B1_SOURCE.md
```

## Prochaine étape technique

```text
J5G-B2 — créer le modèle Doctrine modifiable
```

Puis :

```text
J5G-B3 — seed initial
J5G-B4 — EasyAdmin
J5G-B5 — BFS
```
