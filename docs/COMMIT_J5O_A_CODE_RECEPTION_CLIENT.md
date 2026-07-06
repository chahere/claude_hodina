# Commit J5O-A — Code réception client

## Message conseillé

```bash
git commit -m "feat(j5o): validate customer delivery with encrypted code"
```

## Résumé

Ajoute une preuve de réception client côté Djama : au départ en livraison, Hodina génère un code de réception, le stocke chiffré, l'envoie au client par SMS/e-mail, puis oblige le livreur à saisir ce code pour marquer la commande livrée.

## Points techniques

- Nouveau service `CustomerDeliveryCodeService`.
- Chiffrement AES-256-GCM avec une clé dérivée de `APP_SECRET` / `kernel.secret`.
- Nouveau template e-mail `emails/customer_delivery_code.html.twig`.
- Champs de suivi sur `customer_order` via migration `Version20260623210000`.
- Retour JSON/AJAX conservé dans Djama, avec fallback POST classique.

## Contrôles avant commit

```powershell
php -l src/Entity/CustomerOrder.php
php -l src/Entity/EmailLog.php
php -l src/Service/CustomerDeliveryCodeService.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l migrations/Version20260623210000.php
php bin/console lint:twig templates/courier/dashboard.html.twig templates/emails/customer_delivery_code.html.twig
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console cache:clear
php bin/console cache:warmup
```
