# J5Y-C — Catalogue en homepage et page Découvrir Hodina

Date : 2026-06-30

## Objectif

Mettre le catalogue au centre de l’expérience client en faisant de `/` la page catalogue, puis déplacer l’ancienne homepage éditoriale vers `/blog/decouvrir-hodina`.

## Décisions

- `/` devient le catalogue public Hodina.
- `product_catalogue` et `app_home` génèrent désormais `/`.
- `/catalogue` est conservé en redirection permanente vers `/` pour ne pas casser les anciens liens.
- L’ancienne landing devient une page éditoriale enrichie : `/blog/decouvrir-hodina`.
- `/blog` redirige vers `/blog/decouvrir-hodina`.

## UX

La page Découvrir Hodina parle clairement à trois publics :

- clients ;
- vendeurs ;
- livreurs.

Elle prépare aussi le futur contenu éditorial : histoire, produits locaux, recettes vidéo.

## Hors périmètre

- Aucun changement sur le panier.
- Aucun changement sur les frais de livraison.
- Aucun changement sur le back-office EasyAdmin.
- Aucun changement sur les migrations.
- Aucun changement sur les règles J5X/J5Y-A/J5Y-B.

## Ajustement navigation

Après test visuel, le lien `Catalogue` du header public est retiré car `/` est désormais le catalogue et le logo sert déjà de retour catalogue.

## Note de statut 01/07/2026

Ce document décrit un état intermédiaire du lot. L’état opérationnel courant de J5Y est désormais la validation recette `recette-j5y-carnet-livraison-footer-clean-20260701`. Les routes publiques finales sont `/`, `/decouvrir-hodina`, `/carnet` et `/carnet/livraison`, avec `/blog*` uniquement en redirection legacy.
