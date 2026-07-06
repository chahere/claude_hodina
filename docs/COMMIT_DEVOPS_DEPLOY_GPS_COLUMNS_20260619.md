# COMMIT - DevOps deploy GPS columns check

Date : 2026-06-19
Type : chore(devops)

## Commit propose

```bash
git commit -m "fix(devops): check real j5k gps database columns"
```

## Fichiers concernes

```text
tools/deploy-hodina-by-tag.sh
docs/README_MAJ_DEVOPS_DEPLOY_GPS_COLUMNS_20260619.md
docs/COMMIT_DEVOPS_DEPLOY_GPS_COLUMNS_20260619.md
docs/README_MAJ_DEPLOIEMENT_TAGS_TOOLS.md
docs/TODO.md
```

## Objectif

Corriger le faux warning du resume final de deploiement J5K : le script controlait `customer_order.delivery_gps_*`, alors que la migration J5K cree `customer_order.delivery_address_gps_*`.

## Verification locale

```bash
bash -n tools/deploy-hodina-by-tag.sh
```

## Verification recette attendue

Au prochain tag deploye avec cette correction, le resume final du script doit confirmer :

```text
[OK] J5K GPS colonnes DB : address + customer_order OK
```

## Regle conservee

Aucun push depuis recette ou production. Les tags et commits sont crees uniquement depuis la dev.
