---
name: hodina-core
description: Skill principal pour développer Hodina avec Claude Code. Utiliser pour Symfony, Twig, EasyAdmin, Doctrine, panier, checkout, livraison, commandes, vendeurs, portail livreur, documentation, review et déploiement.
---

# Hodina Core Skill

Tu travailles sur Hodina, marketplace locale mahoraise développée en Symfony / Twig / EasyAdmin / MariaDB.

Ce projet est sensible. Tu dois aider à accélérer le développement sans casser le travail déjà validé.

## Contexte Hodina

Hodina est une marketplace locale à Mayotte.

Stack principale :

- Symfony ;
- Twig ;
- EasyAdmin ;
- Doctrine ;
- MariaDB / MySQL ;
- mobile-first ;
- hébergement o2switch ;
- environnements local, recette et production séparés.

Le MVP ne doit pas être réécrit inutilement. Toute modification doit être ciblée, testable et compatible avec les lots déjà validés.

## Règles absolues

1. Lire le code existant avant de modifier.
2. Ne pas inventer une entité, un champ, une route ou un service sans vérifier l’existant.
3. Ne pas réécrire une fonctionnalité validée si une correction ciblée suffit.
4. Ne pas déplacer une règle métier sans vérifier tous ses usages.
5. Ne pas mettre de logique métier complexe dans Twig.
6. Ne pas dupliquer un calcul métier dans plusieurs endroits.
7. Ne jamais committer `.env.local`, dumps SQL, secrets, tokens, fichiers `vendor`, fichiers `var`, ou fichiers générés inutiles.
8. Toujours distinguer local, recette et production.
9. Ne jamais proposer de push ou de modification directe depuis recette/prod.
10. Toujours terminer par les tests à lancer et les risques de régression.

## Architecture Symfony attendue

- Controllers fins.
- Services pour les règles métier.
- Repositories pour les requêtes.
- Twig pour l’affichage.
- EasyAdmin pour l’administration, sans logique métier centrale.
- Doctrine migrations cohérentes avec les entités.
- Nouvelle migration pour tout changement de schéma.
- Ne jamais modifier une migration déjà jouée en recette ou production sans demande explicite.

## Zones Hodina sensibles

Être très vigilant dès qu’une demande touche à :

- panier ;
- checkout ;
- commande ;
- `CustomerOrder` ;
- `OrderItem` ;
- `Product` ;
- `Seller` ;
- `Address` ;
- `DeliveryCommune` ;
- `DeliveryZone` ;
- `DeliveryLogisticsService` ;
- frais de livraison ;
- snapshot logistique commande ;
- portail livreur `/djama` ;
- EasyAdmin ;
- migrations ;
- scripts de déploiement ;
- emails / SMS ;
- uploads images produits.

## Livraison Hodina

Les règles de livraison sont critiques.

Principes à préserver :

1. La commune livrée est la source de vérité côté client.
2. La zone doit être déduite, pas inventée librement.
3. Les frais de livraison doivent rester cohérents avec les règles existantes.
4. Les frais et snapshots d’une commande validée ne doivent pas être recalculés sans décision explicite.
5. Les règles PT/GT, barge et multi-vendeurs doivent rester centralisées.
6. Les affichages client, admin et livreur doivent refléter les mêmes données métier.
7. Le portail livreur `/djama` doit rester opérationnel.

## EasyAdmin

Pour l’administration :

- ne pas mélanger logique métier et affichage admin ;
- ne pas rendre modifiable une donnée verrouillée sans le signaler ;
- préserver les actions métier existantes ;
- afficher les informations utiles au terrain ;
- vérifier les impacts sur commandes, vendeurs, produits et livraison.

## Twig / UX mobile

Hodina est mobile-first.

Règles UX :

- parcours simple ;
- boutons principaux visibles ;
- informations de livraison claires ;
- messages compréhensibles ;
- pas de refonte inutile ;
- respecter les layouts et blocs existants ;
- ne pas casser desktop en corrigeant mobile ;
- ne pas masquer les erreurs de validation Symfony.

## Import produits / images

Pour les imports catalogue :

- vérifier le schéma réel avant SQL ;
- ne pas activer automatiquement les produits hors saison ;
- lister les produits sans image ;
- images en `.webp` légères ;
- noms de fichiers stables et slugifiés ;
- ne pas prétendre qu’une image existe si elle n’a pas été vérifiée ;
- fournir manifest, SQL et README si nécessaire ;
- ne jamais inclure de données clients ou secrets dans une livraison.

## Documentation Hodina

Ne jamais inventer l’état du projet.

Distinguer clairement :

- validé localement ;
- validé recette ;
- validé production ;
- prévu ;
- repoussé ;
- dette technique ;
- hypothèse à vérifier.

Rôles documentaires :

- `VISION.md` : vision produit.
- `ARCHITECTURE.md` : composants techniques.
- `DECISIONS.md` : décisions métier/techniques.
- `ENTITIES.md` : entités et relations.
- `WORKFLOWS.md` : parcours client/admin/livreur.
- `HISTORIQUE.md` : jalons chronologiques.
- `ROADMAP.md` : suite prévue.
- `TODO.md` : actions concrètes.
- `DEPLOIEMENT_PREPROD.md` : procédures recette/prod.

## Review avant commit

Avant de proposer un commit, vérifier :

```bash
git status
git diff --stat
git diff --name-status
```

## Format de livraison des commandes (local Windows / PowerShell)

Quand une commande doit être lancée depuis le projet, la fournir dans un **bloc unique copiable** commençant par `cd`, une commande par ligne :

```powershell
cd D:\hodina\claude_hodina
git pull origin <branche>
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Règles :

- Toujours démarrer le bloc par `cd D:\hodina\claude_hodina` (chemin local du projet).
- Après tout `git pull`, changement de code, de config ou de réglage compilé, **inclure la régénération du cache** en mode mémoire-safe : `cache:clear --no-warmup` puis `cache:warmup`. La limite PHP 128 Mo fait planter le `cache:clear` standard (OOM Twig). Noter que `cache:cache` n'existe pas.
- Préciser le rôle de chaque commande si ce n'est pas évident.
- Pour lancer/relancer le serveur : `symfony server:start --no-tls` (jamais `php -S … public/index.php`).
