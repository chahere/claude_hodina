# Commit J5P-A — Notifications client sur statuts commande

## Résumé

Ajout d’une couche de notification client par e-mail sur les transitions de commande déjà notifiées par SMS, avec une règle anti-spam pour éviter les doublons.

## Décisions

- Ne pas envoyer d’e-mail générique supplémentaire au passage `OUT_FOR_DELIVERY` car le code de réception client J5O contient déjà cette information.
- Ajouter un jalon client “produits collectés” uniquement quand toutes les collectes vendeurs sont validées.
- Utiliser des `event_key` dédiés dans `email_log` pour permettre le suivi admin.
- Ne pas envoyer automatiquement deux fois un même événement si un log `PENDING` ou `SENT` existe déjà.

## Fichiers principaux

- `src/Service/CustomerOrderNotificationService.php`
- `src/Service/CustomerOrderWorkflowService.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `templates/emails/order_status_update.html.twig`
- `src/Entity/EmailLog.php`

## Validation attendue

- PHP lint OK.
- Twig lint OK.
- Doctrine schema OK, aucune migration nécessaire.
- Flux admin + Djama OK.
- Logs e-mail lisibles depuis EasyAdmin avec bouton Voir.
