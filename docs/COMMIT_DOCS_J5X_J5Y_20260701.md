# COMMIT docs — J5X/J5Y état au 01/07/2026

## Objectif

Mettre à jour la documentation de suivi après les développements J5X/J5Y et avant ouverture d’une nouvelle discussion.

## Points documentés

- J5X-A/B/C/D : code présent, assertions locales OK, recette groupée `recette-j5x-livraison-catalogue-20260630-1440`, validation navigateur complète encore à terminer.
- J5Y-A : interface guidée EasyAdmin pour plages de point de remise, validée localement et poussée sur `develop`.
- J5Y-B : créneaux panier point de remise par demi-heure, validé localement et poussé sur `develop`.
- J5Y-C : catalogue déplacé sur `/`, page Découvrir Hodina sur `/blog/decouvrir-hodina`, header public simplifié.
- J5Y-D : logo header amélioré, favicon encore en arbitrage visuel.

## Fichiers principaux mis à jour

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

## Règles rappelées

- Ne pas taguer J5Y en recette avant validation locale complète.
- Ne pas embarquer `.zip`, `.patch`, `.bak`, `.old`, fichiers temporaires ou images de test.
- Ne pas passer en production J5X/J5Y avant validation navigateur recette.
- Corriger ou vérifier le cron Messenger recette si la ligne contient `bin/consolemessenger:consume`.
