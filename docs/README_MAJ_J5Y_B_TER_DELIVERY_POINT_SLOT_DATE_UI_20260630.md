# README MAJ — J5Y-B-ter — Harmonisation UI date et créneau point de remise

Date : 2026-06-30

## Résumé

J5Y-B-ter améliore uniquement l’interface du panier point de remise. Après J5Y-B, le client pouvait choisir un créneau de 30 minutes. Après J5Y-B-bis, le select était visible. Il restait à rendre le champ `Date de rendez-vous` cohérent visuellement avec le select `Créneau de remise`.

## Pourquoi

Le champ date natif du navigateur apparaissait trop brut : hauteur, bordure, padding et alignement différents du select. Pour un client mobile, cela donnait une impression de formulaire non fini.

## Règle UX

Le bloc doit être simple :

```text
Date de rendez-vous
[ Choisis une date ]

Créneau de remise
[ Choisis d’abord une date ]
```

Les deux champs doivent avoir le même niveau visuel.

## Fichiers modifiés

- `templates/cart/index.html.twig`
- `public/css/style_mobile.css`
- `tools/assert-j5y-b-delivery-point-half-hour-slots.php`

## Tests recommandés

```powershell
php -l src\Form\CheckoutType.php
php -l src\Service\DeliveryPointCartService.php
php -l src\Controller\CheckoutController.php
php -l tools\assert-j5y-b-delivery-point-half-hour-slots.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-b-delivery-point-half-hour-slots.php
```

## Tests navigateur

- Panier avec produit en point de remise.
- Date de rendez-vous visible et propre.
- Créneau de remise visible et propre.
- Avant date : placeholder `Choisis d’abord une date`.
- Date couverte : créneaux par demi-heure.
- Date non couverte : message `Aucun créneau disponible ce jour-là`.
- Validation commande OK.

## Notes

Cette correction est volontairement UI. La validation serveur de J5Y-B reste la source de vérité.
