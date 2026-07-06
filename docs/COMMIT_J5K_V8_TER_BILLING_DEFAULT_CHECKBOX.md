# COMMIT — J5K-v8-ter — Facturation par défaut et cases à cocher panier

## Contexte

Les tests locaux J5K-v8 ont montré que l'utilisateur pouvait avoir une adresse de facturation par défaut côté backoffice (`customer.billing_address_id`) sans que le bloc panier affiche correctement cette adresse à l'ouverture.

Le panier proposait aussi des boutons séparés “Utiliser cette adresse ... par défaut” sous chaque carte, alors que la règle UX retenue est désormais :

- la carte sélectionne l'adresse pour la commande en cours ;
- la case à cocher “Utiliser cette adresse ... par défaut” applique le choix lors de la validation du panier.

## Changements

- Réapplication explicite des valeurs initiales des champs techniques `mapped => false` dans le formulaire panier.
- L'adresse de facturation par défaut est visible dans le bloc “Adresse de facturation utilisée” dès l'ouverture du panier.
- Suppression des boutons séparés “Utiliser cette adresse de livraison/facturation par défaut” sous les cartes.
- Conservation des cases à cocher :
  - “Utiliser cette adresse par défaut” ;
  - “Utiliser cette adresse par défaut”.
- Les cases sont prises en compte à la validation du panier.

## Règles conservées

- Le clic sur une carte sélectionne l'adresse pour la commande en cours.
- La facturation n'affiche pas de GPS, ni d'instructions livreur, ni de zone côté client.
- La livraison conserve les instructions livreur et le GPS.
- Les snapshots commande/admin/livreur ne sont pas modifiés.

## Tests à refaire

- Ouvrir le panier avec un client ayant `billing_address_id` renseigné.
- Vérifier que le bloc “Adresse de facturation utilisée” affiche cette adresse.
- Sélectionner une autre carte de facturation.
- Cocher “Utiliser cette adresse par défaut”.
- Valider la commande.
- Vérifier que `customer.billing_address_id` pointe sur l'adresse choisie.
- Vérifier que le parcours livraison reste inchangé.
