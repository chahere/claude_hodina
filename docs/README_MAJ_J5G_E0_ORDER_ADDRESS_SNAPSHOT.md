# README MAJ — J5G-E0 Snapshot adresse commande

Cette mise à jour documente le jalon J5G-E0, validé en recette le 15/06/2026.

## Pourquoi cette mise à jour

Les tests ont montré que l'utilisateur doit pouvoir supprimer n'importe quelle adresse de son carnet. Une commande ne doit pas casser lorsqu'une adresse client est supprimée.

## Nouvelle règle

```text
Adresse client = carnet vivant.
Adresse commande = snapshot historique.
```

## Ce qui est livré

- Snapshot livraison dans `CustomerOrder`.
- Snapshot facturation dans `CustomerOrder`.
- Migration avec reprise des anciennes commandes.
- `delivery_address_id` rendu tolérant à la suppression.
- Checkout qui copie les adresses dans la commande.
- Affichages admin, livreur et e-mail qui lisent les snapshots.
- Réduction des doublons futurs par réutilisation d'une adresse identique.

## Validation recette

- Migration exécutée.
- Schema Doctrine OK.
- Suppression d'adresse liée à une commande OK.
- Snapshot conservé après suppression.

## Suite recommandée

1. Documenter et commiter cette mise à jour docs.
2. Nettoyer plus tard les doublons d'adresses historiques avec une procédure dédiée.
3. Reprendre J5G-B4 / J5G-C / J5G-D / J5G-E logistique sans recréer l'existant.
