# J5N-D — Détection automatique du fuseau horaire à la commande

Dernière mise à jour : **22/06/2026**

## Objectif

Afficher les dates liées à une commande dans le fuseau horaire détecté côté navigateur au moment de la commande, au lieu d'afficher systématiquement l'heure UTC ou le fuseau serveur.

## Décision métier

- Le fuseau horaire est capturé au checkout avec `Intl.DateTimeFormat().resolvedOptions().timeZone`.
- Il est stocké sur `CustomerOrder.customerTimezone`.
- Si le navigateur ne fournit rien ou fournit une valeur invalide, Hodina conserve le fallback `Indian/Mayotte`.
- Les communications liées à une commande peuvent utiliser le fuseau de la commande.

## Fichiers impactés

- `src/Entity/CustomerOrder.php`
- `src/Form/CheckoutType.php`
- `src/Controller/CheckoutController.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `templates/cart/index.html.twig`
- `templates/emails/order_created.html.twig`
- `templates/admin/dashboard.html.twig`
- `templates/admin/customer_order/operational_sheet.html.twig`
- `migrations/Version20260622204500.php`

## Tests attendus

1. Valider une commande depuis un navigateur.
2. Vérifier en base que `customer_order.customer_timezone` est renseigné.
3. Vérifier que l'e-mail de commande affiche l'heure dans le fuseau capturé.
4. Vérifier que Djama affiche les heures de collecte dans le fuseau de la commande.
5. Vérifier le fallback Mayotte si le champ est vide.
