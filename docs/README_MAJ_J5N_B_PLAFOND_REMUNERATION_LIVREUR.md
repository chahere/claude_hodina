# J5N-B — Plafond de rémunération livreur par commande

Dernière mise à jour : **22/06/2026**

## Objectif

Limiter la rémunération livreur à un maximum par commande pendant le pilote.

Règle métier validée :

- plafond global Hodina par défaut : **20 €** ;
- plafond modifiable dans les réglages globaux avec la clé `global_delivery_courier_payout_cap` ;
- plafond spécifique possible sur la fiche utilisateur livreur ;
- si le livreur a un plafond spécifique > 0, il remplace le plafond global ;
- vide ou 0 = pas de plafond pour le niveau concerné ;
- le plafond s’applique à la rémunération livreur, pas aux frais payés par le client.

## Données ajoutées

- `customer.courier_payout_cap` : plafond spécifique par livreur.
- `hodina_setting.global_delivery_courier_payout_cap` : plafond global par défaut.

## Impact calcul logistique

Le service logistique conserve le détail :

- `uncappedCourierPayout` : rémunération calculée avant plafond ;
- `estimatedCourierPayout` : rémunération effective après plafond ;
- `courierPayoutCap` : plafond utilisé ;
- `courierPayoutCapApplied` : indique si le plafond a réellement réduit le montant.

## Point important

Avant assignation d’un livreur, le panier et l’admin utilisent le plafond global.
Au moment où un livreur prend la commande dans Djama, le snapshot logistique est recalculé selon le plafond spécifique du livreur assigné si ce plafond est renseigné.
