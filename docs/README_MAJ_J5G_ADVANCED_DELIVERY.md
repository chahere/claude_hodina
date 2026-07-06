# Mise à jour documentation Hodina — revue complète J5G avancé

## Objet

Cette archive corrige l'appauvrissement constaté dans les documents de référence présents dans le projet.

Le fichier `ARCHITECTURE.md` avait été identifié comme incomplet par rapport aux décisions prises. La même vérification a été appliquée à l'ensemble des documents fournis.

## Documents revus

```text
ARCHITECTURE.md
DECISIONS.md
WORKFLOWS.md
ENTITIES.md
TODO.md
ROADMAP.md
VISION.md
DEPLOIEMENT_PREPROD.md
PATCH_GUIDELINES.md
COMMIT_J5A.md
COMMIT_J5B.md
COMMIT_J5C.md
COMMIT_J5D.md
COMMIT_J5E.md
COMMIT_J5F_A.md
COMMIT_J5F_B.md
COMMIT_J5F_PREP.md
README_MAJ_J5E.md
README_MAJ_J5F_AB.md
README_MAJ_J5F_BARGE.md
```

## Nouveaux fichiers ajoutés

```text
COMMIT_J5G_A.md
COMMIT_J5G_B_PREP.md
README_MAJ_J5G_ADVANCED_DELIVERY.md
```

## Ce qui a été réintégré

- J5G-A : aperçu logistique panier.
- Signature logistique par adresse + vendeurs uniques.
- Recalcul seulement si le périmètre vendeur change.
- Barge détectée mais frais inchangés si les tarifs de test sont identiques.
- Nouvelle formule : frais local + communes traversées + barge aller-retour.
- Rémunération livreur progressive selon les communes traversées.
- Utilisation des communes voisines comme graphe.
- Algorithme BFS pour le plus court chemin.
- Réglages Hodina nécessaires.
- Données à figer plus tard dans `CustomerOrder`.
- Découpage J5G-B / J5G-C / J5G-D / J5G-E.

## Règle métier centrale conservée

```text
requiresBarge = clientTerritory !== sellerTerritory
```

La barge ne dépend pas :

```text
du nombre de communes traversées
de la distance supposée
du fait d'être voisin ou non
```

## Commande d'intégration conseillée

```powershell
cd E:\hodina\hodina.fr

Expand-Archive -Path .\hodina_docs_reference_revue_complete_J5G.zip -DestinationPath .\docs_j5g_review_tmp -Force
Copy-Item .\docs_j5g_review_tmp\*.md .\docs\ -Force
Remove-Item .\docs_j5g_review_tmp -Recurse -Force

git status
git diff --stat
git add docs
git commit -m "docs(logistics): enrich J5G advanced delivery references"
git push
```

---

# Complément J5G-E0 — Snapshot adresse commande

Ce complément précise que le snapshot adresse a été traité avant le snapshot logistique complet.

## Ce qui est fait

- Adresse livraison figée dans `CustomerOrder`.
- Adresse facturation figée dans `CustomerOrder`.
- Suppression des adresses client tolérée par les commandes.
- Affichages principaux branchés sur les snapshots.

## Ce qui reste dans J5G avancé

- Frais détaillés.
- Rémunération livreur.
- Marge livraison.
- Route logistique.
- Hops terrain / barge.
- Explication avancée panier.

---

# Complément J5G-E1 — Avant B4, fiabiliser la saisie adresse

J5G-B4 doit stabiliser le trajet logistique réel. Avant cela, J5G-E1 doit simplifier la saisie adresse.

Le choix retenu :

```text
commune livrée sélectionnée
→ code postal prérempli
→ zone déduite
→ backend vérifie tout
```

Ce complément ne remplace pas J5G-B4. Il sécurise la donnée d'entrée qui servira ensuite aux calculs avancés.
