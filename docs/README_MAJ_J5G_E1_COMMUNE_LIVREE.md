# README MAJ — J5G-E1 à J5G-E2-bis-A — Commune livrée, livraison dynamique et panier contractuel

Cette mise à jour documente le jalon livré localement le **17/06/2026** sur la branche :

```text
pilot/j5g-e1-commune-livree
```

Commits :

```text
7831c1b feat(j5g): simplify delivery commune checkout and secure delivery totals
a70127c feat(j5g): move delivery validation before cart summary
```

---

## Pourquoi cette mise à jour

J5G-E0 a corrigé le modèle historique des adresses de commande : une commande possède maintenant son propre snapshot d'adresse.

Mais les tests ont montré un problème en amont : le client devait encore manipuler trop d'éléments logistiques dans le checkout.

Avant correction, le parcours exposait encore :

```text
code postal
commune
zone PT / GT
checkout séparé
```

Cela créait :

- de la friction ;
- des erreurs possibles ;
- un risque de zone incohérente ;
- un total de livraison qui pouvait changer sans être suffisamment contractualisé ;
- un parcours trop long pour le pilote à paiement manuel.

---

## Décision métier principale

Pour le pilote Hodina avec paiement manuel :

```text
La commune livrée Hodina est la source de vérité de l'adresse de livraison.
Le code postal est prérempli depuis cette commune.
La zone est déduite côté serveur.
Le panier devient l'écran contractuel du total avant validation.
Le checkout n'est plus une étape fonctionnelle visible tant qu'il n'y a pas de paiement en ligne.
```

Plus tard, quand le paiement en ligne sera ajouté, le checkout pourra revenir avec un rôle limité :

```text
paiement CB
adresse de facturation si différente
confirmation finale
```

Il ne devra pas redevenir le formulaire principal de choix d'adresse de livraison.

---

## Ce qui est livré

### J5G-E1 — Commune livrée comme source de vérité

- Liste des communes livrées issue de `DeliveryCommune`.
- Code postal prérempli depuis `DeliveryCommune.postalCode`.
- Zone affichée en lecture seule.
- Zone recalculée côté serveur.
- Snapshot adresse J5G-E0 conservé dans `CustomerOrder`.
- Aucune recréation de la logistique existante.

Services réutilisés :

```text
DeliveryCommune
DeliveryCommuneMatcherService
DeliveryLogisticsService
```

### J5G-E1B — Recalcul AJAX livraison

- Endpoint : `POST /panier/logistique/apercu`.
- Recalcul des frais quand la commune change.
- Recalcul des frais quand l'adresse sélectionnée change.
- Mise à jour dynamique du récapitulatif livraison.
- Base réutilisable pour le panier fusionné.

### J5G-E1C — Verrouillage du total avant validation

Règle validée :

```text
Le total vu au panier doit être le total validé.
```

Si le panier, l'adresse ou les frais changent entre l'affichage et la validation :

```text
la commande n'est pas créée
le client revient au panier
la raison est affichée
le client revalide après avoir vu le nouveau total
```

### J5G-E1D — Tarif local + barge fixe

Règle pilote retenue avant J5G-B4 :

```text
frais livraison = forfait local de la commune livrée + coût fixe de barge si barge détectée
```

Exemples :

```text
Labattoir PT sans barge     → PT_LOCAL = 12 €
Mamoudzou GT sans barge     → GT_LOCAL = 15 €
Mamoudzou GT avec barge     → GT_LOCAL + coût fixe barge
Labattoir PT avec barge     → PT_LOCAL + coût fixe barge
```

Le coût de barge est porté par la liaison logistique `BARGE`. Il est fixe et identique dans les deux sens.

Les coûts de traversées de communes ne sont pas encore appliqués. Ils seront traités avec J5G-B4.

### J5G-E1E — Confirmation enrichie

La page `/commande/confirmation/{id}` affiche maintenant :

- numéro de commande ;
- statut ;
- paiement manuel pendant le pilote ;
- produits ;
- vendeurs ;
- quantités ;
- prix unitaires ;
- total produits ;
- frais livraison ;
- total commande ;
- adresse de livraison ;
- zone.

### J5G-E2-bis-A — Panier réorganisé

Nouvel ordre UX :

```text
Produits du panier
Livraison et validation
Récapitulatif
Valider la commande
```

Le bloc adresse est visible sous forme d'adresse utilisée, puis modifiable dans un bloc replié :

```text
Adresse utilisée
Modifier l'adresse de livraison ▼
```

Le bloc se déplie seulement si le client veut changer d'adresse, ou automatiquement si une adresse est manquante / invalide.

---

## Tests validés localement, en recette et en production

- Changement Labattoir / Mamoudzou : code postal OK.
- Changement Labattoir / Mamoudzou : zone OK.
- Changement de commune : chemin logistique OK.
- Barge détectée quand vendeur et client sont sur deux territoires différents.
- Prix modifié quand une barge est détectée.
- Forfait local PT / GT correctement appliqué.
- Page confirmation enrichie OK.
- Panier réorganisé OK.
- `cache:clear` OK.
- `doctrine:schema:validate` OK.

---

## Points non traités volontairement

- Paiement en ligne.
- Adresse de facturation avancée dans un checkout dédié.
- Calcul BFS du plus court chemin entre communes.
- Coût des traversées terrestres entre communes.
- Snapshot logistique financier complet : chemin, hops, marge livraison, rémunération livreur.

Ces points restent dans la suite J5G-B4 / J5G-C / J5G-E2.

---

## Déploiement validé

```text
Recette : OK
Production : OK
Tag : j5g-e1-e2bis-prod
Branche prod : pilot/j5j-commerce-mode-role-tester
Commit final : 36cc357
```

## Suite recommandée

```text
1. Ne plus modifier la saisie adresse hors correction de bug.
2. Stabiliser J5G-B4 : plus court chemin BFS sur DeliveryCommuneConnection.
3. Ajouter les coûts de traversées communes.
4. Préparer le snapshot logistique financier complet.
5. Garder le checkout futur uniquement pour paiement / facturation.
```
