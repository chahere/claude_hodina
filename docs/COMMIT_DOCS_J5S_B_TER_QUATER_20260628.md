# COMMIT DOCS — J5S-B-ter/quater — Checkout point de remise / standard

## Résumé

Mise à jour documentaire après les tests et correctifs du checkout point de remise du 28/06/2026.

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
- `COMMIT_J5S_B_QUATER_CHECKOUT_FEEDBACK.md`
- `README_MAJ_J5S_B_TER_QUATER_CHECKOUT_POINT_STANDARD_20260628.md`

## Statut retenu

- J5S-B-ter/quater : validé recette sous le tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628`.
- J5V-A : régression de branchement serveur détectée puis corrigée par `3b508d0`; validation checkout rebranchée et revalidée recette sous `recette-j5v-a-checkout-lead-time-fix-20260628`.
- J5W : prévu, non codé.

## Points de vigilance

- Ne pas confondre `DeliveryZone` et future `DeliveryArea`.
- Ne pas faire dépendre les frais point de remise de l’adresse client.
- Ne pas afficher d’erreur globale avant tentative de validation.
- Garder les erreurs maîtrisées en français.
- Ne pas supprimer l’unicité `order_reference` ; elle reste un garde-fou.
