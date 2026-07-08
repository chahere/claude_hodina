# CLAUDE.md — Guide de travail Hodina (à lire en premier)

Hodina est un pilote e-commerce Symfony 8 / PHP 8.2+ pour Mayotte (marketplace locale de courses reliant vendeurs locaux et clients, livraison à domicile). Mobile-first / PWA, hébergée sur o2switch. Trois environnements séparés : **local, recette, production**.

Le code, la documentation, les libellés d'interface et les messages métier sont **tous en français** — garde cette langue dans les chaînes visibles par l'utilisateur, les textes d'aide des entités et les messages de commit.

Stack : Symfony 8.x, Doctrine ORM, **MariaDB**, EasyAdmin 4, Twig, AssetMapper (pas de npm/webpack — les assets sont compilés par PHP). Développement local sous Windows (PowerShell/Git Bash) ; recette/prod sur l'hébergement mutualisé o2switch.

Ce fichier est le point d'entrée. Il complète — sans les répéter — les règles de développement détaillées du skill.

## Ordre de lecture

1. **Ce fichier** — règles dures, architecture, pièges d'environnement (ce qui casse en pratique).
2. `.claude/skills/hodina-core/SKILL.md` — règles de dev détaillées (architecture, zones sensibles, livraison, EasyAdmin, doc, review).
3. `docs/` : voir § Documentation ci-dessous pour le rôle de chaque fichier.
4. Détail des pièges d'installation/exécution : `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md`.

## Règles non négociables

- **Jamais `git add .`** — archives, patchs et fichiers parasites fréquents. Toujours un `git add` sélectif, fichier par fichier.
- **Un commit = une préoccupation.** Ne pas mélanger les lots dans un même commit.
- **Ne pas toucher aux lots fermés** (J5Z / J5AB / J5AC / J5AA…) hors intégration minimale explicitement autorisée.
- **JAMAIS `doctrine:database:drop` ni écraser une base contenant des données** (règle utilisateur posée le 2026-07-06). Toute correction de schéma passe par une **migration défensive**, jamais par un reset de base.
- **Ne jamais modifier une migration déjà jouée en recette/prod** sans demande explicite → nouvelle migration corrective à la place.
- Nouvelle migration pour tout changement de schéma.
- Toujours finir une réponse par : commandes de test à lancer + risques de régression.
- **Ne jamais présenter des commandes de test comme une validation déjà faite si le sandbox ne permet pas de les exécuter réellement** (pas de `vendor/`, pas de `bin/console`, pas de serveur — voir § Environnement d'exécution). Le dire explicitement : « non testé en conditions réelles, voici comment le vérifier » — pas juste donner les commandes en laissant croire que c'est validé. Incident du 2026-07-08 : une action EasyAdmin custom livrée sans avoir pu être exécutée a cassé au premier clic réel (`AdminContext::getEntity()` hors contexte CRUD, piège n°11) — jamais détecté par lecture de code seule.
- **Avant toute checklist spécifique à un lot en recette/prod, dérouler d'abord la checklist minimale** (`docs/DEPLOIEMENT_PREPROD.md` § Checklist minimale) : catalogue, inscription, connexion d'un client existant, panier/checkout/commande, backoffice, portail livreur. Ne pas continuer sur les tests du lot si un point de cette checklist échoue — le client doit pouvoir commander sans friction avant tout le reste.

## Architecture

### La logique métier vit dans les Services, jamais dans les contrôleurs ni Twig

C'est la règle centrale du code. Les contrôleurs (y compris les contrôleurs CRUD EasyAdmin) et les templates orchestrent ; ils ne doivent pas recalculer prix, marges, frais de livraison ou transitions de commande. Les trois piliers de `src/Service/` :

- **`CustomerOrderWorkflowService`** — la source unique de vérité pour les transitions de statut, les dates métier, l'affectation du livreur et la création des SmsLog. `Admin\CustomerOrderCrudController` et `Courier\CourierDashboardController` l'appellent tous les deux — ne pas dupliquer la logique de transition. Flux des statuts : `PENDING_VALIDATION → CONFIRMED → PREPARING → READY_FOR_PICKUP → OUT_FOR_DELIVERY → DELIVERED` (+ `CANCELED`).
- **`ProductPricingService`** — calcule le prix client à partir de `producerPrice` + marge effective. Priorité des marges : `Product.marginRate` → `Seller.marginRate` → `HodinaSetting.global_margin_rate`. `Product.price` est hérité (legacy) — ne pas l'utiliser comme source de vérité. Le prix client est toujours *calculé* ; les vendeurs ne saisissent jamais que `producerPrice`.
- **`DeliveryLogisticsService`** — calcule les frais de livraison, la rémunération du livreur et la marge à partir du graphe des communes. Renvoie un DTO `CartLogisticsPreview` (`src/Dto/`) au panier ; rien n'est persisté ici.

### Pattern de figeage au checkout (freeze-at-checkout)

Tout ce qui touche au prix ou à la livraison est calculé dynamiquement pour l'aperçu panier mais **figé** (snapshot) sur `OrderItem` / `CustomerOrder` au checkout, afin qu'une commande passée ne change jamais quand un admin modifie plus tard une marge, un frais ou une relation entre communes. Quand tu touches au pricing/à la livraison, distingue toujours ce qui est dynamique de ce qui est figé (ligne de commande : `producerUnitPrice`, `appliedMarginRate`, `hodinaMarginAmount`, `unitPrice`, `lineTotal` ; snapshot livraison de la commande : `deliveryFee`, `courierPayout`, `requiresBarge`, détails du trajet/de la zone).

### Domaine logistique de livraison (spécifique à Mayotte)

- Le **territoire** est `PT` (Petite-Terre) ou `GT` (Grande-Terre) sur `DeliveryCommune`.
- **Règle barge (invariant) :** `requiresBarge = clientTerritory !== sellerTerritory` — *rien d'autre*. Ne jamais déclencher la barge à partir de la distance, d'un statut non-voisin ou d'un chemin manquant. Une livraison sur le même territoire ne prend jamais la barge, même si les communes sont éloignées.
- Le routage utilise un graphe de `DeliveryCommune` + arêtes typées `DeliveryCommuneConnection` (`LAND`/`BARGE`), parcouru en **BFS** (poids de saut égaux ; Dijkstra/GPS reportés après le pilote). Les données du graphe sont seedées/administrées en base — ne jamais lire le fichier Excel source au runtime.
- La validation de l'adresse de livraison passe par `DeliveryCommuneMatcherService` + `DeliverableAddressValidator` ; seule l'adresse de LIVRAISON (jamais la FACTURATION) influence la logistique.

### Authentification, rôles et URL obscurcies

L'unique entité authentifiable est **`Customer`** (il n'y a pas d'entité `User`). Les rôles distinguent les comportements : `ROLE_ADMIN`, `ROLE_COURIER` (le livreur est un Customer avec ce rôle), `ROLE_USER` (client), plus `ROLE_SELLER` (futur portail) et `ROLE_COMMERCE_TESTER`. Les préfixes de route sont volontairement obscurcis (voir `config/packages/security.yaml` `access_control`) :

- `/ouegnewe` → backoffice EasyAdmin (`ROLE_ADMIN`)
- `/djama` → dashboard livreur (`ROLE_COURIER`)
- `/mon-compte` → portail client (`ROLE_USER`)
- `/caribou` (inscription), `/hodi`, `/lawa` → public

Tout blocage panier/checkout/préouverture doit être appliqué **côté serveur, dans le contrôleur/service** — un bouton Twig désactivé ou une vérification côté client ne suffit jamais.

### Réglages et pistes d'audit

- **`HodinaSetting`** = comportement éditable par l'admin, une ligne par paramètre (`settingKey` / `value` / `fieldType`). Le champ technique `field_type` ne doit pas être affiché lors de l'édition d'un réglage système existant. Les réglages sont exposés via des contrôleurs CRUD groupés par domaine (`HodinaSettingCommerce/Email/General/Logistics/Notifications/Payments/Technical`).
- **`SmsLog`** — les SMS sont simulés/journalisés (`LogSmsSender`, câblé dans `services.yaml`) ; l'admin envoie manuellement via l'app Messages de l'iPhone. **`EmailLog`** enregistre les SENT/FAILED SMTP. Un échec commande/e-mail/SMS ne doit jamais bloquer la création de la commande.
- Fuseau horaire : la base stocke l'**UTC** ; les pages et communications affichent `Indian/Mayotte` (paramètre `app.local_timezone`).

## Migrations (sécurité de déploiement)

Les migrations doivent s'exécuter proprement sur une base qui n'a pas tes changements dev manuels, dans un ordre strict par timestamp :

- Une migration **corrective** doit avoir un timestamp **postérieur** à la migration qu'elle corrige, et les tables/colonnes doivent être créées avant d'être modifiées/utilisées.
- Écris des migrations **défensives** (vérifier l'existence de la colonne/table avant l'ajout) — plusieurs migrations existantes le font, cf. pièges n°6 et n°12 ci-dessous.
- Ne jamais utiliser `doctrine:schema:update --force` ; corrige la dérive de schéma par une nouvelle migration versionnée. Utilise `--dump-sql` uniquement pour diagnostiquer.

## Commandes utiles (local uniquement — voir § Environnement d'exécution)

```bash
# Dépendances
composer install

# Lancer l'application (dev) — voir piège n°1 pour la mise en garde sur php -S
symfony server:start --no-tls

# Tests (PHPUnit 12, amorce tests/bootstrap.php, force APP_ENV=test)
php bin/phpunit
php bin/phpunit tests/Controller/HomeControllerTest.php
php bin/phpunit --filter testLoginPageLoads

# Vérifier la syntaxe d'un fichier (utile avant de livrer migrations/services, et seule commande possible en sandbox)
php -l src/Service/SomeService.php

# Doctrine
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --dump-sql   # DIAGNOSTIC UNIQUEMENT — ne jamais lancer --force

# Santé du conteneur / de la config
php bin/console lint:container
php bin/console cache:clear   # voir piège n°2 pour la variante mémoire-safe

# Assets de production (AssetMapper)
php bin/console asset-map:compile
```

Ajoute `--env=prod` aux commandes console pour valider contre la base recette/prod.

### Scripts d'assertion (acceptance)

Les `tools/assert-*.php` sont des scripts d'acceptation autonomes (un par fonctionnalité/jalon, ex. `assert-j5x-b-delivery-schedules.php`). Ils recherchent des symboles/chaînes attendus dans les sources et sortent en code non nul en cas de dérive. Lance-les avec `php tools/assert-<nom>.php`. Quand tu modifies une fonctionnalité couverte par l'un d'eux, garde ses assertions vertes (ou mets à jour le script).

### Déploiement

Déploiement par tag Git uniquement, via `tools/deploy-hodina-by-tag.sh` (le tag doit être atteignable depuis `origin/main` — jamais `develop` seul ; sauvegarde la base, exécute les migrations, compile les assets). Voir son en-tête pour l'usage recette vs prod, et `docs/DEPLOIEMENT_PREPROD.md` pour l'historique complet des déploiements.

## Préférences de communication (utilisateur)

- **Répondre en français.**
- **Toujours donner les commandes avec leur contexte** : le dossier où l'exécuter + ce qu'elle fait.
- **Commandes en un seul bloc copiable**, commençant par `cd D:\hodina\claude_hodina` (ou `D:\hodina\hodina.fr` selon le dépôt concerné), une commande par ligne. Après un `git pull` ou tout changement de code/config/réglage compilé, **inclure la régénération du cache** en mode mémoire-safe (voir piège n°2) :
  ```powershell
  cd D:\hodina\claude_hodina
  git pull origin <branche>
  php -d memory_limit=-1 bin/console cache:clear --no-warmup
  php -d memory_limit=-1 bin/console cache:warmup
  ```
- Pour tout ce qui touche au cache Symfony, utiliser la variante mémoire-safe (voir piège n°2). La commande exacte est `cache:clear --no-warmup` puis `cache:warmup` (`cache:cache` n'existe pas).
- Diagnostiquer et **prouver contre les fichiers réels** avant de corriger. Ne pas appliquer une analyse externe (ChatGPT, etc.) sans vérifier — elle peut viser juste sur la cause mais faux sur le correctif.

## Environnement d'exécution

- **Sandbox Claude Code (cloud)** : contient `src/`, `config/`, `templates/`, `public/`, `migrations/`, `assets/`, `docs/`, `tools/`, `importmap.php`. **PAS** de `vendor/`, `composer.json`, `.env`. → On peut seulement `php -l` (lint syntaxe) ; **pas** de `bin/console`. Le reste se valide par lecture de code + logique.
- **Local utilisateur (Windows / PowerShell)** : `vendor/`, `.env`, `composer.json` présents. C'est là que tournent les commandes Symfony/Doctrine listées en § Commandes utiles.

## Pièges d'environnement rencontrés — à toujours prendre en compte

Symptôme → cause → correctif. Détail complet dans `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md`.

1. **Lancer l'app en local** :
   - ✅ `symfony server:start --no-tls`, **ou** pour choisir le port : `php -S 127.0.0.1:8002 -t public` (**sans** routeur). Les deux servent les assets statiques ET routent les URLs propres.
   - ❌ **Jamais** `php -S host:port -t public public/index.php` : passer `public/index.php` comme script routeur + Symfony Runtime fait `require SCRIPT_FILENAME`, donc chaque asset (CSS/images) renvoie 500 → page nue, icônes SVG géantes (« rond bleu »).

2. **`cache:clear` plante (OOM « Allowed memory size … exhausted » dans Twig Compiler)** → limite PHP 128 Mo trop juste pour le warmup. Utiliser :
   ```powershell
   php -d memory_limit=-1 bin/console cache:clear --no-warmup
   php -d memory_limit=-1 bin/console cache:warmup
   ```

3. **`.env` refuse de charger (« Loading files starting with a byte-order-mark (BOM) is not supported »)** → PowerShell `Out-File -Encoding utf8` ajoute un BOM. Écrire en UTF-8 **sans** BOM :
   ```powershell
   [System.IO.File]::WriteAllText("$PWD\.env", $contenu, (New-Object System.Text.UTF8Encoding $false))
   ```
   Symfony a besoin d'un fichier `.env` (pas seulement `.env.local`). Règle générale : tout fichier PHP édité sous Windows doit être écrit en UTF-8 **sans BOM** (un BOM casse aussi `declare(strict_types=1)`), en conservant les accents français intacts.

4. **`composer require` casse `importmap.php` et pollue `.env`** : Flex rejoue ~30 recettes et **régénère `importmap.php` au défaut** → l'entrée `admin` disparaît → EasyAdmin lève « The entrypoint "admin" does not exist in importmap.php » (500 sur `/ouegnewe`).
   `importmap.php` est **désormais versionné** (entrées `app` + `admin`) pour éviter ça. Après tout `composer require`, revérifier `importmap.php` **et** la fin de `.env` (blocs `###> … ###` en double, un `DATABASE_URL` postgres par défaut à supprimer).

5. **`git stash -u` embarque les fichiers non suivis** (`composer.json`, `.env`, `bin/console`) → peut casser l'install (composer.json remplacé par un vide → 121 paquets désinstallés). Prudence avec `-u` ; restaurer via `git restore --source="stash@{0}^3" -- <fichier>`.

6. **Base neuve : chaîne de migrations réparée.** Deux migrations de juin étaient bancales, rendues défensives : `Version20260604095052` (référençait des colonnes créées après elle par `…100000`) et `Version20260604113406` (doublon de `…112000`), + `Version20260706210000` (normalisation résiduelle du schéma). Sur base neuve, `doctrine:migrations:migrate` passe désormais jusqu'au bout et `doctrine:schema:validate` est vert.

7. **Backoffice EasyAdmin non stylé (rond bleu / icônes SVG géantes) alors que le CSS public charge** → les assets du bundle EasyAdmin renvoient 404 (`GET /bundles/easyadmin/app.*.css`) : périmés/absents dans `public/bundles/` après un `composer install`/`update` (les hashs côté vendor ont changé). Republier :
   ```powershell
   php bin/console assets:install public
   ```

8. **Copier une base en local sans casser les accents (dump/import)** → **jamais** le `>` de PowerShell : il réencode la sortie en UTF-16 (import refusé : `ERROR ... ASCII '\0' appeared ...`) ou transcode les accents (`é`→`├®`, `à`→`├á`). Laisser `mariadb-dump` écrire le fichier (`--result-file`, binaire) et importer avec `cmd /c` + charset explicite des **deux** côtés :
   ```powershell
   mariadb-dump -u root -p --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 --result-file=..\hodina_clean.sql hodina_db
   cmd /c "mariadb -u root -p --default-character-set=utf8mb4 claude_hodina < ..\hodina_clean.sql"
   ```
   Preuve en base : `SELECT HEX(col)` → `C3A0` = `à` sain, `C383C2A0` = double-encodage latin1. Détail : NOTES §9.

9. **Après import d'un dump de prod, `doctrine:migrations:migrate` échoue (« Table … already exists ») mais `schema:validate` est vert** → le dump de prod a remplacé `doctrine_migration_versions` (la prod ignore le lot en cours) tandis que les tables du lot restent en place. Réaligner le suivi **sans dropper** :
   ```powershell
   php bin/console doctrine:migrations:list
   php bin/console doctrine:migrations:version 'DoctrineMigrations\VersionAAAAMMJJHHMMSS' --add --no-interaction
   ```
   `--add` ne touche que la table de suivi. Jamais de drop pour « laisser recréer » (perte des données du lot). Les tables partagées (`hodina_setting`) étant écrasées par la prod, réinsérer les seeds perdus (piège n°10). Détail : NOTES §10.

10. **Exécuter du SQL / insérer de l'accentué en local** → la commande DoctrineBundle est **`db:run-sql`** (pas `doctrine:query:sql`, inexistant). Ne pas taper l'accent dans la console (transite par l'ANSI → corrompu) ; l'injecter par ses octets UTF-8 : `CONVERT(UNHEX('C3A9') USING utf8mb4)` = `é`. Insert idempotent via `WHERE NOT EXISTS`. Détail : NOTES §11.

11. **Action CRUD custom EasyAdmin (`linkToCrudAction`) : `AdminContext::getEntity()` peut lever « Cannot get entity outside of a CRUD context »** dès le premier appel de la méthode, même avec `crudAction`/`crudControllerFqcn`/`entityId` corrects dans l'URL — dépend de la version d'EasyAdminBundle réellement installée (sujette à dérive dans ce projet, cf. piège n°7). Ne jamais dépendre de `$context->getEntity()` dans une action custom : charger l'entité directement depuis `entityId` (query string) via l'`EntityManagerInterface`. Touchait 4 contrôleurs au 2026-07-08 (`Customer`, `CustomerOrder`, `SupportTicket`, `CourierPayout`) — tous corrigés avec le même correctif (`findXFromRequest()`/`getPayoutFromRequest()`). Si un nouveau contrôleur admin ajoute une action custom via `linkToCrudAction`, appliquer ce pattern dès l'écriture plutôt que d'attendre un 500 réel. Détail : NOTES §12.

12. **Migration défensive affichant « 0 sql queries » alors que l'`ALTER` s'est bien exécuté, puis `schema:validate` rouge derrière** → le compteur de Doctrine Migrations ne suit que les instructions passées par `$this->addSql(...)` ; les migrations défensives de ce projet (conditionnelles, cf. `Version20260703093000`) exécutent leurs `ALTER` via `$this->connection->executeStatement(...)`, invisibles pour ce compteur — « 0 sql queries » ne veut pas dire « rien ne s'est passé ». Se fier à `schema:validate`/`information_schema`, jamais à ce seul message. Par ailleurs, une colonne booléenne créée en `TINYINT(1) NOT NULL DEFAULT 1` par `ALTER` brut ne correspond pas forcément au mapping Doctrine d'un simple `#[ORM\Column]` sans `options` (qui attend `TINYINT NOT NULL`, sans largeur ni `DEFAULT`) → `schema:update --dump-sql` propose alors un `CHANGE` : à transformer en migration corrective défensive, jamais en `schema:update --force`. Détail : NOTES §13.

## Assets / CSS

- Un seul CSS public : `public/css/style_mobile.css` (fond blanc, `--bg: #ffffff`).
- `assets/styles/app.css` doit rester **neutre** — ne jamais y remettre `body { background: … }` : c'était la cause du « portail bleu » (règle de démo Symfony `skyblue`). Chargé via `importmap('app')`.
- `assets/admin.js` (entrée `admin` de l'importmap) charge les contrôleurs Stimulus admin (stock, images produit, créneaux) + le menu repliable. **Ne pas retirer** `->addAssetMapperEntry('admin')` du `DashboardController` : ça casserait ces fonctions.
- Chercher un souci d'asset/CSS dans **`assets/` aussi**, pas seulement `public/css/` et `templates/`.

## Conventions

- Les fichiers `.bak` (ex. `Product.php.bak`, `HomeController.php.bak`) sont des snapshots locaux périmés — ignore-les ; ne jamais les éditer ni les référencer, ne jamais les committer.
- Le nommage commit/branche suit le schéma de jalons `J5<lettre>` visible dans l'historique git.

## Documentation (`docs/`)

Le répertoire `docs/` est le dossier de conception vivant. **`docs/PROMPT_MAJ_DOCUMENTATION_HODINA.md` décrit le rôle de chaque fichier et la procédure de mise à jour à respecter** — lis-le avant toute mise à jour documentaire. Rôle de chaque fichier :

- **`VISION.md`** — vision produit, principes de fond.
- **`ARCHITECTURE.md`** — composants techniques, services, routes, responsabilités, séparation des couches.
- **`DECISIONS.md`** — décisions métier/techniques actées et justification (surtout les règles anti-régression).
- **`ENTITIES.md`** — entités Doctrine, champs importants, relations, règles de persistance.
- **`WORKFLOWS.md`** — parcours opérationnels étape par étape.
- **`TODO.md`** — état opérationnel, cases cochées, prochaines priorités, backlog.
- **`ROADMAP.md`** — ordre stratégique de développement, jalons, arbitrages.
- **`PILOT_STATUS_DETAILED.md`** — état détaillé du pilote, validations, risques, statut global.
- **`DEPLOIEMENT_PREPROD.md`** — tags, commandes de déploiement recette, contrôles serveur, warnings connus, checklist minimale (voir § Règles non négociables).
- **`HISTORIQUE.md`** — chronologie des actions, décisions, corrections, validations.
- **`NOTES_ENVIRONNEMENT_LOCAL_20260707.md`** — détail complet des pièges d'environnement numérotés ci-dessus.
- **`README_MAJ_*.md`** / **`COMMIT_*.md`** — documentation et consignes de validation d'un lot précis (jalon J5x).

Règles de mise à jour :

- Ne pas mélanger les rôles des fichiers : une nouvelle route/service/entité va dans `ARCHITECTURE.md` et/ou `ENTITIES.md` ; un parcours dans `WORKFLOWS.md` ; une décision métier dans `DECISIONS.md` ; une validation recette/prod dans `DEPLOIEMENT_PREPROD.md`, `PILOT_STATUS_DETAILED.md`, `HISTORIQUE.md` et le README du lot.
- Distinguer explicitement ce qui est **validé local**, **validé recette**, **validé production**, **repoussé** ou **seulement prévu**. Ne jamais documenter une fonctionnalité absente du code/des tests.
- Signaler les incohérences quand un ancien jalon contredit l'état réel (ex. collision de numéro de lot) au lieu de laisser deux vérités.

## Git / livraison

- Développer sur la branche désignée, **jamais pousser sur `main`** sans demande explicite.
- `git add` sélectif (jamais `.`), un commit par préoccupation, message clair.
- Après push : créer/mettre à jour la Pull Request (draft).
- Ne pas committer : `.env*`, dumps SQL, secrets/clés, `var/`, fichiers `.bak`, `.zip`, `.patch`, `.corrected.php`, archives ou fichiers temporaires. (`vendor/` : suivre la convention o2switch du projet.)
