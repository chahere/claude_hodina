# README — Mise à jour documentation J5Q-C-2 / debug recette — 25/06/2026

## Objet

Mettre à jour la documentation de suivi après :

- validation recette technique de `J5Q-C-2 — Branding e-mail paramétrable` ;
- déploiement du tag `j5q-c2-branding-email-recette` ;
- diagnostic initial de l'incident intermittent `ERR_CONNECTION_CLOSED` observé côté navigateur mobile ;
- ajout du guide `DEBUG_RECETTE_HODINA.md`.

## État réel documenté

### J5Q-C-2

- Commit : `3586560`.
- Tag recette : `j5q-c2-branding-email-recette`.
- Migration recette : `Version20260625090000` exécutée.
- Doctrine schema : OK.
- Twig e-mails : OK.
- Groupe EasyAdmin : `Réglages > Branding e-mail` disponible.
- Réglages `email_branding_*` présents en base.

Validation fonctionnelle e-mails réels : encore à compléter après configuration du préfixe `[Recette]` et envoi SMTP effectif.

### Debug recette

Constats documentés :

- PHP web réel : `8.4.21` ;
- `memory_limit=512M` ;
- `max_execution_time=600` ;
- `public/error_log` reçoit les logs PHP web ;
- `public/.user.ini` est détecté mais la redirection `error_log=/home/.../var/log/php_web_error.log` n'a pas pris effet ;
- Monolog prod actuel écrit vers `php://stderr`, pas directement dans `var/log/prod.log` ;
- les access logs live sont dans `~/access-logs/recette.hodina.fr-ssl_log` ;
- aucun rollback J5Q-C-2 n'est justifié sans preuve applicative.

## Fichiers principaux mis à jour

- `VISION.md` ;
- `ARCHITECTURE.md` ;
- `DECISIONS.md` ;
- `ENTITIES.md` ;
- `WORKFLOWS.md` ;
- `TODO.md` ;
- `ROADMAP.md` ;
- `PILOT_STATUS_DETAILED.md` ;
- `DEPLOIEMENT_PREPROD.md` ;
- `HISTORIQUE.md` ;
- `README_MAJ_J5Q_C2_BRANDING_EMAIL.md` ;
- `COMMIT_J5Q_C2_BRANDING_EMAIL.md` ;
- `DEBUG_RECETTE_HODINA.md`.

## Incohérences clarifiées

- La colonne de valeur de `hodina_setting` est `value`, pas `setting_value`.
- `200 500` dans un access log n'est pas un HTTP 500 : `200` est le statut HTTP, `500` est une taille de réponse.
- `var/log/prod.log` peut rester vide avec la configuration Monolog prod actuelle, car le handler principal pointe vers `php://stderr`.
- `J5R-A — paiements vendeurs` n'est pas démarré ; il reste dans le backlog.

## Commandes de contrôle recommandées

```bash
cd ~/recette.hodina.fr
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console lint:twig templates/emails templates/registration/confirmation_email.html.twig --env=prod
php bin/console dbal:run-sql --env=prod --force-fetch "SELECT setting_key, value, group_key, group_label, sort_order FROM hodina_setting WHERE group_key = 'email_branding' ORDER BY sort_order;"
```

## Commande mémo debug

```bash
cd ~/recette.hodina.fr && \
echo "=== DATE ===" && date && \
echo "=== GIT ===" && git log --oneline -1 && git status --short && \
echo "=== PHP WEB ===" && tail -n 120 public/error_log 2>/dev/null && \
echo "=== SYMFONY PROD ===" && tail -n 120 var/log/prod.log 2>/dev/null && \
echo "=== ACCESS LIVE ===" && tail -n 120 ~/access-logs/recette.hodina.fr-ssl_log 2>/dev/null
```
