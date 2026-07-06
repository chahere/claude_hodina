# README mise à jour — J5K-bis correction carnet d'adresses enrichi

## Objet

Cette mise à jour complète J5K-bis après recette du tag `j5k-gps-livraison-recette-v6`.

Le GPS, les instructions, l'admin et le portail livreur fonctionnaient, mais la recette a montré que le carnet d'adresses pouvait perdre la capacité de réutiliser une adresse enrichie après une commande sans GPS / sans instruction.

## Ce qui était déjà validé

```text
[OK] Commande avec GPS.
[OK] Commande sans GPS.
[OK] Commande avec instructions.
[OK] Commande sans instructions.
[OK] Admin commande.
[OK] Fiche terrain.
[OK] Portail livreur.
[OK] Lien Google Maps livreur.
[OK] Absence GPS non bloquante.
[OK] Absence instructions non bloquante.
```

## Problème restant

Le problème n'était pas le snapshot de commande. Le snapshot fonctionnait.

Le problème était le carnet d'adresses client :

```text
Une adresse enrichie GPS + instructions pouvait être écrasée ou ne plus être proposée clairement après un parcours de commande utilisant une adresse vide sur les mêmes champs de base.
```

## Règle retenue

Hodina doit conserver deux niveaux de données :

```text
Address
= adresse vivante et réutilisable du client.

CustomerOrder
= copie figée de l'adresse, des instructions et du GPS au moment de la commande.
```

Une commande sans GPS / sans instruction ne doit pas effacer une adresse enrichie existante.

## Correction fonctionnelle

### 1. Adresse par défaut Hodina

Ajout d'une notion d'adresse proposée par défaut sans migration supplémentaire.

Comme il n'existe pas encore de champ `is_default`, l'adresse proposée est calculée ainsi :

```text
priorité GPS
puis instructions de livraison
puis notes terrain internes
puis id le plus récent
```

Cette règle est volontairement pragmatique pour le pilote.

### 2. Bouton client

Ajout du bouton :

```text
Utiliser l'adresse par défaut
```

Il recharge dans le formulaire :

```text
adresse
commune
zone
instructions
GPS
identifiant de l'adresse existante
```

### 3. Recherche d'adresse réutilisable plus stricte

Pour les adresses de livraison, la réutilisation automatique compare maintenant aussi les données terrain :

```text
instructions
GPS latitude
GPS longitude
précision GPS
```

Objectif : éviter qu'une nouvelle commande vide écrase une adresse enrichie.

## Limite assumée

Cette correction ne crée pas encore une vraie colonne `address.is_default`.

Cette colonne pourra être ajoutée plus tard si le carnet d'adresses devient une vraie page client complète.

## Tests de non-régression

```text
[ ] Commande avec GPS + instructions.
[ ] Commande sans GPS.
[ ] Commande sans instructions.
[ ] Réutilisation adresse enrichie.
[ ] Bouton Utiliser l'adresse par défaut.
[ ] Modification adresse existante.
[ ] Nouvelle adresse séparée.
[ ] Admin.
[ ] Fiche terrain.
[ ] Portail livreur.
```
