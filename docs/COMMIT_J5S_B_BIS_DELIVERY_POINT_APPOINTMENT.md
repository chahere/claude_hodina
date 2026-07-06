# COMMIT J5S-B-bis — Date/heure client pour point de remise

## Objectif

Adapter le flux point de remise pour que la plage horaire soit une indication de disponibilité Hodina, tandis que le client indique lui-même la date et l’heure réelle de rendez-vous.

## Périmètre validable

- Ajout d’un champ date de rendez-vous pour les commandes en point de remise.
- Ajout d’un champ heure de rendez-vous pour les commandes en point de remise.
- Affichage des plages actives du point comme aide au choix.
- Validation serveur : point autorisé, date obligatoire, heure obligatoire, heure comprise dans une plage active du point.
- Snapshot dans `CustomerOrder` :
  - `deliveryPointScheduledDate`
  - `deliveryPointScheduledTime`
  - plage indicative détectée.
- Affichage du rendez-vous client dans :
  - confirmation commande ;
  - portail client ;
  - EasyAdmin commande ;
  - fiche terrain ;
  - Djama.

## Hors périmètre

- Pas de capacité par créneau.
- Pas de modification client du rendez-vous après commande.
- Pas de changement du checkout standard.
- Pas de changement des statuts Djama.


## Correctif AlreadySubmittedException

Un test de validation panier avec point de remise imposé a déclenché :

```text
AlreadySubmittedException — You cannot change the data of a submitted form.
```

Cause : le contrôleur tentait de faire `setData()` sur `deliveryPointTimeWindowId` après `handleRequest()`.

Correction : conserver la plage détectée dans une variable PHP (`$selectedTimeWindow`) et l’utiliser pour le snapshot de commande, sans réinjecter de données dans le formulaire soumis.

Règle Symfony à conserver : après soumission du formulaire, lire les données est autorisé ; modifier les données du formulaire soumis ne l’est pas.
