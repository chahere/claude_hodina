# NOTES — Erreurs d'environnement local rencontrées (installation & exécution)

Date : 2026-07-07
Statut : journal de dépannage, à consulter avant toute installation/relance locale.
Contexte : mise en place d'un environnement local (Windows / PowerShell / MariaDB) sur la branche `claude/ai-chatbot-customer-account-1i5jbl` (lot J5AD), à partir d'un checkout du dépôt + `composer install` + dump de production.

Ce document liste chaque erreur réellement rencontrée, sa cause et son correctif. Le résumé opérationnel est dans `CLAUDE.md` (racine).

---

## 1. `.env` — byte-order-mark (BOM) refusé

**Symptôme**
```
Symfony\Component\Dotenv\Exception\FormatException:
Loading files starting with a byte-order-mark (BOM) is not supported.
```

**Cause** : sous Windows PowerShell, `Out-File -Encoding utf8` écrit un BOM en tête de fichier. Symfony Dotenv le refuse.

**Correctif** : écrire le `.env` en UTF-8 **sans** BOM.
```powershell
# Racine du projet
$envContent = @'
APP_ENV=dev
APP_SECRET=<hex 32 chars>
DATABASE_URL="mysql://USER:PASS@127.0.0.1:3306/claude_hodina?serverVersion=mariadb-10.11.0&charset=utf8mb4"
MAILER_DSN=null://null
MESSENGER_TRANSPORT_DSN=sync://
DEFAULT_URI=http://localhost:8000
'@
[System.IO.File]::WriteAllText("$PWD\.env", $envContent, (New-Object System.Text.UTF8Encoding $false))
```

**À retenir** : Symfony a besoin d'un fichier `.env` (pas seulement `.env.local`). Ces fichiers ne sont pas versionnés dans ce dépôt.

---

## 2. Chaîne de migrations bancale sur base neuve

**Symptôme (successivement, à chaque tentative)**
```
Unknown column 'order_reference_date' in 'customer_order'        (Version20260604095052)
Duplicate column name 'delivered_communes'                       (Version20260604113406)
```
puis, après migration complète, `doctrine:schema:validate` : *« The database schema is not in sync »* (4 écarts).

**Cause** : deux migrations de juin avaient un ordre/contenu incohérent (générées après coup mais avec un timestamp antérieur à la migration qui crée réellement les colonnes), plus des commentaires `DC2Type` résiduels et un index à renommer.

**Correctif (dans le dépôt, migrations rendues défensives — jamais de reset de base)**
- `Version20260604095052` : gardes `columnExists()`/`indexExists()`/`tableExists()`, no-op si la cible n'existe pas encore.
- `Version20260604113406` : garde `columnExists()` (doublon strict de `Version20260604112000`).
- `Version20260706210000` (nouvelle) : normalisation résiduelle en fin de chaîne (retrait des commentaires `DC2Type`, renommage de l'index `order_reference`), no-op en production.
- `SupportTicketMessage` : ajout de l'index `IDX_SUPPORT_TICKET_MESSAGE_AUTHOR` déclaré sur l'entité (il existait en base mais pas dans le mapping).

**Résultat** : sur base neuve, `doctrine:migrations:migrate` va jusqu'à `Version20260706180000`+, et `schema:validate` est vert. Les migrations défensives affichent « executed but did not result in any SQL statements » — c'est normal.

**Règle** : on ne fait **pas** `doctrine:database:drop`. La migration corrective répare l'existant.

---

## 3. `composer require` — réinstallation partielle + recettes Flex

**Symptôme** : après `git stash -u`, un `composer.json` vide a été créé, entraînant la **désinstallation de 121 paquets** ; `bin/console` a disparu.

**Cause** : `git stash -u` embarque les fichiers **non suivis** — or dans ce dépôt `composer.json`, `composer.lock`, `.env`, `bin/console` ne sont pas versionnés. Le stash les a retirés du working tree ; `composer` a alors généré un `composer.json` vide.

**Correctif**
```powershell
# Lister le contenu "untracked" du stash
git ls-tree -r --name-only "stash@{0}^3"
# Restaurer les fichiers d'install un par un
git restore --source="stash@{0}^3" -- composer.json composer.lock .env bin
# Réinstaller
composer install
```

**À retenir** : prudence avec `git stash -u`. Les fichiers d'install ne sont pas dans git.

---

## 4. `composer require` régénère `importmap.php` (→ EasyAdmin 500)

**Symptôme**
```
InvalidArgumentException / RuntimeError :
The entrypoint "admin" does not exist in importmap.php
(@EasyAdmin/includes/_importmap.html.twig)
```
HTTP 500 sur `/ouegnewe` après un `composer require`.

**Cause** : `composer require symfony/rate-limiter symfony/http-client` a fait rejouer ~30 recettes Flex, qui ont **régénéré `importmap.php` au défaut AssetMapper**, supprimant l'entrée `admin` du projet. `DashboardController::configureAssets()` appelle `->addAssetMapperEntry('admin')` → `importmap('admin')` → entrée absente → exception. `importmap.php` n'était pas versionné, donc l'entrée custom était perdue.

**Ce qu'il NE fallait PAS faire** : retirer `->addAssetMapperEntry('admin')`. `assets/admin.js` est un vrai fichier (contrôleurs Stimulus stock / images produit / créneaux + menu backoffice repliable) ; le retirer aurait cassé ces fonctions.

**Correctif** : restaurer l'entrée `admin` et **versionner `importmap.php`** (commit `aa67cb5`) :
```php
return [
    'app'   => ['path' => './assets/app.js',   'entrypoint' => true],
    'admin' => ['path' => './assets/admin.js', 'entrypoint' => true],
    '@hotwired/stimulus'        => ['version' => '3.2.2'],
    '@symfony/stimulus-bundle'  => ['path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    '@hotwired/turbo'           => ['version' => '8.0.23'],
];
```

**À retenir** : après tout `composer require`, revérifier `importmap.php` (entrée `admin` présente) et la fin de `.env` (blocs `###> … ###` ajoutés en double, `DATABASE_URL` postgres par défaut à retirer).

---

## 5. `cache:clear` — dépassement mémoire (OOM Twig)

**Symptôme**
```
PHP Fatal error: Allowed memory size of 134217728 bytes exhausted
in vendor\twig\twig\src\Compiler.php
```

**Cause** : la limite PHP par défaut (128 Mo) est trop juste pour le réchauffage complet du cache Twig en dev.

**Correctif** : séparer purge et réchauffage, mémoire illimitée.
```powershell
# Racine du projet
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```
(Alternative ponctuelle : `php -d memory_limit=512M bin/console cache:clear`.)

---

## 6. Lancement du serveur — routeur PHP intégré incompatible

**Symptôme**
```
TypeError: Invalid return value: callable object expected, "int" returned
from "…\public\css\style_mobile.css"   (et images, favicon, uploads…)
```
Tous les assets renvoient 500 → page sans styles.

**Cause** : `php -S 0.0.0.0:8001 -t public .\public\index.php` utilise `index.php` comme **script routeur**. Symfony Runtime fait `require SCRIPT_FILENAME` ; pour une requête d'asset, `SCRIPT_FILENAME` pointe le fichier statique (CSS…), que PHP « require » comme du PHP → retour `int(1)` → exception.

**Correctif** : utiliser le serveur Symfony CLI (sert les statiques + route correctement).
```powershell
symfony server:stop
symfony server:start --no-tls
```
Le `php -S … public/index.php` n'est pas compatible avec Symfony Runtime.

---

## 7. « Portail bleu » — CSS de démo AssetMapper

**Symptôme** : fond bleu ciel sur tout le portail public (identique Edge + Firefox), absent en recette.

**Cause** : `assets/styles/app.css` contenait la règle de démo Symfony `body { background-color: skyblue; }`, importée par `assets/app.js` et chargée via `importmap('app')`. Elle écrase le fond blanc de `public/css/style_mobile.css`.

**Correctif (commit `3aa8205`)** : neutraliser `assets/styles/app.css` (commentaire uniquement, aucun `body { background }`).

**À retenir** : pour un souci de couleur/CSS, chercher **aussi dans `assets/`** (source AssetMapper), pas seulement `public/css/` et `templates/`.

---

## 8. Divers (données & affichage)

- **Bannière « Préouverture »** sur base neuve : normal — `commerce_mode` est initialisé à `preopening` par `Version20260613130000`. Passer à « Ouvert » dans EasyAdmin > Réglages > Commerce & commandes.
- **Accents cassés (« L├®gumes »)** : encodage du dump. Réimporter avec `mysql --default-character-set=utf8mb4 …`, ou vérifier `HEX(name)` pour distinguer donnée saine (`C3A9`) vs double-encodage.
- **31 fichiers `.bak`/`.bak.bak`** supprimés du dépôt (commit dédié) : dette pré-existante, hors lot.

---

## Séquence d'installation locale de référence (sans reset de base)

```powershell
# 1. Dépendances (dans D:\...\claude_hodina, composer.json présent)
composer install
composer require symfony/rate-limiter symfony/http-client   # si absents

# 2. .env sans BOM (cf. §1) — adapter DATABASE_URL

# 3. Base (créer si absente ; NE PAS drop une base existante)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate

# 4. Cache (mémoire-safe, cf. §5)
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup

# 5. Vérifs assets (cf. §4)
php -d memory_limit=-1 bin/console debug:asset-map | findstr admin

# 6. Admin + serveur (cf. §6)
php bin/console app:create-admin admin@hodina.fr --password=ChangeMoi123!
symfony server:start --no-tls
```
