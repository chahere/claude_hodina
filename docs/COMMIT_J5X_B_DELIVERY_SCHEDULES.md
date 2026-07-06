# Commit — J5X-B — Calendrier livraison paramétrable par secteur

## Message recommandé

```text
feat(j5x-b): add configurable delivery schedules by sector
```

## Résumé

Ce commit ajoute le calendrier de livraison standard par secteur, porté par `DeliveryPricingZone`, configurable depuis EasyAdmin et affiché dans le panier via l’aperçu logistique AJAX existant.

## Points clés

- Ajout des champs calendrier sur `DeliveryPricingZone`.
- Migration de données avec jours de passage et cutoff 10h J-1.
- Création de `DeliveryScheduleService` et `DeliverySchedulePreview`.
- Enrichissement du JSON `/panier/logistique/apercu` avec `deliverySchedule`.
- Affichage panier mobile-first : passages, prochain passage possible, cutoff, mention de confirmation Hodina.
- Remplacement de l’ancien texte fiche produit `Petite-Terre mardi / Grande-Terre jeudi` par une information cohérente avec les secteurs.
- Garde-fou `tools/assert-j5x-b-delivery-schedules.php`.

## Règles anti-régression

```text
Ne pas coder les jours de livraison dans Twig.
Ne pas promettre une livraison garantie.
Ne pas modifier la formule de frais de livraison.
Ne pas utiliser Product.deliveryDays comme calendrier secteur.
Ne pas dupliquer J5V-A.
Ne pas créer PETITE_TERRE_LOCAL.
```

## Validation attendue

Local ciblé d’abord, puis recette seulement après J5X-B/C/D ou selon arbitrage.

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-b-delivery-schedules.php
```
