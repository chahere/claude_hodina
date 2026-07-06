# J5Y-D-ter — Favicon transparent Hodina

Date : 2026-06-30

## Contexte

Après J5Y-D-bis, le favicon était visible mais trop marqué par un fond blanc carré dans l’onglet du navigateur.

## Décision UI/UX

Pour un favicon, il vaut mieux privilégier un symbole compact plutôt que le logo complet. Le texte Hodina est illisible à très petite taille.

J5Y-D-ter remplace donc le favicon par une version symbole transparente, avec un léger contour clair pour rester visible sur les onglets sombres sans créer de carré blanc.

## Périmètre

Modifié :

- `templates/base.html.twig`
- `public/favicon.ico`
- `public/images/favicon-16x16.png`
- `public/images/favicon-32x32.png`
- `public/images/apple-touch-icon.png`
- `tools/assert-j5y-d-header-logo-favicon.php`

Non modifié :

- catalogue ;
- panier ;
- checkout ;
- back-office ;
- logo header ;
- livraison.

## Tests navigateur

Tester en navigation privée :

- `/` affiche toujours le catalogue ;
- le logo header reste lisible ;
- le favicon n’a plus de carré blanc visible ;
- l’onglet affiche un symbole Hodina plus propre.

## Statut après test visuel

Cette version est une amélioration par rapport au carré blanc, mais le choix favicon reste ouvert. Les fichiers `favicon-16x16.png.old` ou `favicon-32x32.png.old`, s’ils existent localement, sont des artefacts temporaires et ne doivent pas être committés.

## Note de statut 01/07/2026

Ce document décrit un état intermédiaire du lot. L’état opérationnel courant de J5Y est désormais la validation recette `recette-j5y-carnet-livraison-footer-clean-20260701`. Les routes publiques finales sont `/`, `/decouvrir-hodina`, `/carnet` et `/carnet/livraison`, avec `/blog*` uniquement en redirection legacy.
