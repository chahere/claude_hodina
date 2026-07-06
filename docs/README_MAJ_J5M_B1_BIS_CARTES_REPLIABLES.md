# J5M-B1-bis — Cartes repliables portail livreur

Date : 21/06/2026  
Statut : patch préparé — tests locaux / recette à faire

---

## Objectif

Améliorer la vue d’ensemble du portail livreur `/djama` sans modifier le workflow métier et sans migration Doctrine.

Le livreur doit pouvoir scanner rapidement les commandes par secteur. En mode replié, une carte affiche uniquement la commune cliente.

---

## Changements UX

Avant :

```text
Chaque carte était ouverte en permanence.
Le livreur voyait immédiatement commande, commune, vendeurs, articles, total et actions.
```

Après :

```text
Chaque carte est repliable / dépliable.
En mode replié : Commune Client uniquement.
En mode déplié : détails opérationnels et boutons d’action.
```

---

## Libellé corrigé

Le libellé :

```text
Commune
```

devient :

```text
Commune Client
```

Cette formulation évite l’ambiguïté avec les communes vendeurs, qui seront centrales dans J5M-C.

---

## Décision technique

Utilisation du HTML natif :

```html
<details>
  <summary>Commune Client</summary>
</details>
```

Avantages :

- pas de JavaScript custom ;
- comportement mobile natif ;
- accessible au clavier ;
- aucun impact BDD ;
- aucun impact workflow ;
- aucune migration Doctrine.

---

## Fichiers modifiés

```text
templates/courier/dashboard.html.twig
docs/TODO.md
docs/README_MAJ_J5M_B1_PORTAIL_LIVREUR_TERRAIN.md
docs/README_MAJ_J5M_B1_BIS_CARTES_REPLIABLES.md
```

---

## Tests recommandés

```powershell
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
```

Résultat Doctrine attendu :

```text
No changes detected
```

---

## Parcours à tester

```text
1. Aller sur /djama.
2. Vérifier que les cartes sont repliées par défaut.
3. Vérifier qu’en mode replié seule la commune cliente est visible.
4. Déplier une commande à prendre en charge.
5. Vérifier que le bouton Prendre en charge reste fonctionnel.
6. Prendre une commande en charge.
7. Déplier la carte active.
8. Vérifier que Démarrer la livraison reste fonctionnel.
9. Marquer livrée.
10. Vérifier que la commande apparaît dans Livrées cette semaine avec carte repliable.
```

---

## Décision pour la suite

J5M-C ajoutera les étapes de récupération vendeurs dans une vraie table dédiée. J5M-B1-bis ne prépare que l’UX de lecture et ne crée aucune donnée de collecte.
