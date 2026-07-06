# COMMIT DOCS — J5S / J5T-C / J5V-A recette — 2026-06-28

## Résumé

Mise à jour documentaire après validation recette de J5S-B-ter/quater et J5T-C, et après annonce de validation fonctionnelle J5V-A.

## Statuts actés

- J5S-B-ter/quater : validé recette, production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
- J5T-C : validé localement + recette, commit `38f9e23`, tag `recette-j5t-c-checkout-existing-account-20260628`, production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
- J5U-A : rappel validé recette, expéditeur `commande@hodina.fr`.
- J5V-A : régression détectée puis corrigée par `3b508d0`; validation serveur checkout rebranchée et revalidée recette sous `recette-j5v-a-checkout-lead-time-fix-20260628`.
- J5W : prévu / non codé.

## Incohérence levée ou signalée

Les anciennes sections indiquant `J5T-C non clôturé` ou `J5S-B-ter/quater recette à faire` sont supersédées par cette mise à jour.

Incohérence traitée le 28/06/2026 : le service de validation J5V-A existait, mais son appel serveur avait été débranché du checkout. Le correctif `3b508d0` réintroduit l’appel à `DeliveryPointCartService::validateMinimumOrderLeadTime()` dans `CheckoutController` pour le flux point de remise.

## Pas de changement code

Cette mise à jour ne modifie que `docs/`.


## Addendum 29/06/2026 — Production validée

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Les tests minimum production sont annoncés OK. Cette mise à jour supersède les mentions antérieures indiquant que la production n’était pas encore actée.
