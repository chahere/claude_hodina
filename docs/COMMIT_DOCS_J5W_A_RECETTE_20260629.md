# COMMIT DOCS — J5W-A recette validée

Date : 29/06/2026.

Objectif : acter la validation recette de J5W-A après tests serveur et fonctionnels.

## État acté

- Tag recette : `recette-j5w-a-local-pricing-zones-20260629`.
- Commit recette : `162fcb4 merge(j5w-a): local pricing zones by sector`.
- Migration : `DoctrineMigrations\Version20260629083000`, current/latest en recette.
- Garde-fou : `tools/assert-j5w-a-local-pricing-zones.php` OK.
- `PETITE_TERRE_LOCAL` absent.
- `PT_LOCAL` conservé pour Dzaoudzi, Labattoir et Pamandzi.
- Communes Grande-Terre rattachées aux zones locales Mamoudzou/Nord/Centre/Sud.
- État au moment de ce commit : avant MEP production ; production validée ensuite sous `prod-j5w-a-local-pricing-zones-20260629`.

## Fichiers documentaires concernés

- `ARCHITECTURE.md`
- `DECISIONS.md`
- `DEPLOIEMENT_PREPROD.md`
- `ENTITIES.md`
- `HISTORIQUE.md`
- `PILOT_STATUS_DETAILED.md`
- `README_MAJ_J5W_A_LOCAL_PRICING_ZONES_20260629.md`
- `ROADMAP.md`
- `TODO.md`
- `WORKFLOWS.md`

## Commande de commit recommandée

```bash
git commit -m "docs(j5w-a): record recette validation"
```
