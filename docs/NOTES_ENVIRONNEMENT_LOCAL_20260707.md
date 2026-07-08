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

**Correctif** : deux options valides (les deux servent statiques **et** routes).
```powershell
# Option 1 : serveur Symfony CLI
symfony server:stop
symfony server:start --no-tls

# Option 2 : serveur PHP intégré AVEC choix du port, SANS routeur
php -S 127.0.0.1:8002 -t public
```
Le `php -S … public/index.php` (avec `public/index.php` en fin de ligne) n'est **pas** compatible avec Symfony Runtime : c'est ce `public/index.php` en argument routeur qui casse les assets, pas le `php -S` en soi.

---

## 6bis. Backoffice EasyAdmin non stylé (« rond bleu ») malgré le CSS public OK

**Symptôme** : le CSS public (`/css/style_mobile.css`) et l'entrée AssetMapper `admin` chargent en 200, mais `/ouegnewe/dashboard` s'affiche nu (titre en lien bleu souligné, icône compte SVG géante = « rond bleu »). Dans les logs du serveur :
```
[404]: GET /bundles/easyadmin/app.fe563759.css - No such file or directory
[404]: GET /bundles/easyadmin/app.dd0f3718.js  - No such file or directory
[404]: GET /bundles/easyadmin/page-color-scheme.75224563.js
```

**Cause** : les assets **propres au bundle EasyAdmin** vivent dans `public/bundles/easyadmin/` (fichiers hashés). Après un `composer install`/`update`, la version d'EasyAdmin change → les templates réclament de nouveaux hashs (`app.fe563759.css`) alors que `public/bundles/` contient les anciens (ou rien). D'où les 404 → aucun style EasyAdmin.

À ne pas confondre avec l'entrée AssetMapper `admin` (§4), qui, elle, concerne `assets/admin.js` (Stimulus) et charge bien ici.

**Correctif** : republier les assets des bundles vers `public/bundles/`.
```powershell
php bin/console assets:install public
```
Puis `Ctrl+F5`. (`public/bundles/` est normalement régénérable ; en général on le `gitignore`.)

---

## 7. « Portail bleu » — CSS de démo AssetMapper

**Symptôme** : fond bleu ciel sur tout le portail public (identique Edge + Firefox), absent en recette.

**Cause** : `assets/styles/app.css` contenait la règle de démo Symfony `body { background-color: skyblue; }`, importée par `assets/app.js` et chargée via `importmap('app')`. Elle écrase le fond blanc de `public/css/style_mobile.css`.

**Correctif (commit `3aa8205`)** : neutraliser `assets/styles/app.css` (commentaire uniquement, aucun `body { background }`).

**À retenir** : pour un souci de couleur/CSS, chercher **aussi dans `assets/`** (source AssetMapper), pas seulement `public/css/` et `templates/`.

---

## 8. Divers (données & affichage)

- **Bannière « Préouverture »** sur base neuve : normal — `commerce_mode` est initialisé à `preopening` par `Version20260613130000`. Passer à « Ouvert » dans EasyAdmin > Réglages > Commerce & commandes.
- **Accents cassés (« L├®gumes », « ├á »)** : encodage du **transfert**, pas la base — voir §9 pour la cause (le `>` de PowerShell) et le correctif complet. Contrôle rapide : `HEX(name)` → `C3A9`/`C3A0` sains vs double-encodage.
- **31 fichiers `.bak`/`.bak.bak`** supprimés du dépôt (commit dédié) : dette pré-existante, hors lot.

---

## 9. Copier des données entre bases (dump / import) — encodage cassé

**Contexte** : recopier les données d'une base locale vers une autre (ex. `hodina_db` → `claude_hodina`) via `mariadb-dump` puis import.

**Symptôme A — accents corrompus après import** : `Préfixe` → `Pr├®fixe`, `Cannes à sucre` → `Cannes ├á sucre`, identique Edge/Firefox (la donnée elle-même est cassée en base). Motif **CP850** : `é` (UTF-8 `C3 A9`) relu `├`(C3)+`®`(A9) ; `à` (UTF-8 `C3 A0`) relu `├`(C3)+`á`(A0).

**Symptôme B — import refusé** :
```
ERROR: ASCII '\0' appeared in the statement ... Query: '??/'.
```
`??` (octets `FF FE`) = BOM UTF-16 LE, `\0` partout = fichier en UTF-16.

**Cause commune : la redirection `>` de PowerShell.** Sous Windows PowerShell 5.1, `mariadb-dump … > fichier.sql` **réencode** la sortie (UTF-16, ou transcodage console CP850/CP1252) au lieu de garder l'UTF-8 produit par mariadb-dump. Le drapeau `--default-character-set` ne rattrape rien si le fichier est déjà réencodé.

**Correctif : ne jamais dumper via le `>` de PowerShell.** Faire écrire le fichier par mariadb-dump (`--result-file`, mode binaire) et importer avec `cmd /c` (le `<` de cmd = octets bruts) + charset explicite des **deux** côtés.
```powershell
# Dump : --result-file (surtout PAS de > PowerShell)
mariadb-dump -u root -p --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 --result-file=..\hodina_clean.sql hodina_db

# Verifier le fichier : pas de BOM UTF-16 + accents UTF-8 presents (if/else sur UNE ligne)
$b = [System.IO.File]::ReadAllBytes("D:\hodina\hodina_clean.sql"); $utf16 = ($b[0] -eq 0xFF -and $b[1] -eq 0xFE); $sql = [System.IO.File]::ReadAllText("D:\hodina\hodina_clean.sql", [System.Text.Encoding]::UTF8); $e = [char]0xE9
if ($utf16) { 'ECHEC UTF-16' } elseif ($sql.Contains("L" + $e + "gumes")) { 'DUMP SAIN' } else { 'ACCENTS SUSPECTS a la source' }

# Import : cmd /c pour la redirection < + charset explicite
cmd /c "mariadb -u root -p --default-character-set=utf8mb4 claude_hodina < ..\hodina_clean.sql"
```

**Prouver en base (octets réels)** : `SELECT name, HEX(name) FROM product WHERE name LIKE '%sucre%';` → `…C3A0…` (`à`) = sain ; `…C383C2A0…` = double-encodage latin1 ; `E0` isolé = colonne latin1.

**À retenir** : fichier écrit par `--result-file` (jamais `>`), `--default-character-set=utf8mb4` au dump ET à l'import.

---

## 10. Réaligner `doctrine_migration_versions` après import d'un dump de prod

**Symptôme** : après import du dump de prod dans la base locale, `doctrine:migrations:migrate` échoue :
```
Migration DoctrineMigrations\Version20260706120000 failed ... Table 'faq_entry' already exists
```
alors que `doctrine:schema:validate` est **vert**.

**Cause** : le dump de prod contient sa propre `doctrine_migration_versions`, qui **remplace** celle de la base locale. Or la prod n'a pas encore le lot en cours (ici J5AD) : ses migrations n'y figurent donc pas. Mais les tables du lot (`faq_entry`, `ai_chatbot_setting`…) n'étant **pas** dans le dump de prod, elles ne sont ni droppées ni recréées — elles **restent** en place. Doctrine croit alors ces migrations « à faire » et bute sur des tables existantes.

**Correctif (jamais de drop)** : marquer les migrations concernées comme déjà appliquées, sans les rejouer.
```powershell
php bin/console doctrine:migrations:list   # reperer les « not migrated » dont les objets existent deja
php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260706120000' --add --no-interaction
php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260706150000' --add --no-interaction
php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260706180000' --add --no-interaction
php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260706210000' --add --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction   # -> « Already at the latest version »
php bin/console doctrine:schema:validate                       # -> vert
```
`migrations:version --add` ne modifie **que** la table de suivi, aucune table métier. **Ne pas** supprimer les tables du lot pour « laisser la migration les recréer » : perte des données du lot (tickets support, réglages IA, clé API chiffrée) + violation de la règle « jamais de drop ».

**Effet de bord** : les tables **partagées** (ex. `hodina_setting`) sont, elles, écrasées par la prod → les seeds du lot en cours disparaissent (ici le flag `ai_chatbot_enabled`). Les réinsérer manuellement (cf. §11).

---

## 11. Insérer de l'accentué en SQL sans le corrompre (`db:run-sql` + `UNHEX`)

**Contexte** : réinsérer en local une ligne perdue (ex. flag `ai_chatbot_enabled` de `hodina_setting`, effacé par l'import prod — cf. §10).

**Pièges** :
- La commande d'exécution SQL de DoctrineBundle est **`db:run-sql`**. `doctrine:query:sql` **n'existe pas** (la console propose `doctrine:query:dql` — refuser).
- Taper un accent (`é`) dans une commande PowerShell le fait transiter par l'ANSI console (CP1252/CP850) → il arrive faux en base (même bug que §9).

**Correctif** : injecter l'accent par ses **octets UTF-8** au lieu de le taper, et rendre l'insert idempotent.
```powershell
php bin/console db:run-sql "INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'ai_chatbot_enabled', CONCAT('Chatbot IA activ', CONVERT(UNHEX('C3A9') USING utf8mb4)), '0', 'Active ou desactive le chatbot IA pour les clients connectes.', 'boolean', 'technical', 'Technique / maintenance', 900, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'ai_chatbot_enabled')"

# Preuve par les octets : label_hex doit finir par C3A9 (« activ » + e-accent)
php bin/console db:run-sql "SELECT setting_key, value, HEX(label) AS label_hex FROM hodina_setting WHERE setting_key = 'ai_chatbot_enabled'"
```
`CONVERT(UNHEX('C3A9') USING utf8mb4)` = `é` ; `UNHEX('C3A0')` = `à`, etc.

**Alternative « accents parfaits »** (label ET help complets) : si le texte accentué vient d'un fichier de migration, jouer la migration de seed elle-même (idempotente, texte lu depuis le fichier UTF-8, zéro passage console) :
```powershell
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260706180000' --up --no-interaction
```
Cette variante **marque aussi** la migration comme appliquée → ne pas la cumuler avec `--add` sur la même version (sinon « already exists » en fin de course).

---

## 12. Action CRUD custom EasyAdmin : `AdminContext::getEntity()` hors contexte CRUD

**Contexte** : lot J5AF (correctif suppression pilote + anonymisation client). Clic sur « Supprimer pilote » → 500 dès la première ligne de la méthode, avant même le code métier.

**Symptôme** :
```
LogicException: Cannot get entity outside of a CRUD context. Check if getCrud() returns a value before calling getEntity().
  at vendor\easycorp\easyadmin-bundle\src\Context\AdminContext.php:81
  at EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext->getEntity()
    (src\Controller\Admin\CustomerCrudController.php:240)
```
L'URL contient pourtant bien `crudAction=confirmPilotCascadeDelete&crudControllerFqcn=...&entityId=...` — le contexte CRUD ne se construit malgré tout pas correctement pour cette action custom sur la version d'EasyAdminBundle installée (le projet a déjà changé de version plusieurs fois, cf. piège n°7 / §7 ci-dessus).

**Piège** : le pattern `$customer = $context->getEntity()?->getInstance();` (utilisé dans `confirmPilotCascadeDelete`, `confirmAnonymize`, `generatePasswordResetLink` de `CustomerCrudController`) suppose que `AdminContext` est toujours correctement peuplé dans une action `linkToCrudAction`. Le `?->` (nullsafe) ne protège que si `getEntity()` retourne `null` — pas si la méthode **lève une exception avant même de retourner**, ce qui est le cas ici.

**Correctif** : ne pas dépendre du contexte CRUD d'EasyAdmin pour charger l'entité dans une action custom. Charger directement depuis `entityId` (query string, présent dans l'URL que l'action ait été ouverte en GET ou soumise en POST — un `<form method="post">` sans attribut `action` soumet vers l'URL courante, donc conserve la query string) via l'`EntityManagerInterface` :
```php
private function findCustomerFromRequest(Request $request, EntityManagerInterface $entityManager): ?Customer
{
    $entityId = $request->query->get('entityId');

    if ($entityId === null || $entityId === '') {
        return null;
    }

    $customer = $entityManager->getRepository(Customer::class)->find($entityId);

    return $customer instanceof Customer ? $customer : null;
}
```
Remplace `AdminContext $context` par `EntityManagerInterface $entityManager` dans la signature de chaque action custom concernée.

**Non détecté avant livraison** : ce bug n'a pas pu être repéré par relecture de code dans le sandbox Claude Code (pas de `vendor/`, pas de serveur, impossible d'exécuter réellement une action EasyAdmin). Cf. règle ajoutée dans `CLAUDE.md` § Règles non négociables : dire explicitement quand un correctif EasyAdmin/Symfony n'a pas pu être exécuté en conditions réelles, plutôt que de donner des commandes de test comme si c'était déjà validé.

---

## 13. Migration défensive « 0 sql queries » + `schema:validate` rouge sur une colonne pourtant créée

**Contexte** : lot J5AF, migration `Version20260708120000` (ajoute `customer.is_active` et `customer.anonymized_at`). Après `doctrine:migrations:migrate --no-interaction`, le message laissait croire que rien n'avait été appliqué, alors que `schema:validate` échouait juste après — les deux signaux semblaient se contredire.

**Symptôme** :
```
php bin/console doctrine:migrations:migrate --no-interaction
[notice] Migrating up to DoctrineMigrations\Version20260708120000
[warning] Migration DoctrineMigrations\Version20260708120000 was executed but did not result in any SQL statements.
[notice] finished in 144.4ms, used 26M memory, 1 migrations executed, 0 sql queries
[OK] Successfully migrated to version: DoctrineMigrations\Version20260708120000

php bin/console doctrine:schema:validate
[OK] The mapping files are correct.
[ERROR] The database schema is not in sync with the current mapping file.

php bin/console doctrine:schema:update --dump-sql
ALTER TABLE customer CHANGE is_active is_active TINYINT NOT NULL;
```

**Piège (double)** :
1. Le compteur « N sql queries » de Doctrine Migrations ne suit que les instructions passées par `$this->addSql(...)`. Les migrations défensives de ce projet (conditionnelles — `if (!$this->columnExists(...))` — cf. `Version20260703093000`, `Version20260708120000`) exécutent leurs `ALTER`/`CREATE` via `$this->connection->executeStatement(...)` directement, pour pouvoir les entourer d'une condition. Ces instructions **s'exécutent réellement contre la base**, mais restent invisibles pour ce compteur : « 0 sql queries » signifie seulement « rien n'est passé par `addSql()` », pas « rien ne s'est passé ». Ne jamais conclure de l'état réel d'une migration défensive à partir de ce seul message — se fier à `schema:validate` ou à une lecture directe d'`information_schema`.
2. La colonne avait bien été créée par l'`ALTER TABLE customer ADD is_active TINYINT(1) NOT NULL DEFAULT 1` de la migration, mais avec une largeur d'affichage `(1)` et un `DEFAULT 1` que le mapping Doctrine de l'entité n'attend pas : `#[ORM\Column] private bool $isActive = true;` ne déclare aucune `options` de colonne, donc le SQL canonique attendu par Doctrine pour ce booléen est `TINYINT NOT NULL` — sans largeur ni `DEFAULT` (la valeur par défaut `true` n'existe que côté PHP, à la construction de l'entité ; comme toute création de `Customer` passe par l'ORM, un `DEFAULT` SQL n'est pas nécessaire). D'où le `CHANGE` (et non un `ADD`) proposé par `schema:update --dump-sql` : la colonne existe bel et bien, seule sa définition exacte diverge du mapping.

**Correctif** : migration corrective dédiée (`Version20260708130000`), défensive (vérifie le type et le `DEFAULT` réels via `information_schema` avant d'agir, no-op si déjà normalisé) :
```php
$this->connection->executeStatement('ALTER TABLE customer CHANGE is_active is_active TINYINT NOT NULL');
```
Ne jamais lancer `doctrine:schema:update --force` pour « corriger vite » : ça applique le changement hors du suivi des migrations, contraire à la règle « nouvelle migration pour tout changement de schéma » (`CLAUDE.md`). `schema:update --dump-sql` reste un outil de **diagnostic** (il calcule le SQL exact attendu par le mapping) — jamais un outil de correction directe.

**À vérifier après exécution** : `doctrine:schema:validate` doit passer entièrement au vert (mapping **et** base) une fois cette migration corrective jouée.

---

## Séquence d'installation locale de référence (sans reset de base)

> **Recopier des données de prod en local** : dumper/importer selon §9 (jamais le `>` de PowerShell), puis — si l'import a écrasé `doctrine_migration_versions` — réaligner le suivi selon §10 avant de relancer `migrations:migrate`, et réinsérer les seeds du lot en cours perdus (§11).

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
