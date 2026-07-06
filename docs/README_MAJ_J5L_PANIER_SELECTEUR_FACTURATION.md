# README — Mise à jour J5L — Panier mobile, sélecteur compact et facturation admin

Date : **21/06/2026**

## Résumé exécutif

J5L est validé en recette.

Le panier client a été simplifié et rendu plus compatible avec un usage PWA mobile. Les listes d'adresses ne prennent plus de place dans le flux principal du panier. Elles sont accessibles via un sélecteur compact. L'adresse de facturation est maintenant visible côté admin.

## Ce qui change pour le client

Avant J5L, le panier affichait trop d'informations techniques et les sous-menus d'adresses pouvaient devenir très longs.

Après J5L :

```text
1. Articles
2. Total / frais de livraison
3. Livraison
4. Facturation
5. CGV / validation
```

Les chemins de livraison calculés par le moteur logistique ne sont plus exposés au client dans le panier.

## Ce qui change pour les adresses

Les boutons `Changer l'adresse de livraison` et `Changer l'adresse de facturation` ouvrent un panneau compact.

Le panneau permet de :

- sélectionner une adresse ;
- garder le panneau ouvert après sélection ;
- compléter ou corriger les champs ;
- ajouter la position GPS si besoin côté livraison ;
- cocher `Utiliser cette adresse par défaut` ;
- confirmer explicitement avec `Utiliser cette adresse`.

## Ce qui change pour l'admin

La fiche terrain admin affiche désormais un bloc `Facturation`.

La vue détail EasyAdmin affiche également l'adresse de facturation via `billingAddressSummary`.

## Ce qui ne change pas

J5L ne modifie pas :

- les entités ;
- les migrations ;
- les routes ;
- le moteur logistique ;
- le calcul des frais ;
- les snapshots commande ;
- le workflow commande ;
- le portail livreur.

## Tags / commits connus

```text
recette-j5l-b-selecteur-adresses-20260621
Commit : 235a51f
Statut : recette OK
```

## Commandes de contrôle locales

```powershell
php bin/console lint:twig templates/cart/index.html.twig
php bin/console lint:twig templates/admin/customer_order/operational_sheet.html.twig
php bin/console cache:clear
```

## Tests recette validés

- panier court sur mobile ;
- changement adresse livraison via panneau ;
- changement adresse facturation via panneau ;
- panneau qui reste ouvert après sélection ;
- confirmation via bouton `Utiliser cette adresse` ;
- GPS ajouté côté livraison ;
- message flash GPS ;
- validation commande ;
- affichage livraison admin ;
- affichage facturation admin.

## Point de vigilance

Le sélecteur compact est volontairement une amélioration front/Twig/CSS/JS. Il n'est pas encore un portail complet de gestion d'adresses.

La vraie gestion longue durée des adresses client reste à faire plus tard dans :

```text
/mon-compte/adresses
```
