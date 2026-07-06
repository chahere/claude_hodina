# COMMIT DOCS — J5T-C pause checkout invité compte existant — 2026-06-28

## Objet

Documenter l’état de reprise J5T-C avant pause : checkout invité avec e-mail déjà connu, popup de confirmation, rattachement au compte existant, mention dans `ORDER_CREATED`, et points de vigilance après les bugs observés.

## Fichiers mis à jour

- `ARCHITECTURE.md`
- `DECISIONS.md`
- `ENTITIES.md`
- `WORKFLOWS.md`
- `TODO.md`
- `ROADMAP.md`
- `PILOT_STATUS_DETAILED.md`
- `DEPLOIEMENT_PREPROD.md`
- `HISTORIQUE.md`
- `COMMIT_J5T_C_CHECKOUT_EXISTING_ACCOUNT.md`
- `README_MAJ_J5T_C_CHECKOUT_EXISTING_ACCOUNT_20260628.md`

## Statut documenté

- État historique au moment de la pause : J5T-C code présent localement, validation finale à reprendre.
- État supersédé le 28/06/2026 : J5T-C validé localement puis recette sous le tag `recette-j5t-c-checkout-existing-account-20260628`.
- Production : non faite.
- Aucun changement de schéma.

## Commande de commit documentaire suggérée

```powershell
git add docs/ARCHITECTURE.md `
  docs/DECISIONS.md `
  docs/ENTITIES.md `
  docs/WORKFLOWS.md `
  docs/TODO.md `
  docs/ROADMAP.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/HISTORIQUE.md `
  docs/COMMIT_J5T_C_CHECKOUT_EXISTING_ACCOUNT.md `
  docs/README_MAJ_J5T_C_CHECKOUT_EXISTING_ACCOUNT_20260628.md `
  docs/COMMIT_DOCS_J5T_C_PAUSE_20260628.md

git commit -m "docs(j5t-c): document existing account checkout pause"
```
