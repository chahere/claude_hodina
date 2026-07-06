# README MAJ — J5W-A production validée

Cette mise à jour documentaire acte la production de J5W-A.

## Référence

- Tag production : `prod-j5w-a-local-pricing-zones-20260629`
- Commit production : `cea4d19 docs(j5w-a): record recette validation`
- Commit complet : `cea4d19b0ec8b91e836666eefdaa3ba4f87fbaed`
- Avant MEP : `d5466fe`
- Migration : `DoctrineMigrations\Version20260629083000`

## Résultat

J5W-A est validé en production.

Contrôles techniques :

- déploiement terminé avec succès ;
- working tree propre ;
- backups environnement, uploads runtime et base créés ;
- assets compilés ;
- cache prod réchauffé ;
- `doctrine:schema:validate` OK ;
- migrations current/latest sur `Version20260629083000` ;
- garde-fou `tools/assert-j5w-a-local-pricing-zones.php` OK.

Contrôles métier/logistiques :

- `PETITE_TERRE_LOCAL` absent ;
- `PT_LOCAL` conservé pour Dzaoudzi, Labattoir et Pamandzi ;
- Grande-Terre découpée en `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL` ;
- `DeliveryCommune.territory` conserve `PT` / `GT` ;
- EasyAdmin zones tarifaires OK ;
- EasyAdmin communes livrées OK ;
- panier standard OK, sans champ point de remise perdu.

## Warnings non bloquants

- Migration affichée comme exécutée avec `0 sql queries` : non bloquant, les requêtes DBAL idempotentes ne sont pas comptabilisées par Doctrine comme `addSql`, et les contrôles SQL confirment l’état cible.
- Dépréciations DoctrineBundle / EasyAdmin à traiter dans la dette technique Symfony/EasyAdmin.
- `public/uploads/products/.gitkeep` reste suivi par Git, dette runtime/uploads connue.

## Règle anti-régression

J5W-A ne change pas la source de vérité de la barge : elle reste portée par `DeliveryCommuneConnection` et par les territoires techniques PT/GT. Les zones tarifaires locales ne doivent pas devenir des sous-zones opérationnelles livreur.
