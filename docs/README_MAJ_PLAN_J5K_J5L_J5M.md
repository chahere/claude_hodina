# README — Planning Hodina fin juin 2026 mis à jour après clôture J5L

Date : **21/06/2026**

## État actuel

J5K est déployé en production et validé.

Référence production J5K :

```text
Tag prod : prod-j5k-v8-quater-20260620
Commit : 48dae1d
Migrations prod : à jour jusqu'à Version20260619170000
Tests fonctionnels production : OK
```

J5L est validé en recette.

Référence recette J5L-B :

```text
Tag : recette-j5l-b-selecteur-adresses-20260621
Commit : 235a51f
Tests recette : OK
```

J5L-C, affichage facturation admin, est testé fonctionnellement.

## Ce que J5L a clôturé

```text
J5L-A — UX panier mobile PWA
J5L-B — Sélecteur compact d'adresses panier
J5L-C — Affichage facturation admin
```

## Décision de planning mise à jour

L'ordre optimal devient :

```text
21/06 — Clôture documentation J5L
22/06 — Tag / déploiement production J5L si recette confirmée complète
23/06 — J5M-A workflow livreur enrichi
24/06 — Tests livreur terrain
25/06 — Parcours complet client → admin → livreur
26/06 — Corrections terrain / admin exploitation
27/06 — Tests régression complets
28/06 — Gel fonctionnel
29/06 — Préparation ouverture contrôlée
30/06 — Ouverture contrôlée
```

## J5M-A — Prochain chantier

Objectif : enrichir le workflow livreur sans casser le workflow existant.

Statuts cibles :

```text
ready_for_delivery
→ picked_up
→ out_for_delivery
→ delivered
```

Affichages :

```text
picked_up          → Prise en charge par {livreur}
out_for_delivery  → En cours de livraison
```

Règle technique : ne pas stocker `Pris en charge par Chahere` comme statut. Stocker un statut stable et associer le livreur séparément.

## Chantiers à ne pas mélanger avec J5M-A

- portail client complet `/mon-compte` ;
- paiement en ligne ;
- facture PDF ;
- optimisation automatique d'images ;
- refonte profonde du carnet d'adresses ;
- refonte logistique BFS.

Ces sujets restent importants mais ne doivent pas bloquer le workflow livreur terrain.
