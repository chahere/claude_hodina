# J5S-B-quater-quinquies — Référence commande unique robuste

## Contexte

Pendant les tests du checkout point de remise, la soumission arrivait bien au serveur mais échouait sur une contrainte unique `customer_order.order_reference`.

Erreur observée :

```text
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'Test202606283' for key 'UNIQ_3B1CE6A3122432EB'
```

Le champ concerné est `customer_order.order_reference`.

## Cause

Le générateur de référence utilisait un comptage des commandes existantes du jour (`COUNT`) puis ajoutait `+1`.

Cette méthode est fragile : si une référence existe avec un numéro supérieur au nombre de lignes comptées, ou si une commande a été supprimée / rejouée / créée dans un ordre particulier, le générateur peut retomber sur une référence déjà existante.

## Correction

`OrderReferenceGenerator` :

- utilise désormais `MAX(dailyOrderNumber) + 1` au lieu de `COUNT + 1` ;
- vérifie explicitement que la référence exacte n’existe pas ;
- incrémente le numéro si une collision existe encore ;
- conserve l’index unique en base comme garde-fou ;
- ne modifie pas le format actuel de référence.

## Périmètre

- Aucun changement de schéma.
- Aucun changement checkout / panier.
- Aucun changement e-mail.
- Aucun changement Djama.
- Aucun changement calcul de frais.

## Tests recommandés

```powershell
php -l src/Service/OrderReferenceGenerator.php
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Tests fonctionnels :

- refaire deux commandes de test avec les mêmes prénom/nom/téléphone/e-mail ;
- vérifier que la seconde commande ne plante plus sur `order_reference` ;
- vérifier en admin que les références sont distinctes.
