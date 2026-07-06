# README MAJ PROD — J5G-E1 → J5G-E2-bis-A

## Résumé

Le jalon J5G-E1 à J5G-E2-bis-A est maintenant validé en production.

Tag de référence :

```text
j5g-e1-e2bis-prod
```

## Ce qui est désormais en production

```text
Commune livrée = source de vérité
Code postal = prérempli depuis DeliveryCommune
Zone = déduite serveur, non modifiable par le client
Frais = recalculés quand la commune change
Total = verrouillé avant validation
Tarif = zone locale PT / GT + barge fixe si détectée
Panier = écran livraison + validation pendant paiement manuel
Checkout = futur paiement / facturation
Confirmation = récapitulatif complet
```

## Commandes production validées

```bash
cd ~/hodina.fr
git fetch origin
git checkout pilot/j5j-commerce-mode-role-tester
git pull origin pilot/j5j-commerce-mode-role-tester
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## Points importants pour la suite

- Ne pas réintroduire un choix manuel de zone côté client.
- Ne pas refaire le checkout comme écran livraison pendant le pilote.
- Ne pas recréer `DeliveryCommune`, `DeliveryCommuneMatcherService` ou `DeliveryLogisticsService`.
- Utiliser J5G-B4 pour enrichir le calcul de chemin, pas pour refaire la saisie adresse.
- Conserver la règle `localPricingZone + coût fixe barge` tant que BFS / coûts terrestres ne sont pas finalisés.
