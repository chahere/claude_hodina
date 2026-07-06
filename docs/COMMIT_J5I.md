# COMMIT J5I — Préouverture commerciale, compte à rebours et capture e-mail

## Statut

```text
Développé localement : OK
Testé localement : OK
Commit local : OK
Push GitHub : OK
Déployé recette : OK
Paramètres dev injectés recette : OK
Tests recette principaux : OK
Production : EN ATTENTE correction ordre migration
```

## Branche et commit

```text
branche : pilot/j5i-preouverture-countdown
commit  : 5bf3e0e feat: add J5I sales opening countdown and launch email capture
```

## Objectif métier

Avant l'ouverture officielle des commandes, Hodina doit pouvoir :

```text
afficher une bannière de préouverture ;
afficher un compte à rebours ;
laisser le catalogue visible ;
récupérer les e-mails des personnes intéressées ;
bloquer toute création de panier ou commande ;
laisser l'admin modifier les textes et la date depuis EasyAdmin.
```

## Règle métier finale

```text
Catalogue visible : OUI
Fiche produit visible : OUI
Prix visibles : OUI
Ajout panier : NON avant ouverture si blocage actif
Checkout : NON avant ouverture si blocage actif
Commande en base : NON avant ouverture
Capture e-mail : OUI si activée
```

## Paramètres Hodina ajoutés

Les réglages sont stockés dans `hodina_setting` :

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

Valeurs dev reprises en recette :

```text
is_countdown_enabled = 1
sales_opening_at = 2026-06-30 18:00
countdown_title = Votre marché en ligne de produits locaux arrive bientôt
countdown_message = Le catalogue est accessible, mais la prise de commande sera possible à la date officielle. Laisse nous ton e-mail pour être informé de l'ouverture.
countdown_button_label = Me faire signe à l’ouverture
is_email_capture_enabled = 1
is_cart_locked_before_opening = 1
countdown_success_message = Merci, ton e-mail est bien enregistré. On te préviendra pour l’ouverture des commandes.
```

## Fichiers principaux créés

```text
src/Entity/LaunchSubscriber.php
src/Controller/LaunchSubscriberController.php
src/Controller/Admin/LaunchSubscriberCrudController.php
src/Controller/Admin/SalesOpeningSettingsController.php
src/Service/SalesOpeningService.php
src/Twig/SalesOpeningExtension.php
templates/launch/_countdown_banner.html.twig
migrations/Version20260613110000.php
```

## Fichiers principaux modifiés

```text
templates/base.html.twig
templates/product/catalogue.html.twig
templates/product/show.html.twig
templates/cart/index.html.twig
templates/checkout/index.html.twig
src/Controller/CartController.php
src/Controller/CheckoutController.php
src/Controller/Admin/DashboardController.php
public/css/style_mobile.css
```

## Table créée

```text
launch_subscriber
```

Champs :

```text
id
email
source
ip_address
user_agent
created_at
```

Contrainte importante :

```text
email unique
```

## Tests locaux effectués

Commandes et constats :

```powershell
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php bin/console doctrine:schema:validate
```

Résultat attendu obtenu :

```text
[OK] The mapping files are correct.
[OK] The database schema is in sync with the mapping files.
```

Vérification e-mail :

```powershell
php bin/console dbal:run-sql "SELECT id, email, created_at FROM launch_subscriber ORDER BY id DESC"
```

Résultat local validé :

```text
abdamayot@hotmail.fr
```

## Déploiement recette

Commandes principales :

```bash
cd ~/recette.hodina.fr
git fetch origin
git checkout pilot/j5i-preouverture-countdown
git pull
git log --oneline -1
```

Résultat :

```text
5bf3e0e feat: add J5I sales opening countdown and launch email capture
```

## Incident migration recette

Erreur :

```text
SQLSTATE[42S02]: Base table or view not found: 1146
Table 'vopu3712_hodina_recette.launch_subscriber' doesn't exist
```

Cause :

```text
Version20260613094055 tente de modifier launch_subscriber
avant
Version20260613110000 qui crée launch_subscriber
```

Contournement recette appliqué :

```bash
php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260613094055' --add --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:update --dump-sql
php bin/console dbal:run-sql "ALTER TABLE launch_subscriber CHANGE created_at created_at DATETIME NOT NULL"
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
```

Résultat final :

```text
[OK] The database schema is in sync with the mapping files.
```

## Correction `.htaccess` recette

Problème avant correction : après Basic Auth, redirection parasite vers :

```text
https://recette.hodina.fr/401.shtml
```

Correction appliquée :

```apache
ErrorDocument 401 "Authentification requise"
```

avec redirection HTTPS avant routage Symfony.

Validation :

```bash
curl -I https://recette.hodina.fr/
```

Résultat normal sans identifiants :

```text
HTTP/2 401
www-authenticate: Basic realm="Hodina Recette"
```

## Tests recette validés

```text
Basic Auth / HTTPS : OK
Plus de /401.shtml : OK
Branche J5I active : OK
Schéma Doctrine synchronisé : OK
Paramètres dev injectés : OK
Bannière activable avec les paramètres : OK
```

## À faire avant production

```text
1. Corriger l'ordre des migrations J5I dans Git.
2. Tester les migrations sur une base fraîche.
3. Confirmer que schema:validate est OK sans SQL manuel.
4. Revalider recette mobile + desktop.
5. Déployer production.
```
