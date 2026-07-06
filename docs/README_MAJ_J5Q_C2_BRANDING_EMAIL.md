# README — J5Q-C-2 — Branding e-mail paramétrable

Date : 25/06/2026
Lot : J5Q-C-2
Objectif : centraliser le branding de tous les e-mails Hodina pour identifier rapidement l'environnement dev / recette / prod et homogénéiser les formules d'e-mail.

## Décision

Le branding e-mail est paramétré depuis `HodinaSetting`, dans le groupe `Branding e-mail` introduit dans EasyAdmin.

Le service central `EmailBrandingService` devient le point de passage obligatoire pour :

- préfixer les objets d'e-mails ;
- construire la formule d'ouverture ;
- fournir la formule de fin ;
- fournir la signature ;
- éviter les doubles préfixes dans les objets.

## Paramètres ajoutés

Groupe : `email_branding` / `Branding e-mail`.

| Clé | Rôle | Défaut |
| --- | --- | --- |
| `email_branding_subject_prefix` | Préfixe ajouté aux objets d'e-mails, par exemple `[Recette]` | vide |
| `email_branding_opening_formula` | Formule d'ouverture avant le nom du destinataire | `Bonjour` |
| `email_branding_closing_formula` | Formule de fin avant la signature | `Merci,` |
| `email_branding_signature` | Signature affichée en fin d'e-mail | `L’équipe Hodina` |

La valeur recette recommandée pour identifier les e-mails est :

```text
Préfixe objet e-mail : [Recette]
Formule début e-mail : Bonjour
Formule fin e-mail   : Merci,
Signature e-mail     : L’équipe Hodina — Recette
```

En production, le préfixe peut rester vide ou être remplacé par `[Hodina]` selon le choix de marque.

## Inventaire complet des e-mails pris en compte

Le lot parcourt tous les envois `TemplatedEmail` présents dans `src` et tous les templates d'e-mail existants.

| Service / composant | Template | Événement / usage | Branding appliqué |
| --- | --- | --- | --- |
| `OrderEmailService` | `emails/order_created.html.twig` | `ORDER_CREATED` — récap commande client après checkout | objet + formule début + formule fin + signature |
| `CustomerOrderNotificationService` | `emails/order_status_update.html.twig` | `ORDER_STATUS_CONFIRMED`, `PREPARING`, `READY_FOR_PICKUP`, `PICKED_UP`, `DELIVERED`, `CANCELED`, `ORDER_SELLER_COLLECTIONS_COMPLETED` | objet + formule début + formule fin + signature |
| `CustomerDeliveryCodeService` | `emails/customer_delivery_code.html.twig` | `CUSTOMER_DELIVERY_CODE` — code réception client | objet + formule début + formule fin + signature |
| `SellerCollectionCodeService` | `emails/seller_collection_code.html.twig` | `SELLER_COLLECTION_CODE` — code collecte vendeur | objet + formule début + formule fin + signature |
| `CourierPayoutAdminNotificationService` | `emails/admin/courier_payout_recap.html.twig` | `COURIER_PAYOUT_RECAP` — récap admin paiements livreurs | objet + formule début + formule fin + signature |
| `EmailVerifier` | `registration/confirmation_email.html.twig` | confirmation e-mail SymfonyCasts, composant actuellement dormant dans le parcours d'inscription | objet + contexte branding par défaut |

Les SMS ne sont pas modifiés par ce lot.

## Règles anti-régression

- Le sujet enregistré dans `EmailLog.subject` doit être le sujet réellement envoyé, donc avec le préfixe si configuré.
- Le préfixe ne doit pas être ajouté deux fois si un sujet est déjà préfixé.
- Les e-mails en échec doivent quand même être journalisés avec le sujet brandé.
- Le branding ne doit pas changer les règles métier d'envoi, d'anti-spam ou de statut.
- Le lot ne modifie pas les SMS.

## Migration

Migration ajoutée : `Version20260625090000`.

Elle ne modifie pas le schéma. Elle initialise les quatre réglages du groupe `Branding e-mail` si les clés n'existent pas déjà, puis recale leur groupe, type de champ et ordre.

## Contrôles recommandés

```bash
php -l src/Service/EmailBrandingService.php
php -l src/Service/OrderEmailService.php
php -l src/Service/CustomerOrderNotificationService.php
php -l src/Service/CustomerDeliveryCodeService.php
php -l src/Service/SellerCollectionCodeService.php
php -l src/Service/CourierPayoutAdminNotificationService.php
php -l src/Security/EmailVerifier.php
php -l src/Entity/HodinaSetting.php
php -l src/Controller/Admin/HodinaSettingEmailBrandingCrudController.php
php -l src/Controller/Admin/DashboardController.php
php -l migrations/Version20260625090000.php

php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:twig templates/emails templates/registration/confirmation_email.html.twig
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
git diff --check
```

## Tests fonctionnels

1. Ouvrir EasyAdmin > Réglages > Branding e-mail.
2. Mettre `Préfixe objet e-mail` à `[Recette]`.
3. Mettre une signature de test, par exemple `L’équipe Hodina — Recette`.
4. Déclencher un e-mail de récap admin paiements livreurs en recette si des données le permettent.
5. Vérifier `EmailLog.subject` : le sujet doit commencer par `[Recette]`.
6. Vérifier le corps HTML : la formule début, la formule fin et la signature doivent refléter les réglages.
7. Tester au moins un e-mail client ou vendeur si les données de recette le permettent.

## Hors périmètre

- Pas de refonte des modèles d'e-mails.
- Pas de modification des SMS.
- Pas de personnalisation par vendeur/client.
- Pas de traduction multilingue.

---

# Validation recette du 25/06/2026

Tag : `j5q-c2-branding-email-recette`
Commit : `3586560`

## Contrôles réalisés

- déploiement par script extrait du tag ;
- migration `Version20260625090000` exécutée ;
- `doctrine:schema:validate --env=prod` OK ;
- `lint:twig templates/emails templates/registration/confirmation_email.html.twig --env=prod` OK ;
- cache prod clear/warmup OK ;
- EasyAdmin > Réglages > Branding e-mail visible ;
- requête SQL du groupe `email_branding` OK.

## État base recette

```text
email_branding_subject_prefix      vide
email_branding_opening_formula     Bonjour
email_branding_closing_formula     Merci,
email_branding_signature           L’équipe Hodina
```

La configuration `[Recette]` doit être posée manuellement dans EasyAdmin recette pour identifier les e-mails de test.

## Tests restants

- envoyer un récap commande client ;
- envoyer une notification statut client ;
- envoyer un code réception client ;
- envoyer un code collecte vendeur ;
- envoyer un récap admin paiements livreurs ;
- vérifier `EmailLog.subject` après chaque envoi réel.

## Point de vigilance observabilité

Un `ERR_CONNECTION_CLOSED` intermittent a été observé côté navigateur mobile. Les contrôles serveur n'ont pas montré d'erreur applicative récente. Le debug doit suivre `DEBUG_RECETTE_HODINA.md` avant toute décision de rollback.
