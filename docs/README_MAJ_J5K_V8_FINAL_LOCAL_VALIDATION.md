# README MAJ — J5K-v8 final local — Panier adresses livraison/facturation

## Résumé

Cette mise à jour documente la version locale validée du panier adresses après les correctifs J5K-v8 à J5K-v8-quater.

Le panier distingue désormais clairement :

```text
Livraison = terrain, commune, GPS, instructions livreur.
Facturation = adresse administrative sans GPS.
```

## UX validée

- Carte cliquable = sélection pour la commande en cours.
- Pas de bouton `Sélectionner cette adresse`.
- Pas de bouton `Utiliser cette adresse ... par défaut` sur les cartes.
- Pas de case par défaut dans les blocs `Adresse utilisée`.
- Case unique : `Utiliser cette adresse par défaut`.
- La case apparaît seulement dans le formulaire d'ajout ou modification d'adresse.
- Le contexte du formulaire détermine si l'adresse par défaut est livraison ou facturation.

## Données validées

- `customer.delivery_address_id` porte l'adresse de livraison par défaut.
- `customer.billing_address_id` porte l'adresse de facturation par défaut.
- Le snapshot commande reste inchangé.
- Une adresse de facturation créée depuis une adresse de livraison ne reprend pas les données terrain.

## Tests à refaire demain avant recette

1. Repartir d'un `git status` propre.
2. Vérifier qu'aucun tag recette ancien ou intermédiaire ne sera déployé par erreur.
3. Refaire les tests locaux principaux.
4. Créer un tag recette propre uniquement après commit final.
5. Déployer en recette avec le script standard par tag.
6. Rejouer les tests recette : panier, livraison, facturation, commande, admin, fiche terrain, portail livreur.
