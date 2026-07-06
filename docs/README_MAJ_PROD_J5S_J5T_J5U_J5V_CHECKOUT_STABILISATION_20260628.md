# README MAJ PROD — Checkout stabilisation J5S / J5T-C / J5U / J5V — 2026-06-28

## Résumé

Cette mise à jour documentaire acte la MEP production du bloc checkout stabilisé.

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`  
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`

## Validé production

- J5S-B-ter/quater : séparation standard / point de remise.
- J5T-C : checkout invité avec e-mail existant, popup puis rattachement.
- J5U-A : expéditeur e-mails paramétrable, `commande@hodina.fr`.
- J5V-A : délai minimum produit corrigé, validation serveur rebranchée par `3b508d0`.

## Tests minimum production annoncés OK

- accueil / panier accessibles ;
- point de remise avec délai trop proche refusé ;
- point de remise avec délai valide accepté ;
- invité avec e-mail existant : popup puis rattachement ;
- livraison standard / point de remise sans régression visible ;
- `ORDER_CREATED` vérifié au minimum.

## Warnings non bloquants

- `public/uploads/products/.gitkeep` reste suivi par Git ; les uploads produits doivent rester traités comme runtime.
- Dépréciations Doctrine/EasyAdmin à traiter dans un lot dette technique futur.

## Commandes de contrôle utiles

```bash
cd ~/hodina.fr
git rev-parse --short HEAD
git describe --tags --exact-match HEAD 2>/dev/null || true
git status --short
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status --no-interaction
```

## Reprise recommandée

Reprendre J5W seulement après cette documentation. `DeliveryArea` doit rester une couche planning/exploitation et ne doit pas remplacer `DeliveryZone`.
