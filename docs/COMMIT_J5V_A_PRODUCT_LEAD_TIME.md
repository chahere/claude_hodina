# COMMIT J5V-A — Délai minimum de commande par produit

## Objectif

Permettre à l’administrateur de définir, produit par produit, un délai minimum entre la commande client et la remise/livraison prévue.

Exemple : un collier de fleurs peut nécessiter une commande au moins 48 h avant le rendez-vous au point de remise.

## Décision métier

La règle est portée par le produit, pas par le point de remise.

- `0` ou vide : aucune contrainte.
- `24` : commande au moins 24 h avant le rendez-vous.
- `48` : commande au moins 48 h avant le rendez-vous.
- Panier multi-produits : Hodina applique le délai le plus strict.

## Périmètre J5V-A

- Ajout de `Product.minimumOrderLeadTimeHours`.
- Affichage dans EasyAdmin Produit, zone précommande/délais.
- Validation bloquante au checkout uniquement lorsqu’un point de remise est utilisé, car le client renseigne une date et une heure exactes.
- Affichage d’une aide dans le panier quand un produit impose un délai.

## Hors périmètre

- Pas de calendrier avancé.
- Pas de règle par point de remise.
- Pas de modification des e-mails.
- Pas de modification des statuts commande.
- Pas de blocage livraison standard tant que le client ne choisit pas une date/heure de livraison standard.

## Tests attendus

- Produit sans délai : comportement inchangé.
- Produit à point imposé avec délai 48 h : rendez-vous avant 48 h refusé.
- Produit à point imposé avec délai 48 h : rendez-vous après 48 h accepté.
- Panier multi-produits : délai le plus strict appliqué.
- EasyAdmin : le champ est visible et sauvegardable sur le produit.


## Statut au 27/06/2026

Le code et la migration sont présents dans les sources fournies le 27/06/2026 :

```text
Product.minimumOrderLeadTimeHours
Version20260626194000
```

Mise à jour 28/06/2026 : J5V-A est annoncé validé localement et recette fonctionnellement. La production n’est pas actée.

État corrigé : l’absence d’appel serveur à `DeliveryPointCartService::validateMinimumOrderLeadTime()` a été confirmée comme une régression, puis corrigée par le commit `3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout`. La validation serveur est rebranchée dans `CheckoutController` et la recette est validée sous le tag `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Correctif 28/06/2026 — Validation serveur checkout rebranchée

Régression détectée : le champ `Product.minimumOrderLeadTimeHours` était bien présent dans EasyAdmin et `DeliveryPointCartService::validateMinimumOrderLeadTime()` existait, mais la validation n’était plus appelée dans le checkout point de remise.

Correctif :

```text
3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout
```

Tag recette :

```text
recette-j5v-a-checkout-lead-time-fix-20260628
```

Résultat validé en recette : produit à délai 48 h, rendez-vous trop proche refusé, message global affiché, aucune commande créée avant correction de la date/heure. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Production 29/06/2026 — J5V-A validé

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

J5V-A est validé production après correction `3b508d0`. Le délai minimum produit est appliqué au checkout point de remise côté serveur. Tests minimum production annoncés OK : rendez-vous trop proche refusé, rendez-vous valide accepté. Aucune migration nouvelle après `Version20260626194000`.
