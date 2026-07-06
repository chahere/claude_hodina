# J5M-C3-bis — Commune de retrait vendeur depuis DeliveryCommune seedée

## Statut

Patch correctif préparé après test fonctionnel du formulaire vendeur J5M-C3.

## Problème constaté

Le formulaire de création vendeur affichait encore :

```text
Code postal — champ texte libre
Commune — champ texte libre
```

Cela laisse trop de place aux erreurs de saisie (`Labattoir`, `Labatoir`, mauvais code postal, etc.) alors que les communes de retrait sont des communes / points logistiques de Mayotte déjà seedés dans `DeliveryCommune`.

## Règle corrigée

```text
L’admin choisit la commune de retrait depuis DeliveryCommune.
Hodina déduit automatiquement Address.commune et Address.postalCode.
Hodina stocke Seller.deliveryCommune depuis la commune sélectionnée.
DeliveryLogisticsService continue d’utiliser uniquement Seller.deliveryCommune.
```

## Impact fonctionnel

En création / édition vendeur, le formulaire affiche désormais :

```text
Adresse de retrait
Complément
Commune de retrait — liste seed DeliveryCommune
Instructions vendeur / accès
Note terrain interne
GPS latitude / longitude / précision
Marge vendeur Hodina (%)
```

Le code postal n’est plus saisi manuellement.
La commune n’est plus saisie en texte libre.

## Impact technique

Aucune migration Doctrine.

La propriété non persistée `Seller.pickupDeliveryCommune` porte la commune choisie dans le formulaire.
Le service `SellerPickupLogisticsSynchronizer` utilise cette commune pour :

- remplir `Address.commune` ;
- remplir `Address.postalCode` ;
- remplir `Address.deliveryZone` ;
- synchroniser `Seller.deliveryCommune` ;
- synchroniser `Seller.deliveryZone` ;
- conserver le champ legacy `Seller.commune` par compatibilité.

## Garde-fou conservé

```bash
php tools/assert-delivery-logistics-commune-source.php
```

Résultat attendu :

```text
[J5M-C2][OK] DeliveryLogisticsService reste verrouillé sur Seller::deliveryCommune pour les trajets/coûts/barge/BFS.
```

## Fichiers concernés

```text
src/Controller/Admin/SellerCrudController.php
src/Entity/Seller.php
src/Service/SellerPickupLogisticsSynchronizer.php
docs/TODO.md
docs/README_MAJ_J5M_C3_BIS_COMMUNE_RETRAIT_SEEDEE.md
```

## Tests recommandés

```powershell
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Entity/Seller.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
php bin/console cache:clear
php bin/console cache:warmup
```

## Parcours fonctionnel

```text
1. EasyAdmin > Vendeurs > Créer.
2. Vérifier que Commune de retrait est une liste issue de DeliveryCommune.
3. Vérifier que Code postal n’est plus saisi manuellement.
4. Créer un vendeur avec adresse de retrait.
5. Vérifier que le compte client vendeur est créé / rattaché.
6. Vérifier que l’adresse créée possède la commune et le code postal seedés.
7. Vérifier que Seller.deliveryCommune et Seller.deliveryZone sont synchronisées.
8. Ouvrir /djama et déplier une commande contenant ce vendeur.
9. Vérifier le bloc Collecte vendeurs.
```
