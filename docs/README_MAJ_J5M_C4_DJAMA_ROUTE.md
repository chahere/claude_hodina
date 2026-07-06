# README MAJ J5M-C4 — Portail livreur renommé en /djama

## Objectif

Avant la recette de J5M-C3-ter, renommer la route publique du portail livreur :

```text
/livreur → /djama
```

`Djama` porte l'idée d'assembler / rassembler en mahorais. Ce nom correspond mieux à la fonction terrain du portail : rassembler les commandes prêtes, les points de collecte vendeurs, les informations client, les notes GPS et les actions du livreur.

## Périmètre

Cette mise à jour est volontairement limitée :

- changement des routes du `CourierDashboardController` ;
- changement de la règle `access_control` Symfony ;
- changement du libellé rôle côté EasyAdmin ;
- harmonisation des docs courantes ;
- aucune migration Doctrine ;
- aucun changement de workflow métier ;
- aucun changement des noms de routes Symfony.

## Fichiers modifiés

```text
src/Controller/Courier/CourierDashboardController.php
config/packages/security.yaml
src/Controller/Admin/CustomerCrudController.php
docs/HISTORIQUE.md
docs/ARCHITECTURE.md
docs/DECISIONS.md
docs/DEPLOIEMENT_PREPROD.md
docs/ENTITIES.md
docs/ROADMAP.md
docs/TODO.md
docs/VISION.md
docs/WORKFLOWS.md
```

## Détail technique

Les routes applicatives restent nommées de la même façon :

```text
courier_dashboard
courier_order_take
courier_order_start_delivery
courier_order_address_note
courier_order_delivered
```

Cela évite de casser les templates Twig qui utilisent `path('courier_dashboard')` ou les routes d'action livreur.

## Routes attendues

```text
GET  /djama
POST /djama/commande/{id}/prendre
POST /djama/commande/{id}/demarrer-livraison
POST /djama/commande/{id}/note-adresse
POST /djama/commande/{id}/livree
```

## Sécurité attendue

```yaml
- { path: ^/djama, roles: ROLE_COURIER }
```

Un utilisateur non connecté ou sans `ROLE_COURIER` ne doit pas accéder au portail.

## Tests recette à faire

```text
1. Se connecter avec un compte livreur ROLE_COURIER.
2. Ouvrir /djama.
3. Vérifier que le menu livreur pointe vers /djama.
4. Préparer une commande côté admin.
5. Vérifier que la commande prête apparaît dans /djama.
6. Cliquer sur "Prendre en charge".
7. Cliquer sur "Démarrer la livraison".
8. Modifier une note terrain si autorisé.
9. Cliquer sur "Marquer livrée".
10. Vérifier que l'ancien chemin /livreur n'est plus l'entrée utilisée pour les tests courants.
```

## Point d'attention

Les anciens documents historiques J5B/J5C/J5D peuvent encore mentionner `/livreur`, car c'était le nom initial du portail. Depuis J5M-C4, la route courante de recette est `/djama`.
