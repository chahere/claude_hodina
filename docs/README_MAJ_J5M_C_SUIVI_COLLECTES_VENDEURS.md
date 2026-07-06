# J5M-C — Suivi des collectes vendeurs

Date : 21/06/2026  
Statut : cadré — à développer après validation J5M-B1-bis

---

## Objectif

Ajouter un suivi terrain des récupérations chez les vendeurs avant le départ en livraison client.

Le livreur doit savoir chez quels vendeurs passer, quels produits récupérer, et ce qui est déjà récupéré.

---

## Décisions validées

- Garder `CustomerOrder.status` simple et stable.
- Ne pas stocker une phrase dynamique comme statut de commande.
- Ajouter une vraie table de suivi des collectes vendeurs.
- Ne pas passer automatiquement en `OUT_FOR_DELIVERY` au premier jet.
- Quand toutes les collectes sont terminées, afficher `Tous les produits sont récupérés`.
- Le livreur clique ensuite sur `Démarrer la livraison` quand il part réellement vers le client.

---

## Workflow cible

```text
READY_FOR_PICKUP
→ le livreur clique Prendre en charge
→ PICKED_UP
→ le livreur valide les collectes vendeurs une par une
→ tous les produits sont récupérés
→ le livreur clique Démarrer la livraison
→ OUT_FOR_DELIVERY
→ le livreur clique Marquer livrée
→ DELIVERED
```

---

## Modèle technique pressenti

Créer une table dédiée :

```text
customer_order_pickup_stop
```

Champs pressentis :

```text
id
customer_order_id
seller_id
status
collected_at
collected_by_id
items_snapshot
created_at
updated_at
```

Cette étape nécessitera probablement une migration Doctrine.

---

## Point critique

`OUT_FOR_DELIVERY` doit continuer à signifier que le livreur est réellement parti livrer le client. Une collecte terminée ne veut pas toujours dire départ immédiat, surtout en cas de regroupement de commandes, barge, attente terrain ou tournée multi-clients.
