# Commit J5Y-B-bis — Affichage fiable du select de créneau point de remise

## Objectif

Corriger l’affichage du champ **Créneau de remise** dans le panier point de remise.

J5Y-B avait ajouté la logique de créneaux par demi-heure, mais le test navigateur a montré que le libellé apparaissait sans champ de sélection visible. J5Y-B-bis verrouille l’interface : le select est toujours présent, lisible et explicite, même avant choix de date.

## Correction

- Ajout d’un conteneur dédié `delivery-point-slot-control` autour du select.
- Ajout de la classe `delivery-point-slot-select`.
- CSS renforcé pour forcer un affichage visible du select, y compris désactivé.
- Placeholder initial : `Choisis d’abord une date`.
- Messages JavaScript différenciés :
  - point manquant ;
  - date manquante ;
  - aucun créneau disponible ;
  - créneaux disponibles.
- Conservation de la synchronisation avec les champs cachés Symfony.
- Conservation de la validation serveur J5Y-B.

## Hors périmètre

- Aucun changement catalogue.
- Aucun changement frais de livraison.
- Aucun changement back-office.
- Aucun changement calendrier standard par zone tarifaire.
- Aucun quota par créneau.

## Validation

```powershell
php -l src\Form\CheckoutType.php
php -l src\Service\DeliveryPointCartService.php
php -l src\Controller\CheckoutController.php
php -l tools\assert-j5y-b-delivery-point-half-hour-slots.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-b-delivery-point-half-hour-slots.php
```

## Test navigateur attendu

- Sans date : le select est visible et affiche `Choisis d’abord une date`.
- Avec une date couverte : le select propose des créneaux de 30 minutes.
- Avec une plage 08:00–12:00 : dernier créneau `11:30 – 12:00`.
- Avec une date non couverte : le select indique `Aucun créneau disponible ce jour-là`.
