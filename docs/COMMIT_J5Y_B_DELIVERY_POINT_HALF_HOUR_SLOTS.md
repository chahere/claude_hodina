# Commit J5Y-B — Créneaux panier point de remise par demi-heure

## Objectif

Remplacer la saisie libre de l’heure de rendez-vous en point de remise par un choix de créneau contrôlé.

## Règle métier

Pour chaque plage active d’un point de remise, Hodina propose des créneaux de 30 minutes.

Exemple : `08:00 → 12:00` devient :

- 08:00 – 08:30 ;
- 08:30 – 09:00 ;
- 09:00 – 09:30 ;
- 09:30 – 10:00 ;
- 10:00 – 10:30 ;
- 10:30 – 11:00 ;
- 11:00 – 11:30 ;
- 11:30 – 12:00.

L’heure de fin de plage n’est jamais proposée comme début de rendez-vous.

## Périmètre

Inclus :

- panier point de remise ;
- champ heure caché ;
- select client de créneaux ;
- génération JavaScript depuis les plages actives ;
- validation serveur par pas de 30 minutes ;
- garde-fou statique.

Exclus :

- panier standard ;
- frais livraison ;
- calendrier de livraison par secteur ;
- catalogue ;
- back-office EasyAdmin ;
- quotas par créneau.

## Fichiers principaux

- `src/Form/CheckoutType.php`
- `src/Service/DeliveryPointCartService.php`
- `src/Controller/CheckoutController.php`
- `templates/cart/index.html.twig`
- `public/css/style_mobile.css`
- `tools/assert-j5y-b-delivery-point-half-hour-slots.php`
