# README mise à jour docs — J5X/J5Y au 01/07/2026

Date : 2026-07-01

## Note de mise à jour

Ce README décrit l’état de reprise au début du 01/07/2026. Il est supersédé pour J5Y par la validation recette finale `recette-j5y-carnet-livraison-footer-clean-20260701`. Il reste utile pour comprendre la séquence initiale J5X/J5Y, mais ne doit plus être utilisé comme statut opérationnel courant de J5Y.

## Contexte

La discussion est devenue trop large : J5X, J5Y-A/B/C/D, homepage catalogue, page Découvrir Hodina, header logo et favicons. Cette mise à jour documentaire sert à figer l’état réel avant de reprendre dans un nouveau chat.

## État réel à retenir

### Validé production

- J5W-A zones tarifaires locales par secteur : validé production sous `prod-j5w-a-local-pricing-zones-20260629`.
- J5S/J5T/J5U/J5V checkout stabilisation : validé production sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

### Déployé recette mais validation navigateur à terminer

- J5X-A/B/C/D : tag recette `recette-j5x-livraison-catalogue-20260630-1440`.

### Validé local / source actuelle, non recette

- J5Y-A : interface guidée plages horaires point de remise.
- J5Y-B : créneaux demi-heure panier point de remise.
- J5Y-C : catalogue sur `/`, Découvrir Hodina sur `/blog/decouvrir-hodina`.
- J5Y-D : logo header amélioré ; favicon encore à arbitrer.

## Reprise recommandée

1. Vérifier `git status --short`.
2. Finaliser le favicon ou le sortir du périmètre bloquant.
3. Committer uniquement les fichiers voulus.
4. Rejouer tous les asserts J5X/J5Y.
5. Tester navigateur local complet.
6. Préparer merge `develop → main` et tag recette J5Y.

## Commandes de contrôle locales

```powershell
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-a-delivery-pricing-zones.php
php tools/assert-j5x-b-delivery-schedules.php
php tools/assert-j5x-c-product-delivery-promises.php
php tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php tools/assert-j5x-d-catalogue-search-filters.php
php tools/assert-j5y-a-delivery-point-window-ui.php
php tools/assert-j5y-b-delivery-point-half-hour-slots.php
php tools/assert-j5y-c-homepage-catalogue-discover.php
php tools/assert-j5y-d-header-logo-favicon.php
```

## Exclusions Git

Ne pas ajouter avec Git :

```text
*.zip
*.patch
*.bak
*.old
*.corrected.php
public/images/favicon-*.png.old
```

Utiliser seulement des `git add` ciblés.
