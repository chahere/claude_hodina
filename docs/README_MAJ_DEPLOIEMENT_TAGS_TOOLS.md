# README — Outillage de déploiement Hodina par tag et nettoyage commandes

Date : **18/06/2026**

## Objet

Ce document décrit l'ajout des scripts opérationnels Hodina dans le dossier `tools/` :

```text
tools/deploy-hodina-by-tag.sh
tools/reset-commandes-hodina.ps1
```

Ces scripts servent à sécuriser les mises à jour recette / production et à préparer le futur CI/CD.

---

## Décision principale

Le déploiement doit se faire par **tag Git issu de `main`**.

Flux cible :

```text
développement branche pilote / feature
→ merge dans main
→ création tag depuis main
→ déploiement recette par tag
→ validation recette
→ déploiement production par tag
```

Raison : un tag est une version figée, plus sûre qu'une branche mouvante.

---

## Script Bash de déploiement

Fichier :

```text
tools/deploy-hodina-by-tag.sh
```

Usage recette :

```bash
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618   --target recette
```

Usage production :

```bash
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/hodina.fr   --tag j5g-b4-20260618   --target prod
```

Options environnement :

```text
RUN_COMPOSER=1      lance composer install --no-dev --optimize-autoloader
RESET_COMMANDS=1    nettoie les anciennes commandes et logs liés
SKIP_BACKUP=1       ignore le backup DB, déconseillé en prod
ASSUME_YES=1        mode non interactif, utile CI/CD
DRY_RUN=1           simulation partielle
ENFORCE_SSH=0       désactive l'obligation du remote SSH
PHP_BIN=/path/php   force le binaire PHP
```

Le script contrôle :

- l'état Git local ;
- le remote SSH ;
- l'existence du tag ;
- la présence du tag dans `origin/main` ;
- la protection des fichiers `.env.local`, `.env.prod.local`, `prod.env.local` ;
- le backup DB ;
- les migrations ;
- le cache ;
- le schéma Doctrine ;
- le cron Messenger.

---

## Script PowerShell de nettoyage local

Fichier :

```text
tools/reset-commandes-hodina.ps1
```

Usage local Windows :

```powershell
cd E:\hodina\hodina.fr
.	ools
eset-commandes-hodina.ps1
```

Avec reset des IDs :

```powershell
.	ools
eset-commandes-hodina.ps1 -ResetIds
```

Simulation :

```powershell
.	ools
eset-commandes-hodina.ps1 -DryRun
```

Le script utilise :

```text
php bin/console dbal:run-sql
```

et non :

```text
php bin/console doctrine:query:sql
```

Tables nettoyées :

```text
sms_log liés aux commandes
email_log liés aux commandes
order_item
customer_order
```

Tables conservées :

```text
clients
vendeurs
produits
communes
zones
réglages Hodina
```

---

## Commandes pour versionner les scripts

```powershell
git status
git add tools/deploy-hodina-by-tag.sh tools/reset-commandes-hodina.ps1 docs
git commit -m "chore(devops): add tagged deployment and order reset scripts"
git push origin main
```

---

## Tests attendus après déploiement par tag

- ouvrir le site ;
- panier simple sans barge ;
- panier avec barge ;
- panier multi-communes vendeurs ;
- validation commande ;
- Admin > Commandes > Logistique ;
- vérifier le snapshot logistique ;
- vérifier `var/log/messenger_cron.log` après 1 à 2 minutes.

## Mise à jour 18/06/2026 — script v7

La version de référence du script est désormais celle livrée dans le tag :

```text
j5g-b4-20260618-v7
```

### Nouveaux comportements

Le script résout les chemins binaires au début :

```text
git, grep, sed, awk, date, mkdir, mktemp, crontab, flock, cmp, cp, mv, find, tail
PHP_BIN
mariadb-dump / mysqldump
```

Il privilégie :

```text
/bin/mariadb-dump
```

avant `mysqldump`.

Il protège aussi :

```text
public/uploads/products
```

comme donnée runtime.

### Extraction sécurisée du script depuis un tag

Toujours utiliser :

```bash
git fetch origin main --tags --force
rm -f /tmp/deploy-hodina-by-tag.sh
git show j5g-b4-20260618-v7:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh
```

Puis lancer avec la cible voulue.

### Commande recette

```bash
bash /tmp/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618-v7   --target recette
```

### Commande prod

```bash
bash /tmp/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/hodina.fr   --tag j5g-b4-20260618-v7   --target prod
```

### Résultat attendu

```text
Backup DB via mariadb-dump : OK
Migrations : OK
Cache prod : clear --no-warmup + warmup OK
Doctrine schema : OK
Cron Messenger : OK
```

## Complément 19/06/2026 — v11

Pour les prochaines MEP, le modèle de référence est :

```bash
git fetch origin main --tags --force
rm -f /tmp/deploy-hodina-by-tag.sh
git show <tag>:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh
bash /tmp/deploy-hodina-by-tag.sh --project-dir <path> --tag <tag> --target <recette|prod>
```

Tag de référence validé :

```text
j5g-b4-20260618-v11
```

Le script doit maintenant :

- protéger les env locaux ;
- protéger les uploads runtime ;
- accepter `public/assets` ;
- sauvegarder la DB ;
- vérifier la cohérence DB Doctrine ;
- compiler AssetMapper ;
- vider/réchauffer le cache ;
- valider Doctrine ;
- vérifier/installer cron Messenger.

---

## MAJ 2026-06-19 - Correction autocheck GPS J5K

La recette J5K a montre un faux warning dans le resume final du script :

```text
[WARN] J5K GPS colonnes DB : colonnes absentes, verifier le tag ou la migration
```

La migration et Doctrine etaient pourtant OK. La cause etait un controle DevOps sur de mauvais noms de colonnes.

Le script doit verifier :

```text
address.gps_latitude
address.gps_longitude
address.gps_accuracy_meters
customer_order.delivery_address_gps_latitude
customer_order.delivery_address_gps_longitude
customer_order.delivery_address_gps_accuracy_meters
```

et non :

```text
customer_order.delivery_gps_latitude
customer_order.delivery_gps_longitude
```

Cette correction est traitee dans :

```text
docs/README_MAJ_DEVOPS_DEPLOY_GPS_COLUMNS_20260619.md
docs/COMMIT_DEVOPS_DEPLOY_GPS_COLUMNS_20260619.md
```
