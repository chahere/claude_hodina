# COMMIT J5I PREP — Préouverture commerciale et compte à rebours

## Statut

Préparation fonctionnelle et technique décidée le 13/06/2026.

Ce fichier documente le futur jalon J5I. Il ne signifie pas que le code est déjà livré.

## Objectif général

Ajouter une bannière de préouverture avec compte à rebours, paramétrable depuis EasyAdmin, et bloquer toute commande avant l'ouverture officielle.

## Inspiration visuelle

Référence fournie :

```text
Notre site web est presque prêt
Jours / Heures / Minutes / Secondes
M'avertir quand c'est prêt
Champ email
Bouton soumettre
```

Adaptation Hodina : charte graphique Hodina, logo Hodina, texte local, mobile-first, capture e-mail simple.

## Règles métier

Pendant la préouverture :

```text
catalogue visible
produits visibles
prix visibles
bouton Ajouter au panier désactivé
panier non créable
commande non créable
capture e-mail active si paramétrée
```

Après ouverture : panier actif, checkout actif, bannière masquée ou remplacée.

## Configuration EasyAdmin prévue

```text
isCountdownEnabled
salesOpeningAt
countdownTitle
countdownMessage
countdownButtonLabel
isEmailCaptureEnabled
isCartLockedBeforeOpening
successMessage
```

## Entité prévue

```text
LaunchSubscriber
```

## Service prévu

```text
SalesOpeningService
```

## Blocage obligatoire côté serveur

À protéger :

```text
CartController
CheckoutController
service de création de commande si centralisé
```

## Jalon production

La production sera mise à jour après validation de J5I en recette.
