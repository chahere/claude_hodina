# COMMIT — J5AC espace client finalisé avec AJAX

Date : 2026-07-03

## Commits

```text
60d3dee feat(j5ac): finalize client account space with ajax
0966429 fix(j5ac): mark email migration non transactional
```

## Tags

```text
recette-j5ac-espace-client-ajax-20260703
recette-j5ac-espace-client-ajax-v2-20260703
prod-j5ac-espace-client-ajax-20260703
```

## Résumé

J5AC finalise l’espace client : hub `/mon-compte`, profil, sécurité mot de passe, reset connecté via `SmsLog`, email unique nullable, navigation et formulaires en AJAX progressif discret.

## Fichiers principaux

- `src/Controller/Client/AccountController.php`
- `src/Controller/Client/ProfileController.php`
- `src/Controller/Client/PasswordController.php`
- `src/Controller/PasswordResetController.php`
- `src/Entity/Customer.php`
- `src/Form/ClientProfileType.php`
- `src/Form/ClientChangePasswordType.php`
- `src/Service/CustomerPasswordResetLinkService.php`
- `migrations/Version20260703093000.php`
- `templates/client/*`
- `tools/assert-j5ac-*.php`

## Décisions importantes

- `customer.email` unique nullable.
- `customer.phone` non unique.
- Migration non transactionnelle pour MariaDB/MySQL.
- AJAX progressif HTML, pas de contrat JSON.
- Fallback sans JavaScript conservé.

## Validation

Statut : validé local, recette v2 et production.

Correction donnée prod post-MEP :

```text
customer.id=13 : chahere.kdu → chahere.kdu@outlook.fr
```

Contrôles finaux : asserts J5AC/J5AC-B/J5AC-DB OK, Doctrine schema OK, tests navigateur production OK.
