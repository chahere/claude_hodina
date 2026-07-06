# README MAJ — J5M-C1 — Détails terrain utiles dans les cartes livreur

## Objectif

Enrichir le portail `/djama` sans casser le compactage J5M-B2.

La ligne repliée reste synthétique :

```text
06191 · Mamoudzou · 3 pdts · 61,70 €
```

Le mode déplié donne maintenant les informations opérationnelles utiles au livreur :

```text
Test202606191

Client
Chahere Abdallah

Adresse
3 rue ...
97600 Mamoudzou

GPS livraison
Ouvrir la position

Instructions client
...

Commentaire terrain
...

Vendeurs concernés
• Ferme combo — Dzaoudzi
• Ferme Abdallah — Labattoir

Actions
[Appeler]
[SMS client]
[Marquer livrée]
```

## Décisions UX

- Le mode replié reste une ligne de tournée.
- Le mode déplié affiche les détails d’exécution.
- Les informations déjà visibles dans le résumé ne sont pas répétées.
- Le lien GPS est affiché dans les détails terrain, pas dupliqué dans les actions.
- Le commentaire terrain reste modifiable depuis les commandes prêtes ou actives quand le livreur y est autorisé.

## Décisions techniques

Aucune migration Doctrine n’est nécessaire.

J5M-C1 réutilise les données existantes :

- snapshot adresse de `CustomerOrder` ;
- `deliveryAddressNotes` / instructions client ;
- `deliveryAddressCourierNotes` ;
- coordonnées GPS snapshotées ;
- route existante `courier_order_address_note`.

## Fichiers modifiés

```text
src/Controller/Courier/CourierDashboardController.php
templates/courier/dashboard.html.twig
docs/TODO.md
docs/README_MAJ_J5M_C1_DETAILS_TERRAIN_LIVREUR.md
```

## Tests recommandés

```powershell
php -l src/Controller/Courier/CourierDashboardController.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
php bin/console cache:clear
php bin/console cache:warmup
```

Résultat attendu côté Doctrine :

```text
No changes detected
```

## Parcours fonctionnel à tester

1. Ouvrir `/djama`.
2. Vérifier que les cartes restent compactes en mode replié.
3. Déplier une commande prête.
4. Vérifier l’affichage client, adresse, GPS, instructions, commentaire terrain, vendeurs.
5. Modifier le commentaire terrain et enregistrer.
6. Prendre la commande en charge.
7. Vérifier que les détails terrain restent visibles dans le bloc actif.
8. Démarrer la livraison puis marquer livrée.
9. Vérifier qu’aucune migration Doctrine n’est générée.
