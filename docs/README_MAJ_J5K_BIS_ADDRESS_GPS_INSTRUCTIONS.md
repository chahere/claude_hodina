# README mise à jour — J5K-bis adresse enrichie

## Pourquoi cette mise à jour

La recette J5K a validé le GPS côté commande, admin et livreur, mais deux besoins terrain ont été identifiés :

1. le GPS doit rester dans l’adresse client pour être réutilisé aux prochaines livraisons ;
2. une adresse mahoraise a souvent besoin d’une consigne humaine en plus du GPS.

## Règle à retenir

- `Address` = carnet d’adresses vivant du client.
- `CustomerOrder` = snapshot figé au moment de la commande.

On ne remplace pas l’adresse par le GPS : on combine adresse, commune, instructions et GPS.

## Modèle retenu

### Instructions client

On réutilise `Address.notes` comme consigne client. Cela évite de créer une colonne doublon.

Exemple :

```text
près du centre commercial Baobab, portail bleu, appeler en arrivant
```

Snapshot commande existant : `customer_order.delivery_address_notes`.

### Commentaire livreur / terrain

Nouveau champ interne :

- `address.courier_notes` ;
- `customer_order.delivery_address_courier_notes`.

Le commentaire terrain est destiné aux admins/livreurs. Il n’est pas affiché au client dans le pilote.

## Déploiement

Déploiement par la séquence standard Hodina : tag créé depuis dev, recette/prod en lecture seule avec `fetch + git show tag:tools/deploy-hodina-by-tag.sh`.

Exemple tag conseillé :

```text
j5k-bis-address-gps-instructions-recette
```

## Contrôles serveur attendus

Le script de déploiement vérifie désormais aussi :

- `address.courier_notes` ;
- `customer_order.delivery_address_courier_notes`.

## Dette technique connexe à garder

La recette J5K a aussi identifié un sujet performance image : les images catalogue doivent viser 100 à 300 Ko, maximum provisoire 500 Ko. À terme, l’upload produit devra optimiser automatiquement les images.

---

## Complément recette — carnet d'adresses enrichi

Après recette du tag `j5k-gps-livraison-recette-v6`, le cœur de J5K-bis est validé : GPS, instructions, admin, fiche terrain et portail livreur.

Une anomalie complémentaire a été détectée : après une commande sans GPS / sans instruction, une adresse enrichie GPS + instructions pouvait ne plus être proposée correctement dans le carnet d'adresses du panier.

Correction complémentaire :

- ajout du bouton `Utiliser l'adresse par défaut` ;
- tri des adresses pour proposer en priorité l'adresse la plus exploitable terrain ;
- recherche d'adresse de livraison réutilisable plus stricte, incluant instructions et GPS ;
- conservation de la règle `Address` vivant / `CustomerOrder` snapshot figé.

Voir aussi :

```text
docs/README_MAJ_J5K_BIS_ADDRESS_BOOK_FIX.md
docs/COMMIT_J5K_BIS_ADDRESS_BOOK_FIX.md
```
