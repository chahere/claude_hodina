# Commit — J5Y-A-bis — Branchement réel UI plages horaires dans EasyAdmin

Date : 2026-06-30

## Objectif

Corriger J5Y-A : le champ texte `quickDeliveryPointTimeWindows` restait visible dans le formulaire produit EasyAdmin, car `setTemplatePath()` ne remplaçait pas correctement le widget de formulaire en création/édition.

J5Y-A-bis branche l’interface guidée directement sur la ligne du champ EasyAdmin via `row_attr` et Stimulus.

## Décision technique

- Le textarea Symfony reste le champ technique soumis au backend.
- Le textarea est marqué avec `data-delivery-point-windows-source="1"`.
- La ligne EasyAdmin porte `data-controller="delivery-point-windows"`.
- Le contrôleur Stimulus détecte le textarea, le cache, puis injecte l’interface humaine.
- Le template Twig `templates/admin/field/quick_delivery_point_time_windows.html.twig` est abandonné/supprimé.

## UX cible

L’admin voit :

- Libellé ;
- Jours concernés ;
- Heure début ;
- Heure fin ;
- bouton `+ Ajouter une plage horaire` ;
- résumé lisible des plages qui seront créées.

Choix disponibles :

- Tous les jours ;
- Jours ouvrés — lundi à vendredi ;
- Jours ouvrables — lundi à samedi ;
- jours précis de lundi à dimanche.

## Hors périmètre

Aucun changement sur :

- panier ;
- checkout ;
- frais ;
- `DeliveryLogisticsService` ;
- `DeliveryScheduleService` ;
- calendrier standard de livraison par secteur.

## Tests

```powershell
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5y-a-delivery-point-window-ui.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-a-delivery-point-window-ui.php
```

Puis test navigateur EasyAdmin : créer un nouveau point depuis le formulaire produit et vérifier que les plages sont créées dans `Logistique > Plages points de remise`.
