# Commit docs — J5Q-C-2 / debug recette — 25/06/2026

## Objet

Documenter l'état réel après validation recette technique de `J5Q-C-2 — Branding e-mail paramétrable` et ajouter la procédure de debug recette.

## Changements documentaires

- Marquage de J5Q-C, J5Q-C-1 et J5Q-C-2 comme validés recette techniquement.
- Clarification des validations restantes pour les e-mails réels SMTP.
- Ajout de la décision de ne pas rollback J5Q-C-2 sans preuve applicative.
- Ajout du guide `DEBUG_RECETTE_HODINA.md`.
- Clarification du comportement de logs o2switch : `public/error_log`, `php://stderr`, access logs live.
- Correction de l'interprétation SQL/logs : colonne `value`, pas `setting_value`; `200 500` n'est pas un HTTP 500.
- Clarification du backlog : J5Q-D / portail client MVP / J5R-A non démarrés.

## Fichiers concernés

- `docs/VISION.md`
- `docs/ARCHITECTURE.md`
- `docs/DECISIONS.md`
- `docs/ENTITIES.md`
- `docs/WORKFLOWS.md`
- `docs/TODO.md`
- `docs/ROADMAP.md`
- `docs/PILOT_STATUS_DETAILED.md`
- `docs/DEPLOIEMENT_PREPROD.md`
- `docs/HISTORIQUE.md`
- `docs/README_MAJ_J5Q_C2_BRANDING_EMAIL.md`
- `docs/COMMIT_J5Q_C2_BRANDING_EMAIL.md`
- `docs/DEBUG_RECETTE_HODINA.md`
- `docs/README_MAJ_DOCS_J5Q_C2_DEBUG_20260625.md`
- `docs/COMMIT_DOCS_J5Q_C2_DEBUG_20260625.md`

## Validation attendue

```powershell
git diff --check
git diff --cached --check
```

Ne pas embarquer : `.zip`, `.patch`, `.bak`, `public/error_log`, `public/.user.ini`, `var/log/*.log`, fichiers temporaires `_*.php`.
