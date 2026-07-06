# Commit J5G-A — Aperçu logistique panier par périmètre vendeur

## Statut

**Réalisé localement, testé techniquement, commité et poussé.**

Commit connu :

```text
b559edb feat(cart): show logistics preview by seller scope
```

Branche :

```text
pilot/j5-order-delivery-pricing
```

## Objectif métier

Informer le client dès le panier des contraintes logistiques de sa commande.

Le panier doit pouvoir dire :

```text
adresse nécessaire
commune client utilisée
barge requise ou non
frais livraison estimés
message logistique compréhensible
```

## Objectif technique

Brancher `DeliveryLogisticsService` dans le panier sans encore modifier le checkout.

J5G-A ne doit pas figer les données dans `CustomerOrder`.

## Fichiers modifiés

```text
src/Controller/CartController.php
src/Dto/CartLogisticsPreview.php
templates/cart/index.html.twig
public/css/style_mobile.css
```

## Pas de migration

J5G-A ne modifie aucune entité Doctrine.

Il n'y a donc pas de migration.

## Règle de recalcul validée

La livraison dépend des vendeurs présents dans le panier, pas du nombre de produits.

```text
Produit ajouté depuis un vendeur déjà présent
→ pas de recalcul logistique utile

Produit ajouté depuis un nouveau vendeur
→ recalcul logistique

Dernier produit d'un vendeur retiré
→ recalcul logistique

Adresse client changée
→ recalcul logistique
```

## Signature logistique

Le panier utilise une signature logique composée de :

```text
adresse / commune client
+ liste unique des vendeurs du panier
```

Exemple conceptuel :

```text
address:3:dzaoudzi|sellers:1,2
```

Si la quantité change mais que la signature ne change pas, les frais livraison estimés restent identiques.

## Tests techniques locaux

Commandes exécutées :

```powershell
php -l src\Controller\CartController.php
php -l src\Dto\CartLogisticsPreview.php
php bin/console lint:twig templates/cart/index.html.twig
php bin/console cache:clear
php bin/console lint:container
```

Résultats :

```text
syntaxe PHP OK
Twig OK
cache clear OK
container OK
```

## Tests fonctionnels à refaire en recette

```text
Client connecté avec adresse Dzaoudzi + vendeur Mamoudzou
→ barge détectée

Ajouter un deuxième produit du même vendeur
→ total produits change
→ frais livraison restent identiques

Ajouter un produit d'un nouveau vendeur
→ aperçu logistique recalculé

Modifier quantité seulement
→ frais livraison restent identiques

Retirer le dernier produit d'un vendeur
→ aperçu recalculé
```

## Limite identifiée

Pendant les tests, la barge pouvait être détectée correctement mais les frais rester identiques.

Cause possible :

```text
PT_LOCAL et GT_LOCAL ont les mêmes montants de test.
```

Conclusion :

```text
Si les données tarifaires sont identiques, l'écran peut afficher le même prix malgré un changement logique.
```

## Suite

```text
J5G-B — Calcul du plus court chemin entre communes
J5G-C — Réglages Hodina pour suppléments communes/barge
J5G-D — Affichage panier détaillé
J5G-E — Snapshot checkout dans CustomerOrder
```
