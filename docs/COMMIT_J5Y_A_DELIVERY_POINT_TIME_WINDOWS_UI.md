# Commit J5Y-A — UX back-office plages horaires points de remise

## Objectif

Remplacer la saisie avancée peu accessible `Label;jour;début;fin` dans le formulaire produit par une interface guidée pour créer les plages horaires d’un nouveau point de remise.

## Périmètre

Inclus :

- formulaire produit EasyAdmin ;
- interface guidée avec plusieurs plages ;
- bouton “Ajouter une plage horaire” ;
- champs libellé, jours concernés, heure début, heure fin ;
- choix “Tous les jours”, “Jours ouvrés”, “Jours ouvrables” et jours précis ;
- conversion backend vers les plages `DeliveryPointTimeWindow` existantes ;
- garde-fou `tools/assert-j5y-a-delivery-point-window-ui.php`.

Exclus :

- panier ;
- checkout ;
- frais de livraison ;
- calendrier standard par zone tarifaire ;
- logique de disponibilité produit ;
- modification des points existants depuis le produit.

## Décisions métier

- `Jours ouvrés` signifie lundi à vendredi.
- `Jours ouvrables` signifie lundi à samedi.
- `Tous les jours` reste une plage générique tous les jours.
- Pour modifier les plages d’un point déjà existant, l’admin doit utiliser le menu `Plages points de remise`.
- Le bloc dans le produit reste un raccourci de création, pas une interface complète de planning.

## Validation locale attendue

```powershell
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5y-a-delivery-point-window-ui.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-a-delivery-point-window-ui.php
```

## Commit recommandé

```powershell
git add src\Controller\Admin\ProductCrudController.php `
  assets\admin.js `
  assets\controllers\delivery_point_windows_controller.js `
  templates\admin\field\quick_delivery_point_time_windows.html.twig `
  tools\assert-j5y-a-delivery-point-window-ui.php `
  docs\COMMIT_J5Y_A_DELIVERY_POINT_TIME_WINDOWS_UI.md `
  docs\README_MAJ_J5Y_A_DELIVERY_POINT_TIME_WINDOWS_UI_20260630.md

git commit -m "feat(j5y-a): improve delivery point time window admin UI"
```
