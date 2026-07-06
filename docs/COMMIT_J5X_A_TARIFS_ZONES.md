# Commit — J5X-A tarifs zones tarifaires

## Résumé

J5X-A met à jour les frais de livraison client par zone tarifaire locale, sans modifier la formule logistique ni la rémunération livreur.

## Changements techniques

- Ajout migration `Version20260629141000`.
- Mise à jour des `customer_delivery_fee` :
  - `PT_LOCAL` : 12 € ;
  - `MAMOUDZOU_LOCAL` : 12 € ;
  - `CENTRE_LOCAL` : 17 € ;
  - `SUD_LOCAL` : 21 € ;
  - `NORD_LOCAL` : 21 € ;
  - `GT_LOCAL` : 21 € fallback technique.
- Ajout garde-fou `tools/assert-j5x-a-delivery-pricing-zones.php`.
- Aide EasyAdmin enrichie sur `DeliveryPricingZoneCrudController`.
- Documentation mise à jour.

## Règles préservées

- `DeliveryLogisticsService` reste la source du calcul livraison.
- Le forfait local vient de `DeliveryCommune.localPricingZone` / `DeliveryPricingZone`.
- Les liaisons LAND/BARGE restent portées par `DeliveryCommuneConnection`.
- `DeliveryCommune.territory` conserve uniquement le garde-fou PT/GT.
- `PETITE_TERRE_LOCAL` ne doit pas être créé.
- `courierPayout` n’est pas modifié.
- Aucun tarif n’est codé en dur dans Twig, JavaScript, contrôleur ou service.

## Contrôles locaux à exécuter

```powershell
php -l migrations\Version20260629141000.php
php -l tools\assert-j5x-a-delivery-pricing-zones.php
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php tools/assert-j5x-a-delivery-pricing-zones.php
```

## Contrôle SQL

```powershell
php bin/console dbal:run-sql --force-fetch "SELECT code, name, customer_delivery_fee, courier_payout FROM delivery_pricing_zone WHERE code IN ('PT_LOCAL','MAMOUDZOU_LOCAL','CENTRE_LOCAL','SUD_LOCAL','NORD_LOCAL','GT_LOCAL','PETITE_TERRE_LOCAL') ORDER BY code;"
```

## Tests fonctionnels minimum

- Panier Petite-Terre : frais locaux 12 €.
- Panier Mamoudzou : frais locaux 12 €.
- Panier Centre : frais locaux 17 €.
- Panier Sud : frais locaux 21 €.
- Panier Nord : frais locaux 21 €.
- Cas PT/GT : barge toujours détectée si trajet concerné.
- Cas multi-vendeurs : supplément et plafond toujours cohérents.

## Hors périmètre

- Jours de livraison paramétrables.
- Cutoff 10h J-1.
- Promesse de livraison sur fiche produit.
- Produits sur créneau.
- Recherche/filtres/tri catalogue.
- Disponibilité produit par commune.
- DeliveryArea.
