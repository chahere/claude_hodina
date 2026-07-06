# README MAJ — J5K-v8 UX panier adresses livraison/facturation

Cette mise à jour finalise l'ergonomie locale de J5K-v8 avant recette.

## Résumé

Le panier conserve la séparation livraison / facturation, mais simplifie les actions visibles :

- la carte d'adresse est cliquable pour sélectionner une adresse ;
- le bouton de sélection redondant est supprimé ;
- le bouton de définition par défaut est placé sous la carte ;
- la zone de facturation n'est plus affichée au client ;
- le GPS reste réservé à la livraison ;
- les instructions livreur restent réservées à la livraison.

## Vérifications techniques locales

```powershell
php -l src\Controller\CartController.php
php -l src\Controller\CheckoutController.php
php -l src\Form\CheckoutType.php
php bin\console cache:clear
php bin\console doctrine:schema:validate
```

## Tests fonctionnels locaux

```text
[ ] Panier ouvert sans erreur.
[ ] Bloc adresse de livraison visible.
[ ] Bloc adresse de facturation visible.
[ ] Aucun bouton “Sélectionner cette adresse”.
[ ] Clic carte livraison = sélection livraison.
[ ] Clic carte facturation = sélection facturation.
[ ] Bouton “Utiliser cette adresse par défaut” sous la carte.
[ ] Bouton “Utiliser cette adresse par défaut” sous la carte.
[ ] Facturation sans GPS.
[ ] Facturation sans zone visible.
[ ] Modification livraison avec instructions et GPS.
[ ] Modification facturation sans instructions livreur et sans GPS.
[ ] Si aucune adresse livraison par défaut : reprise d'une adresse livraison existante.
[ ] Si aucune adresse facturation par défaut : reprise d'une adresse facturation existante.
```
