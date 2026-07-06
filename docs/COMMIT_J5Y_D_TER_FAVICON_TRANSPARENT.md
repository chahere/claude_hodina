# COMMIT J5Y-D-ter — Favicon transparent et plus discret

## Objectif

Supprimer l’effet carré blanc du favicon Hodina tout en gardant une icône lisible dans l’onglet du navigateur.

## Changements

- Remplacement du favicon par une version symbole transparente.
- Conservation du logo header J5Y-D.
- Ajout d’un cache-busting `?v=j5y-d-ter`.
- Aucun changement routing, catalogue, panier ou back-office.

## Validation attendue

- Le favicon ne s’affiche plus comme un carré blanc.
- Le symbole Hodina reste visible en navigation privée.
- Le header reste inchangé.

## Statut après test visuel

La version transparente supprime le carré blanc, mais le favicon final reste à arbitrer. Après cette tentative, deux images sources ont été redimensionnées en 16x16 et 32x32 pour comparaison. Ne pas considérer J5Y-D-ter comme définitivement validé tant que le choix final n’est pas appliqué et testé.

## Note de statut 01/07/2026

Ce document décrit un état intermédiaire du lot. L’état opérationnel courant de J5Y est désormais la validation recette `recette-j5y-carnet-livraison-footer-clean-20260701`. Les routes publiques finales sont `/`, `/decouvrir-hodina`, `/carnet` et `/carnet/livraison`, avec `/blog*` uniquement en redirection legacy.
