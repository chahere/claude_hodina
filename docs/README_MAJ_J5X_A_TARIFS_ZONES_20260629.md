# README MAJ — J5X-A tarifs zones tarifaires

Date : 2026-06-29  
Statut : implémentation préparée sur `develop`, validation locale à rejouer.

## Objectif

Mettre à jour les frais de livraison client par zone tarifaire locale, sans changer la formule de livraison validée par J5W-A.

Formule à préserver :

```text
Frais livraison client =
forfait local DeliveryCommune.localPricingZone / DeliveryPricingZone
+ coûts de liaison DeliveryCommuneConnection LAND/BARGE
+ supplément multi-vendeurs plafonné
+ plafond global client éventuel.
```

## Tarifs J5X-A

```text
PT_LOCAL          → Petite-Terre       → 12 €
MAMOUDZOU_LOCAL   → Mamoudzou          → 12 €
CENTRE_LOCAL      → Centre             → 17 €
SUD_LOCAL         → Sud                → 21 €
NORD_LOCAL        → Nord               → 21 €
GT_LOCAL          → fallback technique → 21 €
```

`GT_LOCAL` reste un fallback technique. Il ne doit pas être utilisé comme secteur commercial si les communes Grande-Terre sont correctement rattachées à Mamoudzou, Nord, Centre ou Sud.

## Fichiers concernés

```text
migrations/Version20260629141000.php
src/Controller/Admin/DeliveryPricingZoneCrudController.php
tools/assert-j5x-a-delivery-pricing-zones.php
docs/ARCHITECTURE.md
docs/DECISIONS.md
docs/DEPLOIEMENT_PREPROD.md
docs/ENTITIES.md
docs/HISTORIQUE.md
docs/PILOT_STATUS_DETAILED.md
docs/README_MAJ_J5X_A_TARIFS_ZONES_20260629.md
docs/ROADMAP.md
docs/TODO.md
docs/WORKFLOWS.md
docs/COMMIT_J5X_A_TARIFS_ZONES.md
```

## Hors périmètre

J5X-A ne traite pas :

- le calendrier de livraison par secteur ;
- le cutoff 10h J-1 ;
- la promesse de livraison sur fiche produit ;
- les produits sur créneau comme broche de jasmin / collier de fleurs ;
- la recherche catalogue ;
- les filtres et tris catalogue ;
- l’ordre d’affichage admin par catégorie ;
- la disponibilité produit par commune ;
- `DeliveryArea`.

Ces sujets restent prévus pour J5X-B, J5X-C et J5X-D.

## Contrôles locaux recommandés

```powershell
php -l migrations\Version20260629141000.php
php -l tools\assert-j5x-a-delivery-pricing-zones.php
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php tools/assert-j5x-a-delivery-pricing-zones.php
```

Contrôle SQL :

```powershell
php bin/console dbal:run-sql --force-fetch "SELECT code, name, customer_delivery_fee, courier_payout FROM delivery_pricing_zone WHERE code IN ('PT_LOCAL','MAMOUDZOU_LOCAL','CENTRE_LOCAL','SUD_LOCAL','NORD_LOCAL','GT_LOCAL','PETITE_TERRE_LOCAL') ORDER BY code;"
```

## Tests fonctionnels minimum

À vérifier au panier :

```text
Petite-Terre → forfait local 12 €
Mamoudzou    → forfait local 12 €
Centre       → forfait local 17 €
Sud          → forfait local 21 €
Nord         → forfait local 21 €
```

Vérifier aussi :

- `PETITE_TERRE_LOCAL` absent ;
- Dzaoudzi, Labattoir, Pamandzi sur `PT_LOCAL` ;
- Mamoudzou sur `MAMOUDZOU_LOCAL` ;
- Nord/Centre/Sud sur leurs zones J5W-A ;
- les suppléments LAND/BARGE restent appliqués par `DeliveryCommuneConnection` ;
- le plafond global client reste actif ;
- `courierPayout` n’est pas modifié par la migration.

## Commande de commit recommandée

Ne pas utiliser `git add .`.

```powershell
git add migrations/Version20260629141000.php `
  src/Controller/Admin/DeliveryPricingZoneCrudController.php `
  tools/assert-j5x-a-delivery-pricing-zones.php `
  docs/ARCHITECTURE.md `
  docs/COMMIT_J5X_A_TARIFS_ZONES.md `
  docs/DECISIONS.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/ENTITIES.md `
  docs/HISTORIQUE.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/README_MAJ_J5X_A_TARIFS_ZONES_20260629.md `
  docs/ROADMAP.md `
  docs/TODO.md `
  docs/WORKFLOWS.md

git diff --cached --stat
git commit -m "feat(j5x-a): update delivery pricing by sector"
```
