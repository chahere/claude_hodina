# COMMIT J5M-C4 — Renommage du portail livreur en /djama

## Résumé

Renommage de la route publique du portail livreur :

```text
/livreur → /djama
```

Le changement est effectué avant recette afin que les tests terrain et la documentation courante utilisent directement le nom final.

## Décision produit

Le nom `/djama` est choisi pour porter une identité mahoraise. Dans le contexte Hodina, il exprime l'idée d'assembler / rassembler : commandes, collectes vendeurs, adresses, GPS, notes terrain et actions de livraison.

## Changements techniques

- `CourierDashboardController` expose désormais le dashboard et les actions POST sous `/djama`.
- `security.yaml` protège désormais `^/djama` par `ROLE_COURIER`.
- Le libellé EasyAdmin du rôle livreur indique `/djama`.
- Les noms de routes Symfony restent inchangés pour limiter le risque de régression.
- Aucune migration Doctrine.

## Validation locale

```text
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/Admin/CustomerCrudController.php
```

Résultat attendu : aucune erreur de syntaxe.

## Validation recette attendue

Tester le parcours complet sur `/djama` :

```text
READY_FOR_PICKUP → PICKED_UP → OUT_FOR_DELIVERY → DELIVERED
```

Vérifier aussi l'accès refusé pour un utilisateur sans `ROLE_COURIER`.
