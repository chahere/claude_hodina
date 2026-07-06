# README MAJ — J5K-v8-ter — Facturation par défaut et cases à cocher

## Objectif

Stabiliser l'expérience panier avant déploiement recette v8 :

1. l'adresse de facturation par défaut du client doit être affichée automatiquement ;
2. le choix “par défaut” doit passer par une case à cocher prise en compte à la validation du panier ;
3. les cartes restent cliquables pour sélectionner l'adresse de la commande en cours.

## Décision UX

- Carte cliquable = sélectionner l'adresse pour cette commande.
- Case à cocher = définir cette adresse comme adresse par défaut lors de la validation.
- Plus de bouton séparé “Utiliser cette adresse ... par défaut” sous chaque carte.

## Critères d'acceptation

- Le panier affiche l'adresse de facturation si `customer.billing_address_id` existe.
- Si aucune adresse de facturation par défaut n'existe, Hodina suit la règle J5K-v8-bis : adresse BILLING existante puis création depuis une adresse client.
- La case “Utiliser cette adresse par défaut” met à jour `customer.billing_address_id` à la validation.
- La case livraison met à jour `customer.delivery_address_id` à la validation.
- Aucun GPS/instruction livreur côté facturation.

## Note de correction quater

La décision v8-ter a ensuite été affinée :

- les cases par défaut ne sont plus visibles dans les blocs `Adresse utilisée` ;
- les cases par défaut ne sont plus visibles sur les cartes ;
- le libellé final est `Utiliser cette adresse par défaut` ;
- la case apparaît uniquement dans le formulaire d'ajout/modification de l'adresse concernée.
