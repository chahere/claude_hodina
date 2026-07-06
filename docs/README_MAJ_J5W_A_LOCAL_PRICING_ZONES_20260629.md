# README MAJ — J5W-A zones tarifaires locales par secteur

Statut final : **validé localement + validé recette + validé production**.

Tag recette : `recette-j5w-a-local-pricing-zones-20260629`. Commit recette : `162fcb4 merge(j5w-a): local pricing zones by sector`.
Tag production : `prod-j5w-a-local-pricing-zones-20260629`. Commit production : `cea4d19 docs(j5w-a): record recette validation`.

Date : 29/06/2026  
Branche de travail : `develop`  
Statut : **local fonctionnel OK / recette validée / production validée**

## Résumé

J5W-A introduit un découpage tarifaire local par secteur en réutilisant l’existant Hodina.

Le lot ne remplace pas `PT` / `GT`. Il ajoute une granularité locale pour Grande-Terre tout en conservant Petite-Terre sur `PT_LOCAL`.

## Mapping retenu

| Secteur | Communes | Zone tarifaire |
|---|---|---|
| Mamoudzou | Mamoudzou | `MAMOUDZOU_LOCAL` |
| Nord | Acoua, Bandraboua, Koungou, M'Tsangamouji, Mtsamboro | `NORD_LOCAL` |
| Centre | Chiconi, Ouangani, Sada, Tsingoni | `CENTRE_LOCAL` |
| Sud | Bandrélé, Bouéni, Chirongui, Dembéni, Kani-Kéli | `SUD_LOCAL` |
| Petite-Terre | Dzaoudzi, Labattoir, Pamandzi | `PT_LOCAL` |

## Non-objectifs

J5W-A ne fait pas :

- de nouveau `DeliveryArea` ;
- de planning par secteur ;
- de livraison express ;
- de restriction produit par commune ;
- de changement de checkout ;
- de changement de règle barge.

## Correction incluse

Le champ `deliveryPointCustomerInstructions` est rendu au bon endroit dans le panier. Il ne doit plus apparaître comme champ isolé en bas du panier standard.

## Contrôles SQL utiles

```sql
SELECT z.code, z.name, z.customer_delivery_fee, z.courier_payout
FROM delivery_pricing_zone z
WHERE z.code IN ('GT_LOCAL','PT_LOCAL','MAMOUDZOU_LOCAL','NORD_LOCAL','CENTRE_LOCAL','SUD_LOCAL','PETITE_TERRE_LOCAL')
ORDER BY z.code;
```

Attendu : `PETITE_TERRE_LOCAL` absent.

```sql
SELECT c.name, c.territory, z.code AS local_pricing_zone
FROM delivery_commune c
INNER JOIN delivery_pricing_zone z ON z.id = c.local_pricing_zone_id
ORDER BY c.name;
```

Attendu : les 18 communes Hodina sont rattachées selon le mapping J5W-A.

## Risque principal

Le risque principal est de confondre les notions :

- `DeliveryZone` : historique / compatibilité PT-GT ;
- `DeliveryCommune.territory` : territoire technique PT/GT ;
- `DeliveryPricingZone` : forfait tarifaire local ;
- `DeliveryCommuneConnection` : trajet LAND/BARGE ;
- future `DeliveryArea` : secteur de planning / tournée.

## Validation recette 29/06/2026

La recette a été validée sous le tag `recette-j5w-a-local-pricing-zones-20260629`, commit `162fcb4`.

Contrôles recette actés :

- dépôt propre ;
- schéma Doctrine synchronisé ;
- migration `DoctrineMigrations\Version20260629083000` exécutée, current/latest ;
- garde-fou J5W-A OK ;
- `PETITE_TERRE_LOCAL` absent ;
- `PT_LOCAL` conservé pour Dzaoudzi, Labattoir et Pamandzi ;
- `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL` présents et utilisés par les communes Grande-Terre ;
- tests fonctionnels recette annoncés OK.

## Suite

J5W-A a été promu en production sous le tag `prod-j5w-a-local-pricing-zones-20260629`. La production est actée après contrôles techniques, SQL et navigateur.
