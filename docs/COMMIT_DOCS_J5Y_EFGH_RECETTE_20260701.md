# COMMIT docs — J5Y-E/F/G/H validation recette puis production

## Objectif

Mettre à jour la documentation après validation recette puis production du lot public J5Y-E/F/G/H : Découvrir Hodina, Carnet, page livraison, header/footer et illustrations livraison.

## Fichiers de référence mis à jour

- `VISION.md`
- `ARCHITECTURE.md`
- `DECISIONS.md`
- `ENTITIES.md`
- `WORKFLOWS.md`
- `TODO.md`
- `ROADMAP.md`
- `PILOT_STATUS_DETAILED.md`
- `DEPLOIEMENT_PREPROD.md`
- `HISTORIQUE.md`
- `README_MAJ_J5Y_EFGH_RECETTE_20260701.md`

## Décisions actées

- J5Y-E/F/G/H validé recette.
- Tag recette propre : `recette-j5y-carnet-livraison-footer-clean-20260701`.
- Tag avec backup `.bk` supersédé : `recette-j5y-carnet-livraison-footer-20260701`.
- Tag production validé : `prod-j5y-carnet-livraison-footer-20260701`.
- Commit production : `200d84b merge: document j5y recette validation`.
- Production validée le 01/07/2026.
- Le Carnet est activé de manière limitée et pédagogique, pas comme blog généraliste.
- La page livraison reste indicative ; le panier reste source de vérité.

## Commande de commit conseillée

```powershell
git add docs/ARCHITECTURE.md `
  docs/COMMIT_DOCS_J5Y_EFGH_RECETTE_20260701.md `
  docs/COMMIT_J5Y_C_HOMEPAGE_CATALOGUE_DISCOVER.md `
  docs/COMMIT_J5Y_D_TER_FAVICON_TRANSPARENT.md `
  docs/COMMIT_J5Y_E_CLARIFICATION_URL_DECOUVRIR_HODINA.md `
  docs/COMMIT_J5Y_F_PAGE_CARNET_LIVRAISON.md `
  docs/DECISIONS.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/ENTITIES.md `
  docs/HISTORIQUE.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/README_MAJ_DOCS_J5X_J5Y_20260701.md `
  docs/README_MAJ_J5Y_C_HOMEPAGE_CATALOGUE_DISCOVER_20260630.md `
  docs/README_MAJ_J5Y_D_TER_FAVICON_TRANSPARENT_20260630.md `
  docs/README_MAJ_J5Y_EFGH_RECETTE_20260701.md `
  docs/ROADMAP.md `
  docs/TODO.md `
  docs/VISION.md `
  docs/WORKFLOWS.md

git diff --cached --check
git commit -m "docs(j5y): record production validation"
```
