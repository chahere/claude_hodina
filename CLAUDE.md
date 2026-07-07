# CLAUDE.md — Guide de travail Hodina (à lire en premier)

Hodina = marketplace locale mahoraise. **Symfony 8 / Twig / EasyAdmin / Doctrine / MariaDB**, mobile-first, hébergée o2switch. Trois environnements séparés : **local, recette, production**.

Ce fichier est le point d'entrée. Il complète — sans les répéter — les règles de développement détaillées du skill.

## Ordre de lecture

1. **Ce fichier** — règles dures + pièges d'environnement (ce qui casse en pratique).
2. `.claude/skills/hodina-core/SKILL.md` — règles de dev détaillées (architecture, zones sensibles, livraison, EasyAdmin, doc, review).
3. `docs/` : `ARCHITECTURE.md`, `ROADMAP.md`, `TODO.md`, `HISTORIQUE.md`, `ENTITIES.md`, `WORKFLOWS.md`, `DECISIONS.md`, `DEPLOIEMENT_PREPROD.md`.
4. Détail des pièges d'installation/exécution : `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md`.

## Règles non négociables

- **Jamais `git add .`** — archives, patchs et fichiers parasites fréquents. Toujours un `git add` sélectif, fichier par fichier.
- **Un commit = une préoccupation.** Ne pas mélanger les lots dans un même commit.
- **Ne pas toucher aux lots fermés** (J5Z / J5AB / J5AC / J5AA…) hors intégration minimale explicitement autorisée.
- **JAMAIS `doctrine:database:drop` ni écraser une base contenant des données** (règle utilisateur posée le 2026-07-06). Toute correction de schéma passe par une **migration défensive**, jamais par un reset de base.
- **Ne jamais modifier une migration déjà jouée en recette/prod** sans demande explicite → nouvelle migration corrective à la place.
- Nouvelle migration pour tout changement de schéma.
- Toujours finir une réponse par : commandes de test à lancer + risques de régression.

## Préférences de communication (utilisateur)

- **Répondre en français.**
- **Toujours donner les commandes avec leur contexte** : le dossier où l'exécuter + ce qu'elle fait.
- **Commandes en un seul bloc copiable**, commençant par `cd D:\hodina\claude_hodina`, une commande par ligne. Après un `git pull` ou tout changement de code/config/réglage compilé, **inclure la régénération du cache** en mode mémoire-safe (voir piège n°2) :
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
- **Local utilisateur (Windows / PowerShell)** : `vendor/`, `.env`, `composer.json` présents. C'est là que tournent les commandes Symfony/Doctrine.

## Pièges d'environnement rencontrés — à toujours prendre en compte

Symptôme → cause → correctif. Détail complet dans `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md`.

1. **Lancer l'app en local** → `symfony server:start` (sert les assets statiques ET route les URLs propres).
   Ne **PAS** faire `php -S host:port -t public public/index.php` : le script routeur + Symfony Runtime fait `require SCRIPT_FILENAME`, donc chaque asset (CSS/images) renvoie une erreur 500 → page cassée.

2. **`cache:clear` plante (OOM « Allowed memory size … exhausted » dans Twig Compiler)** → limite PHP 128 Mo trop juste pour le warmup. Utiliser :
   ```powershell
   php -d memory_limit=-1 bin/console cache:clear --no-warmup
   php -d memory_limit=-1 bin/console cache:warmup
   ```

3. **`.env` refuse de charger (« Loading files starting with a byte-order-mark (BOM) is not supported »)** → PowerShell `Out-File -Encoding utf8` ajoute un BOM. Écrire en UTF-8 **sans** BOM :
   ```powershell
   [System.IO.File]::WriteAllText("$PWD\.env", $contenu, (New-Object System.Text.UTF8Encoding $false))
   ```
   Symfony a besoin d'un fichier `.env` (pas seulement `.env.local`).

4. **`composer require` casse `importmap.php` et pollue `.env`** : Flex rejoue ~30 recettes et **régénère `importmap.php` au défaut** → l'entrée `admin` disparaît → EasyAdmin lève « The entrypoint "admin" does not exist in importmap.php » (500 sur `/ouegnewe`).
   `importmap.php` est **désormais versionné** (entrées `app` + `admin`) pour éviter ça. Après tout `composer require`, revérifier `importmap.php` **et** la fin de `.env` (blocs `###> … ###` en double, un `DATABASE_URL` postgres par défaut à supprimer).

5. **`git stash -u` embarque les fichiers non suivis** (`composer.json`, `.env`, `bin/console`) → peut casser l'install (composer.json remplacé par un vide → 121 paquets désinstallés). Prudence avec `-u` ; restaurer via `git restore --source="stash@{0}^3" -- <fichier>`.

6. **Base neuve : chaîne de migrations réparée.** Deux migrations de juin étaient bancales, rendues défensives : `Version20260604095052` (référençait des colonnes créées après elle par `…100000`) et `Version20260604113406` (doublon de `…112000`), + `Version20260706210000` (normalisation résiduelle du schéma). Sur base neuve, `doctrine:migrations:migrate` passe désormais jusqu'au bout et `doctrine:schema:validate` est vert.

## Assets / CSS

- Un seul CSS public : `public/css/style_mobile.css` (fond blanc, `--bg: #ffffff`).
- `assets/styles/app.css` doit rester **neutre** — ne jamais y remettre `body { background: … }` : c'était la cause du « portail bleu » (règle de démo Symfony `skyblue`). Chargé via `importmap('app')`.
- `assets/admin.js` (entrée `admin` de l'importmap) charge les contrôleurs Stimulus admin (stock, images produit, créneaux) + le menu repliable. **Ne pas retirer** `->addAssetMapperEntry('admin')` du `DashboardController` : ça casserait ces fonctions.
- Chercher un souci d'asset/CSS dans **`assets/` aussi**, pas seulement `public/css/` et `templates/`.

## Git / livraison

- Développer sur la branche désignée, **jamais pousser sur `main`** sans demande explicite.
- `git add` sélectif (jamais `.`), un commit par préoccupation, message clair.
- Après push : créer/mettre à jour la Pull Request (draft).
- Ne pas committer : `.env*`, dumps SQL, secrets/clés, `var/`, fichiers `.bak`. (`vendor/` : suivre la convention o2switch du projet.)
