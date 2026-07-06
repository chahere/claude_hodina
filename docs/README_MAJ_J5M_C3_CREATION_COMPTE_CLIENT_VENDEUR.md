# J5M-C3 — Création automatique du compte client vendeur

## Objectif

Simplifier la création et l’édition des vendeurs dans EasyAdmin.

Règle retenue :

```text
L’admin renseigne le vendeur et son adresse de retrait.
Hodina crée ou rattache automatiquement un compte client vendeur.
Hodina déduit et stocke Seller.deliveryCommune depuis l’adresse de retrait.
DeliveryLogisticsService continue d’utiliser uniquement Seller.deliveryCommune.
```

## Changements

- Le champ `customerAccount` n’est plus demandé à la création d’un vendeur.
- Si une adresse de retrait est choisie, le compte client propriétaire de cette adresse est rattaché automatiquement.
- Sinon, si l’e-mail vendeur correspond à un client existant, ce client est rattaché automatiquement.
- Sinon, Hodina crée un nouveau `Customer` avec le rôle `ROLE_SELLER`.
- En édition, le compte client vendeur est visible mais désactivé pour éviter les modifications accidentelles.
- Le champ legacy `commune` n’est plus affiché dans le formulaire vendeur.
- Le rôle `ROLE_SELLER` est ajouté à la gestion des rôles client dans EasyAdmin.

## Points de vigilance

- Aucun changement BDD.
- Aucun changement dans `DeliveryLogisticsService`.
- Le script `tools/assert-delivery-logistics-commune-source.php` reste le garde-fou pour empêcher l’utilisation de l’adresse de retrait dans le calcul coût/trajet/barge/BFS.
- Si le vendeur est créé sans téléphone, un téléphone temporaire `0000000000` est posé sur le compte client vendeur et doit être corrigé.
- Si le vendeur est créé sans e-mail, le compte client vendeur ne pourra pas utiliser le reset password tant qu’un e-mail n’est pas renseigné.

## Tests recommandés

```powershell
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Admin/CustomerCrudController.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
```

Parcours fonctionnel :

1. Créer un vendeur sans sélectionner de compte client.
2. Vérifier qu’un `Customer` est créé ou rattaché automatiquement.
3. Vérifier que le rôle `ROLE_SELLER` est présent.
4. En édition vendeur, vérifier que le compte client est désactivé.
5. Choisir une adresse de retrait et enregistrer.
6. Vérifier que la commune logistique et la zone sont déduites automatiquement.
7. Vérifier le portail livreur et le bloc collecte vendeurs.
