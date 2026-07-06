# COMMIT — Dette technique env / uploads / assets / MAILER_DSN

Date : **19/06/2026**  
Position : **après J5G-B4 v11, avant J5K GPS livraison**

## Objectif

Nettoyer une dette d'exploitation courte avant d'ajouter la position GPS de livraison.

Cette étape ne change pas le métier Hodina. Elle sécurise le dépôt et la MEP :

- secrets hors Git ;
- uploads produits hors Git ;
- assets générés hors Git ;
- mailer documenté et testable ;
- validation e-mail basée sur une réception réelle.

## Fichiers / zones concernées

```text
.gitignore
public/uploads/products/.gitkeep
docs/README_MAJ_DETTE_TECHNIQUE_ENV_UPLOADS_ASSETS_MAILER.md
docs/TODO.md
docs/DECISIONS.md
docs/DEPLOIEMENT_PREPROD.md
docs/ARCHITECTURE.md
docs/WORKFLOWS.md
docs/PILOT_STATUS_DETAILED.md
docs/ROADMAP.md
tools/dette-runtime-mailer-pre-j5k.ps1
```

## Règle retenue

```text
.env.local / .env.prod.local / prod.env.local = secrets environnement, jamais Git
public/uploads/products = données métier runtime, jamais Git sauf .gitkeep
public/assets = sortie AssetMapper, jamais Git
public/error_log = log runtime o2switch, jamais Git
```

## Commandes appliquées / à appliquer

```bash
git rm --cached --ignore-unmatch .env.local .env.prod.local prod.env.local
git rm --cached -r --ignore-unmatch public/assets
git rm --cached -r --ignore-unmatch public/uploads/products
git add .gitignore public/uploads/products/.gitkeep docs tools
```

## Validation attendue

```bash
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

Sortie attendue :

```text
public/uploads/products/.gitkeep
```

## Validation mailer

La configuration code reste :

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

La configuration runtime doit être côté serveur :

```env
MAILER_DSN=smtps://...
MAILER_FROM=commandes@hodina.fr
MAILER_FROM_NAME="Hodina"
```

La valeur suivante reste acceptable dans `.env` comme valeur de sécurité par défaut, mais ne valide aucun envoi réel :

```env
MAILER_DSN=null://null
```

## Décision de validation

Un e-mail de commande est considéré validé uniquement si les trois conditions sont vraies :

```text
EmailLog = SENT
+ MAILER_DSN réel côté serveur
+ réception effective dans une boîte mail réelle
```

## Impact J5K

Après cette dette, J5K peut démarrer sans risque de confusion entre :

- données runtime à conserver ;
- assets à régénérer ;
- secrets environnementaux ;
- futurs champs GPS sur `Address` et `CustomerOrder`.
