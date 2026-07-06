# J5U-A — Expéditeur e-mails paramétrable EasyAdmin

Date : 26/06/2026
Statut : **validé recette**. Les e-mails partent bien avec `commande@hodina.fr`, et `ORDER_CREATED` est copié vers `commande@hodina.fr`.

## Objectif

Rendre l’expéditeur des e-mails de commande paramétrable dans EasyAdmin, sans SSH ni modification `.env`.

Adresse cible demandée pour le pilote : `commande@hodina.fr`.

## Périmètre

Sont concernés :

- `ORDER_CREATED`
- `ORDER_STATUS_CONFIRMED`
- `ORDER_STATUS_PREPARING`
- `ORDER_STATUS_READY_FOR_PICKUP`
- `ORDER_STATUS_PICKED_UP`
- `SELLER_COLLECTION_CODE`
- `ORDER_SELLER_COLLECTIONS_COMPLETED`
- `CUSTOMER_DELIVERY_CODE`
- `ORDER_STATUS_DELIVERED`
- `ORDER_STATUS_CANCELED`

## Réglages EasyAdmin

Les nouveaux paramètres sont rangés dans :

```text
Réglages → Branding e-mail
```

Paramètres ajoutés :

- `email_sender_name` : nom expéditeur, défaut `Hodina` ;
- `email_sender_email` : adresse expéditeur, défaut `commande@hodina.fr` ;
- `email_reply_to_name` : nom de réponse, défaut `Service commande Hodina` ;
- `email_reply_to_email` : adresse Reply-To, défaut `commande@hodina.fr` ;
- `email_order_created_copy_email` : copie interne des `ORDER_CREATED`, défaut `commande@hodina.fr`.

## Règles métier

- Tous les e-mails de commande/statut/collecte/code réception utilisent l’expéditeur configuré.
- Si un réglage est vide ou invalide, le code retombe sur un fallback sécurisé.
- Les e-mails `ORDER_CREATED` sont envoyés au client et en copie cachée à l’adresse interne configurée.
- Les modèles et les corps journalisés contiennent une mention demandant de ne pas répondre directement à l’e-mail.
- `EmailLog` historise désormais l’expéditeur et le Reply-To réellement utilisés.

## Fichiers principaux

- `src/Entity/HodinaSetting.php`
- `src/Entity/EmailLog.php`
- `src/Service/EmailBrandingService.php`
- `src/Service/EmailSenderSettings.php`
- `src/Service/OrderEmailService.php`
- `src/Service/CustomerOrderNotificationService.php`
- `src/Service/SellerCollectionCodeService.php`
- `src/Service/CustomerDeliveryCodeService.php`
- `src/Controller/Admin/HodinaSettingCrudController.php`
- `src/Controller/Admin/EmailLogCrudController.php`
- `templates/emails/*.html.twig`
- `migrations/Version20260626151000.php`

## Tests à faire

```powershell
php -l src/Service/OrderEmailService.php
php -l src/Service/CustomerOrderNotificationService.php
php -l src/Service/SellerCollectionCodeService.php
php -l src/Service/CustomerDeliveryCodeService.php
php -l src/Service/EmailBrandingService.php
php -l src/Service/EmailSenderSettings.php
php -l src/Entity/EmailLog.php
php -l src/Entity/HodinaSetting.php
php -l migrations/Version20260626151000.php

php bin/console lint:twig templates/emails/order_created.html.twig templates/emails/order_status_update.html.twig templates/emails/seller_collection_code.html.twig templates/emails/customer_delivery_code.html.twig
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Vérifications fonctionnelles

- EasyAdmin → Réglages → Branding e-mail : les 5 nouveaux paramètres sont visibles.
- `email_sender_email` vaut `commande@hodina.fr`.
- Un `ORDER_CREATED` part au client et en copie cachée à `commande@hodina.fr`.
- Les e-mails de statut partent avec `From: Hodina <commande@hodina.fr>`.
- Les e-mails de collecte vendeur partent avec `From: Hodina <commande@hodina.fr>`.
- Le mail de code réception client part avec `From: Hodina <commande@hodina.fr>`.
- Les templates affichent la mention de non-réponse.
- `EmailLog` affiche l’expéditeur et le Reply-To sur la fiche détail.


## Validation recette 27/06/2026

Validation confirmée : les e-mails sont bien envoyés avec l’expéditeur `commande@hodina.fr`.

Points validés :

- réglages visibles dans EasyAdmin, groupe `Branding e-mail` ;
- expéditeur `commande@hodina.fr` ;
- e-mails de commande/statut/collecte/code réception avec l’expéditeur configuré ;
- copie interne de `ORDER_CREATED` vers `commande@hodina.fr` ;
- mention de non-réponse dans les templates ;
- `EmailLog` enrichi avec expéditeur et Reply-To.

## Production 29/06/2026 — J5U-A inclus dans la MEP checkout

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

J5U-A reste validé : l’expéditeur e-mails est paramétrable via EasyAdmin/HodinaSetting, avec `commande@hodina.fr` comme valeur pilote utilisée pour les e-mails de commande.
