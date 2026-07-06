# J5Y-E — Clarification URL publique Découvrir Hodina

## Objectif

Clarifier l’expérience publique autour de la page de présentation Hodina.

Décision :

```text
Découvrir Hodina = page institutionnelle publique.
Carnet Hodina = rubrique éditoriale future, non activée dans le MVP.
Blog = terme évité côté UX publique.
```

## Changements

- Route canonique : `/decouvrir-hodina`.
- Anciennes routes `/blog/decouvrir-hodina` et `/blog` conservées en redirections permanentes.
- Template déplacé de `templates/blog/decouvrir_hodina.html.twig` vers `templates/pages/decouvrir_hodina.html.twig` pour ne pas maintenir une confusion métier dans l’arborescence.
- Section publique `Le Carnet Hodina` remplacée par une section `À venir`, qui présente l’espace éditorial comme futur et non comme rubrique active du MVP.
- Assert J5Y-C réaligné pour sécuriser la nouvelle route, la redirection legacy et l’absence de vocabulaire public `Blog` / `Le Carnet Hodina` sur la page Découvrir.

## Anti-régression

- `/` reste le catalogue.
- `/catalogue` reste une redirection vers `/`.
- Le lien header `Découvrir Hodina` pointe toujours vers la route Symfony `app_discover_hodina`.
- Djama reste privé et non exposé sur la page publique.
- Aucun changement sur panier, checkout, point de remise, logistique ou paiement.

## Statut après J5Y-F/G/H

Cette décision reste valide, mais la phrase initiale `Carnet Hodina = rubrique éditoriale future, non activée dans le MVP` est partiellement supersédée : le Carnet est désormais activé de manière limitée sous `/carnet`, avec une seule page réelle `/carnet/livraison`.

Le principe reste inchangé : le Carnet n’est pas un blog généraliste et le terme `Blog` reste évité côté UX publique.

Validation recette : `recette-j5y-carnet-livraison-footer-clean-20260701`.
