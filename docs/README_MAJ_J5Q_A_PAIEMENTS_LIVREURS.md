# J5Q-A — Paiements livreurs : historique, génération et suivi admin

Date de clôture recette : **24/06/2026**
Tag recette : **`j5q-paiements-livreurs-recette`**
Commit : **`12bb402 feat(j5q): add courier payout history and admin tracking`**

## Objectif

J5Q-A ajoute une première brique fiable de rémunération livreur : Hodina calcule les gains des livreurs sur une période de paiement, l’admin valide puis marque le paiement comme effectué, et le livreur consulte son historique dans Djama.

Ce lot répond à une contrainte terrain : les livreurs doivent être payés deux fois par mois, autour du **15** et du **30**. Hodina ne déclenche pas de virement automatique ; il garde un historique exploitable et permet à l’admin de dire clairement si le paiement a été fait.

## Règles métier validées

- Les livreurs sont payés sur deux périodes mensuelles :
  - du 1 au 15 inclus ;
  - du 16 au dernier jour du mois.
- Le paiement cible est le 15 pour la première période et le 30 environ pour la deuxième période.
- Pour les mois sans 30, la date cible de la deuxième période devient le dernier jour réel du mois.
- Seules les commandes `DELIVERED` entrent dans les rémunérations.
- Le rattachement d’une commande à une période s’appuie sur `CustomerOrder.deliveredAt`.
- Le champ `deliveredAt` est une source métier, pas un simple `updatedAt` de secours.
- Une commande livrée ne peut être rattachée qu’à une seule ligne de rémunération livreur.
- Un paiement marqué `PAID` n’est plus recalculé.
- Le paiement réel reste une action manuelle hors plateforme ; Hodina trace le statut, la date, la référence, le mode et la note admin.

## Données ajoutées

### `CourierPayout`

Paiement global d’un livreur sur une période.

Champs principaux :

- `courier` ;
- `periodStart` ;
- `periodEnd` ;
- `paymentDueDate` ;
- `status` ;
- `totalAmount` ;
- `ordersCount` ;
- `validatedAt` ;
- `paidAt` ;
- `paymentMethod` ;
- `paymentReference` ;
- `adminNote` ;
- `createdAt` ;
- `updatedAt`.

Statuts :

- `DRAFT` : calculé, à contrôler ;
- `VALIDATED` : validé, à payer ;
- `PAID` : payé ;
- `CANCELED` : annulé.

Contrainte importante : une rémunération est unique par livreur et par période.

### `CourierPayoutLine`

Ligne de paiement commande par commande.

Champs principaux :

- `courierPayout` ;
- `customerOrder` ;
- `orderReference` ;
- `deliveredAt` ;
- `customerCommune` ;
- `courierPayoutAmount` ;
- `deliveryFeeCustomer` ;
- `snapshot` ;
- `createdAt`.

Contrainte importante : `customer_order_id` est unique dans `courier_payout_line`. Une commande ne peut donc pas être payée deux fois via deux lignes actives.

Le snapshot conserve une trace stable : identifiant commande, référence, date de livraison, commune, total client, frais client, gain livreur et extrait du snapshot logistique.

## Service métier

Service ajouté :

```text
src/Service/CourierPayoutService.php
```

Responsabilités :

- calculer la période courante ;
- calculer la période précédente ;
- créer ou compléter les paiements `DRAFT` ;
- ignorer les paiements `VALIDATED`, `PAID` ou `CANCELED` ;
- ignorer les commandes déjà rattachées à une ligne de rémunération ;
- calculer l’estimation de la période en cours pour le livreur connecté ;
- rechercher les paiements d’un livreur pour Djama.

Source du montant livreur :

```text
CustomerOrder.deliveryLogisticsSnapshot.preview.estimatedCourierPayout
→ sinon CustomerOrder.deliveryLogisticsSnapshot.courierPayout
→ sinon CustomerOrder.deliveryFee en secours
```

## Backoffice EasyAdmin

Menu métier validé après réorganisation :

```text
Logistique
Catalogue
Commandes
Clients
Vendeurs
Livreurs
  - Livreurs
  - Rémunérations livreurs
  - Lignes rémunération
Logs
Réglages
```

Fichiers concernés :

- `src/Controller/Admin/DashboardController.php` ;
- `assets/admin.js`.

`assets/admin.js` reconnaît désormais les sections repliables suivantes :

```text
Logistique, Catalogue, Commandes, Clients, Vendeurs, Livreurs, Logs, Réglages
```

### `Livreurs > Livreurs`

Contrôleur dédié :

```text
src/Controller/Admin/CourierCrudController.php
```

Il hérite de `CustomerCrudController` et filtre uniquement les comptes dont `roles` contient `ROLE_COURIER`.

### `Livreurs > Rémunérations livreurs`

Contrôleur :

```text
src/Controller/Admin/CourierPayoutCrudController.php
```

Actions disponibles :

- générer la période en cours ;
- générer la période précédente ;
- voir le détail ;
- valider ;
- marquer payé ;
- annuler.

La génération ajoute uniquement les commandes livrées non encore rattachées à une rémunération.

### `Livreurs > Lignes rémunération`

Contrôleur :

```text
src/Controller/Admin/CourierPayoutLineCrudController.php
```

Rôle : consulter le détail commande par commande.

## Portail Djama

Section ajoutée :

```text
Mes paiements
```

Affichage mobile-first avec cartes repliées comme les commandes :

- estimation de la période en cours ;
- paiements à venir ou en attente ;
- historique payé ;
- détail des commandes composant chaque paiement.

La recette a validé l’affichage suivant dans Djama :

```text
Historique payé
16/06/2026 → 30/06/2026 · 30,00 €
Payé le 24/06/2026 15:17
RECHODINA202606232 → 15,00 €
HODINA202606221    → 15,00 €
```

## Migration

Migration ajoutée :

```text
migrations/Version20260624140000.php
```

Tables créées :

```text
courier_payout
courier_payout_line
```

Contrôles recette validés :

```text
DoctrineMigrations\Version20260624140000 exécutée
Doctrine schema validate OK
Tables courier_payout et courier_payout_line présentes
Routes EasyAdmin backoffice_courier*, backoffice_courier_payout*, backoffice_courier_payout_line* présentes
```

## Test recette validé

Requêtes de validation observées :

```text
courier_payout
id = 1
courier_id = 10
status = PAID
total_amount = 30.00
orders_count = 2
period_start = 2026-06-16
period_end = 2026-06-30
payment_due_date = 2026-06-30
validated_at = 2026-06-24 13:42:49
paid_at = 2026-06-24 15:17:16
```

```text
courier_payout_line
HODINA202606221      → 15.00 €
RECHODINA202606232   → 15.00 €
```

Statut final : **J5Q-A validé recette**.

## Fichiers principaux

- `assets/admin.js`
- `src/Entity/CourierPayout.php`
- `src/Entity/CourierPayoutLine.php`
- `src/Repository/CourierPayoutRepository.php`
- `src/Repository/CourierPayoutLineRepository.php`
- `src/Service/CourierPayoutService.php`
- `src/Controller/Admin/CourierCrudController.php`
- `src/Controller/Admin/CourierPayoutCrudController.php`
- `src/Controller/Admin/CourierPayoutLineCrudController.php`
- `src/Controller/Admin/DashboardController.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `templates/courier/dashboard.html.twig`
- `migrations/Version20260624140000.php`

## Contrôles recommandés

```powershell
php -l src/Entity/CourierPayout.php
php -l src/Entity/CourierPayoutLine.php
php -l src/Repository/CourierPayoutRepository.php
php -l src/Repository/CourierPayoutLineRepository.php
php -l src/Service/CourierPayoutService.php
php -l src/Controller/Admin/CourierCrudController.php
php -l src/Controller/Admin/CourierPayoutCrudController.php
php -l src/Controller/Admin/CourierPayoutLineCrudController.php
php -l src/Controller/Admin/DashboardController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l migrations/Version20260624140000.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php bin/console debug:router | findstr courier-payout
php bin/console cache:clear
php bin/console cache:warmup
git diff --check
```

## Points non inclus volontairement

- Pas de virement automatique.
- Pas d’export CSV avancé.
- Pas de ligne d’ajustement manuelle.
- Pas de cron automatique de génération.
- Pas de suivi complet des reversements vendeurs.

Ces sujets pourront être traités après validation terrain du suivi livreur.

---

# Suite J5Q-C — Automatisation douce renforcée

Après validation de J5Q-A, la suite retenue est J5Q-C.

J5Q-B, initialement prévu comme portail Djama “Mes paiements”, a été intégré dans J5Q-A à la demande produit, avec cartes repliées.

J5Q-C ajoute la commande Symfony et le cron de préparation :

```text
hodina:courier-payouts:generate --auto-due --notify-admins
```

Le cron prépare les DRAFT et envoie un récap admin. Il ne valide pas et ne paie pas.
