# README MAJ — J5Q-C automatisation paiements livreurs

Date : 24/06/2026
Statut : patch préparé, à valider localement puis en recette

## Objectif

J5Q-C automatise la préparation des rémunérations livreurs sans automatiser le paiement réel.

Le besoin terrain est clair : à partir de septembre, Hodina ne doit pas dépendre d'une présence quotidienne à Mayotte ou d'un clic manuel oublié pour préparer les paiements livreurs.

## Principe retenu

```text
Cron quotidien
→ commande Symfony hodina:courier-payouts:generate --auto-due
→ no-op les jours non dus
→ génération DRAFT le 15 ou le dernier jour du mois
→ e-mail de récap aux admins
→ validation et paiement restent manuels dans EasyAdmin
```

## Commande Symfony ajoutée

```bash
php bin/console hodina:courier-payouts:generate
```

Options :

```text
--period=current|previous
--date=YYYY-MM-DD
--timezone=Indian/Mayotte
--dry-run
--auto-due
--notify-admins
```

Exemples :

```bash
php bin/console hodina:courier-payouts:generate --period=current --dry-run
php bin/console hodina:courier-payouts:generate --period=previous --dry-run
php bin/console hodina:courier-payouts:generate --auto-due --timezone=Indian/Mayotte --notify-admins --env=prod --no-interaction
```

## Règle auto-due

En mode `--auto-due`, la commande génère uniquement :

```text
le 15 du mois
le dernier jour du mois
```

Les autres jours, elle sort proprement sans modifier la base et sans envoyer de récap.

## E-mail de récap admin

Service ajouté :

```text
src/Service/CourierPayoutAdminNotificationService.php
```

Template ajouté :

```text
templates/emails/admin/courier_payout_recap.html.twig
```

Destinataires : comptes `Customer` ayant `ROLE_ADMIN` et une adresse e-mail valide.

L'e-mail indique :

```text
période générée
paiement prévu
nombre de paiements créés
nombre de paiements complétés
nombre de lignes rattachées
total à contrôler
détail par livreur
action requise dans EasyAdmin
```

## Cron fourni

Script ajouté :

```text
tools/install-courier-payout-cron.sh
```

Recette :

```bash
bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/recette.hodina.fr --target recette
```

Production :

```bash
bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/hodina.fr --target prod
```

Ligne installée par défaut :

```cron
10 5 * * * cd <project> && mkdir -p var/log && flock -n /tmp/hodina_<target>_courier_payout.lock /usr/local/bin/php bin/console hodina:courier-payouts:generate --auto-due --timezone=Indian/Mayotte --notify-admins --env=prod --no-interaction >> <project>/var/log/courier_payout_cron.log 2>&1
```

Le serveur est en UTC. `05:10 UTC` correspond à environ `08:10` à Mayotte.

## Anti-régression

```text
Le cron ne paie jamais automatiquement.
Le cron ne valide jamais automatiquement.
Le cron prépare uniquement des brouillons DRAFT.
Le récap admin est une alerte de contrôle.
Une commande déjà rattachée à une rémunération n'est pas reprise.
Un paiement PAID n'est jamais recalculé.
Une relance idempotente ne doit pas créer de DRAFT vide.
```

## Correction incluse

`CourierPayoutService::generateForPeriod()` ne persiste plus un paiement DRAFT vide quand toutes les commandes de la période sont déjà rattachées à des lignes existantes.

## Validation locale recommandée

```powershell
php -l src/Command/GenerateCourierPayoutsCommand.php
php -l src/Service/CourierPayoutService.php
php -l src/Service/CourierPayoutAdminNotificationService.php
php -l src/Entity/EmailLog.php

php bin/console lint:twig templates/emails/admin/courier_payout_recap.html.twig
php bin/console doctrine:schema:validate
php bin/console list hodina
php bin/console hodina:courier-payouts:generate --period=current --dry-run
php bin/console hodina:courier-payouts:generate --period=previous --dry-run
php bin/console hodina:courier-payouts:generate --date=2026-06-15 --auto-due --dry-run
php bin/console hodina:courier-payouts:generate --date=2026-06-16 --auto-due --dry-run

bash -n tools/install-courier-payout-cron.sh
php bin/console cache:clear
php bin/console cache:warmup
git diff --check
```

## Validation recette attendue

```bash
php bin/console doctrine:schema:validate --env=prod
php bin/console hodina:courier-payouts:generate --period=current --dry-run --env=prod
php bin/console hodina:courier-payouts:generate --date=2026-06-15 --auto-due --dry-run --env=prod
php bin/console hodina:courier-payouts:generate --date=2026-06-16 --auto-due --dry-run --env=prod
bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/recette.hodina.fr --target recette
crontab -l | grep courier-payouts
```

## Limite volontaire

J5Q-C ne crée pas d'export CSV, pas d'ajustement manuel, pas de récapitulatif mensuel avancé. Ces sujets restent pour J5Q-D.
