# COMMIT — Ajax ajout produit au panier

## Objectif

Améliorer l'expérience d'ajout panier depuis :

- le catalogue ;
- la fiche produit.

Avant, chaque ajout entraînait un rechargement. Après ce commit, l'ajout peut se faire en Ajax.

## Fichiers modifiés

```text
src/Controller/CartController.php
templates/base.html.twig
templates/product/catalogue.html.twig
templates/product/show.html.twig
```

## Règles conservées

- Le panier serveur reste la vérité.
- Le formulaire POST classique reste le fallback.
- Le verrouillage préouverture reste prioritaire.
- Les produits inactifs ne peuvent pas être ajoutés.
- Les caches logistiques panier sont invalidés après ajout.

## Réponse JSON en succès

```json
{
  "ok": true,
  "message": "Produit ajouté au panier.",
  "cartCount": 3,
  "productId": 12,
  "qtyAdded": 1,
  "cartUrl": "/panier"
}
```

## Réponse JSON en cas de panier verrouillé

```text
HTTP 423 Locked
ok=false
message=<message préouverture>
cartCount=<quantité actuelle>
```

## UX

- Interception des formulaires `data-ajax-cart-form`.
- `fetch()` avec `X-Requested-With: XMLHttpRequest`.
- Bouton temporairement désactivé.
- Texte bouton `Ajout...`.
- Toast court de confirmation.
- Pastille panier mise à jour.

## Validation

- Dev OK.
- Recette OK.
- Production OK dans tag `j5g-b4-20260618-v11`.
