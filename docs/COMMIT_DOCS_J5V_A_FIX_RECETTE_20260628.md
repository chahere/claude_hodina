# COMMIT docs — J5V-A correctif checkout lead time recette — 2026-06-28

## Résumé

Documente le correctif J5V-A appliqué après régression du branchement serveur checkout.

## Points actés

- Régression détectée : `minimumOrderLeadTimeHours` présent, service de validation présent, mais validation non appelée dans le checkout.
- Correctif code : `3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout`.
- Tag recette : `recette-j5v-a-checkout-lead-time-fix-20260628`.
- Recette validée : rendez-vous trop proche refusé avec message global.
- Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Non-changements

- Pas de migration nouvelle.
- Pas de changement frais.
- Pas de changement Djama.
- Pas de changement barge/BFS.
- Pas de changement du mode standard.

## Vigilance production

Avant production, promouvoir un tag contenant `3b508d0`, rejouer le scénario produit à délai 48 h trop proche/refusé, puis délai valide/accepté, et rejouer les non-régressions J5S/J5T-C.


## Addendum 29/06/2026 — Production validée

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Les tests minimum production sont annoncés OK. Cette mise à jour supersède les mentions antérieures indiquant que la production n’était pas encore actée.
