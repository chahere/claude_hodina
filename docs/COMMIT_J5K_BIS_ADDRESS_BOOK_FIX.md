# Commit — J5K-bis correction carnet d'adresses enrichi

## Contexte

La recette `j5k-gps-livraison-recette-v6` a validé le cœur de J5K-bis :

- commande avec GPS ;
- commande sans GPS ;
- commande avec instructions ;
- commande sans instructions ;
- affichage admin ;
- fiche terrain ;
- portail livreur ;
- lien Google Maps livreur.

Mais la recette terrain a révélé une anomalie côté carnet d'adresses client : une adresse enrichie avec GPS et instructions pouvait être perdue ou remplacée après un parcours utilisant une adresse identique sans GPS / sans instruction.

## Anomalies constatées en recette

### Anomalie 1 — adresse enrichie non retrouvée

Scénario constaté :

```text
1. Créer une commande avec GPS + instructions.
2. Créer ensuite une commande sans instruction / sans GPS.
3. Revenir au panier.
4. Chercher à réutiliser l'adresse enrichie.
```

Résultat obtenu :

```text
L'adresse enrichie n'est plus proposée correctement.
Il reste surtout des adresses sans GPS ni instructions.
```

Résultat attendu :

```text
L'adresse avec GPS + instructions doit rester disponible dans Mes adresses de livraison.
Une commande sans GPS / sans instruction ne doit pas effacer l'adresse enrichie existante.
```

### Anomalie 2 — bouton adresse par défaut absent

Le panier devait proposer une action rapide pour revenir à l'adresse de livraison principale / la plus utile.

### Anomalie 3 — modification et ajout d'adresse pas assez distingués

Le parcours devait mieux distinguer :

```text
Modifier l'adresse sélectionnée
Ajouter / utiliser une nouvelle adresse
```

## Correction appliquée

### Sélection d'adresse par défaut Hodina

Comme le modèle `Address` ne possède pas encore de champ `is_default`, le panier choisit une adresse proposée par défaut selon une règle prudente :

```text
1. priorité aux adresses avec GPS ;
2. puis aux adresses avec instructions de livraison ;
3. puis aux adresses avec notes terrain internes ;
4. puis l'adresse la plus récente.
```

Cette règle évite qu'une adresse vide récente remplace automatiquement une adresse terrain enrichie.

### Bouton client ajouté

Ajout du bouton :

```text
Utiliser l'adresse par défaut
```

Le bouton reprend l'adresse proposée par défaut et renseigne :

- adresse ;
- commune ;
- zone ;
- instructions ;
- GPS latitude / longitude / précision ;
- identifiant d'adresse existante.

### Réutilisation d'adresse corrigée

Lorsqu'un client saisit une nouvelle adresse sans sélectionner une adresse existante, la recherche d'une adresse réutilisable devient plus stricte pour la livraison.

Elle compare maintenant :

- ligne 1 ;
- ligne 2 ;
- code postal ;
- commune ;
- zone ;
- instructions de livraison ;
- GPS latitude ;
- GPS longitude ;
- précision GPS.

Ainsi, une adresse sans GPS / sans instruction ne réutilise plus automatiquement une adresse enrichie identique sur le texte.

## Règle métier conservée

```text
Address = carnet d'adresses vivant du client.
CustomerOrder = snapshot figé au moment de la commande.
```

Une commande sans GPS ou sans instruction doit rester valide, mais elle ne doit pas effacer une adresse enrichie existante.

## Fichiers modifiés

```text
src/Controller/CheckoutController.php
templates/cart/index.html.twig
docs/COMMIT_J5K_BIS_ADDRESS_BOOK_FIX.md
docs/README_MAJ_J5K_BIS_ADDRESS_BOOK_FIX.md
```

## Tests à faire

```text
[ ] Refaire une commande avec GPS + instructions.
[ ] Refaire une commande sans GPS / sans instructions.
[ ] Revenir au panier.
[ ] Vérifier que l'adresse enrichie reste disponible.
[ ] Cliquer sur Utiliser l'adresse par défaut.
[ ] Vérifier que GPS + instructions sont restaurés dans le formulaire.
[ ] Modifier l'adresse sélectionnée et vérifier qu'elle reste liée à son id.
[ ] Utiliser une nouvelle adresse et vérifier qu'une adresse séparée est créée.
[ ] Vérifier admin, fiche terrain et portail livreur.
```
