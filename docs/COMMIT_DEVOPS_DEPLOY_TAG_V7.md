
# COMMIT — DevOps J5G-B4 v7 : déploiement par tag sécurisé recette / production

Date : **18/06/2026**  
Tag final déployé : **`j5g-b4-20260618-v7`**  
Commit final : **`a888a90 chore(devops): resolve deploy binary paths during prechecks`**  
Recette : **déployée et validée techniquement**  
Production : **déployée et validée techniquement**

---

## Résumé exécutif

Cette mise à jour clôt la séquence de fiabilisation du déploiement Hodina J5G-B4.

L'objectif initial était de mettre en production le calcul logistique avancé J5G-B4. Pendant la recette, plusieurs problèmes opérationnels ont été révélés puis corrigés proprement dans le script de déploiement versionné `tools/deploy-hodina-by-tag.sh` :

- déploiement uniquement par tag issu de `main` ;
- passage des remotes Git de HTTPS vers SSH ;
- prévention du piège du script `/tmp` vide si le tag n'est pas encore fetché ;
- protection des fichiers d'environnement locaux ;
- protection des uploads runtime `public/uploads/products` ;
- cache prod optimisé avec `cache:clear --no-warmup` puis `cache:warmup` ;
- backup automatique de base via `mariadb-dump` ;
- contrôle que la base sauvegardée correspond bien à la base Doctrine ;
- résolution des chemins binaires dès les précontrôles ;
- ajout / contrôle du cron Messenger séparé recette / prod.

La version **v7** est la version de référence pour les prochaines mises en production.

---

## Historique des tags J5G-B4

```text
j5g-b4-20260618      première livraison J5G-B4 depuis main
j5g-b4-20260618-v2   correction aperçu images produits + cache prod no-warmup/warmup
j5g-b4-20260618-v3   protection des uploads runtime public/uploads/products
j5g-b4-20260618-v4   fallback backup DB via mysqldump/mariadb-dump
j5g-b4-20260618-v5   correction exécution helper PHP temporaire, car php - non supporté sur o2switch
j5g-b4-20260618-v6   vérification que la base dumpée correspond à la base Doctrine
j5g-b4-20260618-v7   résolution des binaires au début, priorité à mariadb-dump
```

---

## Incidents rencontrés et décisions prises

### 1. Remote Git production en HTTPS

Symptôme en prod :

```bash
git fetch origin main --tags --force
Username for 'https://github.com':
```

Cause : le dépôt `/home/vopu3712/hodina.fr` avait encore :

```text
origin https://github.com/chahere/hodina.git
```

Correction :

```bash
cd /home/vopu3712/hodina.fr
git remote set-url origin git@github.com:chahere/hodina.git
ssh -T git@github.com
```

Résultat attendu :

```text
Hi chahere/hodina! You've successfully authenticated, but GitHub does not provide shell access.
```

Décision : **tous les dépôts serveur Hodina doivent utiliser SSH**, même s'ils sont sur la même machine. Chaque clone possède son propre `.git/config`.

---

### 2. Tag non fetché et script `/tmp` vide

Symptôme :

```bash
git show j5g-b4-20260618-v7:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
fatal : nom d'objet invalide : 'j5g-b4-20260618-v7'.
```

Comme la redirection `>` crée quand même le fichier cible, `/tmp/deploy-hodina-by-tag.sh` pouvait devenir vide. `bash -n` sur un fichier vide ne retourne pas d'erreur, donc il y avait un risque de croire que la MEP a été lancée alors que rien ne s'est exécuté.

Procédure sécurisée retenue :

```bash
git fetch origin main --tags --force

git tag -l "j5g-b4-20260618-v7"
git show --oneline --no-patch j5g-b4-20260618-v7

rm -f /tmp/deploy-hodina-by-tag.sh
git show j5g-b4-20260618-v7:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh
```

Décision : **toujours fetcher les tags et tester que le script extrait n'est pas vide avant exécution**.

---

### 3. Uploads produits bloquaient le déploiement

Symptôme recette :

```text
?? public/uploads/products/20260618-f32aa8457768a72907c7e9749943148f8a031465.png
[ERREUR] Working tree non propre hors fichiers env locaux autorisés.
```

Cause : les fichiers uploadés par l'application sont des données runtime. Ils ne doivent pas bloquer le checkout d'un tag.

Correction v3 : le script protège et restaure :

```text
public/uploads/products
```

Règle retenue :

```text
Code source / templates / migrations / assets versionnés → Git
Fichiers client/admin uploadés à l'exécution → runtime hors Git
```

À corriger plus tard : plusieurs anciennes images produits sont encore suivies par Git. Elles doivent sortir du dépôt proprement.

---

### 4. `doctrine:database:export` indisponible

Symptôme :

```text
[WARN] doctrine:database:export indisponible.
```

Cause : la commande n'est pas fournie par la stack actuelle.

Correction v4/v5/v6/v7 : fallback vers `mariadb-dump` / `mysqldump`.

Règles finales :

- le script tente d'abord `doctrine:database:export` si disponible ;
- sinon il utilise `mariadb-dump` en priorité ;
- `mysqldump` reste un fallback ;
- un fichier temporaire `my.cnf` est créé avec `chmod 600` pour ne pas exposer le mot de passe ;
- le script contrôle que la base extraite de `DATABASE_URL` correspond à la base affichée par Doctrine.

Exemple validé recette :

```text
DATABASE_URL retenue pour backup : .env.local
Base sauvegardée cohérente avec Doctrine : vopu3712_hodina_recette
Dump DB utilisé : /bin/mariadb-dump
```

Exemple validé prod :

```text
DATABASE_URL retenue pour backup : .env.local
Base sauvegardée cohérente avec Doctrine : vopu3712_hodina_db
Dump DB utilisé : /bin/mariadb-dump
```

---

### 5. Cache prod

Ancienne commande :

```bash
php bin/console cache:clear --env=prod
```

Nouvelle séquence :

```bash
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
```

Décision : on évite de supprimer brutalement `var/cache/prod` pendant que le site tourne. Le script nettoie puis réchauffe le cache proprement.

---

### 6. Cron Messenger séparé recette / prod

Le script vérifie ou ajoute le cron adapté à la cible :

```text
recette → /tmp/hodina_recette_messenger.lock
prod    → /tmp/hodina_prod_messenger.lock
```

Prod validée : le cron a été ajouté :

```cron
* * * * * cd /home/vopu3712/hodina.fr && mkdir -p var/log && flock -n /tmp/hodina_prod_messenger.lock /usr/local/bin/php bin/console messenger:consume async --env=prod --limit=10 --time-limit=50 --memory-limit=128M --no-interaction >> /home/vopu3712/hodina.fr/var/log/messenger_cron.log 2>&1
```

---

## Commandes de déploiement retenues

### Recette / prod — extraction sécurisée du script

```bash
cd /home/vopu3712/<dossier-projet>

git fetch origin main --tags --force

git tag -l "j5g-b4-20260618-v7"
git show --oneline --no-patch j5g-b4-20260618-v7

rm -f /tmp/deploy-hodina-by-tag.sh
git show j5g-b4-20260618-v7:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh
```

### Recette

```bash
bash /tmp/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618-v7   --target recette
```

### Production

```bash
bash /tmp/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/hodina.fr   --tag j5g-b4-20260618-v7   --target prod
```

---

## Résultat production validé

```text
Cible       : prod
Projet      : /home/vopu3712/hodina.fr
Tag         : j5g-b4-20260618-v7
Commit tag  : a888a909ac79d3073dee2568d6b5ce3b0a13dae9
Avant MEP   : 36cc357
Déployé     : a888a90
Backup DB   : /home/vopu3712/hodina.fr/var/backups/backup_avant_prod_j5g-b4-20260618-v7_20260618_170724.sql
Backup env  : /home/vopu3712/hodina.fr/var/deploy_env_backup/20260618_170724
Backup uploads : /home/vopu3712/hodina.fr/var/deploy_runtime_backup/20260618_170724
Dump DB bin : /bin/mariadb-dump
Migrations  : 29 → 33, latest Version20260617162000
Doctrine schema : OK
Cron Messenger prod : ajouté
```

---

## Points à traiter plus tard

- Sortir `prod.env.local` du suivi Git.
- Sortir les fichiers `public/uploads/products/*` du suivi Git, après sauvegarde et vérification DB.
- Ajouter `#[AdminDashboard]` dans `DashboardController` avant EasyAdmin 5.
- Corriger `doctrine.orm.controller_resolver.auto_mapping` avant DoctrineBundle 4.
- Corriger les migrations qui déclenchent l'avertissement Doctrine sur transaction implicite, probablement en `isTransactional(): false` sur les migrations concernées ou configuration globale adaptée.
- Envisager de committer une version CI/CD du script sans prompts, avec variables d'environnement contrôlées.
