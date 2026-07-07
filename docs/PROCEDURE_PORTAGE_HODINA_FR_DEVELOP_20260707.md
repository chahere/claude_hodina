# Procédure de portage — claude_hodina → chahere/hodina (branche develop)

Date : 2026-07-07
Statut : procédure prête à exécuter, non encore jouée.

## Contexte

- `chahere/claude_hodina` (ce dépôt, travaillé avec Claude Code) et `chahere/hodina` (dépôt `D:\hodina\hodina.fr` en local, branche `develop`, **iso recette/prod**) ont des **historiques Git sans ancêtre commun** (confirmé le 2026-07-07). Impossible de faire un `merge`, `rebase` ou `cherry-pick` direct entre les deux.
- `chahere/claude_hodina` a été initialisé par un commit unique `7c840e7 "commit source hodina"` qui est un instantané du code hodina.fr **à la date de création du dépôt**. Tout ce qui suit ce commit (`45b671b` → `87001dc`, HEAD actuel de la branche `claude/ai-chatbot-customer-account-1i5jbl`) est du travail neuf fait exclusivement dans claude_hodina : lot **J5AD** (chatbot IA client connecté, tickets support, FAQ, contact) puis lot **J5AE** (widget flottant Assistant Hodina) + correctifs et docs intermédiaires.
- Objectif : porter ce travail dans `chahere/hodina` (branche `develop`), **sans jamais toucher `develop` directement**, avec vérification à chaque étape avant d'aller plus loin.

Principe retenu : **diff + apply**, pas merge. On génère deux patches depuis claude_hodina (déjà poussés, donc disponibles par un simple `git pull` sur le checkout local de claude_hodina), on les applique sur une branche dédiée créée depuis `develop` dans hodina.fr, on vérifie, on teste, on commit, on pousse une Pull Request contre `develop` — jamais de push direct dessus.

Découpage en 2 patches (calé sur les lots déjà livrés, pas sur chaque micro-commit) :

| Patch | Plage de commits | Contenu | Fichiers | Insertions / suppressions |
|---|---|---|---|---|
| **Patch 1** | `7c840e7..7bbafa2` | Base J5AD (tickets support, FAQ, contact, chatbot IA) + correctifs intermédiaires (migrations défensives, admin, assets, SKILL.md, CLAUDE.md, docs) | 91 fichiers | +4463 / -4193 |
| **Patch 2** | `7bbafa2..87001dc` | Lot J5AE (widget flottant Assistant Hodina complet) | 22 fichiers | +1863 / -2 |

Le patch 2 inclut des images binaires (4 mascottes PNG) : la commande de génération **doit** utiliser `--binary`, sinon les images ne seront pas portées.

## Sécurité — à respecter absolument

- **Jamais de commande sur `develop` directement.** Tout se passe sur une branche dédiée `port/j5ad-j5ae-20260707` créée depuis `origin/develop`. Si quoi que ce soit tourne mal, on supprime cette branche et on recommence — `develop` n'a jamais été touché.
- **Sauvegarder la base de données locale hodina.fr avant toute migration** (mêmes commandes que celles déjà validées sur claude_hodina, cf. `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md` §9) : jamais de `>` PowerShell (réencode en UTF-16, corrompt les accents), toujours `--result-file`.
- **Jamais `git add .` ni `git add -A`** en aveugle. Ici on l'autorise *uniquement* après un `git status` qui confirme que la branche est propre et que seuls les fichiers du patch ont bougé (branche dédiée fraîchement créée = rien d'autre ne peut traîner).
- **Ne pas forcer un `git apply` qui échoue.** Si `--check` remonte des conflits, ne pas passer en force : ce sont exactement les fichiers où hodina.fr/develop a divergé de l'instantané `7c840e7` (ex. un correctif fait uniquement sur hodina.fr) — à traiter au cas par cas (voir § Points de vigilance).
- **Patches stockés hors des deux dépôts** (`D:\hodina\_portage_j5ad_j5ae\`) pour ne jamais risquer de les committer par erreur dans hodina.fr.

## Fichiers à revoir manuellement (pas un simple écrasement)

Ces fichiers existaient déjà dans l'instantané `7c840e7` et ont été modifiés (pas ajoutés) : si hodina.fr/develop a divergé dessus indépendamment, le patch peut échouer ou fusionner silencieusement de façon inattendue.

- **`templates/base.html.twig`** — touché par les deux patches (menu compte J5AD, puis bloc `chat_widget` J5AE).
- **`public/css/style_mobile.css`** — touché uniquement par le patch 2 (ajout en fin de fichier, gros volume de CSS J5AE).
- **`docs/DEPLOIEMENT_PREPROD.md`** — journal de déploiement, modifié par les deux patches ; à fusionner à la main si hodina.fr a son propre historique de déploiement écrit dans ce fichier depuis l'instantané.
- **`importmap.php`** — **n'existait pas** dans l'instantané `7c840e7` (confirmé : gitignoré/non suivi à l'origine), ajouté ensuite dans claude_hodina (`aa67cb5`) avec seulement les entrées `app` + `admin`. Si hodina.fr génère son propre `importmap.php` (probable, avec potentiellement d'autres entrées liées à des bundles réels installés côté hodina.fr), le patch va probablement échouer avec « already exists in working directory ». **Ne pas écraser aveuglément** : comparer les deux fichiers et fusionner à la main (garder les entrées propres à hodina.fr + ajouter `admin`).
- **`CLAUDE.md`, `.claude/skills/hodina-core/SKILL.md`** — nouveaux fichiers (pas de conflit d'application), mais leur contenu décrit explicitly la distinction « sandbox cloud Claude Code (pas de vendor/) vs local Windows » propre à claude_hodina. Cette distinction n'a pas de sens telle quelle pour hodina.fr (qui a toujours `vendor/`, `.env`, `composer.json`). **À adapter, pas à copier tel quel**, une fois le reste validé.
- **`config/bundles.php`** — vérifié : **aucune différence** entre `7c840e7` et HEAD. Rien à faire de spécial sur ce fichier.

## Phase 0 — Préparatifs (hodina.fr)

```powershell
cd D:\hodina\hodina.fr
git status
git fetch origin develop
git checkout develop
git merge --ff-only origin/develop
php bin/console doctrine:migrations:list
```
Vérifier : `git status` propre avant de commencer, `merge --ff-only` réussit (pas de divergence locale surprise), la liste de migrations ne montre rien d'inattendu.

```powershell
mkdir D:\hodina\_portage_j5ad_j5ae
mariadb-dump -u root -p --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 --result-file=D:\hodina\_portage_j5ad_j5ae\backup_hodina_fr_avant_portage_20260707.sql <nom_base_hodina_fr>
```
Remplacer `<nom_base_hodina_fr>` par le nom réel (visible dans `D:\hodina\hodina.fr\.env`, variable `DATABASE_URL`).

```powershell
cd D:\hodina\hodina.fr
git checkout -b port/j5ad-j5ae-20260707 develop
```

## Phase 1 — Générer les 2 patches (claude_hodina, local)

```powershell
cd D:\hodina\claude_hodina
git fetch origin claude/ai-chatbot-customer-account-1i5jbl
git checkout claude/ai-chatbot-customer-account-1i5jbl
git pull origin claude/ai-chatbot-customer-account-1i5jbl
git rev-parse HEAD
```
Le dernier `git rev-parse HEAD` doit afficher `87001dc9f671d1c3f7e2fa357e5fb4df6ab0f28f`. Si un autre hash sort, ne pas continuer (le contenu ne correspondrait plus à ce qui est décrit ici).

```powershell
git diff --binary 7c840e7 7bbafa2 --output=D:\hodina\_portage_j5ad_j5ae\port1_j5ad_base.patch
git diff --binary 7bbafa2 87001dc --output=D:\hodina\_portage_j5ad_j5ae\port2_j5ae_widget.patch
```
`--output=` (et non `>`) : git écrit directement le fichier lui-même, sans repasser par le pipeline texte de PowerShell — donc pas de risque de ré-encodage UTF-16 ni de corruption des images binaires (même piège que les dumps SQL, cf. `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md` §9).

## Phase 2 — Lot 1 : vérification à blanc puis application

```powershell
cd D:\hodina\hodina.fr
git apply --check --3way D:\hodina\_portage_j5ad_j5ae\port1_j5ad_base.patch
```
- **Si aucune erreur** : passer à l'application réelle ci-dessous.
- **Si des erreurs sortent** : noter précisément les fichiers en échec, ne rien appliquer, me transmettre la sortie exacte (+ le contenu actuel des fichiers concernés côté hodina.fr) pour construire une résolution manuelle avant de continuer.

```powershell
git apply --3way D:\hodina\_portage_j5ad_j5ae\port1_j5ad_base.patch
git status
git diff --check
```
`git diff --check` doit ressortir vide (sinon il détecte des marqueurs de conflit `<<<<<<<` restés dans des fichiers — à résoudre avant de continuer). Vérifier ensuite à l'œil `templates/base.html.twig`, `docs/DEPLOIEMENT_PREPROD.md` et `importmap.php` (§ Points de vigilance) même si l'application n'a rien signalé.

## Phase 3 — Dépendances, assets, migrations, cache (après le lot 1)

```powershell
composer require symfony/rate-limiter symfony/http-client
php bin/console assets:install public
php bin/console doctrine:migrations:list
php -d memory_limit=-1 bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Phase 4 — Tests fonctionnels du lot 1 (J5AD)

```powershell
symfony server:start --no-tls
```
Reprendre le § Tests locaux de `docs/README_MAJ_J5AD_CHATBOT_IA_SUPPORT_CLIENT_20260706.md` : connexion client, `/mon-compte/assistant`, tickets support et FAQ côté EasyAdmin, formulaire `/contact`.

## Phase 5 — Commit du lot 1

```powershell
git status
git add -A
git commit -m "feat(j5ad): assistant IA client connecte, tickets support, FAQ et contact (portage depuis claude_hodina)"
```
`git add -A` est acceptable ici uniquement parce que la branche a été créée propre à la Phase 0 et que `git status` juste au-dessus ne doit montrer que ce que le patch a introduit — le vérifier avant de lancer le commit.

## Phase 6 — Lot 2 (J5AE) : vérification puis application

```powershell
git apply --check --3way D:\hodina\_portage_j5ad_j5ae\port2_j5ae_widget.patch
```
Même logique qu'en Phase 2 : ne rien forcer si erreur.

```powershell
git apply --3way D:\hodina\_portage_j5ad_j5ae\port2_j5ae_widget.patch
git status
git diff --check
```
Vérifier à l'œil `public/css/style_mobile.css` et `templates/base.html.twig` (§ Points de vigilance).

```powershell
php bin/console assets:install public
php -d memory_limit=-1 bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
symfony server:start --no-tls
```

## Phase 7 — Tests fonctionnels du lot 2 (J5AE)

Reprendre le § Tests locaux de `docs/README_MAJ_J5AE_WIDGET_ASSISTANT_HODINA_20260707.md` (desktop + mobile + contrôles transverses).

## Phase 8 — Commit du lot 2

```powershell
git status
git add -A
git commit -m "feat(j5ae): widget flottant Assistant Hodina site-wide (portage depuis claude_hodina)"
```

## Phase 9 — Push et Pull Request (jamais de merge direct dans develop)

```powershell
git push -u origin port/j5ad-j5ae-20260707
```
Ouvrir une Pull Request `port/j5ad-j5ae-20260707` → `develop` sur `chahere/hodina` pour revue avant fusion. Ne pas fusionner directement.

## Phase 10 — Nettoyage

```powershell
Remove-Item -Recurse -Force D:\hodina\_portage_j5ad_j5ae
```
À faire une fois la Pull Request ouverte et les deux patches confirmés inutiles (ils ne servent qu'à la génération, pas à conserver).

## Plan de secours

Toute cette procédure se passe sur une branche dédiée (`port/j5ad-j5ae-20260707`) jamais fusionnée automatiquement dans `develop`. En cas de blocage à n'importe quelle étape :
```powershell
cd D:\hodina\hodina.fr
git checkout develop
git branch -D port/j5ad-j5ae-20260707
```
`develop` n'a subi aucune modification à aucun moment : ceci annule uniquement la tentative de portage. La base de données locale reste restaurable depuis `D:\hodina\_portage_j5ad_j5ae\backup_hodina_fr_avant_portage_20260707.sql` si une migration avait déjà tourné avant l'abandon.

## Ce qui a été vérifié pour construire cette procédure

- `7c840e7` = instantané exact de hodina.fr à la création de claude_hodina (premier commit après `df2933a`, message « commit source hodina »).
- HEAD actuel de claude_hodina (`87001dc`) confirmé identique entre local (sandbox) et `origin/claude/ai-chatbot-customer-account-1i5jbl` — un simple `git pull` suffit côté checkout local de l'utilisateur, aucun transfert de fichier nécessaire.
- Diffstat complet `7c840e7..HEAD` : 106 fichiers, +6326/-4195, dont 4 images binaires ajoutées (mascottes J5AE) et 30 fichiers `.bak`/`.bak.bak` supprimés (nettoyage légitime, probablement aussi présents côté hodina.fr).
- Aucun fichier `.zip` suivi par Git dans ce diff.
- `config/bundles.php` : aucune différence entre `7c840e7` et HEAD → rien à fusionner sur ce fichier.
- `config/` : seuls deux fichiers YAML *ajoutés* (`http_client.yaml`, `rate_limiter.yaml`) + suppression de deux `.bak` — pas de fichier de config existant modifié en dehors de ceux listés en § Points de vigilance.
- `importmap.php` confirmé absent de l'instantané `7c840e7` (`git show 7c840e7:importmap.php` → « exists on disk, but not in 7c840e7 »).
