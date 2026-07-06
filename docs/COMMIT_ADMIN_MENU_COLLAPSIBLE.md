# COMMIT — Admin mobile : menu gauche repliable

## Objectif

Rendre le menu EasyAdmin utilisable sur mobile lorsque les sections deviennent nombreuses.

Problème constaté : la section `Réglages` / `Préouverture` se retrouvait trop bas dans le menu et devenait difficile d'accès.

## Solution

Ajout d'un comportement JavaScript dans :

```text
assets/admin.js
```

Sections repliables :

```text
Logistique
Catalogue
Commandes
Utilisateurs
Pilote
Réglages
```

## Fonctionnement

- Le menu latéral devient scrollable.
- Les sections ont un bouton avec flèche.
- L'état est mémorisé dans `localStorage`.
- Sur mobile, les sections non actives sont repliées par défaut.
- La section active reste ouverte.

## Correctif Utilisateurs

Cas rencontré : section `Utilisateurs` + item `Utilisateurs`.

Le premier patch détectait les deux comme sections. Le correctif ignore les vrais liens `<a href>` pour éviter de transformer une entrée en faux sous-menu.

## Validation

- Menu repliable / dépliable en dev.
- Section `Réglages` accessible.
- Section `Préouverture` accessible.
- Item `Utilisateurs` cliquable.
- Recette OK.
- Production OK via tag `j5g-b4-20260618-v11`.

---

# Mise à jour 24/06/2026 — sections métier après J5Q-A

Le menu repliable EasyAdmin a été adapté à la nouvelle organisation métier.

Anciennes sections reconnues :

```text
Logistique
Catalogue
Commandes
Utilisateurs
Pilote
Réglages
```

Nouvelles sections reconnues :

```text
Logistique
Catalogue
Commandes
Clients
Vendeurs
Livreurs
Logs
Réglages
```

Pourquoi : `Livreurs` et `Vendeurs` doivent être des sections autonomes. Si `assets/admin.js` ne connaît pas une section, elle est absorbée visuellement par la section précédente sur mobile.

Validation :

```text
Clients → Clients
Vendeurs → Vendeurs
Livreurs → Livreurs / Rémunérations livreurs / Lignes rémunération
Logs → SMS, e-mails, abonnés, adhésions
```
