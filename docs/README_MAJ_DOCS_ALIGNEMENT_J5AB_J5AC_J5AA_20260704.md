# README mise à jour — Alignement docs J5AB/J5AC et cadrage J5AA

Date : 2026-07-04
Statut : documentation uniquement, pas de code applicatif.

## Objectif

Aligner la documentation avec le code actuel avant de démarrer J5AA.

## Incohérences corrigées

- Le bloc `Prochaine priorité P1 — Portail client MVP` dans `TODO.md` était obsolète.
- `/mon-compte` n’est plus une redirection MVP : c’est un hub compte client depuis J5AC.
- `/mon-compte/profil` est fait, contrairement à l’ancien TODO.
- `/mon-compte/adresses` reste non fait : page autonome absente du code.
- Le carnet `Address` utilisé par panier/checkout ne doit pas être confondu avec une future page client d’adresses.

## Cadrage J5AA complété

J5AA ne doit pas seulement ajouter `AddressLocality`. Le lot doit aussi respecter la cohérence code postal / commune avec les données seedées.

Règles ajoutées :

```text
Code postal = aide de sélection.
Localité = précision terrain.
DeliveryCommune = source logistique et tarifaire.
```

Le code actuel possède déjà `DeliveryCommune.postalCode` et une validation serveur commune/code postal/zone. J5AA devra vérifier si ce référentiel suffit avant d’envisager une nouvelle entité de code postal.

## Hors périmètre

- pas de migration ;
- pas de déploiement ;
- pas de changement de code applicatif ;
- pas de refonte panier / checkout ;
- pas de création de `/mon-compte/adresses` ;
- pas de calcul tarifaire par localité ou code postal.

## Fichiers centraux mis à jour

- `TODO.md` ;
- `ARCHITECTURE.md` ;
- `DECISIONS.md` ;
- `ENTITIES.md` ;
- `WORKFLOWS.md` ;
- `ROADMAP.md` ;
- `PILOT_STATUS_DETAILED.md` ;
- `DEPLOIEMENT_PREPROD.md` ;
- `VISION.md` ;
- `HISTORIQUE.md`.
