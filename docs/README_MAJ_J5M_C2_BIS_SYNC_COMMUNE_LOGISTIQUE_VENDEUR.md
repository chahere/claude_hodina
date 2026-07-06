# J5M-C2-bis — Synchronisation commune logistique vendeur depuis adresse de retrait

## Statut

Patch préparé après application technique de J5M-C2.

## Règle métier validée

```text
L’admin renseigne l’adresse de retrait.
Hodina déduit et stocke Seller.deliveryCommune.
DeliveryLogisticsService continue d’utiliser uniquement Seller.deliveryCommune.
```

## Pourquoi cette correction

J5M-C2 introduit `Seller.customerAccount` et `Seller.pickupAddress` pour guider le livreur vers le point réel de collecte vendeur.

Mais le calcul logistique existant repose sur `Seller.deliveryCommune` : coût, trajet BFS, barge, communes de collecte distinctes et snapshot commande.

J5M-C2-bis évite une double saisie dangereuse : l’admin ne choisit plus manuellement la commune logistique dans le formulaire vendeur. Elle est déduite côté serveur depuis l’adresse de retrait.

## Décisions

- `Seller.pickupAddress` reste l’adresse terrain visible par le livreur.
- `Seller.deliveryCommune` reste la source de vérité logistique stockée.
- `DeliveryLogisticsService` reste verrouillé sur `Seller.deliveryCommune`.
- `Seller.deliveryZone` est aussi synchronisée depuis la commune logistique pour garder l’ancien champ obligatoire cohérent.
- Le formulaire vendeur masque `deliveryCommune` et `deliveryZone` en édition/création.
- Les champs restent visibles hors formulaire pour contrôle admin.
- Si l’adresse de retrait appartient à un compte client et que le vendeur n’a pas encore de compte rattaché, Hodina rattache automatiquement ce compte.
- Si l’adresse de retrait appartient à un autre compte que le compte vendeur choisi, la sauvegarde est bloquée.

## Service ajouté

```text
src/Service/SellerPickupLogisticsSynchronizer.php
```

Responsabilités :

- vérifier la cohérence `Seller.customerAccount` / `Seller.pickupAddress.customer` ;
- initialiser `Seller.pickupAddress` depuis l’adresse de livraison par défaut du compte vendeur si possible ;
- résoudre `DeliveryCommune` depuis `Address.commune` + `Address.postalCode` ;
- synchroniser `Seller.deliveryCommune` ;
- synchroniser `Seller.deliveryZone` ;
- créer une adresse de retrait minimale depuis `Seller.deliveryCommune` uniquement via commande de rattrapage contrôlée.

## Commande de rattrapage ajoutée

```bash
php bin/console hodina:j5m:c2:sync-seller-pickup
```

Par défaut, la commande est en simulation.

Exemples :

```bash
php bin/console hodina:j5m:c2:sync-seller-pickup
php bin/console hodina:j5m:c2:sync-seller-pickup --apply
php bin/console hodina:j5m:c2:sync-seller-pickup --apply --seller-id=12
php bin/console hodina:j5m:c2:sync-seller-pickup --apply --create-missing-pickup-address
```

L’option `--create-missing-pickup-address` crée une adresse minimale de retrait depuis la commune logistique existante seulement si le vendeur possède déjà un `customerAccount`.

Adresse créée :

```text
Label : Point de retrait vendeur
Line1 : Point de retrait à préciser
Commune : Seller.deliveryCommune.name
Code postal : Seller.deliveryCommune.postalCode
Zone : zone déduite de Seller.deliveryCommune
Instructions : adresse initialisée depuis la commune logistique, à préciser avant exploitation terrain
```

La commande ne crée pas automatiquement de compte client vendeur. Ce choix reste manuel pour éviter de créer de faux comptes ou de mauvaises identités.

## Garde-fou conservé

Le script portable Windows/Linux reste obligatoire :

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
src/Service/SellerPickupLogisticsSynchronizer.php
src/Command/J5mC2SyncSellerPickupCommand.php
docs/README_MAJ_J5M_C2_BIS_SYNC_COMMUNE_LOGISTIQUE_VENDEUR.md
docs/TODO.md
```

## Tests recommandés

```powershell
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php -l src/Command/J5mC2SyncSellerPickupCommand.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
php bin/console hodina:j5m:c2:sync-seller-pickup
php bin/console cache:clear
php bin/console cache:warmup
```

## Parcours fonctionnel

```text
1. EasyAdmin > Vendeurs.
2. Ouvrir un vendeur.
3. Renseigner une adresse / point de retrait.
4. Sauvegarder.
5. Vérifier que la commune logistique affichée est déduite de l’adresse.
6. Vérifier que la zone de livraison est cohérente.
7. Ouvrir /djama.
8. Déplier une commande.
9. Vérifier le bloc Collecte vendeurs.
10. Vérifier que le garde-fou DeliveryLogisticsService passe toujours.
```

## Point de vigilance

Si une adresse de retrait est dans une commune non paramétrée dans `DeliveryCommune`, la sauvegarde du vendeur est bloquée. Il faut d’abord corriger l’adresse ou paramétrer la commune livrable/logistique.
