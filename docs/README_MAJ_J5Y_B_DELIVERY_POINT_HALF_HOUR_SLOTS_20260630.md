# README mise à jour — J5Y-B — Créneaux panier point de remise par demi-heure

## Résumé

J5Y-B améliore l’expérience client lors d’un choix de point de remise.

Avant, le client pouvait saisir une heure libre. Maintenant, il choisit un créneau disponible, généré à partir des plages horaires actives du point de remise.

## Décision UX

Le client ne choisit plus une heure isolée. Il choisit un créneau lisible :

```text
08:00 – 08:30
08:30 – 09:00
...
```

Cela limite les erreurs et évite de promettre une remise hors des horaires configurés.

## Règles

- les créneaux durent 30 minutes ;
- un créneau doit commencer dans une plage active ;
- un créneau doit finir avant ou exactement à la fin de la plage ;
- l’heure de fin de plage n’est pas proposée comme début ;
- le serveur refuse une heure hors plage ou non alignée sur 30 minutes.

## Tests locaux

```powershell
php -l src\Form\CheckoutType.php
php -l src\Service\DeliveryPointCartService.php
php -l src\Controller\CheckoutController.php
php -l tools\assert-j5y-b-delivery-point-half-hour-slots.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-b-delivery-point-half-hour-slots.php
```

## Tests navigateur

Créer ou utiliser un produit en point de remise, puis vérifier au panier :

- choisir un point de remise ;
- choisir une date ;
- voir une liste de créneaux de 30 minutes ;
- vérifier qu’une plage `08:00–12:00` s’arrête à `11:30–12:00` ;
- vérifier qu’aucun créneau hors plage n’est proposé ;
- valider une commande ;
- vérifier en admin que le rendez-vous correspond au créneau choisi.

## Hors périmètre

Cette mise à jour ne modifie pas :

- les frais de livraison ;
- le panier standard ;
- les calendriers par zone tarifaire ;
- les produits sur créneau indicatif ;
- les quotas par créneau.
