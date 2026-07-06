# README — Mise à jour planning fin juin 2026 après clôture J5K-v8-quater

Date : **20/06/2026**

## Contexte

J5K-v8-quater a été validé en recette. La clôture a inclus à la fois le fonctionnel panier/adresses/facturation et la sécurisation DevOps autour de `vendor/` et du script de déploiement.

Référence finale recette :

```text
devops-deploy-composer-before-console-v2
Commit : 48dae1d
```

## Ce qui a été clôturé

- Panier / livraison / facturation J5K-v8-quater.
- 12 tests fonctionnels recette.
- `vendor/` retiré du suivi Git.
- Composer lancé avant le premier `bin/console` si `vendor/autoload.php` est absent.
- Script de déploiement validé de bout en bout.
- Cron Messenger recette corrigé.

## Décision principale de planning

L’ordre final avant ouverture est :

```text
20/06 — DevOps script + docs
21/06 — Prod J5K-v8-quater
22/06 — J5L-A UX panier mobile PWA
23/06 — Tests panier mobile
24/06 — J5L portail client MVP
25/06 — Tests portail client
26/06 — J5M portail livreur MVP
27/06 — Test parcours complet
28/06 — Admin exploitation + finance manuelle
29/06 — Gel fonctionnel
30/06 — Ouverture contrôlée
```

## Correction importante

J5L-A est ajouté avant le portail client.

Raison : le panier est l’écran de conversion. Il doit être plus mobile-first avant de construire le portail client.

## Fichiers mis à jour

- `docs/TODO.md`
- `docs/ROADMAP.md`
- `docs/DECISIONS.md`
- `docs/PILOT_STATUS_DETAILED.md`
- `docs/DEPLOIEMENT_PREPROD.md`
- `docs/README_MAJ_PLAN_J5K_J5L_J5M.md`
- `docs/README_MAJ_PLANNING_FIN_JUIN_20260620.md`
- `docs/README_MAJ_J5K_V8_QUATER_RECETTE_VALIDEE_20260620.md`
- `docs/COMMIT_J5K_V8_QUATER_RECETTE_VALIDEE_20260620.md`

## Point de vigilance

Ne plus déployer un tag J5K intermédiaire. La référence recette validée est `devops-deploy-composer-before-console-v2`. Pour la production, créer ou choisir un tag propre basé sur cette référence.
