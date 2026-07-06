# COMMIT J5G-E1 → J5G-E2-bis-A — Commune livrée, recalcul livraison et panier contractuel

Date : **17/06/2026**  
Branche : `pilot/j5g-e1-commune-livree`

Commits :

```text
7831c1b feat(j5g): simplify delivery commune checkout and secure delivery totals
a70127c feat(j5g): move delivery validation before cart summary
```

---

## Objectif

Simplifier la saisie d'adresse de livraison et fiabiliser le total de commande pendant le pilote Hodina à paiement manuel.

Le principe validé est :

```text
Commune livrée Hodina = source de vérité.
Panier = écran contractuel du total.
Checkout = réservé plus tard au paiement en ligne / facturation.
```

---

## Sous-jalons livrés

### J5G-E1 — Saisie adresse par commune livrée

- Le client choisit une commune livrée dans une liste.
- Le code postal est prérempli.
- La zone est affichée mais non modifiable.
- Le serveur recalcule tout avant validation.
- `DeliveryCommuneMatcherService` reste la source de résolution.

### J5G-E1B — Recalcul AJAX livraison

- Ajout de `POST /panier/logistique/apercu`.
- Recalcul immédiat des frais lorsque la commune ou l'adresse change.
- Mise à jour du bloc livraison estimée sans rechargement.

### J5G-E1C — Verrouillage total

- Signature du panier + adresse + calcul livraison stockée en session.
- Recalcul au moment de valider.
- Refus de commande si le total réel diffère du total affiché.

### J5G-E1D — Tarif local + barge fixe

- Forfait local de la commune livrée appliqué en base.
- Coût fixe de barge ajouté uniquement si une liaison `BARGE` est détectée.
- Même coût de barge dans les deux sens PT ↔ GT.
- Les traversées terrestres restent pour J5G-B4.

### J5G-E1E — Confirmation enrichie

- Récapitulatif commande ajouté sur `/commande/confirmation/{id}`.
- Produits, vendeur, quantités, frais, total, adresse et zone affichés.

### J5G-E2-bis-A — Panier réordonné

- `Livraison et validation` passe avant le récapitulatif.
- Adresse utilisée visible.
- Modification adresse repliée par défaut.
- Récapitulatif final placé après l'adresse.

---

## Fichiers principaux modifiés

```text
src/Controller/Admin/DeliveryCommuneConnectionCrudController.php
src/Controller/CartController.php
src/Controller/CheckoutController.php
src/Form/CheckoutType.php
src/Service/DeliveryCommuneMatcherService.php
src/Service/DeliveryLogisticsService.php
templates/cart/index.html.twig
templates/checkout/confirmation.html.twig
templates/checkout/index.html.twig
```

---

## Tests effectués

```powershell
php -l src/Service/DeliveryLogisticsService.php
php -l src/Controller/Admin/DeliveryCommuneConnectionCrudController.php
php -l src/Controller/CheckoutController.php
php bin/console cache:clear
php bin/console doctrine:schema:validate
```

Résultat : OK.

Tests métier validés :

```text
Labattoir PT → code postal 97615 → zone PT
Mamoudzou GT → code postal 97600 → zone GT
Changement commune → recalcul chemin
Barge détectée → prix recalculé
Confirmation commande → récapitulatif complet
Panier → livraison avant récapitulatif
```

---

## Points de vigilance

- Ne pas réintroduire une zone modifiable par le client.
- Ne pas recréer une table de communes côté front.
- Ne pas coder la logistique dans Twig.
- Ne pas confondre coût de barge et coût des traversées terrestres.
- Ne pas utiliser le checkout comme étape principale tant que le paiement est manuel.

---

## Déploiement

```text
Recette : OK
Production : OK
Tag : j5g-e1-e2bis-prod
Branche prod : pilot/j5j-commerce-mode-role-tester
Commit final docs : 36cc357
```

Production : migrations exécutées jusqu'à `DoctrineMigrations\Version20260615225836`, `New = 0`, schema synchronisé, tests fonctionnels OK.

## Suite

```text
J5G-B4 : BFS / plus court chemin
J5G-C : coûts supplémentaires terrestres
J5G-E2 : snapshot logistique financier complet
```
