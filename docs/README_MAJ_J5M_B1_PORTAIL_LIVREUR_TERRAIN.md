# J5M-B1 — Portail livreur terrain synthétique

Date : 21/06/2026  
Statut : prêt pour tests locaux / recette

---

## Objectif

J5M-B1 améliore le portail livreur après J5M-A.

Le workflow `PICKED_UP` / `OUT_FOR_DELIVERY` étant fonctionnel, l’objectif est de rendre la page `/djama` plus exploitable sur téléphone par un livreur terrain.

Le portail est maintenant organisé en trois blocs :

```text
1. À prendre en charge
2. Prises en charge / en cours
3. Livrées cette semaine
```

---

## Décision UX

Le portail livreur ne doit pas devenir une fiche admin mobile.

Chaque carte affiche uniquement les informations nécessaires après dépliage :

```text
Commande
Commune Client
Vendeurs concernés
Nombre d’articles
Montant total
Action principale
```

En J5M-B1-bis, les cartes deviennent repliables pour améliorer la vue d’ensemble. En mode replié, le livreur voit seulement la commune cliente.

Les adresses longues, détails client, lignes produit détaillées et éléments secondaires sont volontairement retirés de l’affichage principal pour réduire la charge cognitive sur mobile.

---

## Bloc 1 — À prendre en charge

Statut concerné :

```text
READY_FOR_PICKUP
```

Condition :

```text
assignedCourier IS NULL
```

Affichage :

```text
Commande
Commune Client
Vendeurs concernés
Nombre d’articles
Montant total
Bouton Prendre en charge
```

État vide :

```text
Aucune commande prête pour le moment.
```

---

## Bloc 2 — Prises en charge / en cours

Statuts concernés :

```text
PICKED_UP
OUT_FOR_DELIVERY
```

Condition :

```text
assignedCourier = livreur connecté
```

Pour `PICKED_UP`, la carte affiche :

```text
PRISE EN CHARGE
Commande
Commune Client
Vendeurs concernés
Nombre d’articles
Montant total
Collecte / départ pas encore démarré
Appeler
SMS client
Démarrer la livraison
```

Pour `OUT_FOR_DELIVERY`, la carte affiche :

```text
EN COURS DE LIVRAISON
Commande
Commune Client
Vendeurs concernés
Nombre d’articles
Montant total
Appeler
SMS client
Marquer livrée
```

---

## Bloc 3 — Livrées cette semaine

Statut concerné :

```text
DELIVERED
```

Condition :

```text
assignedCourier = livreur connecté
AND deliveredAt >= aujourd’hui - 6 jours
```

Affichage compact :

```text
Commande
Commune Client
Vendeurs concernés
Nombre d’articles
Montant total
```

Résumé :

```text
Total livré cette semaine : X commande(s)
Montant transport estimé : somme des frais de livraison des commandes livrées cette semaine
```

Note : le montant transport estimé utilise actuellement `CustomerOrder.deliveryFee`. Ce n’est pas encore une rémunération livreur officielle ; c’est une estimation opérationnelle.

---

## Données calculées côté contrôleur

Le contrôleur prépare des cartes d’affichage pour éviter une logique Twig trop lourde.

Pour chaque commande :

```text
label
commune client
vendeurs distincts
nombre total d’articles
total commande
frais livraison
statut
état PICKED_UP / OUT_FOR_DELIVERY
téléphone client nettoyé
message SMS
```

Les vendeurs sont récupérés depuis :

```text
CustomerOrder
→ OrderItem
→ Seller
→ DeliveryCommune / commune libre
```

Les doublons vendeurs sont supprimés par identifiant vendeur.

---

## Fichiers modifiés

```text
src/Controller/Courier/CourierDashboardController.php
templates/courier/dashboard.html.twig
docs/TODO.md
docs/README_MAJ_J5M_B1_PORTAIL_LIVREUR_TERRAIN.md
docs/README_MAJ_J5M_B1_BIS_CARTES_REPLIABLES.md
```

---

## Tests recommandés

```powershell
php -l src/Controller/Courier/CourierDashboardController.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
```

Aucune migration Doctrine attendue.

---

## Parcours fonctionnel à valider

```text
1. Créer ou reprendre une commande prête.
2. Vérifier qu’elle apparaît dans “À prendre en charge”.
3. Cliquer “Prendre en charge”.
4. Vérifier qu’elle passe dans “Prises en charge / en cours” avec l’état “Prise en charge”.
5. Cliquer “Démarrer la livraison”.
6. Vérifier l’état “En cours de livraison”.
7. Cliquer “Marquer livrée”.
8. Vérifier que la commande apparaît dans “Livrées cette semaine”.
9. Vérifier les compteurs des trois blocs.
10. Vérifier le total transport estimé de la semaine.
```

---

## Point de vigilance

J5M-B1 ne modifie pas le workflow métier.

Il s’appuie sur J5M-A :

```text
READY_FOR_PICKUP
→ PICKED_UP
→ OUT_FOR_DELIVERY
→ DELIVERED
```

Il ne faut donc pas mélanger cette évolution avec J5L panier ou avec une future optimisation de tournée.
