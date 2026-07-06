# COMMIT J5R-A — Portail client commandes + annulation client encadrée

Date : 25/06/2026

## Objectif

Créer la première version du Portail client MVP, centrée sur le suivi de commande et l’annulation client encadrée.

## Inclus

- Création du contrôleur `src/Controller/Client/AccountController.php`.
- Routes :
  - `/mon-compte` ;
  - `/mon-compte/commandes` ;
  - `/mon-compte/commandes/{id}` ;
  - `POST /mon-compte/commandes/{id}/annuler`.
- Liste commandes en cours / historique.
- Détail commande propriétaire uniquement.
- Statuts client lisibles.
- Message de prochaine étape.
- Produits, totaux, adresse de livraison snapshotée, consignes, GPS.
- Information code de réception sans affichage du code.
- Annulation client directe uniquement si `PENDING_VALIDATION` ou `CONFIRMED`.
- Nouvelle entité `CustomerOrderFeedback` pour stocker le motif/commentaire d’annulation.
- Migration `Version20260625163000`.
- Entrée EasyAdmin `Retours clients` en lecture seule.
- Lien `Compte` dans la navigation client.
- Lien `Voir le suivi de ma commande` après confirmation.

## Non inclus

- Notation vendeur/livreur : prévue J5R-B.
- Modification profil/adresses hors panier : prévue J5R-C.
- Paiement en ligne.
- Remboursement.
- Messagerie.
- Litige.
- Suivi GPS live.

## Règles métier

- Le détail commande est accessible uniquement au propriétaire de la commande.
- L’annulation client est possible avant préparation uniquement.
- Le motif d’annulation est facultatif mais persisté si renseigné.
- Le code de réception n’est jamais affiché en clair dans le portail client.
- Le portail client ne modifie pas Djama, le panier, les snapshots, le calcul livraison ou les transitions livreur.

## Tests techniques

```bash
php -l src/Entity/CustomerOrderFeedback.php
php -l src/Repository/CustomerOrderFeedbackRepository.php
php -l src/Controller/Client/AccountController.php
php -l src/Controller/Admin/CustomerOrderFeedbackCrudController.php
php -l src/Controller/Admin/DashboardController.php
php -l src/Service/CustomerOrderWorkflowService.php
php -l migrations/Version20260625163000.php
php bin/console lint:twig templates/client templates/base.html.twig templates/checkout/confirmation.html.twig
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate --skip-sync
php bin/console debug:router | grep -E "client_|mon-compte"
```

## Tests navigateur

1. Client non connecté ouvre `/mon-compte/commandes` : redirection connexion.
2. Client connecté sans commande : état vide clair.
3. Client connecté avec commande en validation : carte visible.
4. Détail commande propriétaire : visible.
5. Détail commande autre client : 404.
6. Annulation `PENDING_VALIDATION` : statut `CANCELED`, feedback créé.
7. Annulation `CONFIRMED` : statut `CANCELED`, feedback créé.
8. Annulation `PREPARING` : refus.
9. Commande avec GPS : lien carte visible.
10. Commande sans GPS : message clair sans erreur.
11. Djama : prise en charge, démarrage livraison et livraison inchangés.
