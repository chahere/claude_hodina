# COMMIT J5K — GPS livraison

## Branche

`pilot/j5k-gps-livraison`

## Commit conseille

```bash
git commit -m "feat(j5k): add optional gps coordinates for delivery addresses"
```

## Changements

- Adresse client : coordonnees GPS facultatives.
- Commande : snapshot GPS de livraison fige au moment de la commande.
- Checkout : bouton navigateur pour capturer la position actuelle.
- Admin : lien carte dans la fiche terrain et detail commande.
- Livreur : bouton `Carte GPS` sur les commandes pretes / en cours.
- Migration : `Version20260619102000`.

## Points de vigilance

- Le GPS ne remplace pas la commune livree.
- Le GPS ne doit pas impacter le calcul des frais de livraison pilote, qui reste base sur la commune/zone/logistique.
- Le GPS ne doit pas bloquer la commande.
- Le GPS est une donnee personnelle precise : ne pas l'afficher partout.
