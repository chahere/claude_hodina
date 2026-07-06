# README MAJ - DevOps deploy autocheck GPS J5K

Date : 2026-06-19
Contexte : correction du script standard de deploiement par tag apres recette J5K.

## Probleme constate en recette

Le deploiement `j5k-gps-livraison-recette-v2` s'est termine avec succes :

- tag checkout OK ;
- backup DB OK ;
- migrations OK ;
- `doctrine:schema:validate --env=prod` OK ;
- cache prod OK ;
- assets OK ;
- URL publique OK.

Mais le resume final du script affichait a tort :

```text
[WARN] J5K GPS colonnes DB : colonnes absentes, verifier le tag ou la migration
```

## Cause

Le script controlait les mauvais noms de colonnes dans `customer_order` :

```text
customer_order.delivery_gps_latitude
customer_order.delivery_gps_longitude
```

Or la migration J5K a cree les colonnes de snapshot avec le prefixe complet de l'adresse livree :

```text
customer_order.delivery_address_gps_latitude
customer_order.delivery_address_gps_longitude
customer_order.delivery_address_gps_accuracy_meters
```

Cote `address`, les colonnes attendues sont :

```text
address.gps_latitude
address.gps_longitude
address.gps_accuracy_meters
```

## Correction appliquee

Dans `tools/deploy-hodina-by-tag.sh`, la fonction :

```bash
check_optional_j5k_gps_columns()
```

controle maintenant les 6 colonnes reelles :

```text
address.gps_latitude
address.gps_longitude
address.gps_accuracy_meters
customer_order.delivery_address_gps_latitude
customer_order.delivery_address_gps_longitude
customer_order.delivery_address_gps_accuracy_meters
```

La documentation interne du script mentionne aussi `PUBLIC_URL`, utilise pour tester automatiquement l'URL publique dans le resume final.

## Regle de deploiement confirmee

Aucun commit, aucun tag et aucun push ne doit etre fait depuis recette ou production.

La sequence reste :

1. en dev : commit + push sur `main` ;
2. en dev : creation du tag ;
3. en dev : push du tag ;
4. en recette/prod : `git fetch` ;
5. en recette/prod : execution du script contenu dans le tag via fichier temporaire ;
6. en recette/prod : aucun push.

## Sequence standard recette

```bash
cd /home/vopu3712/recette.hodina.fr

git fetch --prune origin main
git fetch origin --tags --force

git rev-parse <TAG>
git show -s --oneline <TAG>

tmp_script="$(mktemp)"
git show <TAG>:tools/deploy-hodina-by-tag.sh > "$tmp_script"

test -s "$tmp_script"
bash -n "$tmp_script"

PUBLIC_URL=https://recette.hodina.fr RUN_COMPOSER=0 RESET_COMMANDS=0 SKIP_BACKUP=0 bash "$tmp_script" \
  --project-dir /home/vopu3712/recette.hodina.fr \
  --tag <TAG> \
  --target recette

rm -f "$tmp_script"
```

## Sequence standard production

```bash
cd /home/vopu3712/hodina.fr

git fetch --prune origin main
git fetch origin --tags --force

git rev-parse <TAG>
git show -s --oneline <TAG>

tmp_script="$(mktemp)"
git show <TAG>:tools/deploy-hodina-by-tag.sh > "$tmp_script"

test -s "$tmp_script"
bash -n "$tmp_script"

PUBLIC_URL=https://hodina.fr RUN_COMPOSER=0 RESET_COMMANDS=0 SKIP_BACKUP=0 bash "$tmp_script" \
  --project-dir /home/vopu3712/hodina.fr \
  --tag <TAG> \
  --target prod

rm -f "$tmp_script"
```

## Test attendu apres correction

Au prochain deploiement contenant cette correction, le resume final doit afficher :

```text
[OK] J5K GPS colonnes DB : address + customer_order OK
```

si la migration J5K est bien appliquee.

## A surveiller plus tard

Les warnings deprecation observes en recette ne bloquent pas le deploiement, mais doivent etre traites dans une dette separee :

- migration Doctrine avec DDL transactionnel implicite ;
- option DoctrineBundle `doctrine.orm.controller_resolver.auto_mapping` ;
- attribut EasyAdmin `#[AdminDashboard]`.
