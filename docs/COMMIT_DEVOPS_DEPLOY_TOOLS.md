# COMMIT — Outillage DevOps Hodina : déploiement par tag et reset commandes

Date : **18/06/2026**

## Résumé

Ajout du dossier `tools/` dans le suivi Git afin de versionner les scripts opérationnels Hodina :

```text
tools/deploy-hodina-by-tag.sh
tools/reset-commandes-hodina.ps1
```

## Objectifs

- Standardiser les mises à jour recette / production.
- Déployer uniquement des tags issus de `main`.
- Préparer un futur CI/CD.
- Éviter les déploiements depuis une branche mouvante.
- Protéger les fichiers d'environnement locaux.
- Garder une procédure reproductible pour la MEP J5G-B4 et les futures MEP.

## Décisions

### Déploiement par tag

Le script Bash refuse de déployer un tag qui n'est pas contenu dans `origin/main`.

```text
tag absent → erreur claire
tag non issu de main → erreur claire
working tree sale hors env local → erreur claire
remote non SSH → erreur claire par défaut
```

### Protection env local

Le script protège :

```text
.env.local
.env.prod.local
prod.env.local
```

avant le checkout du tag, puis les restaure si besoin.

Cette règle répond à l'incident observé lors du merge dans `main` : la perte de `.env.local` avait provoqué `could not find driver` jusqu'à restauration du fichier local.

### Nettoyage commandes

Le script PowerShell et l'option Bash `RESET_COMMANDS=1` utilisent `dbal:run-sql` pour supprimer les anciennes commandes et logs liés.

Tables nettoyées :

```text
sms_log WHERE customer_order_id IS NOT NULL
email_log WHERE customer_order_id IS NOT NULL
order_item
customer_order
```

Tables conservées :

```text
customer
seller
product
delivery_commune
delivery_commune_connection
delivery_pricing_zone
hodina_setting
```

## Commandes de commit recommandées

```powershell
git status
git add tools/deploy-hodina-by-tag.sh tools/reset-commandes-hodina.ps1 docs
git commit -m "chore(devops): add tagged deployment and order reset scripts"
git push origin main
```

## Tests techniques

À faire avant commit si possible :

```bash
bash -n tools/deploy-hodina-by-tag.sh
```

À faire sur recette :

```bash
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag <tag>   --target recette
```

Puis vérifier :

```text
cache clear OK
migrations OK
schema validate OK
cron Messenger présent
panier et Admin > Commandes > Logistique OK
```

## Mise à jour 18/06/2026 — v7 validée recette et production

La première version du script a été enrichie après plusieurs tests réels en recette puis en production.

Version finale de référence :

```text
Tag : j5g-b4-20260618-v7
Commit : a888a90 chore(devops): resolve deploy binary paths during prechecks
Script : tools/deploy-hodina-by-tag.sh
```

### Évolutions ajoutées après le premier commit DevOps

- `cache:clear --env=prod --no-warmup` puis `cache:warmup --env=prod` au lieu d'un `cache:clear` simple.
- Protection de `public/uploads/products` comme donnée runtime.
- Parking temporaire des uploads avant `git checkout -f tags/<tag>`.
- Restauration des uploads après checkout.
- Backup DB automatique via `mariadb-dump` / `mysqldump` si `doctrine:database:export` est absent.
- Exécution du helper PHP de backup via fichier temporaire, car `php -` n'est pas supporté sur o2switch.
- Vérification que la base sauvegardée correspond à la base vue par Doctrine.
- Résolution des chemins binaires dès les précontrôles.
- Priorité à `/bin/mariadb-dump`, pour éviter le warning de dépréciation de `/bin/mysqldump`.
- Ajout automatique du cron Messenger prod si absent.

### Résultat validé

Recette et production ont été déployées avec `j5g-b4-20260618-v7`.

Production :

```text
Projet : /home/vopu3712/hodina.fr
Avant MEP : 36cc357
Déployé : a888a90
Base : vopu3712_hodina_db
Migrations : 29 → 33
Latest : DoctrineMigrations\Version20260617162000
Backup DB : OK via /bin/mariadb-dump
Cron Messenger prod : ajouté
Doctrine schema : OK
```

### Points à corriger plus tard

- Sortir `prod.env.local` du suivi Git.
- Sortir `public/uploads/products/*` du suivi Git.
- Garder le script `deploy-hodina-by-tag.sh` comme base du futur CI/CD.

## Mise à jour 19/06/2026 — v8, v9, v11

Le script de MEP a été enrichi après v7.

### v8

Ajout de :

```bash
php bin/console asset-map:compile --env=prod
```

Objectif : rendre disponibles les assets admin en production, notamment `admin.js` et les contrôleurs Stimulus utilisés par EasyAdmin.

### v9

`public/assets` est accepté comme dossier généré non bloquant dans le `git status`.

Règle :

```text
public/assets = généré par AssetMapper
public/uploads/products = runtime métier
```

### v11

Validation production finale du script avec :

```text
tag j5g-b4-20260618-v11
asset-map:compile OK
cache clear/warmup OK
schema validate OK
cron Messenger OK
```

### Dette DevOps restante

- Sortir `prod.env.local` du suivi Git.
- Sortir les uploads historiques du suivi Git.
- Ajouter / vérifier `.gitignore` pour `public/assets/`.
- Documenter `MAILER_DSN` par environnement sans secrets.
- Préparer à terme un déploiement atomique ou une courte maintenance.
