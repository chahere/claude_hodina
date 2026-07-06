# README MAJ — J5S-B-ter/quater — Checkout point de remise / livraison standard

## But de la mise à jour documentaire

Documenter les correctifs successifs appliqués au checkout point de remise : séparation stricte point/adresse standard, feedback global mobile, masquage des points en mode standard, référence commande robuste et validation conditionnelle du formulaire.

## État documenté

- Code présent dans les sources du 28/06/2026.
- Tests locaux fonctionnels améliorés.
- Recette validée sous le tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628`.
- Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Règles à retenir

1. En mode point de remise, la commune du `DeliveryPoint` est la source du calcul des frais.
2. En mode standard, la commune de l’adresse client reste la source du calcul des frais.
3. Les `DeliveryPoint` ne sont visibles que lorsque le mode point de remise est actif.
4. Formulation pré-J5W-A : `DeliveryZone` gardait la frontière PT/GT/barge/BFS ; depuis J5W-A, le forfait local passe par `DeliveryPricingZone` / `DeliveryCommune.localPricingZone`.
5. Les futurs `DeliveryArea` ne devront pas remplacer `DeliveryZone`.
6. Les erreurs client doivent apparaître au bon moment : pas d’alerte rouge avant tentative de validation.
7. Les messages maîtrisés doivent être en français.

## Contrôles rejoués avant production / à conserver

```powershell
php -l src/Controller/CartController.php
php -l src/Controller/CheckoutController.php
php -l src/Form/CheckoutType.php
php -l src/Service/DeliveryPointCartService.php
php -l src/Service/OrderReferenceGenerator.php
php -l src/Entity/Product.php
php bin/console lint:twig templates/cart/index.html.twig templates/product/catalogue.html.twig templates/product/show.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tag recette validé

```text
recette-j5s-b-ter-quater-checkout-point-standard-20260628
```

## Hors périmètre

- Livraison express.
- DeliveryArea.
- Affectation livreur par sous-zone.
- Modification client du rendez-vous après commande.
- Changement e-mails/SMS/Djama.

## Addendum 29/06/2026 — Production validée

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Les tests minimum production sont annoncés OK. Cette validation production clôture le cycle recette → production pour le bloc checkout stabilisé. J5W / `DeliveryArea` reste prévu/non codé et ne doit pas modifier les responsabilités `DeliveryZone`.
