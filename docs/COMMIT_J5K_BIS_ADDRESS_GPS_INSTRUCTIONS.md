# COMMIT J5K-bis — Persistance GPS adresse + instructions/commentaires terrain

## Objectif

Compléter J5K GPS livraison sans créer de doublons métier :

- conserver la position GPS dans l’adresse client pour les prochaines livraisons ;
- conserver un snapshot GPS dans la commande au moment de la validation ;
- ajouter une consigne client réutilisable sur l’adresse ;
- permettre au livreur/admin de laisser une note terrain réutilisable ;
- figer les consignes et notes terrain dans la commande pour garder l’historique.

## Décision métier

`Address` reste la donnée vivante et réutilisable.
`CustomerOrder` reste le snapshot figé au moment de la commande.

Le champ historique `Address.notes` est conservé comme consigne client afin d’éviter un doublon de colonne. Il est exposé dans le code via les alias :

- `getDeliveryInstructions()` ;
- `setDeliveryInstructions()`.

Un nouveau champ distinct est ajouté pour les notes internes terrain :

- `Address.courierNotes` ;
- `CustomerOrder.deliveryAddressCourierNotes`.

## Points fonctionnels

- Le panier affiche un champ facultatif `Instructions de livraison`.
- Exemple affiché : `près du centre commercial Baobab, portail bleu, appeler en arrivant`.
- Le GPS reste facultatif et recommandé.
- Une adresse sauvegardée recharge ses instructions et son GPS.
- Le livreur voit les instructions client.
- Le livreur peut enregistrer une note terrain depuis `/livreur`.
- La note terrain met à jour l’adresse pour les prochaines livraisons.
- La commande en cours reçoit aussi le snapshot de la note terrain.

## Migration

Migration ajoutée :

- `migrations/Version20260619135000.php`

Colonnes ajoutées :

- `address.courier_notes` ;
- `customer_order.delivery_address_courier_notes`.

Aucune nouvelle colonne n’est créée pour les instructions client, car `address.notes` et `customer_order.delivery_address_notes` existent déjà et sont réutilisées.

## Recette attendue

- Créer une commande avec GPS + instructions client.
- Vérifier que `address.gps_*` est rempli.
- Vérifier que `customer_order.delivery_address_gps_*` est rempli.
- Vérifier que l’adresse du client recharge le GPS et les instructions au prochain panier.
- Passer la commande en prête.
- Vérifier que le livreur voit GPS + instructions client.
- Ajouter une note terrain livreur.
- Vérifier que `address.courier_notes` est rempli.
- Vérifier que `customer_order.delivery_address_courier_notes` est rempli.
- Vérifier que les anciennes commandes gardent leur snapshot si l’adresse est modifiée ensuite.
