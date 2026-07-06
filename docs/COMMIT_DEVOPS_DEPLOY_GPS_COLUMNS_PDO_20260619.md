# Commit - fix(devops): check j5k gps columns with pdo

## Objectif

Supprimer le faux warning du resume final de deploiement J5K concernant les colonnes GPS en base.

## Changements

- Remplacement du controle `dbal:run-sql` + `SHOW COLUMNS` par un controle PHP PDO.
- Verification directe dans `INFORMATION_SCHEMA.COLUMNS`.
- Conservation de la liste exacte des colonnes J5K attendues.
- Ajout de la documentation de suivi.

## Impact

Aucun impact fonctionnel Hodina.
Changement limite au script de deploiement et a la documentation.

## Validation attendue

Sur recette/prod, apres redeploiement par tag :

```text
[OK] J5K GPS colonnes DB : address + customer_order OK
```
