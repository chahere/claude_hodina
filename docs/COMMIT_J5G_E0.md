# COMMIT J5G-E0 — Snapshot adresse commande

Date : **15/06/2026**  
Commit principal : `279f49c feat(j5g): snapshot order addresses`  
Commit préparatoire : `d4c5ab9 fix(j5g): use delivery address for cart logistics preview`  
Branche : `pilot/j5j-commerce-mode-role-tester`

---

## Objectif

Corriger la conception historique des adresses de commande.

Avant J5G-E0, une commande dépendait encore de `customer_order.delivery_address_id`, donc d'une adresse du carnet client. Or le carnet client doit rester vivant et supprimable.

L'objectif de J5G-E0 est de figer l'adresse de livraison et l'adresse de facturation directement dans `CustomerOrder` au moment de la commande.

---

## Décision métier

```text
Carnet d'adresses client = vivant et supprimable.
Commande = historique figé.
```

Le client ou l'admin doit pouvoir supprimer une adresse sans casser les commandes passées.

---

## Fichiers modifiés

```text
migrations/Version20260615225836.php
src/Entity/CustomerOrder.php
src/Controller/CheckoutController.php
src/Controller/Admin/CustomerOrderCrudController.php
src/Controller/Admin/EmailLogCrudController.php
templates/admin/customer_order/operational_sheet.html.twig
templates/courier/dashboard.html.twig
templates/emails/order_created.html.twig
```

---

## Migration

La migration ajoute dans `customer_order` :

```text
delivery_address_label
delivery_address_line1
delivery_address_line2
delivery_address_postal_code
delivery_address_commune
delivery_address_zone_code
delivery_address_zone_name
delivery_address_notes
billing_address_label
billing_address_line1
billing_address_line2
billing_address_postal_code
billing_address_commune
billing_address_zone_code
billing_address_zone_name
billing_address_notes
```

Elle reprend les anciennes commandes depuis les adresses existantes et rend la relation `delivery_address_id` tolérante à la suppression.

---

## Tests locaux

```powershell
php -l src\Entity\CustomerOrder.php
php -l src\Controller\CheckoutController.php
php -l src\Controller\Admin\CustomerOrderCrudController.php
php -l src\Controller\Admin\EmailLogCrudController.php
php -l migrations\Version20260615225836.php
php bin/console cache:clear
php bin/console lint:container
php bin/console doctrine:migrations:migrate --dry-run
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
```

Résultat : OK.

---

## Tests recette

```bash
php -l src/Entity/CustomerOrder.php
php -l src/Controller/CheckoutController.php
php -l src/Controller/Admin/CustomerOrderCrudController.php
php -l src/Controller/Admin/EmailLogCrudController.php
php -l migrations/Version20260615225836.php
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:schema:update --dump-sql --env=prod
```

Résultat : OK après refresh du cache prod.

---

## Tests métier validés

- Nouvelle commande créée avec snapshot livraison et facturation.
- Pas de nouveau doublon d'adresse sur le cas testé.
- Suppression d'une adresse de livraison liée à une commande depuis EasyAdmin.
- La commande conserve son snapshot même si `delivery_address_id` devient `NULL`.
- Les commandes gardent le snapshot facturation historique.

---

## Points non traités

- Nettoyage automatique des anciennes adresses doublons.
- Affichage propre de l'ID adresse dans EasyAdmin.
- Snapshot logistique financier complet : frais, marge, rémunération livreur, barge, route, hops.
