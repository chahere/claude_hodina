# J5O-A — Code de réception client à la livraison

Dernière mise à jour : **23/06/2026**

## Objectif

Sécuriser la fin de livraison dans Djama : le livreur ne peut plus marquer une commande comme livrée sans validation par un code communiqué au client.

## Règle métier

- Quand le livreur clique sur **Démarrer la livraison**, la commande passe en `OUT_FOR_DELIVERY`.
- Hodina génère immédiatement un code de réception client à 6 chiffres.
- Le code est stocké **chiffré** sur la commande.
- Le code est envoyé au client par SMS et par e-mail lorsque les coordonnées existent.
- Les envois sont journalisés dans `sms_log` et `email_log` avec l'événement `customer_delivery_code` / `CUSTOMER_DELIVERY_CODE`.
- Si le client ne retrouve pas le code, le livreur peut cliquer sur **Valider la livraison** sans saisir de code : Hodina renvoie le même code.
- Si le livreur saisit le bon code, la commande passe en `DELIVERED`.
- Le code chiffré est supprimé après validation de la livraison.

## Décisions importantes

- Le code n'est pas stocké en clair.
- Il est déchiffré uniquement pour être renvoyé au client tant que la livraison n'est pas validée.
- `contact@hodina.fr` ne doit jamais être utilisé comme destinataire client de secours.
- Si aucun SMS/e-mail ne peut être envoyé, la commande n'est pas validée par code et l'incident est visible via les logs.
- L'action admin `Livrée + SMS` reste possible comme action manuelle d'exploitation, mais le flux livreur terrain passe par le code client.

## Fichiers impactés

- `src/Entity/CustomerOrder.php`
- `src/Entity/EmailLog.php`
- `src/Service/CustomerDeliveryCodeService.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `templates/courier/dashboard.html.twig`
- `templates/emails/customer_delivery_code.html.twig`
- `migrations/Version20260623210000.php`

## Tests attendus

1. Commande avec client ayant un téléphone et un e-mail : démarrer livraison, vérifier SMS/e-mail code.
2. Dans Djama, laisser le champ code vide et valider : le même code doit être renvoyé.
3. Saisir un mauvais code : la commande ne passe pas livrée et le compteur d'échec augmente.
4. Saisir le bon code : la commande passe livrée, la carte reste ouverte en AJAX puis bascule en historique.
5. Client sans e-mail/téléphone : vérifier que les logs sont en échec et qu'aucun envoi ne part vers `contact@hodina.fr`.
6. Vérifier en base que `delivery_validation_code_encrypted` est remis à `NULL` après livraison validée.

## Commandes utiles

```bash
php bin/console dbal:run-sql "SELECT id, order_reference, status, delivery_validation_code_encrypted, delivery_validation_code_send_count, delivery_validation_code_failed_attempts, delivery_validation_code_sent_at, delivery_validation_code_validated_at FROM customer_order ORDER BY id DESC LIMIT 5;"
php bin/console dbal:run-sql "SELECT id, context, phone, status, error_message FROM sms_log WHERE context = 'customer_delivery_code' ORDER BY id DESC LIMIT 10;"
php bin/console dbal:run-sql "SELECT id, event_key, recipient_email, status, error_message FROM email_log WHERE event_key = 'CUSTOMER_DELIVERY_CODE' ORDER BY id DESC LIMIT 10;"
```

---

# Validation recette complémentaire 24/06/2026

J5O-A est confirmé en recette avant J5P/J5Q : le code client est envoyé au démarrage livraison, peut être renvoyé, refuse les mauvais codes et permet le passage `DELIVERED` uniquement si le bon code est saisi.

Le test final a confirmé que `delivery_validation_code_encrypted` est vidé après livraison et que `delivery_validation_code_send_count` reflète les renvois.
