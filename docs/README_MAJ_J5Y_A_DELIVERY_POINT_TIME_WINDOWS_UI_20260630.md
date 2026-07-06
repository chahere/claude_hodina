# README mise à jour — J5Y-A — UX back-office plages horaires points de remise

Date : 2026-06-30

## Résumé

J5Y-A améliore l’expérience back-office dans le formulaire produit, section :

```text
Avancé — points de remise : plages horaires du nouveau point
```

Avant J5Y-A, l’admin devait saisir les plages sous forme de texte technique :

```text
Matin;0;08:00;12:00
Après-midi;0;14:00;18:00
```

Après J5Y-A, l’admin saisit chaque plage avec des champs simples :

```text
Libellé : Matin
Jours concernés : Jours ouvrés
Heure début : 08:00
Heure fin : 12:00
```

Un bouton permet d’ajouter d’autres plages.

## Règles de conversion

| Choix UI | Conversion technique |
|---|---|
| Tous les jours | une plage générique tous les jours |
| Jours ouvrés | lundi, mardi, mercredi, jeudi, vendredi |
| Jours ouvrables | lundi, mardi, mercredi, jeudi, vendredi, samedi |
| Jour précis | le jour choisi uniquement |

## Pourquoi ce choix

Cette solution réduit les erreurs de saisie pour les utilisateurs non techniques tout en conservant le modèle de données existant `DeliveryPointTimeWindow`.

On ne crée pas de codes spéciaux en base pour `jours ouvrés` ou `jours ouvrables`. L’interface développe ces choix en plages jour par jour.

## Points de vigilance

- Ce bloc ne sert que si l’admin crée un nouveau point depuis le produit.
- Pour modifier les plages d’un point existant, utiliser le menu `Logistique > Plages points de remise`.
- J5Y-A ne modifie pas le panier, le checkout, les frais ou le calendrier de livraison standard.

## Tests navigateur

Dans EasyAdmin :

1. Aller dans `Catalogue > Produits`.
2. Créer ou éditer un produit.
3. Ouvrir `Avancé — points de remise : créer un nouveau point`.
4. Renseigner un nom de point et une commune logistique.
5. Ouvrir `Avancé — points de remise : plages horaires du nouveau point`.
6. Vérifier les deux plages préremplies :
   - Matin — jours ouvrés — 08:00 à 12:00 ;
   - Après-midi — jours ouvrés — 14:00 à 18:00.
7. Ajouter une plage `Samedi matin`, jour `Samedi`, 08:00–12:00.
8. Enregistrer.
9. Vérifier dans `Logistique > Plages points de remise` que les plages ont bien été créées.

## Tests techniques

```powershell
php -l src\Controller\Admin\ProductCrudController.php
php -l tools\assert-j5y-a-delivery-point-window-ui.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-a-delivery-point-window-ui.php
```
