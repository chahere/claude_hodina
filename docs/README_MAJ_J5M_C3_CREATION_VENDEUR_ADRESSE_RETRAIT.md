# J5M-C3 — Création vendeur avec adresse de retrait intégrée

## Objectif

Simplifier le formulaire EasyAdmin de création/édition vendeur pour coller au besoin métier terrain :

- l’admin crée un vendeur ;
- Hodina crée ou rattache automatiquement le compte client vendeur ;
- l’admin saisit directement l’adresse / point de retrait du vendeur ;
- Hodina crée ou met à jour l’adresse de retrait ;
- Hodina déduit et stocke `Seller.deliveryCommune` depuis cette adresse ;
- `DeliveryLogisticsService` continue d’utiliser uniquement `Seller.deliveryCommune` pour les trajets, coûts, barge et BFS.

## Décisions

### Formulaire vendeur

Le sélecteur technique `Compte client vendeur` n’est plus affiché en création/édition.

Le sélecteur `Adresse / point de retrait` est remplacé par des champs de saisie directe :

- adresse de retrait ;
- complément ;
- code postal ;
- commune ;
- instructions vendeur / accès ;
- note terrain interne ;
- latitude GPS ;
- longitude GPS ;
- précision GPS.

La commune texte historique reste masquée du formulaire.

### Compte client vendeur

À la sauvegarde :

1. si un compte client est déjà rattaché, il est conservé ;
2. sinon, si un client existe avec le même e-mail, il est rattaché ;
3. sinon, Hodina crée automatiquement un `Customer` vendeur ;
4. le rôle `ROLE_SELLER` est ajouté si nécessaire.

Le mot de passe est généré aléatoirement. Le vendeur pourra utiliser le parcours “mot de passe oublié” ou une invitation dédiée plus tard.

### Adresse de retrait

À la sauvegarde :

- si `Seller.pickupAddress` existe, elle est mise à jour ;
- sinon, une nouvelle `Address` est créée ;
- cette adresse appartient au `Customer` vendeur ;
- elle devient aussi l’adresse de livraison par défaut du compte vendeur si ce compte n’en a pas encore.

### Logistique

La commune logistique est toujours une valeur stockée dans `Seller.deliveryCommune`.

Elle est maintenant déduite automatiquement depuis l’adresse de retrait, puis utilisée par les calculs existants.

Règle non négociable :

```text
DeliveryLogisticsService ne doit pas utiliser pickupAddress pour calculer trajet/coût/barge/BFS.
```

Le script de garde-fou reste :

```bash
php tools/assert-delivery-logistics-commune-source.php
```

## Fichiers modifiés

- `src/Controller/Admin/SellerCrudController.php`
- `src/Entity/Seller.php`
- `src/Service/SellerPickupLogisticsSynchronizer.php`
- `docs/TODO.md`
- `docs/README_MAJ_J5M_C3_CREATION_VENDEUR_ADRESSE_RETRAIT.md`

## Tests recommandés

```bash
php -l src/Entity/Seller.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
php bin/console cache:clear
php bin/console cache:warmup
```

## Tests fonctionnels

1. Créer un vendeur depuis EasyAdmin.
2. Renseigner nom, téléphone, e-mail et adresse de retrait complète.
3. Enregistrer.
4. Vérifier qu’un compte client vendeur est créé ou rattaché.
5. Vérifier que l’adresse de retrait est créée sur ce client.
6. Vérifier que `Seller.deliveryCommune` et `Seller.deliveryZone` sont déduits.
7. Ouvrir `/djama` et déplier une commande contenant ce vendeur.
8. Vérifier l’affichage de l’adresse de retrait, GPS et produits à collecter.
