# Commit J5G-B2 — Modèle Doctrine modifiable communes / liaisons

## Statut

**Terminé, testé localement, commité, déployé sur recette et validé.**

## Objectif

Transformer la source communes / voisinage validée en modèle Doctrine modifiable.

Le but n'était pas encore de calculer le chemin, mais de préparer une base propre pour que le calcul puisse ensuite être fiable.

## Fichiers principaux

```text
src/Entity/DeliveryCommune.php
src/Entity/DeliveryCommuneConnection.php
src/Controller/Admin/DeliveryCommuneCrudController.php
src/Controller/Admin/DeliveryCommuneConnectionCrudController.php
src/Controller/Admin/DashboardController.php
migrations/Version20260607213000.php
migrations/Version20260607214500.php
```

## DeliveryCommune enrichi

Champs ajoutés :

```text
slug
postalCode
inseeCode
parentInseeCode
isLogisticsPoint
```

La relation `neighboringCommunes` est conservée pour compatibilité avec J5F-A/J5F-B.

## DeliveryCommuneConnection créé

Rôle : représenter un lien typé entre deux points logistiques.

Champs :

```text
fromCommune
toCommune
linkType
isBidirectional
hopCount
customerExtraFee
courierExtraPayout
isActive
internalNote
createdAt
updatedAt
```

Types :

```text
LAND
BARGE
```

## EasyAdmin

Le backoffice permet désormais de consulter et modifier :

```text
Logistique → Communes livrées
Logistique → Liaisons logistiques
```

## Migrations

```text
Version20260607213000
→ création / évolution du modèle

Version20260607214500
→ alignement schéma Doctrine / MariaDB
```

## Incidents et corrections

### Patch corrompu

Le patch correctif initial a été rejeté :

```text
error: corrupt patch
```

Décision : ne pas forcer, créer la migration corrective manuellement.

### BOM UTF-8 PowerShell

La migration créée avec PowerShell a d'abord produit :

```text
strict_types declaration must be the very first statement
```

Cause : BOM invisible avant `<?php`.

Correction : réécriture UTF-8 sans BOM.

### Recette

Une faute de frappe `hp` au lieu de `php` a empêché la première exécution de migration. Après correction, les migrations ont été appliquées et le schéma est devenu valide.

## Tests validés

Local :

```text
php -l OK
doctrine:migrations:migrate OK
doctrine:schema:validate OK
lint:container OK
```

Recette :

```text
git pull OK
migrations OK
schema:validate OK
cache clear/warmup OK
lint:container OK
interface EasyAdmin visible
```

## Ce que J5G-B2 ne fait pas

```text
pas de seed complet
pas de BFS
pas de calcul avancé des frais
pas de modification checkout
pas de snapshot CustomerOrder
```
