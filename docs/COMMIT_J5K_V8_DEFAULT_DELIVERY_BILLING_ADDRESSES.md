# Commit J5K-v8 — Adresses par défaut livraison / facturation dans le panier

Date : 2026-06-19
Contexte : suite recette J5K / J5K-bis v6-v7.

## Objectif

Clarifier la gestion des adresses client dans le panier sans casser le flux commande déjà validé.

La recette J5K-bis avait validé :

- GPS facultatif fonctionnel ;
- instructions de livraison fonctionnelles ;
- commande avec ou sans GPS ;
- commande avec ou sans instructions ;
- affichage admin ;
- fiche terrain ;
- portail livreur ;
- conservation des adresses enrichies GPS + instructions dans le carnet.

La recette a ensuite montré que le client doit pouvoir distinguer clairement :

- sélectionner une adresse pour la commande en cours ;
- définir une adresse de livraison par défaut ;
- définir une adresse de facturation par défaut ;
- modifier une adresse existante ;
- créer une nouvelle adresse.

## Décision métier

### Adresse de livraison

Le client peut avoir plusieurs adresses de type `DELIVERY`.
Une seule adresse de livraison peut être utilisée comme adresse par défaut via :

```text
customer.delivery_address_id
```

À l'ouverture du panier :

1. Hodina utilise `customer.delivery_address_id` si elle existe et si elle est de type `DELIVERY`.
2. Si aucune adresse de livraison par défaut n'existe encore, Hodina garde un repli prudent : adresse la plus utile terrain, puis plus récente.
3. La zone de livraison reste un champ métier calculé automatiquement côté serveur. Elle n'est plus affichée dans le bloc client du panier.

Le bloc livraison affiche au client :

- adresse utilisée ;
- commune livrée ;
- instructions livreur si renseignées ;
- GPS si renseigné.

### Adresse de facturation

Le client peut avoir plusieurs adresses de type `BILLING`.
L'adresse de facturation par défaut reste portée par :

```text
customer.billing_address_id
```

Le panier affiche désormais explicitement un bloc adresse de facturation, séparé du bloc livraison.

## Changements techniques

### Base de données

Migration ajoutée :

```text
migrations/Version20260619170000.php
```

Ajout de la colonne :

```text
customer.delivery_address_id
```

avec index et clé étrangère vers `address.id` en `ON DELETE SET NULL`.

### Entité Customer

Ajout :

- propriété `$deliveryAddress` ;
- `getDeliveryAddress()` ;
- `setDeliveryAddress()` ;
- nettoyage de la référence si l'adresse est retirée de la collection client.

### Panier

Ajout de routes côté panier :

```text
cart_set_default_delivery_address
cart_set_default_billing_address
```

Ces routes définissent respectivement l'adresse de livraison ou de facturation par défaut, puis redirigent vers le panier.

### Interface panier

Le panier distingue deux blocs :

1. **Adresse de livraison utilisée**
   - adresse ;
   - commune ;
   - instructions ;
   - GPS ;
   - icône stylo pour modifier ;
   - option “Utiliser cette adresse par défaut”.

2. **Adresse de facturation utilisée**
   - adresse de facturation ;
   - commune / code postal ;
   - icône stylo pour modifier ;
   - option “Utiliser cette adresse par défaut”.

Les cartes d'adresses existantes conservent la sélection au clic, mais ajoutent des boutons explicites :

- “Sélectionner cette adresse” ;
- “Utiliser cette adresse par défaut” ;
- “Utiliser cette adresse par défaut”.

### Règle importante conservée

Le snapshot commande reste inchangé : une commande validée garde ses données figées même si le client modifie ensuite son carnet d'adresses.

## Tests fonctionnels attendus

- Créer une commande avec GPS + instructions.
- Définir cette adresse comme adresse de livraison par défaut.
- Créer ensuite une commande sans GPS / sans instruction.
- Vérifier que l'adresse enrichie reste disponible.
- Revenir au panier : l'adresse de livraison par défaut doit être reprise automatiquement.
- Sélectionner une autre adresse sans la définir par défaut : cela ne doit pas changer le défaut.
- Définir une adresse de facturation par défaut.
- Revenir au panier : l'adresse de facturation par défaut doit être reprise automatiquement.
- Modifier une adresse via le stylo : cela doit remplir le formulaire correspondant.
- Valider une commande avec livraison et facturation séparées.
- Vérifier admin, fiche terrain et portail livreur.

## Hors périmètre volontaire

- Nettoyage automatique des doublons d'adresses existants.
- Interface profil client complète.
- Gestion avancée de suppression d'adresse côté client.
- Paiement en ligne.
