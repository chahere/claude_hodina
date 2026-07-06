# Commit — J5Y-B-ter — Harmonisation UI date et créneau point de remise

## Objectif

Corriger la finition visuelle du panier point de remise après J5Y-B/J5Y-B-bis. Le select de créneau était désormais visible, mais le champ `Date de rendez-vous` restait rendu comme un champ navigateur brut et désaligné.

## Changements

- Harmonisation du bloc date + créneau dans `templates/cart/index.html.twig`.
- Ajout de classes dédiées : `delivery-point-appointment-grid`, `delivery-point-date-input`, `delivery-point-slot-select`.
- CSS commun pour la date et le select : hauteur, bordure, arrondi, padding, focus, comportement mobile.
- Renforcement de l’assert J5Y-B pour verrouiller le rendu visible.

## Hors périmètre

- Aucun changement de frais de livraison.
- Aucun changement de catalogue.
- Aucun changement back-office.
- Aucun changement de validation métier J5Y-B.
- Aucun changement de calendrier standard J5X-B.

## Validation attendue

- Le champ Date de rendez-vous et le champ Créneau de remise ont le même style.
- Sur mobile, les champs sont empilés et lisibles.
- Sur écran large, les champs peuvent être côte à côte.
- Le select reste visible même désactivé.
- Les créneaux de 30 minutes restent générés correctement.
