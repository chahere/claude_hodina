# README mise à jour — J5J mode commerce et testeurs

## Objectif

J5J transforme la préouverture J5I en un système durable de pilotage commercial du portail Hodina.

Le besoin initial était double :

```text
- réutiliser le blocage panier + compte à rebours pendant les mises à jour de production ;
- permettre à certains comptes de test d'utiliser le portail normalement pendant que le public reste bloqué.
```

La décision finale est de ne pas créer un second mécanisme. J5J fusionne tout dans un seul **mode commerce**.

## Résultat attendu

```text
Le site reste visible.
Le catalogue reste visible.
Les commandes publiques peuvent être suspendues.
La bannière explique la situation.
Le compte à rebours annonce la réactivation si une date est renseignée.
Les comptes ROLE_COMMERCE_TESTER peuvent tester panier et checkout.
```

## Pourquoi un rôle plutôt qu'une liste d'e-mails

La liste d'e-mails testeurs a été refusée parce qu'elle aurait ajouté un paramètre à nettoyer plus tard.

Le rôle retenu est :

```text
ROLE_COMMERCE_TESTER
```

Il est plus propre, plus Symfony, plus durable et administrable depuis la fiche client.

## Réglages à connaître

```text
commerce_mode                    choice
commerce_reopens_at              text
commerce_cart_locked             boolean
commerce_allow_testers           boolean
commerce_banner_title            text
commerce_banner_message          textarea
commerce_banner_button_label     text
commerce_email_capture_enabled   boolean
commerce_success_message         textarea
```

## Valeurs de test recette validées

```text
commerce_mode = preopening
commerce_cart_locked = 1
commerce_allow_testers = 1
commerce_email_capture_enabled = 1
commerce_reopens_at = 2026-06-30 18:00
```

## Scénarios de test

### Mode ouvert

```text
commerce_mode = open
```

Résultat :

```text
- aucune bannière ;
- aucun chrono ;
- site affiché normalement ;
- panier utilisable selon le fonctionnement normal.
```

### Mode préouverture

```text
commerce_mode = preopening
commerce_cart_locked = 1
commerce_allow_testers = 1
```

Résultat public :

```text
- catalogue visible ;
- bannière visible ;
- ajout panier bloqué ;
- checkout bloqué.
```

Résultat testeur :

```text
- compte connecté avec ROLE_COMMERCE_TESTER ;
- ajout panier autorisé ;
- checkout autorisé ;
- commande de test possible.
```

### Mode maintenance production

```text
commerce_mode = maintenance
commerce_cart_locked = 1
commerce_allow_testers = 1
```

Usage : mise à jour de production avec validation interne avant réouverture publique.

### Mode fermé

```text
commerce_mode = closed
commerce_cart_locked = 1
```

Usage : fermeture manuelle temporaire des commandes.

## Commandes de vérification

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console dbal:run-sql "SELECT setting_key, value, field_type FROM hodina_setting ORDER BY setting_key"
php bin/console dbal:run-sql "SELECT id, email, created_at FROM launch_subscriber ORDER BY id DESC"
```

## Piège connu

Si `doctrine:schema:validate` propose uniquement :

```sql
DROP TABLE hodina_setting_backup_20260613;
```

ce n'est pas un écart applicatif. C'est seulement la table de sauvegarde créée manuellement avant injection des réglages. Elle peut être supprimée après validation.
---

# Remise à plat production — 14/15 juin 2026

## Contexte

La production historique n'était pas iso préproduction. Le domaine `hodina.fr` pointait vers la racine du projet `~/hodina.fr` au lieu de pointer vers `~/hodina.fr/public`. Le dossier de production n'était pas non plus un dépôt Git exploitable : `git status` retournait que le dossier n'était pas un dépôt.

Cette situation exposait un risque structurel : une application Symfony doit publier uniquement le dossier `public/`. La racine du projet contient des fichiers sensibles ou techniques (`.env`, `composer.json`, `src/`, `config/`, `migrations/`, `vendor/`) qui ne doivent pas être accessibles via le web.

## Décision prise

La décision retenue a été de ne pas bricoler l'ancienne production. La production a été remise à plat proprement :

```text
- sauvegarde complète de l'ancien dossier production ;
- correction du DocumentRoot o2switch vers /public ;
- remplacement de l'ancien dossier par un vrai clone Git ;
- déploiement de la branche J5J ;
- remplacement de la base production par un dump de recette ;
- nettoyage des données de test ;
- maintien du mode commerce en préouverture ;
- sécurisation HTTPS via public/.htaccess ;
- retrait de .env.local du suivi Git ;
- rotation des mots de passe et secrets après exposition accidentelle dans le terminal.
```

## Résultat validé

```text
https://hodina.fr/ fonctionne en HTTP 200.
http://hodina.fr/ redirige en 301 vers https://hodina.fr/.
http://www.hodina.fr/ redirige en 301 vers https://www.hodina.fr/.
.env.local est présent sur le serveur mais retiré de Git.
Doctrine migrations est à jour.
Doctrine schema validate est OK.
Le mode commerce est configuré en preopening.
Les commandes, items, logs SMS et adresses de test ont été nettoyés.
```

## Commandes et actions réalisées

### Sauvegarde fichiers production

```bash
cd ~
tar -czf backup_hodina_prod_files_$(date +%Y%m%d_%H%M%S).tar.gz hodina.fr
cp ~/hodina.fr/.htaccess ~/backup_htaccess_prod_root_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
cp ~/hodina.fr/public/.htaccess ~/backup_htaccess_prod_public_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
```

### Correction hébergement o2switch

Le DocumentRoot du domaine `hodina.fr` a été corrigé dans o2switch pour pointer vers :

```text
/home/vopu3712/hodina.fr/public
```

### Ancienne production conservée

```bash
cd ~
mv hodina.fr hodina.fr_old_$(date +%Y%m%d_%H%M%S)
```

Ancien dossier conservé observé :

```text
/home/vopu3712/hodina.fr_old_20260614_071905
```

### Nouveau clone Git production

```bash
git clone https://github.com/chahere/hodina.git hodina.fr
cd hodina.fr
git checkout pilot/j5j-commerce-mode-role-tester
```

### Récupération configuration production

```bash
cp ~/hodina.fr_old_20260614_071905/.env.local ~/hodina.fr/.env.local
cp ~/hodina.fr_old_20260614_071905/public/.htaccess ~/hodina.fr/public/.htaccess 2>/dev/null || true
```

### Installation production

```bash
composer install --no-dev --optimize-autoloader
```

Cette commande a modifié le dossier `vendor/` parce que le dépôt suivait encore certaines dépendances. Pour éviter de polluer les futurs pulls, `vendor/` a ensuite été restauré côté Git avec :

```bash
git restore vendor
```

## Base de données production

### Décision

La base production existante était désalignée avec l'historique Doctrine. Plutôt que de baseliner migration par migration, la décision a été de remplacer la base production par un dump de la recette, puis de nettoyer les données de test.

Cette option était la plus propre car la recette était déjà validée avec J5J.

### Sauvegarde production

Un backup de la base production a été créé avant remplacement :

```text
backup_prod_before_preprod_restore_20260614_073456.sql
```

### Dump recette

Un dump recette a été créé et vérifié :

```text
dump_recette_for_prod_20260614_074824.sql
Taille observée : 161K
```

### Import recette vers production

La base production a été vidée puis alimentée avec le dump recette. Après import, les tables attendues étaient présentes :

```text
address
category
customer
customer_order
customer_signup
delivery_commune
delivery_commune_connection
delivery_commune_neighbor
delivery_pricing_zone
delivery_zone
doctrine_migration_versions
hodina_setting
launch_subscriber
messenger_messages
order_item
product
product_image
seller
sms_log
```

### Correction MariaDB / Doctrine

La version MariaDB production observée est :

```text
11.4.12-MariaDB
```

Le `DATABASE_URL` production a été ajusté pour utiliser :

```text
serverVersion=mariadb-11.4.12&charset=utf8mb4
```

Les mots de passe et secrets ont ensuite été mis à jour. Aucun secret ne doit être stocké dans Git ou dans la documentation.

### Validation Doctrine après import

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --dump-sql
```

Résultat validé :

```text
Migrations exécutées : 27
Version courante : DoctrineMigrations\Version20260613130000
Nouvelle migration : 0
Mapping files are correct.
Database schema is in sync with the mapping files.
Nothing to update.
```

## Mode commerce production

La production a été forcée en mode préouverture :

```bash
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = 'preopening' WHERE setting_key = 'commerce_mode'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_cart_locked'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_allow_testers'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_email_capture_enabled'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '2026-06-30 18:00' WHERE setting_key = 'commerce_reopens_at'"
php bin/console cache:clear --env=prod
```

Valeurs validées :

```text
commerce_allow_testers = 1
commerce_cart_locked = 1
commerce_email_capture_enabled = 1
commerce_mode = preopening
commerce_reopens_at = 2026-06-30 18:00
```

## Nettoyage des données de test importées depuis recette

Volumes avant nettoyage :

```text
customer_order = 9
order_item = 16
launch_subscriber = 0
sms_log = 25
```

Nettoyage réalisé :

```bash
php bin/console dbal:run-sql "DELETE FROM sms_log"
php bin/console dbal:run-sql "DELETE FROM order_item"
php bin/console dbal:run-sql "DELETE FROM customer_order"
php bin/console dbal:run-sql "DELETE FROM address"
php bin/console cache:clear --env=prod
```

Résultat validé :

```text
customer_order = 0
order_item = 0
sms_log = 0
address = 0
```

Les comptes clients n'ont pas été supprimés automatiquement afin de conserver les comptes utiles admin, livreur et testeur.

## HTTPS production

La redirection HTTP vers HTTPS a été ajoutée dans :

```text
public/.htaccess
```

Règle appliquée :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Force HTTPS - o2switch / cPanel
    RewriteCond %{HTTP:X-Forwarded-Proto} !https
    RewriteCond %{HTTPS} !on
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Symfony front controller
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

Tests validés :

```text
http://hodina.fr/      → 301 vers https://hodina.fr/
https://hodina.fr/     → 200
http://www.hodina.fr/  → 301 vers https://www.hodina.fr/
https://www.hodina.fr/ → 200
```

Décision restante : choisir plus tard l'URL canonique entre `hodina.fr` et `www.hodina.fr`. Recommandation actuelle : `https://hodina.fr`.

## Git production

Commit créé depuis la production pour versionner la règle HTTPS et ignorer les fichiers locaux sensibles :

```text
028b7e5 chore: force HTTPS and ignore local production files
```

Actions réalisées :

```bash
git config user.name "chahere"
git config user.email "abdamayot@hotmail.fr"
git add .gitignore public/.htaccess
git commit -m "chore: force HTTPS and ignore local production files"
git push
```

`.env.local` a été retiré du suivi Git mais reste présent sur le serveur. Il doit rester hors dépôt.

Permission recommandée :

```bash
chmod 600 .env.local
```

## État final production

```text
Production remise à plat : OK
Production sous Git : OK
DocumentRoot vers /public : OK
Base production alignée avec recette : OK
Doctrine migrations : OK
Doctrine schema : OK
J5J mode commerce : OK
Mode preopening : OK
HTTPS forcé : OK
Données de test principales nettoyées : OK
Secrets et mots de passe mis à jour : OK
```

## Procédure de déploiement production à partir de maintenant

La production étant désormais un vrai clone Git, les prochains déploiements doivent suivre cette procédure :

```bash
cd ~/hodina.fr
git status --short -- . ':!vendor'
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
curl -I http://hodina.fr/
curl -I https://hodina.fr/
```

Important : ne jamais committer `.env.local`, les dumps SQL, les backups `.htaccess`, ni les modifications de `vendor/` générées par `composer install --no-dev`.

