# Mise à jour documentation Hodina — J5F-A / J5F-B

## Objet

Ce dossier contient les documents de suivi mis à jour après :

```text
J5F-A — Communes, zones tarifaires et commune logistique vendeur
J5F-B — DeliveryLogisticsService et CartLogisticsPreview
Ajustement header Admin / Livreur
Jeu de test recette J5F-A
```

## Niveau de détail

Les documents sont volontairement détaillés pour qu'un développeur débutant puisse reprendre le projet sans avoir besoin de relire toute la conversation.

Ils expliquent :

- pourquoi les entités ont été créées ;
- comment les migrations ont été diagnostiquées ;
- pourquoi une migration corrective a été ajoutée ;
- pourquoi `schema:update --force` n'a pas été utilisé ;
- pourquoi `active` a échoué en SQL et pourquoi `is_active` est le vrai nom ;
- ce qu'est un DTO ;
- pourquoi `DeliveryLogisticsService` n'est pas encore branché au panier ;
- comment la règle barge PT / GT doit être respectée ;
- comment le serveur recette a été nettoyé.

## Nouveaux fichiers

```text
COMMIT_J5F_A.md
COMMIT_J5F_B.md
```

## Fichiers mis à jour

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
COMMIT_J5D.md
COMMIT_J5E.md
COMMIT_J5F_PREP.md
```

## Règle métier centrale rappelée

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

## État projet après cette mise à jour

```text
J5E validé
J5F-A validé local + recette
J5F-B validé local + recette
header Admin/Livreur ajusté
recette propre côté Git
```

## Prochaine étape

```text
J5G-A — Aperçu logistique panier
```

Objectif : brancher `DeliveryLogisticsService` dans le panier pour afficher un message logistique et les frais estimés, sans encore figer dans `CustomerOrder`.

---

# Note corrective — revue complète J5G avancé

Après J5F-A / J5F-B, une nouvelle revue documentaire a été faite pour éviter que la documentation reste bloquée sur le modèle simple `zone locale / zone barge`.

La suite J5G introduit :

```text
signature vendeur dans le panier
chemin de communes
supplément par commune traversée
barge aller-retour
rémunération livreur progressive
snapshot checkout futur
```

Le détail est documenté dans :

```text
COMMIT_J5G_A.md
COMMIT_J5G_B_PREP.md
README_MAJ_J5G_ADVANCED_DELIVERY.md
```
