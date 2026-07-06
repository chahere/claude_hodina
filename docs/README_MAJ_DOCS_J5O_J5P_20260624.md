# Mise à jour documentation — 24/06/2026 — clôture J5O/J5P

## Objectif

Mettre les documents de référence Hodina à jour après validation recette :

- J5O-A — code réception client chiffré ;
- J5P-A — notifications client SMS/e-mail sur statuts ;
- J5N — consolidation Djama, collecte vendeur, AJAX, timezone, plafond livreur ;
- hotfix GPS panier ;
- clarification de la roadmap et des anciennes collisions de numérotation J5O/J5P.

## Fichiers mis à jour

```text
docs/ARCHITECTURE.md
docs/DECISIONS.md
docs/DEPLOIEMENT_PREPROD.md
docs/ENTITIES.md
docs/HISTORIQUE.md
docs/PILOT_STATUS_DETAILED.md
docs/ROADMAP.md
docs/TODO.md
docs/VISION.md
docs/WORKFLOWS.md
docs/README_MAJ_J5O_A_CODE_RECEPTION_CLIENT.md
docs/README_MAJ_J5P_A_NOTIFICATIONS_STATUTS_CLIENT.md
docs/COMMIT_J5O_A_CODE_RECEPTION_CLIENT.md
docs/COMMIT_J5P_A_NOTIFICATIONS_STATUTS_CLIENT.md
docs/PROMPT_MAJ_DOCUMENTATION_HODINA.md
```

## Décision importante

Les anciens jalons prévisionnels :

```text
J5O = images automatiques
J5P = suivi financier manuel
```

sont déplacés dans le backlog post-MVP sans numéro définitif, car les lots réels validés sont désormais :

```text
J5O-A = code réception client chiffré
J5P-A = notifications client statuts
```

## Contrôle recommandé

```powershell
git diff --check
git status
git add docs
git commit -m "docs(j5p): close customer delivery code and notifications follow-up"
```
