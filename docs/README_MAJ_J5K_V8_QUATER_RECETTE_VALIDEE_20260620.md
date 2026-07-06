# README — J5K-v8-quater validé recette et DevOps sécurisé

Date : **20/06/2026**

## Objet

Documenter la clôture de J5K-v8-quater en recette et les corrections DevOps associées.

## Référence finale recette

```text
Tag : devops-deploy-composer-before-console-v2
Commit : 48dae1d
```

## Historique des tags

```text
j5k-gps-livraison-recette-v9
→ validation fonctionnelle J5K-v8-quater.

devops-vendor-untracked-recette-v1
→ retrait de vendor/ du suivi Git.

devops-deploy-composer-before-console-v2
→ script de déploiement corrigé, validation recette de bout en bout.
```

## Tests fonctionnels validés

- Adresse livraison par défaut.
- Adresse facturation par défaut.
- Facturation auto depuis livraison seule.
- Clic carte livraison.
- Clic carte facturation.
- Case `Utiliser cette adresse par défaut` seulement dans ajout / modification.
- GPS facultatif.
- Instructions livraison.
- Validation commande.
- Admin commande.
- Passage statut prête.
- Portail livreur existant en non-régression.

## Validations techniques

- `vendor/` n’est plus suivi par Git.
- Composer est lancé avant le premier `bin/console` si `vendor/autoload.php` est absent.
- Déploiement recette du tag final OK.
- Backup DB OK via `/bin/mariadb-dump`.
- Migrations Doctrine latest.
- Doctrine schema validate OK.
- AssetMapper compile OK.
- Cache prod OK.
- URL recette HTTP 200.
- Cron Messenger recette corrigé.
- Working tree recette propre.

## Suite directe

```text
21/06 — Prod J5K-v8-quater
22/06 — J5L-A UX panier mobile PWA
```
