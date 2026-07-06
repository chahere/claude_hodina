# Commit J5F-B — DeliveryLogisticsService et CartLogisticsPreview

## Statut

**Réalisé, testé localement, poussé, déployé en préproduction et validé techniquement.**

## Branche

```text
pilot/j5-order-delivery-pricing
```

## Objectif métier

Préparer le calcul automatique des contraintes logistiques du panier.

Le service doit répondre aux questions suivantes :

```text
Quelle est la relation entre la commune client et les vendeurs ?
La commande nécessite-t-elle la barge ?
Quelle zone tarifaire faut-il appliquer ?
Quels frais estimés peut-on afficher ?
Quelles données faudra-t-il figer plus tard dans CustomerOrder ?
```

## Objectif technique

Créer un service métier centralisé avant de modifier le panier.

Décision : ne pas mettre ces règles directement dans Twig, dans CartController ou dans CheckoutController.

## Fichiers ajoutés

```text
src/Service/DeliveryLogisticsService.php
src/Dto/CartLogisticsPreview.php
```

## Pas de migration

J5F-B n'ajoute aucune colonne et aucune table.

Il n'y a donc pas de migration Doctrine.

## DeliveryLogisticsService

Rôle : centraliser les règles de livraison.

Méthodes principales :

```text
previewForCart(?Address $address, array $detailedCart)
getCommuneRelation(DeliveryCommune $clientCommune, ?DeliveryCommune $sellerCommune)
requiresBarge(DeliveryCommune $clientCommune, DeliveryCommune $sellerCommune)
getPricingZoneForRequirement(DeliveryCommune $clientCommune, bool $requiresBarge)
```

## Règle barge

Règle verrouillée :

```text
barge = client.territory !== seller.territory
```

Donc :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

Le voisinage ne déclenche jamais la barge.

## Relations calculées

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
UNKNOWN
```

Priorité globale :

```text
OTHER_TERRITORY > REMOTE_COMMUNE > NEIGHBOR_COMMUNE > SAME_COMMUNE > UNKNOWN
```

## Gestion des cas incomplets

Le service gère :

```text
adresse absente
commune client non paramétrée
vendeur sans commune logistique
commune vendeur inactive
zone tarifaire inactive
```

Ces cas produisent un message ou des warnings plutôt qu'une erreur brutale.

## CartLogisticsPreview

DTO = Data Transfer Object.

Rôle : transporter le résultat calculé vers le futur affichage panier.

Champs :

```text
addressRequired
clientCommuneName
clientTerritory
requiresBarge
hasNeighborSeller
hasRemoteSeller
hasUnknownSellerCommune
relationLevel
estimatedDeliveryFee
estimatedCourierPayout
estimatedDeliveryMargin
pricingZoneName
pricingZoneCode
message
warnings
```

## Pourquoi un DTO ?

Un DTO rend le résultat plus lisible qu'un tableau.

Exemple :

```php
$preview->requiresBarge
$preview->estimatedDeliveryFee
$preview->message
```

C'est plus clair qu'un tableau avec des clés libres.

## Tests locaux validés

```powershell
php -l src\Dto\CartLogisticsPreview.php
php -l src\Service\DeliveryLogisticsService.php
php bin/console cache:clear
php bin/console lint:container
php bin/console debug:container App\Service\DeliveryLogisticsService
```

Résultat : OK.

Note : `debug:container` peut indiquer que le service est inliné ou retiré du container compilé. Ce n'est pas une erreur tant qu'il est autowirable et que `lint:container` est OK.

## Déploiement préproduction

Commandes :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

Résultat : OK.

## Ce qui n'est pas encore fait

J5F-B ne branche pas encore le service :

```text
pas d'affichage panier
pas de modification checkout
pas de gel CustomerOrder
pas de migration
```

## Suite

```text
J5G-A — Aperçu logistique panier
```

À faire :

- injecter `DeliveryLogisticsService` dans le parcours panier ;
- choisir l'adresse client utilisée pour l'estimation ;
- afficher le message ;
- afficher les frais estimés ;
- tester PT/PT, GT/GT, PT/GT et GT/PT.

---

# Note postérieure — J5G-A réalisé et J5G-B préparé

Après J5F-B, le service a été branché dans le panier avec J5G-A.

J5G-A ajoute :

```text
aperçu logistique dans le panier
signature logistique adresse + vendeurs uniques
recalcul seulement quand le périmètre vendeur change
```

Limite observée :

```text
La barge peut être détectée sans changement de frais si les zones tarifaires de test ont les mêmes montants.
```

Décision suivante :

```text
J5G-B doit enrichir DeliveryLogisticsService avec un calcul de plus court chemin entre communes.
```

La formule cible devient :

```text
frais livraison =
frais local client
+ supplément communes traversées
+ supplément barge aller-retour si PT ↔ GT
```
