# COMMIT — J5K-v8-final local — Validation panier adresses par défaut

## Contexte

Après les itérations J5K-v8, v8-bis, v8-ter et v8-quater, le panier a été testé localement avant tout déploiement recette.

L'objectif était de stabiliser l'expérience client autour des deux usages distincts :

```text
Adresse de livraison = localisation terrain, instructions livreur, GPS facultatif.
Adresse de facturation = adresse administrative, sans GPS ni instruction livreur.
```

## Règles finales validées localement

### Adresse de livraison

À l'ouverture du panier pour un client connecté :

1. si `customer.delivery_address_id` est renseigné, Hodina utilise cette adresse ;
2. sinon, si le client possède une adresse de livraison disponible, Hodina sélectionne la première adresse de livraison disponible ;
3. sinon, le panier propose d'ajouter une adresse de livraison.

Le bloc livraison affiche uniquement :

- l'adresse utilisée ;
- la commune ;
- les instructions livreur si présentes ;
- le GPS si présent.

La zone de livraison reste une donnée métier serveur. Elle n'est pas affichée dans le bloc client.

### Adresse de facturation

À l'ouverture du panier pour un client connecté :

1. si `customer.billing_address_id` est renseigné, Hodina utilise cette adresse ;
2. sinon, si une adresse `BILLING` existe dans le carnet du client, Hodina l'utilise ;
3. sinon, si le client possède au moins une adresse, Hodina crée une vraie adresse `BILLING` en copiant uniquement les champs postaux de la première adresse disponible ;
4. sinon, le panier propose d'ajouter une adresse de facturation.

Lors de la création automatique d'une adresse `BILLING`, Hodina ne copie pas :

- GPS ;
- précision GPS ;
- instructions livreur ;
- notes terrain livreur.

### Sélection et adresse par défaut

- Une carte d'adresse est cliquable et sélectionne l'adresse pour la commande en cours.
- Le bouton redondant `Sélectionner cette adresse` n'est plus affiché.
- Les cartes ne changent pas l'adresse par défaut.
- Les blocs `Adresse utilisée` ne proposent pas de case par défaut.
- La case s'appelle uniquement `Utiliser cette adresse par défaut`.
- Cette case est visible seulement dans les formulaires d'ajout ou de modification d'adresse.
- Dans un formulaire de livraison, la case met à jour `customer.delivery_address_id` à la validation du panier.
- Dans un formulaire de facturation, la case met à jour `customer.billing_address_id` à la validation du panier.

## Tests locaux validés

- Ouverture de l'accueil local en HTTP.
- Ouverture catalogue et panier.
- Correction du comportement Brave qui tente parfois HTTPS sur le serveur PHP local : le `Unsupported SSL request` n'est pas une erreur Hodina si les requêtes HTTP finissent en `200`.
- Sélection d'une carte de livraison.
- Sélection d'une carte de facturation.
- Suppression du bouton `Sélectionner cette adresse`.
- Absence de GPS côté facturation.
- Absence d'instructions livreur côté facturation.
- GPS et instructions conservés côté livraison.
- Case `Utiliser cette adresse par défaut` visible uniquement en ajout / édition.
- Prise en compte de la case à la validation du panier.
- Validation locale Symfony : syntaxe PHP, cache clear, Doctrine schema validate.

## État avant reprise

Le correctif est validé localement, mais il reste à refaire la séquence propre de livraison :

```text
commit local final
push main
tag recette propre
déploiement recette
tests recette
documentation finale de recette
```

Aucun déploiement recette de cette version finale ne doit être considéré comme validé tant que les tests recette n'ont pas été rejoués.
