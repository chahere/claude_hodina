# Commit — J5N-A — Code de validation collecte vendeur

## Résumé

Ajoute une validation métier forte de la collecte vendeur dans Djama : chaque vendeur collecté doit être confirmé par un code transmis au livreur par le vendeur.

## Changements

- Ajout d'un code fixe optionnel sur `Seller`.
- Ajout d'un snapshot JSON de collecte vendeur sur `CustomerOrder`.
- Ajout du service `SellerCollectionCodeService`.
- Génération d'un code ponctuel quand le vendeur n'a pas de code fixe.
- Envoi du code ponctuel par SMS et e-mail.
- Journalisation des envois dans `SmsLog` et `EmailLog`.
- Ajout du corps optionnel `EmailLog.body` pour les e-mails rejouables manuellement.
- Blocage du démarrage livraison tant que toutes les collectes vendeur ne sont pas validées.
- Mise à jour de l'interface Djama.

## Migration

```text
DoctrineMigrations\\Version20260622172000
```

Colonnes ajoutées :

```text
seller.collection_validation_code
customer_order.seller_collection_snapshot
email_log.body
```

## Validation technique recommandée

```powershell
php -l src/Entity/Seller.php
php -l src/Entity/CustomerOrder.php
php -l src/Entity/EmailLog.php
php -l src/Service/SellerCollectionCodeService.php
php -l src/Service/CustomerOrderWorkflowService.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Admin/EmailLogCrudController.php
php -l migrations/Version20260622172000.php
php bin/console lint:twig templates/courier/dashboard.html.twig templates/emails/seller_collection_code.html.twig
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console debug:router | findstr collecte-vendeur
```

## Statut

- Local : à valider
- Recette : à faire
- Production : non déployé
