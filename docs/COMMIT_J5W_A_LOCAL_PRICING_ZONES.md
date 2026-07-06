# COMMIT — J5W-A zones tarifaires locales par secteur

Statut : **validé localement sur `develop` + validé recette + validé production / garde-fou corrigé et rejoué OK**.

## Objectif

Ajouter un découpage tarifaire local plus fin par secteur, sans remplacer les territoires techniques Petite-Terre / Grande-Terre.

## Décisions

- Ne pas remplacer `DeliveryZone`.
- Ne pas remplacer `DeliveryCommune.territory`.
- Ne pas créer `PETITE_TERRE_LOCAL`.
- Réutiliser `PT_LOCAL` pour Dzaoudzi, Labattoir et Pamandzi.
- Créer uniquement les nouvelles zones Grande-Terre par secteur :
  - `MAMOUDZOU_LOCAL`
  - `NORD_LOCAL`
  - `CENTRE_LOCAL`
  - `SUD_LOCAL`
- Garder la barge dans les liaisons logistiques `DeliveryCommuneConnection` et le garde-fou PT/GT.
- Corriger le rendu Twig du champ `deliveryPointCustomerInstructions` pour éviter son affichage perdu en bas du panier standard.

## Fichiers code concernés

- `migrations/Version20260629083000.php`
- `src/Controller/Admin/DeliveryCommuneCrudController.php`
- `src/Controller/Admin/DeliveryPricingZoneCrudController.php`
- `src/Service/DeliveryLogisticsService.php`
- `templates/cart/index.html.twig`
- `tools/assert-j5w-a-local-pricing-zones.php`

## Validation locale annoncée

- `php -l` OK sur les fichiers PHP concernés.
- `lint:twig` OK sur `templates/cart/index.html.twig`.
- `doctrine:schema:validate` OK.
- `Version20260629083000` exécutée localement.
- SQL : `PETITE_TERRE_LOCAL` absent.
- SQL : Dzaoudzi, Labattoir, Pamandzi rattachées à `PT_LOCAL`.
- EasyAdmin : zones tarifaires visibles sans doublon Petite-Terre.
- EasyAdmin : communes livrées rattachées aux zones locales.
- Panier standard fonctionnel.
- Panier point de remise fonctionnel.
- Champ instructions point de remise rangé au bon endroit.


## Validation recette actée le 29/06/2026

Tag recette : `recette-j5w-a-local-pricing-zones-20260629`.

Commit déployé : `162fcb4 merge(j5w-a): local pricing zones by sector`.

Contrôles serveur recette validés :

- `git rev-parse --short HEAD` : `162fcb4` ;
- `git describe --tags --exact-match HEAD` : `recette-j5w-a-local-pricing-zones-20260629` ;
- `git status --short` : propre ;
- `doctrine:schema:validate` : OK ;
- `doctrine:migrations:status --no-interaction` : current/latest `Version20260629083000`, 55 migrations exécutées ;
- `tools/assert-j5w-a-local-pricing-zones.php` : OK ;
- SQL zones tarifaires : `PETITE_TERRE_LOCAL` absent ;
- SQL communes : Dzaoudzi, Labattoir, Pamandzi sur `PT_LOCAL`, Grande-Terre découpée en Mamoudzou/Nord/Centre/Sud.

Tests fonctionnels recette annoncés OK : zones tarifaires, communes livrées, panier standard, absence du champ instructions perdu en bas du panier.

État au moment de la recette : avant MEP production. La production est ensuite validée sous `prod-j5w-a-local-pricing-zones-20260629` dans la section dédiée ci-dessous.

## Point technique corrigé avant commit

Le garde-fou de l’archive initiale a retourné KO parce qu’il détectait `PETITE_TERRE_LOCAL` dans la logique de nettoyage de la migration. Il a été corrigé avant commit final : il interdit une création/rattachement réel de `PETITE_TERRE_LOCAL`, tout en acceptant une suppression défensive du doublon.

## Commandes de validation avant commit

```powershell
php -l migrations\Version20260629083000.php
php -l tools\assert-j5w-a-local-pricing-zones.php
php -l src\Service\DeliveryLogisticsService.php
php bin/console lint:twig templates/cart/index.html.twig
php tools\assert-j5w-a-local-pricing-zones.php
php bin/console doctrine:schema:validate
```

## Commit conseillé

```text
feat(j5w-a): add local pricing zones by sector
```


## Validation production — 29/06/2026

Production déployée sous le tag `prod-j5w-a-local-pricing-zones-20260629` sur le commit `cea4d19 docs(j5w-a): record recette validation`.

Contrôles production validés :

- déploiement terminé avec succès via `tools/deploy-hodina-by-tag.sh --target prod` ;
- checkout tag OK sur `cea4d19` ;
- working tree propre ;
- backup environnement, uploads runtime et base de données créés ;
- assets compilés et cache prod réchauffé ;
- `doctrine:schema:validate` OK ;
- `DoctrineMigrations\Version20260629083000` current/latest ;
- garde-fou `tools/assert-j5w-a-local-pricing-zones.php` OK ;
- SQL : `PETITE_TERRE_LOCAL` absent ;
- SQL : `PT_LOCAL` conservé pour Dzaoudzi, Labattoir et Pamandzi ;
- EasyAdmin production : zones tarifaires et communes livrées conformes.

Warnings non bloquants observés :

- migration J5W-A indiquée comme exécutée avec `0 sql queries`, attendu car elle repose sur une logique DBAL idempotente ; les contrôles SQL confirment l’état réel ;
- dépréciations DoctrineBundle / EasyAdmin à traiter plus tard dans la dette technique Symfony/EasyAdmin ;
- `public/uploads/products/.gitkeep` reste suivi par Git, à maintenir comme dette runtime/uploads connue.
