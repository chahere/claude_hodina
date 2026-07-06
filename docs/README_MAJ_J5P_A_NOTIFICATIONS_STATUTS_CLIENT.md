# J5P-A — Notifications client sur les étapes de commande

## Objectif

Ajouter une notification client cohérente par e-mail pour les changements d’état déjà notifiés par SMS, sans multiplier inutilement les messages.

## Règle anti-spam retenue

- Le SMS automatique existant sur les statuts est conservé.
- Un e-mail client est ajouté sur les étapes réellement utiles : commande validée, en préparation, prête, prise en charge, livrée, annulée.
- L’étape `OUT_FOR_DELIVERY` n’ajoute pas un e-mail générique supplémentaire : le code de réception client J5O contient déjà l’information “commande en cours de livraison”.
- L’étape “produits collectés” est envoyée une seule fois lorsque toutes les collectes vendeurs sont validées.
- Un même `event_key` déjà journalisé en `PENDING` ou `SENT` n’est pas renvoyé automatiquement.
- `contact@hodina.fr` reste expéditeur Hodina et n’est jamais utilisé comme destinataire client de secours.

## Événements e-mail ajoutés

- `ORDER_STATUS_CONFIRMED`
- `ORDER_STATUS_PREPARING`
- `ORDER_STATUS_READY_FOR_PICKUP`
- `ORDER_STATUS_PICKED_UP`
- `ORDER_STATUS_DELIVERED`
- `ORDER_STATUS_CANCELED`
- `ORDER_SELLER_COLLECTIONS_COMPLETED`

## Étapes couvertes

| Étape | SMS | E-mail | Commentaire |
| --- | --- | --- | --- |
| Commande créée | Non | Oui | Déjà couvert par J5H-A |
| Commande validée | Oui | Oui | Ajout J5P-A |
| Commande en préparation | Oui | Oui | Ajout J5P-A |
| Commande prête | Oui | Oui | Ajout J5P-A |
| Produits collectés | Oui | Oui | Nouveau jalon terrain, une seule fois quand toutes les collectes vendeurs sont faites |
| Commande prise en charge livreur | Oui | Oui | Ajout J5P-A |
| Commande en cours de livraison | Oui | Oui | Le mail/SMS de code réception J5O sert de notification, pas de doublon générique |
| Commande livrée | Oui | Oui | Ajout J5P-A |
| Commande annulée | Oui | Oui | Ajout J5P-A |

## Fichiers ajoutés / modifiés

- `src/Service/CustomerOrderNotificationService.php`
- `src/Service/CustomerOrderWorkflowService.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `src/Entity/EmailLog.php`
- `templates/emails/order_status_update.html.twig`
- `docs/README_MAJ_J5P_A_NOTIFICATIONS_STATUTS_CLIENT.md`
- `docs/COMMIT_J5P_A_NOTIFICATIONS_STATUTS_CLIENT.md`

## Tests recommandés

```powershell
php -l src/Service/CustomerOrderNotificationService.php
php -l src/Service/CustomerOrderWorkflowService.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Entity/EmailLog.php
php bin/console lint:twig templates/emails/order_status_update.html.twig
php bin/console doctrine:schema:validate
php bin/console cache:clear
php bin/console cache:warmup
git diff --check
```

## Parcours fonctionnel à tester

1. Créer une commande.
2. Vérifier l’e-mail de création déjà existant.
3. Valider la commande côté admin : SMS + e-mail `ORDER_STATUS_CONFIRMED`.
4. Passer en préparation : SMS + e-mail `ORDER_STATUS_PREPARING`.
5. Marquer prête : SMS + e-mail `ORDER_STATUS_READY_FOR_PICKUP`.
6. Djama : prise en charge livreur : SMS + e-mail `ORDER_STATUS_PICKED_UP`.
7. Valider toutes les collectes vendeurs : SMS + e-mail `ORDER_SELLER_COLLECTIONS_COMPLETED` une seule fois.
8. Démarrer livraison : code réception J5O envoyé, sans e-mail générique supplémentaire.
9. Valider le bon code client : SMS + e-mail `ORDER_STATUS_DELIVERED`.
10. Vérifier EasyAdmin > Journaux e-mails > Voir.

---

# Validation recette complémentaire 24/06/2026

J5P-A est confirmé en recette : les logs e-mail et SMS montrent les événements attendus pour validation, préparation, commande prête, prise en charge, collectes vendeurs terminées, code de réception et livraison.

Point accepté : le SMS générique `customer_order_out_for_delivery` peut encore coexister avec `customer_delivery_code`. Ce doublon potentiel est noté dans `TODO.md` pour arbitrage ultérieur.
