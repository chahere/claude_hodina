# README — Mise à jour J5K-v8-bis création automatique adresse de facturation

## Objectif

Corriger l’ouverture du panier lorsque le client connecté n’a pas encore d’adresse de facturation enregistrée.

## Règle appliquée

À l’ouverture du panier :

1. utiliser `customer.billing_address_id` si disponible ;
2. sinon utiliser une adresse `BILLING` existante ;
3. sinon créer une adresse `BILLING` en copiant la première adresse client disponible ;
4. sinon proposer au client d’ajouter une adresse de facturation.

## Point de vigilance

La copie depuis une adresse de livraison ne doit pas transporter les informations terrain : pas de GPS, pas d’instructions livreur, pas de notes livreur.

## Tests

- panier avec client ayant une adresse de facturation par défaut ;
- panier avec client ayant une adresse `BILLING` non définie par défaut ;
- panier avec client ayant seulement une adresse `DELIVERY` ;
- panier avec client sans adresse ;
- validation commande avec livraison GPS/instructions inchangée ;
- admin, fiche terrain et portail livreur inchangés.
