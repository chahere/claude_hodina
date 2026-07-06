# README MAJ — J5K-v8-quater case adresse par défaut

## Objectif

Clarifier l’expérience panier : la définition d’une adresse par défaut se fait uniquement quand le client ajoute ou modifie une adresse.

## Changement visible

La case affichée dans les formulaires devient :

```text
Utiliser cette adresse par défaut
```

Elle remplace les libellés longs séparés “livraison” et “facturation”.

## Règle métier

- Formulaire livraison : la case met à jour `customer.delivery_address_id` à la validation du panier.
- Formulaire facturation : la case met à jour `customer.billing_address_id` à la validation du panier.
- Cartes d’adresses : clic = sélection pour la commande courante uniquement.

## Non-régression attendue

- GPS uniquement côté livraison.
- Instructions livreur uniquement côté livraison.
- Facturation sans GPS ni instructions livreur.
- Snapshot commande inchangé.
- Admin, fiche terrain et portail livreur inchangés.

## Validation locale du 19/06/2026 soir

Tests bons avant reprise recette :

- les cartes restent sélectionnables ;
- aucune case par défaut n'est visible sur les cartes ;
- aucune case par défaut n'est visible dans les blocs `Adresse utilisée` ;
- la case `Utiliser cette adresse par défaut` apparaît uniquement en ajout/modification ;
- la case est prise en compte à la validation du panier ;
- facturation sans GPS ni instructions livreur ;
- livraison avec GPS et instructions conservés ;
- Symfony local OK : syntaxe, cache clear, schema validate.

## Reprise prévue

La recette n'est pas encore considérée comme validée pour cette version finale. À la reprise :

1. vérifier l'état Git ;
2. créer ou recréer un tag propre ;
3. déployer en recette ;
4. rejouer les tests panier, admin, fiche terrain et portail livreur.
