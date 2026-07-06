# COMMIT J5J — Mode commerce durable avec rôle testeur

## Résumé

J5J remplace le mécanisme trop spécifique de J5I (`préouverture`) par un **mode commerce durable**. Le même système sert désormais à :

```text
- préparer l'ouverture officielle des commandes ;
- suspendre temporairement les commandes pendant une mise à jour de production ;
- fermer manuellement les commandes ;
- laisser certains comptes tester le portail réel en production.
```

Le jalon a été développé après J5I parce qu'il fallait éviter d'empiler des paramètres à nettoyer plus tard. La décision prise est donc de **fusionner préouverture, maintenance commerciale et test production dans un seul système**.

## Branche et commit

```text
Branche locale / distante : pilot/j5j-commerce-mode-role-tester
Commit final : 0c2b357 feat: add J5J commerce mode with tester role
```

Un premier commit J5J avait embarqué par erreur un fichier `.patch` historique dans `src/Controller/Admin/`. Il a été corrigé par amend puis republié avec `git push --force-with-lease`. Le commit final propre est `0c2b357`.

## Décision importante

La première version envisagée utilisait une liste d'e-mails testeurs dans les réglages. Cette option a été abandonnée.

Décision retenue : utiliser un rôle Symfony dédié :

```text
ROLE_COMMERCE_TESTER
```

Raisons :

```text
- plus propre qu'une liste d'e-mails dans un réglage ;
- standard Symfony ;
- lisible dans EasyAdmin sur la fiche client ;
- plus durable pour la production ;
- évite de devoir nettoyer un paramètre commerce_tester_emails plus tard.
```

## Paramètres J5J

Les anciens réglages J5I sont migrés vers des réglages génériques `commerce_*`.

```text
commerce_mode
commerce_reopens_at
commerce_cart_locked
commerce_allow_testers
commerce_banner_title
commerce_banner_message
commerce_banner_button_label
commerce_email_capture_enabled
commerce_success_message
```

Les anciens réglages supprimés par la migration J5J sont :

```text
is_countdown_enabled
sales_opening_at
countdown_title
countdown_message
countdown_button_label
is_email_capture_enabled
is_cart_locked_before_opening
countdown_success_message
```

## Valeurs observées en recette après migration

```text
commerce_mode = preopening
commerce_cart_locked = 1
commerce_allow_testers = 1
commerce_email_capture_enabled = 1
commerce_reopens_at = 2026-06-30 18:00
```

## Modes commerce

```text
open
preopening
maintenance
closed
```

Signification :

```text
open :
  Le portail est ouvert normalement. Pas de bannière, pas de compte à rebours, pas de message de préouverture.

preopening :
  Le catalogue est visible, les commandes publiques sont bloquées, la bannière et le compte à rebours peuvent s'afficher.

maintenance :
  Le site reste consultable pendant une mise à jour de production, les commandes publiques sont suspendues, les testeurs peuvent valider le portail.

closed :
  Fermeture manuelle des commandes.
```

## Règle d'affichage de la bannière

Décision validée après test :

```text
commerce_mode = open
→ aucune bannière
→ aucun chrono
→ affichage normal du portail comme avant J5I/J5J
```

La bannière est uniquement liée aux modes restrictifs :

```text
preopening
maintenance
closed
```

La simple présence d'une date future dans `commerce_reopens_at` ne doit pas afficher la bannière si `commerce_mode = open`.

## Règle panier / checkout

Le blocage n'est pas seulement visuel. Il est appliqué côté serveur.

```text
Public non testeur :
  - catalogue visible ;
  - ajout panier bloqué ;
  - checkout bloqué.

Utilisateur ROLE_COMMERCE_TESTER :
  - catalogue visible ;
  - ajout panier autorisé ;
  - checkout autorisé ;
  - commande de test possible.

Administrateur :
  - considéré comme autorisé pour tester.
```

## EasyAdmin

### Réglages Hodina

Les réglages booléens ne sont plus de simples champs texte. Ils sont affichés en interrupteurs :

```text
commerce_cart_locked
commerce_allow_testers
commerce_email_capture_enabled
```

Le mode commerce est affiché en liste de choix :

```text
Ouvert — commandes publiques actives
Préouverture — catalogue visible, commandes publiques bloquées
Maintenance — mise à jour production, commandes publiques suspendues
Fermé — suspension manuelle des commandes
```

Le champ technique `Type de champ` est masqué lors de l'édition des réglages existants. Il n'apparaît que lors de la création d'un nouveau réglage, pour éviter qu'un admin casse l'affichage des réglages système.

### Clients

Le CRUD client permet de choisir les rôles dans une liste, dont :

```text
ROLE_USER
ROLE_COURIER
ROLE_COMMERCE_TESTER
ROLE_ADMIN
```

Pour un compte de test production, conserver `ROLE_USER` et ajouter `ROLE_COMMERCE_TESTER`.

## Fichiers modifiés

```text
migrations/Version20260613094055.php
migrations/Version20260613110000.php
migrations/Version20260613130000.php
src/Controller/Admin/CustomerCrudController.php
src/Controller/Admin/HodinaSettingCrudController.php
src/Controller/Admin/SalesOpeningSettingsController.php
src/Entity/HodinaSetting.php
src/Service/SalesOpeningService.php
src/Twig/SalesOpeningExtension.php
templates/cart/index.html.twig
templates/launch/_countdown_banner.html.twig
templates/product/catalogue.html.twig
templates/product/show.html.twig
```

## Migrations

`Version20260613130000` est la migration clé de J5J.

Elle fait :

```text
- création défensive de launch_subscriber si nécessaire ;
- correction du type created_at ;
- migration des anciens paramètres J5I vers les nouveaux paramètres commerce_* ;
- suppression des anciens paramètres J5I ;
- ajout du field_type adapté aux réglages : boolean, choice, textarea, text.
```

Les migrations `Version20260613094055` et `Version20260613110000` ont été ajustées pour éviter le problème d'ordre rencontré en recette pendant J5I.

## Déploiement recette réalisé

Commandes réalisées sur recette :

```bash
cd ~/recette.hodina.fr
git fetch origin
git checkout pilot/j5j-commerce-mode-role-tester
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
```

La migration J5J a été exécutée avec succès :

```text
Successfully migrated to version DoctrineMigrations\Version20260613130000
```

## Point schema:validate en recette

Après migration J5J, `doctrine:schema:validate` signalait encore un écart uniquement parce qu'une table de sauvegarde manuelle existait :

```text
hodina_setting_backup_20260613
```

`doctrine:schema:update --dump-sql` affichait seulement :

```sql
DROP TABLE hodina_setting_backup_20260613;
```

Conclusion : ce n'était pas un problème J5J, mais une table de backup créée manuellement pendant l'injection des réglages J5I/J5J.

Action possible après validation :

```bash
php bin/console dbal:run-sql "DROP TABLE hodina_setting_backup_20260613"
php bin/console doctrine:schema:validate
```

## Tests validés

```text
- Les paramètres commerce_* sont présents en recette.
- Les anciens paramètres J5I ne sont plus utilisés.
- En mode open, la bannière et le chrono disparaissent.
- En mode preopening, la bannière et le blocage public fonctionnent.
- Un utilisateur avec ROLE_COMMERCE_TESTER peut commander malgré le blocage public.
- Les booléens apparaissent en switch dans EasyAdmin.
- commerce_mode apparaît en liste de choix.
```

## Attention pour production

Avant production :

```text
1. vérifier que la branche déployée contient bien le commit 0c2b357 ;
2. exécuter les migrations ;
3. vérifier les paramètres commerce_* ;
4. vérifier le rôle ROLE_COMMERCE_TESTER sur les comptes de test ;
5. tester un public non testeur ;
6. tester un compte testeur ;
7. basculer commerce_mode selon le besoin réel : open, preopening, maintenance ou closed.
```
