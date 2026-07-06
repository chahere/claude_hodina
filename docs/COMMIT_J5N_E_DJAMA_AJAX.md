# Commit J5N-E — Actions AJAX du portail livreur Djama

## Résumé

Ajout d’un mode AJAX progressif sur le portail livreur `/djama` pour les actions terrain.

## Changements principaux

- Les formulaires Djama peuvent être envoyés en AJAX via `data-djama-ajax="true"`.
- Les actions serveur retournent du JSON pour les requêtes AJAX.
- Le comportement sans JavaScript reste inchangé avec redirection classique.
- Le JavaScript recharge la section Djama après action et restaure les cartes ouvertes.
- Les boutons sont désactivés pendant le traitement pour éviter les doubles clics.

## Fichiers principaux

- `src/Controller/Courier/CourierDashboardController.php`
- `templates/courier/dashboard.html.twig`
- `docs/README_MAJ_J5N_E_DJAMA_AJAX.md`

## Migration

Aucune migration Doctrine.

## Message de commit conseillé

```bash
git commit -m "feat(j5n): add ajax courier actions in djama"
```
