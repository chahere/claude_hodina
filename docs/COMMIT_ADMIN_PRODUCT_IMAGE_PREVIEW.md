
# COMMIT — Admin produit : aperçu stable des images dans EasyAdmin

Date : **18/06/2026**  
Commit : **`cd5d4cd fix(admin): restore stable product image previews in EasyAdmin`**  
Contexte : recette J5G-B4 avant tag `j5g-b4-20260618-v2`

---

## Problème constaté

Dans EasyAdmin, en édition / ajout produit, les images produit existaient bien en base et le nom de fichier était visible, mais aucune miniature n'apparaissait dans le formulaire.

Observation :

```text
ProductImage existe
champ path renseigné
fichier image présent
EasyAdmin affiche le nom du fichier
mais aperçu miniature absent
```

Ce n'était pas un problème de migration, ni de relation Doctrine, ni d'upload. C'était un problème de rendu admin / JS de prévisualisation.

---

## Première correction et incident

Une première correction a ajouté une détection très large côté JavaScript, mais elle a provoqué un blocage / chargement long de la page produit.

Cause retenue : `MutationObserver` trop large et scan DOM trop lourd dans un formulaire EasyAdmin imbriqué.

Décision : stabiliser la correction, supprimer l'observation DOM lourde, garder uniquement les refresh utiles.

---

## Correction finale

Fichiers modifiés :

```text
assets/admin.js
assets/controllers/product_images_controller.js
src/Controller/Admin/ProductCrudController.php
templates/admin/field/product_images_collection.html.twig
```

Principes :

- démarrer Stimulus une seule fois ;
- raccorder explicitement le champ Photos avec un wrapper stable ;
- chercher le nom de fichier existant dans les zones utiles EasyAdmin ;
- détecter les extensions image ;
- afficher la miniature existante ;
- afficher la miniature lors de la sélection d'un nouveau fichier ;
- éviter les observers lourds.

---

## Résultat

L'affichage produit est redevenu fluide et les aperçus d'images s'affichent correctement en édition / ajout.

Tests à refaire :

- ouvrir un produit avec image existante ;
- vérifier l'aperçu ;
- sélectionner une nouvelle image ;
- ajouter un nouvel item photo ;
- vérifier que la page ne bloque pas sur mobile.

---

## Règle pour la suite

Pour les composants EasyAdmin imbriqués, éviter les scripts qui scannent tout le DOM ou observent toute une collection sans limite. Préférer :

```text
rendu serveur simple
wrapper stable
JS ciblé
refresh explicite après ajout / changement fichier
```
