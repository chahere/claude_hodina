# README — J5X-C — Promesse produit / produits sur créneau

Date : 2026-06-29
Branche de développement : `develop`

## Objectif

Rendre la fiche produit plus rassurante avant l’ouverture publique Hodina :

- produit standard : promesse selon la commune / le secteur client ;
- produit sur créneau : broche de jasmin, collier de fleurs, accueil aéroport, cérémonie ou événement ;
- commune connue : afficher uniquement la promesse pertinente ;
- commune inconnue : afficher un résumé et un tableau repliable.

## Ce que J5X-C ne change pas

J5X-C ne modifie pas :

- la formule des frais de livraison ;
- `DeliveryLogisticsService` ;
- les tarifs J5X-A ;
- le calendrier secteur J5X-B ;
- le checkout ;
- la validation serveur J5V-A ;
- le catalogue recherche/filtres/tri.

## Champs ajoutés sur Product

```text
deliveryPromiseMode
deliveryPromiseTitle
deliveryPromiseDescription
appointmentDeliveryWeekdays
appointmentTimeWindowStart
appointmentTimeWindowEnd
appointmentCutoffTime
appointmentCutoffDaysBefore
```

## Services / DTO

```text
src/Dto/ProductDeliveryPromise.php
src/Service/ProductDeliveryPromiseService.php
```

Le service construit une promesse d’affichage. Il ne calcule pas les frais et ne promet pas une livraison garantie.

## UX fiche produit

Produit standard, commune inconnue :

```text
Livraison selon ta commune
Choisis ta commune au panier pour voir les frais et le prochain passage Hodina.
Voir les jours de livraison par secteur
```

Produit standard, commune connue :

```text
Livraison à Petite-Terre
Ce produit suit les passages Hodina de ton secteur : lundi, jeudi.
Prochain passage possible : jeudi 2 juillet
Commande avant mercredi 10h
```

Produit sur créneau :

```text
Livraison sur créneau
Indique l’heure souhaitée à la commande. Hodina confirme ensuite le créneau selon la disponibilité terrain.
Jours possibles : tous les jours
Plage souhaitée : entre 8h et 18h
Commande : commande à valider avant 10h la veille
```

## Tests minimum

```powershell
php -l migrations\Version20260629163000.php
php -l src\Entity\Product.php
php -l src\Dto\ProductDeliveryPromise.php
php -l src\Service\ProductDeliveryPromiseService.php
php -l src\Controller\Admin\ProductCrudController.php
php -l src\Controller\ProductController.php
php -l tools\assert-j5x-c-product-delivery-promises.php
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-c-product-delivery-promises.php
```

## Tests navigateur ciblés

- EasyAdmin > Produits : vérifier les champs de promesse livraison.
- Produit standard sans commune connue : résumé + tableau repliable.
- Produit standard avec commune connue : promesse du secteur seulement.
- Produit sur créneau : badge “Sur créneau”, jours, plage, cutoff, mention de confirmation Hodina.
- Ajouter au panier reste fonctionnel.

## Règles anti-régression

- Ne pas coder les frais dans la fiche produit.
- Ne pas afficher `PT_LOCAL`, `GT_LOCAL` ou `localPricingZone` au client.
- Ne pas écrire “livraison garantie”.
- Ne pas remplacer J5V-A.
- Ne pas mélanger J5X-C avec J5X-D catalogue.

## Complément J5X-C-bis — clarification du formulaire produit

Après test du formulaire EasyAdmin Produit, les libellés ont été clarifiés pour éviter la confusion entre le message de livraison affiché sur la fiche produit et la création rapide de points de remise.

- Les plages produit sont des plages indicatives.
- Les plages de point de remise sont rangées dans `Avancé — points de remise`.
- La création rapide de point reste disponible mais n’est pas le parcours principal.
- Pour modifier les plages d’un point existant, utiliser le menu `Plages points de remise`.
