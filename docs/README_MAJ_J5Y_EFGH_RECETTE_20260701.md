# README MAJ — J5Y-E/F/G/H validés recette puis production

Date : 2026-07-01

## Périmètre

Cette mise à jour documente la validation recette finale, puis la validation production, des évolutions publiques J5Y :

- J5Y-E : clarification de l’URL publique `/decouvrir-hodina`.
- J5Y-F : activation limitée du Carnet Hodina avec `/carnet` et `/carnet/livraison`.
- J5Y-G : header orienté `Infos livraison` et footer public réassurance.
- J5Y-H : visuels WebP de zones livraison et simplification éditoriale de la page livraison.

## Tag recette validé

```text
recette-j5y-carnet-livraison-footer-clean-20260701
```

Commit tag :

```text
b1bbab6 chore(j5y): remove delivery guide backup template
```

## État validé recette

- Déploiement recette terminé avec succès.
- Tests navigateur recette annoncés OK.
- Déploiement production terminé avec succès.
- Tests navigateur production annoncés OK.

## Anti-régression

- `/` reste le catalogue.
- `/catalogue` redirige vers `/`.
- `/decouvrir-hodina` reste la page institutionnelle.
- `/blog` et `/blog/decouvrir-hodina` restent des redirections legacy.
- `/carnet/livraison` ne promet pas une livraison garantie.
- Le panier reste la source de vérité pour frais, dates et créneaux.
- Djama reste privé.
- Ne pas déployer le tag supersédé `recette-j5y-carnet-livraison-footer-20260701`.

## Commandes de contrôle locales

```powershell
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-c-homepage-catalogue-discover.php
php tools/assert-j5y-f-carnet-livraison.php
php tools/assert-j5y-d-header-logo-favicon.php
php tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php tools/assert-j5x-d-catalogue-search-filters.php
```

## Tag production validé

```text
prod-j5y-carnet-livraison-footer-20260701
```

Commit production :

```text
200d84b merge: document j5y recette validation
```

## Statut final

J5Y-E/F/G/H est clos côté MVP public. Ne plus modifier ce périmètre sauf bug bloquant. Les évolutions suivantes doivent partir dans un nouveau lot.
