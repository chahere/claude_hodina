
# README — Mise à jour documentation J5G-B4 production v7

Date : **18/06/2026**

Cette archive met à jour les documents de suivi après :

- correction aperçu images produits EasyAdmin ;
- stabilisation du script de déploiement par tag ;
- protection des uploads runtime ;
- backup DB via `mariadb-dump` ;
- validation recette ;
- déploiement production du tag `j5g-b4-20260618-v7` ;
- ajout du cron Messenger production.

## Nouveaux fichiers

```text
COMMIT_ADMIN_PRODUCT_IMAGE_PREVIEW.md
COMMIT_DEVOPS_DEPLOY_TAG_V7.md
README_MAJ_PROD_J5G_B4_V7.md
README_MAJ_DOCS_J5G_B4_PROD_V7_20260618.md
```

## Fichiers mis à jour

```text
ARCHITECTURE.md
COMMIT_DEVOPS_DEPLOY_TOOLS.md
COMMIT_J5G_B4.md
DECISIONS.md
DEPLOIEMENT_PREPROD.md
ENTITIES.md
PILOT_STATUS_DETAILED.md
README_MAJ_DEPLOIEMENT_TAGS_TOOLS.md
README_MAJ_J5G_B4.md
ROADMAP.md
TODO.md
VISION.md
WORKFLOWS.md
```

## Commande Git recommandée

```powershell
git add docs
git commit -m "docs(j5g): document production deployment by tag v7"
git push origin main
```
