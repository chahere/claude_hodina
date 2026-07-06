# COMMIT — J5L — UX panier mobile, sélecteur compact et facturation admin

Date : **21/06/2026**

## Statut

**Validé en recette.**

J5L est clôturé fonctionnellement côté recette. Le jalon a transformé le panier en écran mobile exploitable sans modifier la logique métier J5K.

## Périmètre J5L

J5L a été volontairement limité à l'expérience utilisateur du panier et à l'affichage admin associé.

Fichiers principalement concernés :

```text
templates/cart/index.html.twig
public/css/style_mobile.css
templates/admin/customer_order/operational_sheet.html.twig
src/Controller/Admin/CustomerOrderCrudController.php
```

Aucune modification attendue sur :

```text
entités métier
migrations
routes
calcul logistique
workflow commande
snapshots commande
services de livraison
```

## J5L-A — UX panier mobile PWA

Objectif : rendre le panier plus clair sur mobile avant d'aller plus loin sur le portail client.

Livré :

- panier réorganisé en flux linéaire ;
- liste des articles affichée en premier ;
- total, sous-total et frais de livraison placés juste après les articles ;
- livraison affichée ensuite ;
- facturation affichée ensuite ;
- CGV et bouton de validation placés en fin de parcours ;
- détails techniques des chemins de livraison masqués côté client ;
- logique métier de livraison conservée ;
- sélection visuelle des adresses corrigée ;
- synchronisation `aria-pressed` corrigée ;
- `.is-default` ne colore plus une adresse comme sélectionnée ;
- seule `.is-selected` apparaît comme l'adresse active ;
- champ texte GPS visible ajouté à côté du bouton `Utiliser ma position actuelle` ;
- suppression du bouton `Retirer la position GPS` côté UX.

Décision importante : les détails BFS / barge / chemins logistiques restent utiles en admin, mais ne doivent pas encombrer le client au moment de valider son panier.

## J5L-B — Sélecteur compact d'adresses panier

Objectif : éviter que le panier s'allonge lorsque le client possède plusieurs adresses.

Livré :

- suppression des sous-menus longs sous `Changer l'adresse de livraison` et `Changer l'adresse de facturation` ;
- remplacement par un panneau compact de sélection ;
- panneau mobile type bottom sheet ;
- comportement desktop compatible modale / panneau centré ;
- liste d'adresses scrollable dans le panneau ;
- le panier reste court même avec de nombreuses adresses ;
- clic sur une adresse = sélection visuelle seulement ;
- le panneau ne se ferme plus immédiatement après sélection ;
- ajout d'un bouton explicite `Utiliser cette adresse de livraison` ;
- ajout d'un bouton explicite `Utiliser cette adresse de facturation` ;
- fermeture du panneau uniquement après clic sur `Utiliser cette adresse` ;
- possibilité de corriger les champs avant confirmation ;
- possibilité d'ajouter la position GPS avant confirmation ;
- possibilité de cocher `Utiliser cette adresse par défaut` avant confirmation ;
- boutons GPS affichés sur les adresses de livraison sélectionnables sans GPS ;
- message flash affiché lorsque la position GPS actuelle est affectée à l'adresse.

Décision importante : la sélection et la confirmation sont séparées. Cette UX évite les validations involontaires et laisse le temps de compléter GPS / défaut / champs.

## J5L-C — Affichage facturation admin

Objectif : rendre visible l'adresse de facturation utilisée sur la commande.

Livré :

- ajout du bloc `Facturation` dans la fiche terrain admin ;
- affichage de l'adresse de facturation snapshotée ;
- affichage de l'adresse de facturation dans la vue détail EasyAdmin via `billingAddressSummary` ;
- conservation de la séparation livraison / facturation ;
- aucune modification du stockage ou des migrations.

Décision importante : même en paiement manuel, l'adresse de facturation doit être visible côté admin pour la traçabilité et pour préparer la suite facture / paiement.

## Références recette connues

Déploiement recette J5L-B :

```text
Tag : recette-j5l-b-selecteur-adresses-20260621
Commit : 235a51f
Cible : recette
Statut : déploiement OK
```

Contrôles recette confirmés :

```text
git status propre
migrations à jour jusqu'à Version20260619170000
Doctrine schema validate OK
cache prod clear / warmup OK
assets compilés
uploads restaurés
cron Messenger recette présent
```

J5L-C a été testé fonctionnellement après ajout de l'affichage facturation admin.

## Tests fonctionnels validés

- ouverture panier mobile ;
- panier court et lisible ;
- total et frais de livraison visibles juste après les articles ;
- panneau compact livraison ouvert via `Changer l'adresse de livraison` ;
- sélection d'une adresse de livraison sans fermeture immédiate ;
- bouton `Utiliser cette adresse de livraison` ferme le panneau ;
- ajout / récupération GPS dans le panneau ;
- message flash lors de l'affectation GPS ;
- panneau compact facturation ouvert via `Changer l'adresse de facturation` ;
- sélection d'une adresse de facturation sans fermeture immédiate ;
- bouton `Utiliser cette adresse de facturation` ferme le panneau ;
- validation commande OK ;
- admin commande OK ;
- fiche terrain admin affiche livraison et facturation ;
- vue détail EasyAdmin affiche l'adresse de facturation.

## Non-régression attendue

- calculs livraison J5G-B4 conservés ;
- GPS J5K conservé ;
- instructions livreur conservées ;
- adresse de facturation automatique conservée ;
- adresses par défaut livraison / facturation conservées ;
- workflow commande inchangé ;
- portail livreur inchangé.

## Suite décidée

Après clôture J5L, le prochain chantier est :

```text
J5M-A — Workflow livreur enrichi : picked_up et out_for_delivery
```

Décision technique pour J5M : ne pas stocker un statut contenant le nom du livreur. Stocker un statut stable, puis afficher le nom du livreur séparément.

Exemple :

```text
status = picked_up
courier = Chahere
Affichage : Prise en charge par Chahere
```

Puis :

```text
status = out_for_delivery
Affichage : En cours de livraison
```
