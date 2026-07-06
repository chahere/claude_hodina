# README mise à jour — J5Y-A-bis — Branchement réel des plages horaires dans EasyAdmin

Date : 2026-06-30

## Pourquoi ce correctif

Le premier patch J5Y-A ajoutait une interface guidée mais s’appuyait sur `setTemplatePath()` dans EasyAdmin. En pratique, le formulaire produit continuait à afficher le textarea technique.

J5Y-A-bis corrige l’intégration : l’interface est maintenant attachée au vrai champ formulaire via `row_attr` et Stimulus.

## Fonctionnement

Dans `ProductCrudController` :

- le champ `quickDeliveryPointTimeWindows` reste un `TextareaField` non mappé ;
- la ligne du champ reçoit `data-controller="delivery-point-windows"` ;
- le textarea reçoit `data-delivery-point-windows-source="1"` ;
- le template Twig spécifique est supprimé.

Dans `delivery_point_windows_controller.js` :

- le textarea est trouvé automatiquement ;
- il est caché ;
- une interface lisible est injectée ;
- à chaque modification, le textarea est mis à jour au format historique `Label;jour;début;fin`.

## Règles jour

| Choix UI | Conversion backend |
|---|---|
| Tous les jours | plage générique tous les jours |
| Jours ouvrés | lundi à vendredi |
| Jours ouvrables | lundi à samedi |
| Jour précis | jour choisi uniquement |

## À retenir pour l’exploitation

Cette interface est un raccourci pour créer les plages d’un **nouveau point de remise créé depuis le produit**.

Pour modifier un point existant, utiliser :

```text
Logistique > Plages points de remise
```

## Tests navigateur

1. Aller dans `/ouegnewe`.
2. Ouvrir `Catalogue > Produits`.
3. Créer ou éditer un produit test.
4. Ouvrir `Avancé — points de remise : créer un nouveau point`.
5. Renseigner `Nom du nouveau point` et `Commune logistique du point`.
6. Ouvrir `Avancé — points de remise : plages horaires du nouveau point`.
7. Vérifier que le textarea technique n’est plus visible.
8. Vérifier l’interface : Libellé / Jours concernés / Début / Fin.
9. Créer :
   - Matin — jours ouvrés — 08:00 à 12:00 ;
   - Après-midi — jours ouvrés — 14:00 à 18:00 ;
   - Samedi matin — samedi — 08:00 à 12:00.
10. Enregistrer.
11. Vérifier dans `Logistique > Plages points de remise` que les plages sont créées.

## Tests techniques

```powershell
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5y-a-delivery-point-window-ui.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-a-delivery-point-window-ui.php
```

Après modification d’assets en local :

```powershell
Remove-Item public\assets -Recurse -Force -ErrorAction SilentlyContinue
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```
