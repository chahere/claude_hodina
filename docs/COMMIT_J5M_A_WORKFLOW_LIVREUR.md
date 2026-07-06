# COMMIT — J5M-A — Workflow livreur enrichi

Date : 21/06/2026  
Statut : patch préparé, à tester localement puis en recette.

## Objectif

Insérer une étape claire entre `READY_FOR_PICKUP` et `OUT_FOR_DELIVERY` pour éviter de confondre :

```text
commande prête
→ prise en charge par un livreur
→ départ réel en livraison
→ livrée
```

## Décision métier

Le nom du livreur ne doit pas être stocké dans le statut.

La donnée métier est donc séparée :

```text
status = PICKED_UP
assignedCourier = livreur connecté
courierAssignedAt = date de prise en charge
```

L’affichage devient :

```text
Prise en charge par {livreur}
```

Puis, au départ réel :

```text
status = OUT_FOR_DELIVERY
outForDeliveryAt = date de départ livraison
affichage = En cours de livraison
```

## Périmètre modifié

Fichiers touchés :

```text
src/Entity/CustomerOrder.php
src/Service/CustomerOrderWorkflowService.php
src/Controller/Courier/CourierDashboardController.php
src/Controller/Admin/CustomerOrderCrudController.php
src/Controller/Admin/DashboardController.php
src/Service/Sms/OrderSmsMessageBuilder.php
templates/courier/dashboard.html.twig
templates/admin/customer_order/operational_sheet.html.twig
templates/admin/customer_order/send_sms.html.twig
templates/admin/dashboard.html.twig
docs/TODO.md
docs/COMMIT_J5M_A_WORKFLOW_LIVREUR.md
```

## Ce qui a été ajouté

- Ajout du statut technique `PICKED_UP` dans `CustomerOrder`.
- Conservation du statut existant `OUT_FOR_DELIVERY`.
- `takeForDelivery()` ne passe plus directement la commande en livraison.
- `takeForDelivery()` affecte le livreur connecté et passe la commande en `PICKED_UP`.
- Ajout de `startDelivery()` pour passer de `PICKED_UP` à `OUT_FOR_DELIVERY`.
- Le portail livreur affiche les commandes assignées en `PICKED_UP` et `OUT_FOR_DELIVERY`.
- Le portail livreur propose maintenant trois actions terrain :
  - `Prendre en charge` ;
  - `Démarrer la livraison` ;
  - `Marquer livrée`.
- L’admin affiche `Prise en charge par {livreur}` quand le statut est `PICKED_UP`.
- Le tableau de bord admin compte `PICKED_UP` et `OUT_FOR_DELIVERY` dans les commandes actives.
- Les modèles SMS manuels incluent maintenant :
  - `Client — prise en charge` ;
  - `Client — en cours de livraison`.

## Ce qui n’a pas été modifié

- Pas de changement panier J5L.
- Pas de changement checkout.
- Pas de migration ajoutée : les champs existants `assignedCourier`, `courierAssignedAt` et `outForDeliveryAt` suffisent.
- Pas de stockage du nom du livreur dans `status`.
- Pas de refonte du portail livreur.
- Pas de géolocalisation temps réel.

## Workflow cible

```text
PENDING_VALIDATION
→ CONFIRMED
→ PREPARING
→ READY_FOR_PICKUP
→ PICKED_UP
→ OUT_FOR_DELIVERY
→ DELIVERED
```

Fallback admin conservé :

```text
READY_FOR_PICKUP / PICKED_UP / OUT_FOR_DELIVERY
→ DELIVERED
```

## Tests à exécuter

### Tests techniques locaux

```bash
php -l src/Entity/CustomerOrder.php
php -l src/Service/CustomerOrderWorkflowService.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/Admin/CustomerOrderCrudController.php
php -l src/Controller/Admin/DashboardController.php
php -l src/Service/Sms/OrderSmsMessageBuilder.php
php bin/console lint:twig templates/courier/dashboard.html.twig templates/admin/customer_order/operational_sheet.html.twig templates/admin/customer_order/send_sms.html.twig templates/admin/dashboard.html.twig
php bin/console doctrine:schema:validate
```

### Tests fonctionnels

1. Créer ou utiliser une commande existante.
2. Passer la commande en `PREPARING`.
3. Passer la commande en `READY_FOR_PICKUP`.
4. Se connecter avec un utilisateur `ROLE_COURIER`.
5. Vérifier que la commande apparaît dans `Commandes prêtes`.
6. Cliquer sur `Prendre en charge`.
7. Vérifier que :
   - le statut devient `PICKED_UP` ;
   - le livreur connecté est assigné ;
   - `courierAssignedAt` est renseigné ;
   - l’admin affiche `Prise en charge par {livreur}`.
8. Cliquer sur `Démarrer la livraison`.
9. Vérifier que :
   - le statut devient `OUT_FOR_DELIVERY` ;
   - `outForDeliveryAt` est renseigné ;
   - le badge affiche `En cours de livraison`.
10. Cliquer sur `Marquer livrée`.
11. Vérifier que le statut devient `DELIVERED`.
12. Vérifier qu’un autre livreur ne peut pas démarrer ou clôturer la commande assignée.
13. Vérifier la non-régression admin : `Valider`, `Préparer`, `Prête`, `Livrée`.
14. Vérifier que le panier J5L n’a pas bougé.

## Definition of Done J5M-A

```text
Un livreur connecté peut prendre une commande prête, la passer en cours de livraison, puis la marquer livrée. L’admin voit clairement le statut et le livreur responsable, sans que le nom du livreur soit stocké dans le statut.
```
