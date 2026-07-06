
# README — Mise en production J5G-B4 par tag v7

Date : **18/06/2026**  
Tag de production : **`j5g-b4-20260618-v7`**  
Commit : **`a888a90 chore(devops): resolve deploy binary paths during prechecks`**

---

## Statut

La mise en production J5G-B4 a été réalisée avec succès sur :

```text
/home/vopu3712/hodina.fr
```

Le site est positionné sur le tag :

```text
j5g-b4-20260618-v7
```

La base de production est alignée sur :

```text
DoctrineMigrations\Version20260617162000
```

---

## Pourquoi il y a eu plusieurs tags v2 à v7

Le jalon fonctionnel J5G-B4 était prêt, mais la mise en production a révélé plusieurs points d'exploitation qu'il fallait fiabiliser avant de considérer le script comme référence.

Historique :

```text
v2 : aperçu stable des images produits + cache prod optimisé
v3 : protection uploads runtime
v4 : fallback DB backup via dump
v5 : correction helper PHP temporaire sur o2switch
v6 : vérification cohérence base dumpée / base Doctrine
v7 : résolution des chemins binaires dès les précontrôles, mariadb-dump prioritaire
```

La version **v7** remplace les précédentes pour les prochaines MEP.

---

## Procédure sûre pour extraire et lancer le script

Toujours faire :

```bash
cd /home/vopu3712/hodina.fr

git fetch origin main --tags --force

git tag -l "j5g-b4-20260618-v7"
git show --oneline --no-patch j5g-b4-20260618-v7

rm -f /tmp/deploy-hodina-by-tag.sh
git show j5g-b4-20260618-v7:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh
```

Puis :

```bash
bash /tmp/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/hodina.fr   --tag j5g-b4-20260618-v7   --target prod
```

---

## Contrôles automatisés par le script

- vérifie les commandes système ;
- résout les chemins binaires (`git`, `grep`, `sed`, `awk`, `date`, `mkdir`, `mktemp`, `crontab`, `flock`, `cmp`, `cp`, `mv`, `find`, `tail`) ;
- résout `PHP_BIN` ;
- résout `mariadb-dump` ou fallback `mysqldump` ;
- vérifie que le remote Git est en SSH ;
- vérifie que le tag existe ;
- vérifie que le tag est contenu dans `origin/main` ;
- protège les fichiers env locaux ;
- protège les uploads runtime ;
- fait un backup DB ;
- migre Doctrine ;
- nettoie et réchauffe le cache prod ;
- valide le schema Doctrine ;
- ajoute ou vérifie le cron Messenger ;
- affiche le résumé final.

---

## Résultat MEP prod du 18/06/2026

```text
Cible : prod
Projet : /home/vopu3712/hodina.fr
Tag : j5g-b4-20260618-v7
Commit tag : a888a909ac79d3073dee2568d6b5ce3b0a13dae9
Avant MEP : 36cc357
Déployé : a888a90
Dump DB : /bin/mariadb-dump
Backup DB : var/backups/backup_avant_prod_j5g-b4-20260618-v7_20260618_170724.sql
Backup env : var/deploy_env_backup/20260618_170724
Backup uploads : var/deploy_runtime_backup/20260618_170724
Cron Messenger prod : ajouté
Doctrine schema : OK
```

---

## Tests fonctionnels post-MEP

À refaire après chaque MEP :

- ouvrir `https://hodina.fr` ;
- vérifier l'accès admin `/ouegnewe` ;
- modifier un produit avec image et vérifier l'aperçu ;
- ajouter une image produit et vérifier l'aperçu ;
- panier simple sans barge ;
- panier avec barge ;
- panier multi-communes vendeurs ;
- validation commande ;
- Admin > Commandes > Logistique ;
- snapshot logistique présent ;
- `var/log/messenger_cron.log` présent après 1 à 2 minutes.

---

## Ne pas oublier

La production est désormais sur un **HEAD détaché volontairement** : c'est normal, car on déploie un tag.

Pour connaître la version prod :

```bash
cd /home/vopu3712/hodina.fr
git log --oneline -3
git status --short
```

La présence éventuelle de fichiers runtime non suivis dans `public/uploads/products` est normale, mais les anciens fichiers suivis par Git doivent être sortis du dépôt plus tard.
