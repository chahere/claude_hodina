# README mise à jour docs — 19/06/2026

## Pourquoi cette mise à jour

Après validation production du tag `j5g-b4-20260618-v11`, les docs de suivi sont mises à jour pour figer l'historique et l'ordre de développement suivant.

## Événements documentés

- Déploiement v11 recette / production.
- AssetMapper obligatoire en prod.
- `public/assets` généré non versionné.
- Miniatures EasyAdmin OK.
- Menu admin mobile repliable.
- Correction du menu Utilisateurs.
- Ajax ajout panier.
- MAILER_DSN réel nécessaire pour recevoir les mails.
- Incident admin classé transitoire pendant MEP.
- Prochaine roadmap : dette technique, J5K, J5L, J5M, paiement plus tard.

## Fichiers enrichis

- `TODO.md`
- `ROADMAP.md`
- `DECISIONS.md`
- `DEPLOIEMENT_PREPROD.md`
- `ARCHITECTURE.md`
- `WORKFLOWS.md`
- `ENTITIES.md`
- `VISION.md`
- `PILOT_STATUS_DETAILED.md`
- `COMMIT_J5G_B4.md`
- `README_MAJ_J5G_B4.md`
- `COMMIT_DEVOPS_DEPLOY_TOOLS.md`
- `README_MAJ_DEPLOIEMENT_TAGS_TOOLS.md`

## Commande Git proposée

```powershell
git add docs
git commit -m "docs(j5g): document v11 production validation and next roadmap"
git push origin main
```
