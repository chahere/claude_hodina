# COMMIT — Docs planning fin juin 2026

Date : **20/06/2026**

## Objet

Reconstruction du planning de suite Hodina après validation locale J5K-v8-quater et mise en production manuelle des images catalogue optimisées.

## Changements documentaires

- Reconstruction complète de `TODO.md`.
- Priorisation du portail client après la clôture panier.
- Priorisation du portail livreur après le portail client.
- Repositionnement de l’optimisation admin après client / livreur.
- Ajout du chantier images automatique en priorité moyenne à haute, mais non bloquant immédiat grâce aux images WebP déjà mises en production.
- Ajout du suivi financier manuel comme jalon après exploitation client/livreur/admin.
- Ajout d’un planning conseillé jusqu’au 30/06/2026.

## Décision critique

L’ordre optimal pour le pilote devient :

```text
J5K final
→ J5L portail client MVP
→ J5M portail livreur MVP
→ J5N admin exploitation
→ J5O images automatiques MVP
→ J5P suivi financier manuel
```

## Images catalogue

Images de démarrage mises en production :

```text
ananas_600.webp
canne_a_sucre_600.webp
mangues_600.webp
manioc_600.webp
jackfruit_600.webp
```

Toutes les images sont en WebP, 600 x 600 et inférieures à 200 Ko.

## Non-fait volontaire

- Pas de paiement en ligne.
- Pas de portail vendeur.
- Pas d’upload AJAX images avant le MVP automatique.
- Pas de refonte admin complète.
- Pas de logistique avancée type tournée optimisée / géolocalisation temps réel.
