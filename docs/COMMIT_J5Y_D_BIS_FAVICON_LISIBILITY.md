# J5Y-D-bis — Favicon Hodina plus lisible

## Objectif

Rendre le favicon Hodina plus visible dans les onglets navigateur, surtout en thème sombre.

## Décision UX

Le logo complet Hodina est trop détaillé pour un favicon. À 16 px, le texte devient illisible et l'hippocampe vertical paraît trop fin.

J5Y-D-bis utilise donc une icône symbolique plus lisible : la tête / partie haute de l'hippocampe, agrandie dans un carré blanc.

## Changements

- Ajout d'un `favicon-16x16.png`.
- Remplacement du `favicon-32x32.png` par une version plus lisible.
- Remplacement du `favicon.ico` avec variantes 16/32/48 px.
- Remplacement du `apple-touch-icon.png` avec la même logique visuelle.
- Ajout d'un cache-busting `?v=j5y-d-bis` dans les liens favicon.

## Hors périmètre

- Pas de modification du header.
- Pas de modification du catalogue.
- Pas de modification panier / checkout.
- Pas de modification back-office.

## Statut après test visuel

Cette variante avec carré blanc a été rejetée visuellement. Elle est conservée comme historique de tentative, mais ne doit pas être considérée comme favicon final.
