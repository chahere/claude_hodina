# COMMIT DOCS — J5Z production validation — 02/07/2026

## Commit recommandé

```bash
git commit -m "docs(j5z): record checkout admin ux production validation"
```

## Fichiers documentaires concernés

- `docs/ARCHITECTURE.md`
- `docs/DECISIONS.md`
- `docs/DEPLOIEMENT_PREPROD.md`
- `docs/ENTITIES.md`
- `docs/HISTORIQUE.md`
- `docs/PILOT_STATUS_DETAILED.md`
- `docs/README_MAJ_J5Z_CHECKOUT_ADMIN_UX_PROD_20260702.md`
- `docs/ROADMAP.md`
- `docs/TODO.md`
- `docs/VISION.md`
- `docs/WORKFLOWS.md`

## Résumé

La documentation acte la clôture J5Z : checkout/admin UX validé production, tags recette/prod, décisions téléphone, frais livraison, flash, correctifs mobile, cache preview logistique et réflexion J5AA `AddressLocality`.

## Anti-régression à conserver

```text
Frais standard simple → pas d’annotation.
Frais avec barge / commune(s) traversée(s) → annotation affichée.
Indicatif visible pour invité, caché pour client connecté.
Commune = source de vérité logistique.
Future localité = précision terrain, pas source tarifaire.
```
