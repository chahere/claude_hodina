# COMMIT PROD — J5W-A zones tarifaires locales par secteur

Statut : **production validée**.

## Tag

```text
prod-j5w-a-local-pricing-zones-20260629
```

## Commit

```text
cea4d19 docs(j5w-a): record recette validation
```

## Déploiement

Déploiement production réalisé via :

```bash
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/hodina.fr \
  --tag prod-j5w-a-local-pricing-zones-20260629 \
  --target prod
```

## Contrôles validés

- Checkout tag OK sur `cea4d19`.
- Working tree propre.
- Backup DB créé : `/home/vopu3712/hodina.fr/var/backups/backup_avant_prod_prod-j5w-a-local-pricing-zones-20260629_20260629_101226.sql`.
- Backup env : `/home/vopu3712/hodina.fr/var/deploy_env_backup/20260629_101226`.
- Backup uploads : `/home/vopu3712/hodina.fr/var/deploy_runtime_backup/20260629_101226`.
- Assets compilés.
- Cache prod réchauffé.
- `doctrine:schema:validate` OK.
- Migrations current/latest sur `DoctrineMigrations\Version20260629083000`.
- Garde-fou J5W-A OK.
- SQL zones tarifaires : `PETITE_TERRE_LOCAL` absent.
- Tests navigateur production annoncés OK.

## Décision

J5W-A est considéré validé production. La suite J5W-B peut reprendre uniquement après clôture documentaire, sans modifier la séparation :

- `DeliveryZone` / `DeliveryCommune.territory` : territoires techniques PT/GT ;
- `DeliveryPricingZone` : forfait local ;
- `DeliveryCommuneConnection` : barge et liaisons ;
- `DeliveryArea` futur : planning/exploitation, pas les coûts.
