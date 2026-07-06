# COMMIT J5K-v8-quater — Case “Utiliser cette adresse par défaut” limitée aux formulaires

## Contexte

Après les tests mobiles J5K-v8/v8-bis/v8-ter, la sélection des cartes d’adresse fonctionne mais l’option de définition par défaut ne doit pas apparaître sur les cartes ni dans le bloc “adresse utilisée”.

L’utilisateur doit pouvoir sélectionner une carte pour la commande en cours sans modifier son adresse par défaut. Le choix de définir une adresse par défaut doit être une action volontaire au moment où il crée ou modifie une adresse.

## Décision UX

La case à cocher s’appelle désormais simplement :

```text
Utiliser cette adresse par défaut
```

Le contexte du formulaire suffit à comprendre si l’on parle d’une adresse de livraison ou de facturation.

## Règles validées

- La case est visible uniquement dans le formulaire d’ajout/modification d’adresse de livraison.
- La case est visible uniquement dans le formulaire d’ajout/modification d’adresse de facturation.
- La case n’est pas affichée dans le bloc “Adresse de livraison utilisée”.
- La case n’est pas affichée dans le bloc “Adresse de facturation utilisée”.
- La case n’est pas affichée sur les cartes d’adresses enregistrées.
- Cliquer sur une carte sélectionne l’adresse pour la commande en cours sans changer l’adresse par défaut.
- À la validation du panier, si la case livraison est cochée, `customer.delivery_address_id` est mis à jour.
- À la validation du panier, si la case facturation est cochée, `customer.billing_address_id` est mis à jour.

## Fichiers concernés

- `src/Form/CheckoutType.php`
- `templates/cart/index.html.twig`
- `docs/WORKFLOWS.md`
- `docs/TODO.md`

## Tests à faire

- Ouvrir le panier : aucune case “par défaut” visible sur les blocs d’adresses utilisées.
- Ouvrir la modification livraison : la case “Utiliser cette adresse par défaut” est visible.
- Ouvrir la modification facturation : la case “Utiliser cette adresse par défaut” est visible.
- Sélectionner une carte sans cocher la case : l’adresse utilisée change pour la commande en cours, mais l’adresse par défaut ne change pas.
- Modifier une adresse et cocher la case : l’adresse devient par défaut après validation du panier.
- Ajouter une nouvelle adresse et cocher la case : la nouvelle adresse devient par défaut après validation du panier.
