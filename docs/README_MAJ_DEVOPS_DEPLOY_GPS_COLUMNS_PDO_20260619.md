# Mise a jour DevOps - controle DB J5K GPS via PDO (2026-06-19)

## Contexte

Lors du deploiement recette `j5k-gps-livraison-recette-v3`, le script a correctement deployee le tag, execute les migrations, compile les assets et valide Doctrine, mais le resume final affichait encore :

```text
[WARN] J5K GPS colonnes DB : colonnes absentes, verifier le tag ou la migration
```

Ce warning etait incoherent avec :

- `doctrine:migrations:status --env=prod` : derniere version `Version20260619102000` ;
- `doctrine:schema:validate --env=prod` : mapping et base synchronises ;
- migration J5K : colonnes GPS bien ajoutees sur `address` et `customer_order`.

## Cause probable

Le controle final utilisait `bin/console dbal:run-sql` avec `SHOW COLUMNS`. Selon l'environnement Symfony/Doctrine/o2switch, cette commande peut ne pas renvoyer une sortie exploitable par `grep`, ce qui cree un faux negatif.

## Correction

Le script `tools/deploy-hodina-by-tag.sh` controle maintenant les colonnes via un petit bloc PHP PDO qui lit `DATABASE_URL`, se connecte a la base cible et interroge directement `INFORMATION_SCHEMA.COLUMNS`.

Colonnes controlees :

```text
address.gps_latitude
address.gps_longitude
address.gps_accuracy_meters
customer_order.delivery_address_gps_latitude
customer_order.delivery_address_gps_longitude
customer_order.delivery_address_gps_accuracy_meters
```

## Regle de deploiement conservee

Aucun commit, tag ou push depuis recette/prod.

Recette et production font seulement :

1. `git fetch` ;
2. lecture du script depuis le tag avec `git show <tag>:tools/deploy-hodina-by-tag.sh` ;
3. execution du script temporaire ;
4. suppression du script temporaire.

## Attendu au prochain deploiement

Le resume final doit afficher :

```text
[OK] J5K GPS colonnes DB : address + customer_order OK
```
