# README MAJ — J5M-B2 — Compactage des cartes livreur

## Objectif

Améliorer le portail `/djama` après J5M-B1 en supprimant les répétitions visuelles dans les cartes repliables.

Le livreur doit pouvoir scanner les commandes actives sur mobile sans ouvrir chaque carte.

## Décision UX

Le mode replié devient une ligne de tournée compacte :

```text
06191 · Mamoudzou · 3 pdts · 61,70 €
```

Le début du numéro de commande est volontairement tronqué en mode replié. Le numéro complet reste disponible dans le mode déplié.

## Mode déplié

Le mode déplié ne répète plus `commune`, `articles` et `total`, déjà visibles dans le résumé.

Il affiche uniquement les informations d’exécution :

```text
Test202606191

Vendeurs concernés
• Ferme combo — Dzaoudzi
• Ferme Abdallah — Labattoir
• Ferme allaoui — Mtsamboro

Actions
[Appeler]
[SMS client]
[Marquer livrée]
```

Pour une commande `PICKED_UP`, le message `Collecte / départ pas encore démarré.` reste visible.

## Décision technique

- Pas de migration Doctrine.
- Pas de nouvelle table.
- Pas de nouvelle colonne.
- Ajout de deux champs de présentation calculés côté contrôleur : `shortLabel` et `summaryLine`.
- Conservation du composant HTML natif `<details>/<summary>`.

## Fichiers modifiés

```text
src/Controller/Courier/CourierDashboardController.php
templates/courier/dashboard.html.twig
docs/TODO.md
docs/README_MAJ_J5M_B2_COMPACTAGE_CARTES_LIVREUR.md
```

## Tests recommandés

```powershell
php -l src/Controller/Courier/CourierDashboardController.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
```

Résultat attendu côté Doctrine : aucune migration générée.

## Parcours fonctionnel à tester

1. Une commande prête apparaît dans `À prendre en charge` avec une ligne compacte.
2. Le dépliage affiche le numéro complet, les vendeurs et le bouton `Prendre en charge`.
3. Après prise en charge, la commande reste compacte dans `Prises en charge / en cours`.
4. Le dépliage affiche `Démarrer la livraison`.
5. Après départ, le dépliage affiche `Marquer livrée`.
6. Après livraison, la commande apparaît dans `Livrées cette semaine` avec le même format compact.
