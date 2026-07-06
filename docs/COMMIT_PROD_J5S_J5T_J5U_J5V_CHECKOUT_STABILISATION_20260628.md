# COMMIT PROD — J5S / J5T-C / J5U / J5V checkout stabilisation — 2026-06-28

## Objet

Acter la mise en production du bloc checkout stabilisé : livraison standard / point de remise, checkout invité avec e-mail existant, expéditeur e-mails paramétrable et délai minimum produit corrigé.

## Tag production

```text
prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628
```

## Commit promu

```text
d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix
```

## Tags recette liés

```text
recette-j5s-b-ter-quater-checkout-point-standard-20260628
recette-j5t-c-checkout-existing-account-20260628
recette-j5v-a-checkout-lead-time-fix-20260628
```

## Résultat

Production validée après tests minimum annoncés OK.

Lots inclus :

- J5S-B-ter/quater : séparation standard / point de remise ;
- J5T-C : invité avec e-mail existant et rattachement compte ;
- J5U-A : expéditeur e-mails paramétrable `commande@hodina.fr` ;
- J5V-A : délai minimum produit corrigé et appliqué côté serveur.

## Hors périmètre

- J5W / `DeliveryArea` ;
- livraison express ;
- changement des coûts, de la barge, du BFS ou de Djama ;
- nouvelle migration après `Version20260626194000`.

## Points de vigilance

- Formulation pré-J5W-A clarifiée ensuite : conserver `DeliveryZone` / `DeliveryCommune.territory` pour les garde-fous PT/GT et barge ; depuis J5W-A, le forfait local est porté par `DeliveryPricingZone` / `DeliveryCommune.localPricingZone`.
- Garder J5V-A côté serveur, pas seulement côté JavaScript.
- Ne pas créer de commande avant confirmation J5T-C quand l’e-mail existe déjà.
