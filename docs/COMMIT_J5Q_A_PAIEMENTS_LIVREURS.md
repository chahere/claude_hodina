# Commit J5Q-A — Paiements livreurs

## Commit réel

```text
12bb402 feat(j5q): add courier payout history and admin tracking
```

## Tag recette

```text
j5q-paiements-livreurs-recette
```

## Message utilisé

```bash
git commit \
  -m "feat(j5q): add courier payout history and admin tracking" \
  -m "Add CourierPayout and CourierPayoutLine entities for semi-monthly courier payments." \
  -m "Generate payout drafts from delivered orders using deliveredAt as the source date." \
  -m "Add EasyAdmin courier list, payout generation and payment tracking." \
  -m "Display collapsible payment cards in Djama with current estimate, pending payouts and paid history." \
  -m "Reorganize EasyAdmin menu into business sections for clients, sellers, couriers and logs." \
  -m "Include documentation follow-up for J5O, J5P and J5Q."
```

## Résumé

J5Q-A ajoute le suivi de rémunération livreur sur Hodina : génération des paiements par période, contrôle admin, statut payé et historique visible dans Djama.

## Fichiers modifiés ou ajoutés

- `assets/admin.js`
- `docs/COMMIT_DOCS_J5O_J5P_20260624.md`
- `docs/COMMIT_J5Q_A_PAIEMENTS_LIVREURS.md`
- `docs/PROMPT_MAJ_DOCUMENTATION_HODINA.md`
- `docs/README_MAJ_DOCS_J5O_J5P_20260624.md`
- `docs/README_MAJ_J5Q_A_PAIEMENTS_LIVREURS.md`
- `docs/TODO.md`
- `migrations/Version20260624140000.php`
- `src/Controller/Admin/CourierCrudController.php`
- `src/Controller/Admin/CourierPayoutCrudController.php`
- `src/Controller/Admin/CourierPayoutLineCrudController.php`
- `src/Controller/Admin/DashboardController.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `src/Entity/CourierPayout.php`
- `src/Entity/CourierPayoutLine.php`
- `src/Repository/CourierPayoutLineRepository.php`
- `src/Repository/CourierPayoutRepository.php`
- `src/Service/CourierPayoutService.php`
- `templates/courier/dashboard.html.twig`

## Validations locales avant commit

- `php -l` OK sur les nouvelles entités, repositories, services, contrôleurs et migration.
- `lint:twig templates/courier/dashboard.html.twig` OK.
- `doctrine:schema:validate` OK.
- Routes `courier-payout` OK.
- `cache:clear` / `cache:warmup` OK.
- `git diff --check` OK.
- `git status` propre après commit et push.

## Validation recette

Déploiement par tag :

```bash
bash tools/deploy-hodina-by-tag.sh --project-dir "$HOME/recette.hodina.fr" --tag j5q-paiements-livreurs-recette --target recette
```

Résultat :

```text
Checkout tag : OK
Commit déployé : 12bb402
Migration Version20260624140000 : exécutée
Doctrine schema : OK
Assets compilés : OK
Cache prod : OK
Git working tree : propre
```

Contrôles serveur validés :

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console debug:router --env=prod | grep -Ei "courier|payout|livreur"
php bin/console dbal:run-sql --force-fetch "SHOW TABLES LIKE 'courier_payout%';"
```

Routes observées :

```text
backoffice_courier_index
backoffice_courier_payout_index
backoffice_courier_payout_line_index
admin_courier_payout_generate_current
admin_courier_payout_generate_previous
courier_dashboard
```

## Test fonctionnel recette validé

Le paiement test a été généré, validé et marqué payé.

```text
courier_payout.id = 1
courier_id = 10
status = PAID
total_amount = 30.00
orders_count = 2
period = 2026-06-16 → 2026-06-30
payment_due_date = 2026-06-30
validated_at = 2026-06-24 13:42:49
paid_at = 2026-06-24 15:17:16
```

Lignes rattachées :

```text
HODINA202606221      15.00 €
RECHODINA202606232   15.00 €
```

Djama affiche bien l’historique payé avec cartes repliées/dépliées.

## Points de vigilance

- Ne jamais générer un paiement à partir de commandes non `DELIVERED`.
- Ne jamais rattacher une commande à deux lignes de paiement.
- Ne pas recalculer un paiement `PAID`.
- Le passage à `PAID` doit rester une action admin, car le paiement réel se fait hors plateforme.
- Le suivi des reversements vendeurs reste un sujet distinct.
