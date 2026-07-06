# README MAJ — J5Y validation production

Date : 2026-07-01

## Périmètre

Cette mise à jour documentaire acte la validation production du lot public J5Y : points de remise UX, catalogue en homepage, Découvrir Hodina, favicon/logo, Carnet, page livraison, header `Infos livraison`, footer réassurance et images WebP de zones.

## Tag production validé

```text
prod-j5y-carnet-livraison-footer-20260701
```

Commit :

```text
200d84b merge: document j5y recette validation
```

## Résultat MEP production

Déploiement production réussi sur `~/hodina.fr` avec `PUBLIC_URL=https://hodina.fr`.

Contrôles automatiques confirmés : checkout tag, working tree propre, backup DB, uploads restaurés, assets compilés, cache prod warmup, Doctrine schema OK, migrations à jour, cron Messenger prod présent, URL publique `https://hodina.fr` HTTP 200.

Tests navigateur production annoncés validés.

## Anti-régression

- `/` reste le catalogue public.
- `/catalogue` redirige vers `/`.
- `/decouvrir-hodina` reste la page institutionnelle canonique.
- `/blog` et `/blog/decouvrir-hodina` restent des redirections legacy.
- `/carnet` reste l’entrée pédagogique.
- `/carnet/livraison` reste une page de réassurance indicative.
- Le panier reste la source de vérité pour frais, dates et créneaux.
- Djama reste privé.
- Le header public priorise `Infos livraison`.
- Le footer reste compact.

## Dettes non bloquantes à suivre

- `public/uploads/products/.gitkeep` suivi par Git.
- Symfony 8.0.5 non-LTS.
- Dépréciations Doctrine/EasyAdmin.
- Migrations Doctrine avec warnings `implicit commit`.
- Wording du script de MEP qui parle encore de “recette” dans les tests navigateur restants même en prod.

## Statut final

J5Y est clos. Le prochain chantier doit partir d’un nouveau lot distinct.
