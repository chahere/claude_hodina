# README — J5Q-C-1 — Structuration des réglages en groupes

Date : 24/06/2026
Lot : J5Q-C-1
Objectif : sortir les réglages globaux du mode « liste en vrac » et poser une structure durable par sujet métier.

## Décision

`HodinaSetting` reste le registre central des paramètres globaux, mais il est enrichi pour porter des métadonnées d'UX admin : groupe, libellé de groupe, ordre d'affichage, modifiabilité et sensibilité.

On ne crée pas une table par famille de paramètres. On garde un stockage simple, mais EasyAdmin expose des écrans spécialisés par sujet.

## Champs ajoutés à `HodinaSetting`

- `groupKey` / colonne `group_key` : clé technique du groupe (`general`, `commerce`, `logistics`, `notifications`, `payments`, `technical`).
- `groupLabel` / colonne `group_label` : libellé affiché dans EasyAdmin.
- `sortOrder` / colonne `sort_order` : ordre d'affichage dans le groupe.
- `isEditable` / colonne `is_editable` : indique si la valeur peut être modifiée depuis EasyAdmin.
- `isSensitive` / colonne `is_sensitive` : masque la valeur dans les listes si nécessaire.

Le champ existant `help` reste l'aide/description du paramètre. Le champ existant `fieldType` reste le type de saisie.

## Groupes EasyAdmin introduits

Dans la section `Réglages`, le menu est structuré ainsi :

- Tous les paramètres ;
- Général ;
- Commerce & commandes ;
- Livraison & logistique ;
- Notifications ;
- Paiements ;
- Technique / maintenance ;
- Initialiser préouverture.

Les sous-écrans filtrent la même entité `HodinaSetting` par `groupKey`. La vue `Tous les paramètres` reste disponible pour l'admin expert.

## Migration

Migration ajoutée : `Version20260624233000`.

Elle ajoute les colonnes de structuration à `hodina_setting`, crée l'index `IDX_HODINA_SETTING_GROUP_SORT`, puis classe les paramètres existants connus :

- paramètres `commerce_*`, marge globale et préfixe commande → `commerce` ;
- paramètres de livraison, barge, multi-vendeurs et plafonds → `logistics` ;
- timezone par défaut → `general` ;
- inconnus → `general` par défaut.

## Contrôles recommandés

```bash
php -l src/Entity/HodinaSetting.php
php -l src/Controller/Admin/HodinaSettingCrudController.php
php -l src/Controller/Admin/HodinaSettingGeneralCrudController.php
php -l src/Controller/Admin/HodinaSettingCommerceCrudController.php
php -l src/Controller/Admin/HodinaSettingLogisticsCrudController.php
php -l src/Controller/Admin/HodinaSettingNotificationsCrudController.php
php -l src/Controller/Admin/HodinaSettingPaymentsCrudController.php
php -l src/Controller/Admin/HodinaSettingTechnicalCrudController.php
php -l src/Controller/Admin/DashboardController.php
php -l src/Service/SalesOpeningService.php
php -l migrations/Version20260624233000.php

php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console cache:clear
php bin/console cache:warmup
git diff --check
```

## Tests navigateur

- Ouvrir EasyAdmin > Réglages > Tous les paramètres.
- Vérifier que la colonne `Groupe` apparaît.
- Ouvrir EasyAdmin > Réglages > Commerce & commandes.
- Vérifier que les paramètres de préouverture sont visibles dans ce groupe.
- Ouvrir EasyAdmin > Réglages > Livraison & logistique.
- Vérifier que les paramètres de frais/plafonds livraison sont visibles dans ce groupe.
- Modifier une valeur non sensible, enregistrer, vérifier qu'elle reste dans le bon groupe.

## Hors périmètre

J5Q-C-1 ne modifie pas les e-mails et ne crée pas encore le branding e-mail. Le branding e-mail devient le lot suivant : `J5Q-C-2`.


## Complément J5Q-C-1 — Paramètres paiements livreurs

Le groupe `Paiements` contient désormais les réglages opérationnels du module de rémunération livreur :

- `courier_payouts_enabled` : active ou suspend les générations de paiements livreurs ;
- `courier_payout_cron_enabled` : autorise la génération cron `--auto-due` ;
- `courier_payout_admin_recap_enabled` : autorise l’envoi du récapitulatif admin après génération réelle ;
- `courier_payout_frequency` : fréquence métier, valeur pilote `semi_monthly` pour la quinzaine.

Ces réglages restent des garde-fous : ils ne valident pas et ne marquent jamais un paiement comme payé automatiquement.
