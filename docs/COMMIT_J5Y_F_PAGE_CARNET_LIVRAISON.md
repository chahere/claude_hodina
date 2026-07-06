# J5Y-F — Page Carnet et page pédagogique Livraison Hodina

## Objectif

Créer une première rubrique publique `Le Carnet Hodina` sans ouvrir un blog généraliste.

Décision UX :

```text
/decouvrir-hodina = page institutionnelle publique.
/carnet = espace pédagogique Hodina.
/carnet/livraison = première page utile du Carnet, centrée sur la réassurance livraison.
Blog = terme évité côté UX publique.
```

## Changements

- Création de la route `/carnet` avec une page d’entrée qui explique le rôle du Carnet.
- Création de la route `/carnet/livraison` avec une page pédagogique sur la livraison Hodina à Mayotte.
- La page `/carnet` liste :
  - `Livraison Hodina` avec lien actif ;
  - `Fruits, légumes et saisons` en contenu à venir, sans lien ;
  - `Nos vendeurs et producteurs partenaires` en contenu à venir, sans lien.
- La page livraison couvre : fonctionnement, communes concernées, jours indicatifs, domicile/points de remise, délais variables, médias terrain à intégrer, vérification au panier.
- Les contenus rappellent que les jours, frais et créneaux exacts sont confirmés au panier, afin d’éviter une promesse logistique figée.
- Aucune exposition du portail livreur privé Djama.
- Ajout d’un assert dédié : `tools/assert-j5y-f-carnet-livraison.php`.

## Anti-régression

- `/` reste le catalogue.
- `/decouvrir-hodina` reste la page institutionnelle.
- `/blog` et `/blog/decouvrir-hodina` restent des redirections legacy.
- Le Carnet n’introduit pas de paiement en ligne, de livraison garantie ou de promesse rigide non validée par le panier.
- Les contenus futurs du Carnet restent affichés comme `À venir` tant qu’ils ne sont pas développés.

## Statut recette 01/07/2026

J5Y-F a été étendu par les ajustements J5Y-G/H : header `Infos livraison`, footer réassurance compact, images WebP de zones, simplification rédactionnelle de la page livraison.

Tag recette validé :

```text
recette-j5y-carnet-livraison-footer-clean-20260701
```

Commit recette final :

```text
b1bbab6 chore(j5y): remove delivery guide backup template
```

Production validée ensuite le 01/07/2026 sous :

```text
prod-j5y-carnet-livraison-footer-20260701
```

Commit production :

```text
200d84b merge: document j5y recette validation
```
