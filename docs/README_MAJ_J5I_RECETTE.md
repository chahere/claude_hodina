# README mise à jour — J5I recette 13/06/2026

## Pourquoi ce fichier existe

Ce fichier résume l'historique exact de la livraison J5I pour qu'un développeur débutant comprenne ce qui s'est passé sans devoir relire toute la conversation.

## Résumé court

J5I ajoute une préouverture commerciale : Hodina peut afficher un compte à rebours, récupérer les e-mails des visiteurs et bloquer les paniers/commandes avant l'ouverture officielle.

## Chronologie

```text
1. Développement local J5I.
2. Migration locale.
3. Test local de la bannière et de la capture e-mail.
4. Commit local 5bf3e0e.
5. Push GitHub branche pilot/j5i-preouverture-countdown.
6. Correction .htaccess recette pour Basic Auth / HTTPS / 401.shtml.
7. Checkout branche J5I sur recette.
8. Tentative migration recette.
9. Incident ordre migration.
10. Contournement recette documenté.
11. Schema Doctrine OK.
12. Injection paramètres dev en recette.
13. Validation fonctionnelle recette.
```

## Ce qui est validé

```text
J5I local : OK
J5I recette : OK
Basic Auth recette : OK
HTTPS recette : OK
Réglages Hodina recette : OK
```

## Ce qui reste fragile

L'ordre de migration `Version20260613094055` / `Version20260613110000` doit être corrigé avant production.

## Commande de vérification utile

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console dbal:run-sql "SELECT setting_key, value FROM hodina_setting ORDER BY id"
php bin/console dbal:run-sql "SELECT id, email, created_at FROM launch_subscriber ORDER BY id DESC"
```

---

# Note postérieure — J5I remplacé par J5J

Après validation de J5I, le besoin a évolué : le même système devait aussi servir aux maintenances de production et aux tests de production par des utilisateurs autorisés.

J5J remplace donc l'usage opérationnel de J5I avec des paramètres `commerce_*` et le rôle `ROLE_COMMERCE_TESTER`.

J5I reste documenté pour l'historique, mais un développeur doit désormais regarder `COMMIT_J5J.md` et `README_MAJ_J5J_COMMERCE_MODE.md` pour comprendre le fonctionnement actuel.
