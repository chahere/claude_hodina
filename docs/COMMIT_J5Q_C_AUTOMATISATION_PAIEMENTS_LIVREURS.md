# COMMIT — J5Q-C automatisation paiements livreurs

## Message conseillé

```bash
git commit -m "feat(j5q): automate courier payout draft generation"
```

## Résumé

Ajoute une automatisation contrôlée des rémunérations livreurs : commande Symfony, mode cron `--auto-due`, récap e-mail aux admins, script d'installation cron.

## Fichiers concernés

```text
src/Command/GenerateCourierPayoutsCommand.php
src/Service/CourierPayoutService.php
src/Service/CourierPayoutAdminNotificationService.php
src/Entity/EmailLog.php
templates/emails/admin/courier_payout_recap.html.twig
tools/install-courier-payout-cron.sh
docs/README_MAJ_J5Q_C_AUTOMATISATION_PAIEMENTS_LIVREURS.md
docs/COMMIT_J5Q_C_AUTOMATISATION_PAIEMENTS_LIVREURS.md
```

## Changements

- Ajout de `hodina:courier-payouts:generate`.
- Ajout de `--period=current|previous`.
- Ajout de `--date=YYYY-MM-DD`.
- Ajout de `--timezone=Indian/Mayotte`.
- Ajout de `--dry-run`.
- Ajout de `--auto-due`.
- Ajout de `--notify-admins`.
- Ajout d'un e-mail admin de récap.
- Ajout d'un script d'installation cron.
- Correction anti-DRAFT vide dans `CourierPayoutService`.

## Règle métier

```text
Le système prépare.
L'admin valide.
L'admin marque payé après paiement réel.
```

Le cron ne doit jamais être transformé en paiement automatique sans décision métier explicite.

## Contrôles

```powershell
php -l src/Command/GenerateCourierPayoutsCommand.php
php -l src/Service/CourierPayoutService.php
php -l src/Service/CourierPayoutAdminNotificationService.php
php -l src/Entity/EmailLog.php
php bin/console lint:twig templates/emails/admin/courier_payout_recap.html.twig
php bin/console doctrine:schema:validate
php bin/console hodina:courier-payouts:generate --period=current --dry-run
php bin/console hodina:courier-payouts:generate --date=2026-06-16 --auto-due --dry-run
bash -n tools/install-courier-payout-cron.sh
git diff --check
```

## Commande Git conseillée

```powershell
git add src/Command/GenerateCourierPayoutsCommand.php `
  src/Service/CourierPayoutService.php `
  src/Service/CourierPayoutAdminNotificationService.php `
  src/Entity/EmailLog.php `
  templates/emails/admin/courier_payout_recap.html.twig `
  tools/install-courier-payout-cron.sh `
  docs/README_MAJ_J5Q_C_AUTOMATISATION_PAIEMENTS_LIVREURS.md `
  docs/COMMIT_J5Q_C_AUTOMATISATION_PAIEMENTS_LIVREURS.md `
  docs/ARCHITECTURE.md `
  docs/DECISIONS.md `
  docs/WORKFLOWS.md `
  docs/TODO.md `
  docs/ROADMAP.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/HISTORIQUE.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/README_MAJ_J5Q_A_PAIEMENTS_LIVREURS.md
```
