# README MAJ J5K — GPS livraison

## Objectif

J5K ajoute une position GPS facultative sur les adresses de livraison afin d'aider les livreurs Hodina a retrouver les clients a Mayotte, ou les adresses textuelles peuvent etre insuffisantes.

## Perimetre pilote

- Ajout de `gpsLatitude`, `gpsLongitude` et `gpsAccuracyMeters` sur `Address`.
- Snapshot GPS fige sur `CustomerOrder` au moment de la commande.
- Bouton client `Utiliser ma position actuelle` au panier / checkout.
- Reutilisation de la position GPS quand une adresse enregistree est selectionnee.
- Liens Google Maps dans la fiche terrain admin et le portail livreur.
- Aucun geocodage automatique, aucune cle API, aucune dependance externe obligatoire.

## Choix volontaire

Le GPS reste facultatif. Une commande ne doit pas etre bloquee si le client refuse la geolocalisation ou si son navigateur ne la supporte pas. L'adresse textuelle + commune livree restent obligatoires.

## Donnees personnelles

La position GPS est une donnee sensible de livraison. Elle doit etre limitee a l'usage operationnel Hodina : admin, livreur assigne, support commande. Ne pas l'exposer dans les emails publics ni dans les pages catalogue.

## Recette locale

1. Créer une adresse de livraison depuis le panier.
2. Cliquer sur `Utiliser ma position actuelle`.
3. Autoriser la geolocalisation dans le navigateur.
4. Verifier que le recap affiche une latitude/longitude.
5. Valider la commande.
6. Verifier en admin que la fiche terrain affiche `Ouvrir la carte`.
7. Passer la commande en `prete` et verifier dans `/djama` que le bouton `Carte GPS` apparait.
8. Verifier que le lien ouvre Google Maps.

## Commandes

```powershell
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:schema:validate
php bin/console cache:clear
```

## Limites post-pilote

- Ajouter une saisie manuelle carte si le client n'est pas sur le lieu de livraison au moment de la commande.
- Ajouter un controle de coherence approximatif commune <-> GPS.
- Ajouter une politique de retention / anonymisation des coordonnees anciennes.
