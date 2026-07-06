# Mise à jour J5Y-D — Logo header et favicon

Date : 2026-06-30

## Résumé

Cette mise à jour améliore la lisibilité de la marque Hodina dans l'en-tête public.

Avant :

- le header utilisait `logo_hodina_mobile.png`, une image carrée de 1024x1024 px ;
- cette image était compressée dans un carré de 38x38 px ;
- le mot “Hodina” devenait trop petit à côté des liens de navigation.

Après :

- le header utilise `logo_hodina_header.png`, une variante horizontale recadrée ;
- le logo est affiché en hauteur 52 px sur desktop et 46 px sur mobile étroit ;
- le favicon Hodina est branché dans le `<head>`.

## Périmètre

Inclus :

- identité visuelle du header ;
- favicon navigateur ;
- icône Apple touch ;
- assert de garde-fou.

Exclu :

- routage homepage/catalogue ;
- catalogue ;
- panier ;
- checkout ;
- back-office ;
- logique métier.

## Points UX

Le logo reste volontairement contenu pour éviter d'écraser la navigation mobile.
L'objectif est que le texte du logo soit lisible et cohérent avec la taille des liens, sans transformer le header en bannière.

## Tests navigateur

Vérifier :

1. Le logo est plus lisible dans le header.
2. Le header ne prend pas trop de hauteur.
3. Le logo reste propre en mobile.
4. Les liens et icônes du header restent alignés.
5. Le favicon apparaît dans l'onglet après refresh dur ou navigation privée.
