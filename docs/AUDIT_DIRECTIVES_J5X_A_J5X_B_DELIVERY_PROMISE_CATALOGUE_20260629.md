# Hodina — Audit technique/UI-UX et directives J5X-A / J5X-B

Date : 2026-06-29  
Contexte : après validation production J5W-A, audit du code actuel autour de `Product`, `DeliveryPricingZone`, catalogue, panier, `DeliveryLogisticsService` et J5V-A.

---

## 1. Résumé exécutif

Le code actuel est suffisamment solide pour lancer un nouveau cycle autour des tarifs et de la promesse de livraison, à condition de séquencer strictement le développement.

La logique tarifaire J5W-A est bien structurée : `DeliveryCommune.localPricingZone` fournit le forfait local de base, `DeliveryCommuneConnection` porte les coûts de liaison `LAND` / `BARGE`, `DeliveryCommune.territory` reste le garde-fou PT/GT, et `DeliveryLogisticsService` agrège le tout.

Le point faible actuel est côté UX/catalogue : la fiche produit affiche encore une information ancienne du pilote PT/GT (`Petite-Terre mardi`, `Grande-Terre jeudi`) alors que la logique réelle est désormais sectorisée par zone tarifaire. Le catalogue n’a pas encore de recherche, filtre, tri, ni ordre d’affichage administrable. Le produit dispose déjà d’un délai minimum J5V-A (`minimumOrderLeadTimeHours`), mais ce délai est appliqué uniquement au flux point de remise, car c’est le seul flux actuel où le client choisit explicitement une date/heure.

Recommandation :

1. Faire d’abord **J5X-A — mise à jour des tarifs par zone tarifaire**.
2. Ensuite seulement faire **J5X-B — calendrier de livraison paramétrable par secteur**.
3. Ne pas mélanger dans le même lot les filtres catalogue, les fiches produit, les règles de disponibilité produit et le calendrier de livraison.

---

## 2. Formule de livraison à préserver

La formule validée en production après J5W-A est :

```text
Frais de livraison client =
forfait local de la zone tarifaire de la commune client
+ coûts de liaison nécessaires entre commune(s) vendeur et commune client
+ éventuel supplément multi-vendeurs plafonné
puis application éventuelle du plafond global client.
```

Dans le code actuel :

```text
DeliveryCommune.localPricingZone
= forfait local de départ.

DeliveryCommuneConnection
= coûts de trajet LAND / BARGE.

DeliveryCommune.territory PT/GT
= garde-fou pour détecter une traversée Petite-Terre / Grande-Terre.

DeliveryLogisticsService::calculateDeliveryAmounts()
= agrégation forfait local + route + multi-vendeurs + plafonds.
```

Règle anti-régression : ne jamais remplacer cette formule par un calcul direct dans Twig, JavaScript, contrôleur catalogue ou fiche produit.

---

## 3. Audit du code actuel

### 3.1 `Product`

Fichier audité : `src/Entity/Product.php`.

Champs importants existants :

```text
seller
category
name
slug
description
price
producerPrice
marginRate
unit
stockQty
isUnlimitedStock
isPreorder
manufacturingDays
deliveryDays
deliveryMode
minimumOrderLeadTimeHours
productDeliveryPoints
isActive
createdAt
updatedAt
```

Constats :

- `minimumOrderLeadTimeHours` existe et correspond à J5V-A.
- `deliveryMode` distingue bien :
  - livraison standard uniquement ;
  - point de remise imposé ;
  - livraison standard + point de remise.
- `deliveryDays` existe déjà, mais il est aujourd’hui un champ simple et générique. Il ne doit pas être confondu avec les jours calendaires de passage par secteur.
- `manufacturingDays` et `isPreorder` existent, mais ne forment pas encore une promesse client complète.
- Il n’existe pas encore de mode produit du type `SECTOR_SCHEDULE`, `CUSTOM_DAYS`, `APPOINTMENT`.
- Il n’existe pas encore de champs de merchandising catalogue : `displayPriority`, `isFeatured`, ordre par catégorie.

Risque identifié : si J5X-B ajoute rapidement de nouveaux champs produit sans clarifier les champs existants, on risque d’avoir plusieurs délais concurrents :

```text
manufacturingDays
deliveryDays
minimumOrderLeadTimeHours
cutoffDaysBefore
customDeliveryDays
```

Décision recommandée :

- Ne pas utiliser `Product.deliveryDays` comme source des jours de passage secteur.
- Garder `minimumOrderLeadTimeHours` pour le flux point de remise / rendez-vous.
- Porter les jours de passage standard au niveau `DeliveryPricingZone`, pas au niveau produit.
- Ajouter les modes produit personnalisés seulement dans un lot ultérieur si nécessaire.

---

### 3.2 `DeliveryPricingZone`

Fichier audité : `src/Entity/DeliveryPricingZone.php`.

Champs actuels :

```text
name
code
customerDeliveryFee
courierPayout
isActive
internalNote
createdAt
updatedAt
```

Constats :

- La zone tarifaire est aujourd’hui le bon endroit pour porter le forfait local.
- Elle ne porte pas encore de calendrier de livraison.
- EasyAdmin permet déjà d’éditer les frais client et la rémunération livreur.

Décision recommandée :

- J5X-A ne doit modifier que les montants de `customerDeliveryFee`.
- J5X-B pourra enrichir `DeliveryPricingZone` avec le calendrier de passage : jours, heure limite, nombre de jours avant livraison.

---

### 3.3 `DeliveryCommune`

Fichier audité : `src/Entity/DeliveryCommune.php`.

Champs critiques :

```text
name
territory
slug
postalCode
isLogisticsPoint
localPricingZone
bargePricingZone
neighboringCommunes
isActive
```

Constats :

- `territory` reste PT/GT et ne doit pas être remplacé par les secteurs tarifaires.
- `localPricingZone` est la source du forfait local client.
- `bargePricingZone` est historique / compatibilité admin ; le calcul pilote part du forfait local et ajoute les liaisons.

Règle anti-régression : ne pas créer `PETITE_TERRE_LOCAL`. Petite-Terre doit rester sur `PT_LOCAL`.

---

### 3.4 `DeliveryLogisticsService`

Fichier audité : `src/Service/DeliveryLogisticsService.php`.

Constats :

- Le service est déjà la source métier du calcul livraison panier/commande.
- `getPricingZoneForRequirement()` retourne `clientCommune.getLocalPricingZone()`.
- `calculateDeliveryAmounts()` additionne :
  - forfait local client ;
  - supplément route ;
  - supplément multi-vendeurs ;
  - plafond client ;
  - plafond livreur.
- Le calcul n’est pas dans Twig : c’est sain.

Points de vigilance :

- Le service charge toutes les communes actives dans `findActiveCommuneByName()`. Pour 18 communes, c’est acceptable. Si Hodina grossit, il faudra optimiser avec un index / requête par slug.
- Le service ne calcule pas de prochain créneau de livraison. C’est normal aujourd’hui, mais J5X-B doit ajouter un service séparé plutôt que surcharger `DeliveryLogisticsService`.

Décision recommandée : créer plus tard un service dédié :

```text
DeliveryScheduleService
```

Responsabilités :

```text
- lire le calendrier d’une DeliveryPricingZone ;
- calculer le prochain passage ;
- appliquer la règle cutoff ;
- produire des libellés client simples ;
- exposer les données au catalogue, fiche produit, panier.
```

Ne pas mettre ces calculs dans `DeliveryLogisticsService` sauf pour exposer une donnée déjà calculée.

---

### 3.5 Catalogue

Fichiers audités :

```text
src/Controller/ProductController.php
templates/product/catalogue.html.twig
```

Constats :

- `/catalogue` récupère tous les produits actifs avec `findBy(['isActive' => true], ['createdAt' => 'DESC'])`.
- Il n’y a pas de pagination.
- Il n’y a pas de recherche.
- Il n’y a pas de filtre catégorie.
- Il n’y a pas de tri.
- Il n’y a pas de priorité admin.
- Le prix client est calculé correctement via `ProductPricingService`.
- Le bouton “Ajouter au panier” est déjà AJAX via `data-ajax-cart-form`.

Risques :

- N+1 possible sur `seller`, `category`, `images` si le catalogue grossit.
- Le catalogue est aujourd’hui trop brut pour une ouverture publique rassurante.
- Les produits sont triés par création récente, pas par stratégie commerciale.

Recommandation pour un lot catalogue ultérieur :

```text
ProductRepository::createCatalogueQuery()
+ filtres GET
+ recherche
+ tri
+ pagination si volume important
+ préchargement seller/category/images
```

---

### 3.6 Fiche produit

Fichier audité : `templates/product/show.html.twig`.

Constat critique :

La section `Livraison (pilote)` affiche encore :

```text
Petite-Terre : livraison mardi (commandes avant vendredi 23h)
Grande-Terre : livraison jeudi (commandes avant mardi 23h)
```

Cette information est désormais obsolète par rapport à la logique J5W-A et à la nouvelle grille demandée.

Décision :

- Ne pas corriger cette section manuellement avec du texte statique durable.
- J5X-B doit fournir un bloc dynamique basé sur le calendrier de `DeliveryPricingZone`.
- En attendant, si J5X-B n’est pas encore prêt, remplacer par un texte neutre :

```text
Les jours et frais de livraison dépendent de votre commune.
Ils sont confirmés dans le panier avant validation.
```

---

### 3.7 Panier / checkout

Fichiers audités :

```text
src/Controller/CartController.php
src/Controller/CheckoutController.php
templates/cart/index.html.twig
src/Form/CheckoutType.php
```

Constats :

- Le panier recalcule la logistique en AJAX via `/panier/logistique/apercu`.
- Le total mobile sticky est déjà mis à jour côté front.
- Le checkout recalcule côté serveur avant création de commande.
- La validation finale ne repose pas uniquement sur JavaScript.
- Le flux point de remise valide désormais J5V-A côté serveur.

Décision :

- Pour J5X-B, l’affichage du prochain passage peut être rafraîchi en AJAX dans le panier quand la commune change.
- Mais la décision finale doit être recalculée côté serveur au checkout.
- Aucune promesse de date ne doit être figée uniquement en JavaScript.

---

### 3.8 J5V-A

Fichiers audités :

```text
src/Entity/Product.php
src/Service/DeliveryPointCartService.php
src/Controller/CheckoutController.php
```

Constats :

- `Product.minimumOrderLeadTimeHours` existe.
- `DeliveryPointCartService::getMaximumOrderLeadTimeHours()` lit le délai le plus strict du panier.
- `DeliveryPointCartService::validateMinimumOrderLeadTime()` vérifie la date/heure demandée.
- `CheckoutController` appelle bien cette validation dans le flux point de remise.

Limite actuelle volontaire :

- J5V-A ne s’applique pas au flux standard, car le client ne choisit pas encore explicitement une date de livraison standard.

Conséquence pour J5X-B :

- Le cutoff “avant 10h la veille” ne doit pas écraser J5V-A.
- Pour la livraison standard, le cutoff est une règle secteur.
- Pour le point de remise / rendez-vous, `minimumOrderLeadTimeHours` reste une contrainte produit.

---

## 4. Directives J5X-A — Mise à jour des tarifs par zone tarifaire + garde-fou SQL + documentation

### 4.1 Objectif

Mettre à jour les frais de livraison client par zone tarifaire locale sans changer la formule de livraison.

Tarifs cibles :

```text
PT_LOCAL          → Petite-Terre       → 12 €
MAMOUDZOU_LOCAL   → Mamoudzou          → 12 €
CENTRE_LOCAL      → Centre             → 17 €
SUD_LOCAL         → Sud                → 21 €
NORD_LOCAL        → Nord               → 21 €
GT_LOCAL          → fallback technique → 21 € recommandé
```

Rémunération livreur : ne pas modifier sans décision explicite. Si rien n’est demandé, conserver les valeurs actuelles.

### 4.2 Périmètre strict

Inclus :

```text
- migration de données pour customer_delivery_fee ;
- garde-fou PHP/SQL de cohérence ;
- affichage EasyAdmin inchangé ou aide légèrement améliorée ;
- documentation J5X-A ;
- tests panier par zone.
```

Exclus :

```text
- calendrier de livraison ;
- fiche produit dynamique ;
- recherche catalogue ;
- disponibilité produit par commune ;
- DeliveryArea ;
- changement de formule DeliveryLogisticsService.
```

### 4.3 Fichiers probables

```text
migrations/Version20260629XXXXXX.php
src/Controller/Admin/DeliveryPricingZoneCrudController.php        optionnel
src/Service/DeliveryLogisticsService.php                          idéalement inchangé
tools/assert-j5x-a-delivery-pricing-zones.php
docs/...
```

### 4.4 Migration recommandée

Créer une migration dédiée qui fait uniquement des `UPDATE` sur les codes existants.

Pseudo SQL :

```sql
UPDATE delivery_pricing_zone
SET customer_delivery_fee = '12.00', updated_at = NOW()
WHERE code = 'PT_LOCAL';

UPDATE delivery_pricing_zone
SET customer_delivery_fee = '12.00', updated_at = NOW()
WHERE code = 'MAMOUDZOU_LOCAL';

UPDATE delivery_pricing_zone
SET customer_delivery_fee = '17.00', updated_at = NOW()
WHERE code = 'CENTRE_LOCAL';

UPDATE delivery_pricing_zone
SET customer_delivery_fee = '21.00', updated_at = NOW()
WHERE code = 'SUD_LOCAL';

UPDATE delivery_pricing_zone
SET customer_delivery_fee = '21.00', updated_at = NOW()
WHERE code = 'NORD_LOCAL';

UPDATE delivery_pricing_zone
SET customer_delivery_fee = '21.00', updated_at = NOW()
WHERE code = 'GT_LOCAL';
```

La migration doit être défensive :

- vérifier que `delivery_pricing_zone` existe ;
- ne pas créer `PETITE_TERRE_LOCAL` ;
- ne pas changer les rattachements de communes ;
- ne pas modifier `courier_payout` sans demande explicite.

### 4.5 Garde-fou `tools/assert-j5x-a-delivery-pricing-zones.php`

Le script doit vérifier :

```text
- PT_LOCAL existe et vaut 12.00 ;
- MAMOUDZOU_LOCAL existe et vaut 12.00 ;
- CENTRE_LOCAL existe et vaut 17.00 ;
- SUD_LOCAL existe et vaut 21.00 ;
- NORD_LOCAL existe et vaut 21.00 ;
- GT_LOCAL existe et vaut 21.00 ou est explicitement documenté comme fallback ;
- PETITE_TERRE_LOCAL n’existe pas ;
- Dzaoudzi, Labattoir, Pamandzi restent sur PT_LOCAL ;
- Mamoudzou reste sur MAMOUDZOU_LOCAL ;
- les communes Nord/Centre/Sud gardent leur rattachement J5W-A ;
- DeliveryLogisticsService contient encore l’usage de localPricingZone ;
- DeliveryLogisticsService ne contient pas de logique tarifaire codée en dur 12/17/21.
```

### 4.6 Contrôles locaux

```powershell
php -l migrations\Version20260629XXXXXX.php
php -l tools\assert-j5x-a-delivery-pricing-zones.php
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php tools/assert-j5x-a-delivery-pricing-zones.php
```

Contrôle SQL :

```powershell
php bin/console dbal:run-sql --force-fetch "SELECT code, name, customer_delivery_fee, courier_payout FROM delivery_pricing_zone WHERE code IN ('PT_LOCAL','MAMOUDZOU_LOCAL','CENTRE_LOCAL','SUD_LOCAL','NORD_LOCAL','GT_LOCAL','PETITE_TERRE_LOCAL') ORDER BY code;"
```

### 4.7 Tests fonctionnels manuels

Tester au panier, avec un produit vendeur correctement rattaché :

```text
- adresse Petite-Terre → forfait local 12 € ;
- adresse Mamoudzou → forfait local 12 € ;
- adresse Centre → forfait local 17 € ;
- adresse Sud → forfait local 21 € ;
- adresse Nord → forfait local 21 € ;
- produit vendeur autre commune → suppléments route toujours appliqués ;
- cas PT/GT → barge toujours détectée si le chemin l’impose ;
- plafond global client reste appliqué si dépassement.
```

### 4.8 Documentation J5X-A

Fichiers à mettre à jour :

```text
docs/DECISIONS.md
docs/ARCHITECTURE.md
docs/ENTITIES.md
docs/WORKFLOWS.md
docs/TODO.md
docs/ROADMAP.md
docs/PILOT_STATUS_DETAILED.md
docs/DEPLOIEMENT_PREPROD.md
docs/HISTORIQUE.md
docs/README_MAJ_J5X_A_TARIFS_ZONES_20260629.md
docs/COMMIT_J5X_A_TARIFS_ZONES.md
```

À documenter :

```text
- formule de livraison préservée ;
- nouveaux tarifs par zone ;
- GT_LOCAL fallback technique ;
- PETITE_TERRE_LOCAL interdit ;
- aucun changement calendrier ;
- aucun changement fiche produit ;
- aucun changement disponibilité produit par commune.
```

### 4.9 Commit recommandé

Ne jamais utiliser `git add .`.

```powershell
git add migrations/Version20260629XXXXXX.php `
  tools/assert-j5x-a-delivery-pricing-zones.php `
  docs/ARCHITECTURE.md `
  docs/DECISIONS.md `
  docs/DEPLOIEMENT_PREPROD.md `
  docs/ENTITIES.md `
  docs/HISTORIQUE.md `
  docs/PILOT_STATUS_DETAILED.md `
  docs/README_MAJ_J5X_A_TARIFS_ZONES_20260629.md `
  docs/ROADMAP.md `
  docs/TODO.md `
  docs/WORKFLOWS.md `
  docs/COMMIT_J5X_A_TARIFS_ZONES.md

git diff --cached --stat
git commit -m "feat(j5x-a): update delivery pricing by sector"
```

---

## 5. Directives J5X-B — Calendrier de livraison paramétrable par secteur

### 5.1 Objectif

Rendre les jours de livraison paramétrables depuis EasyAdmin au niveau des secteurs de livraison, puis préparer un affichage client clair sur catalogue, fiche produit et panier.

Table métier cible :

```text
Petite-Terre                → lundi, jeudi
Mamoudzou                   → mercredi, samedi
Grande-Terre Sud            → mercredi, samedi
Grande-Terre Nord           → mardi, vendredi
Grande-Terre Centre         → mardi, vendredi
```

Règle cutoff cible :

```text
Pour être pris en compte au prochain passage, la commande doit être faite avant 10h la veille.
```

Cette règle doit être paramétrable dans EasyAdmin.

### 5.2 Décision d’architecture

Le calendrier de livraison standard doit être porté par `DeliveryPricingZone`, pas par `Product`.

Pourquoi :

- les jours de passage dépendent de la commune/secteur client ;
- `DeliveryPricingZone` représente déjà le secteur tarifaire client ;
- un produit standard suit le secteur client ;
- le produit ne doit pas répliquer le calendrier de toutes les zones.

### 5.3 Champs recommandés sur `DeliveryPricingZone`

Ajouter :

```text
publicLabel : string nullable
publicDescription : text nullable
deliveryWeekdays : json nullable
cutoffTime : time nullable
cutoffDaysBefore : int default 1
isDeliveryScheduleActive : bool default true
```

Exemples :

```text
PT_LOCAL.publicLabel = Petite-Terre
PT_LOCAL.deliveryWeekdays = [1,4]
PT_LOCAL.cutoffTime = 10:00
PT_LOCAL.cutoffDaysBefore = 1

MAMOUDZOU_LOCAL.publicLabel = Mamoudzou
MAMOUDZOU_LOCAL.deliveryWeekdays = [3,6]

SUD_LOCAL.publicLabel = Grande-Terre Sud
SUD_LOCAL.deliveryWeekdays = [3,6]

NORD_LOCAL.publicLabel = Grande-Terre Nord
NORD_LOCAL.deliveryWeekdays = [2,5]

CENTRE_LOCAL.publicLabel = Grande-Terre Centre
CENTRE_LOCAL.deliveryWeekdays = [2,5]
```

Convention weekdays recommandée :

```text
1 = lundi
2 = mardi
3 = mercredi
4 = jeudi
5 = vendredi
6 = samedi
7 = dimanche
```

Ne pas utiliser `0 = tous les jours` ici. Pour un calendrier hebdomadaire standard, `0` crée de la confusion. Les produits sur créneau doivent être traités séparément.

### 5.4 EasyAdmin

Dans `DeliveryPricingZoneCrudController`, ajouter :

```text
- Libellé public
- Description publique
- Jours de livraison
- Heure limite de commande
- Nombre de jours avant passage
- Planning actif
```

UX admin recommandée :

```text
Nom interne : Mamoudzou local
Libellé public : Mamoudzou
Jours de livraison : mercredi, samedi
Heure limite : 10:00
Jours avant passage : 1
```

Aide admin :

```text
Exemple : si mercredi est un jour de livraison, cutoff 10:00 et J-1, les commandes doivent être validées avant mardi 10:00 pour viser mercredi.
```

### 5.5 Service recommandé : `DeliveryScheduleService`

Créer un service dédié :

```text
src/Service/DeliveryScheduleService.php
```

Responsabilités :

```text
- lire le calendrier d’une DeliveryPricingZone ;
- calculer le prochain passage possible ;
- appliquer la règle cutoff ;
- formater les jours pour l’UX ;
- retourner un DTO simple ;
- rester indépendant de Twig.
```

DTO recommandé :

```text
DeliverySchedulePreview
```

Champs :

```text
pricingZoneCode
publicLabel
weekdayLabels
nextDeliveryDate
nextDeliveryDateLabel
cutoffDateTime
cutoffLabel
isCurrentNextSlotOpen
message
warning
```

Exemples de messages :

```text
Livraison à Mamoudzou : mercredi et samedi.
Prochain passage possible : samedi.
Commande avant vendredi 10h.
```

Si cutoff dépassé :

```text
Le prochain passage est fermé. Prochaine livraison possible : mercredi.
```

### 5.6 Panier AJAX

Le panier a déjà un endpoint AJAX pour la logistique :

```text
POST /panier/logistique/apercu
```

Pour rester réactif, J5X-B peut enrichir la réponse JSON existante avec :

```json
{
  "deliverySchedule": {
    "publicLabel": "Mamoudzou",
    "weekdayLabels": ["mercredi", "samedi"],
    "nextDeliveryDateLabel": "samedi 4 juillet",
    "cutoffLabel": "commande avant vendredi 10h",
    "message": "Livraison à Mamoudzou : mercredi et samedi."
  }
}
```

Ne pas créer un second endpoint si l’information est nécessaire au même endroit que les frais.

Un endpoint séparé peut être utile plus tard pour le catalogue si la commune est choisie avant le panier :

```text
GET /catalogue/livraison/apercu?commune=Labattoir
```

Mais pour J5X-B, enrichir l’existant suffit.

### 5.7 Checkout serveur

Le checkout doit recalculer la promesse serveur.

Pour J5X-B MVP :

- afficher la promesse de passage ;
- ne pas forcément figer une date de livraison dans `CustomerOrder` si le workflow admin garde la validation manuelle ;
- documenter que la date finale reste confirmée par Hodina.

Message panier :

```text
Prochain passage proposé selon votre commune. La date finale est confirmée après vérification des vendeurs.
```

Ne pas promettre :

```text
Livré garanti mercredi.
```

Préférer :

```text
Prochain passage possible : mercredi.
```

### 5.8 Produits “sur créneau” : broche de jasmin / collier de fleurs

Ne pas les traiter comme de simples produits “livrables tous les jours” dans J5X-B.

Formulation métier :

```text
Produit sur créneau / rendez-vous.
```

Exemples :

```text
Broche de jasmin : idéale pour accueil aéroport ou événement.
Collier de fleurs : livraison sur créneau selon heure souhaitée.
```

Décision MVP :

- J5X-B peut documenter ce besoin.
- Ne pas l’implémenter dans le même lot si cela exige de nouveaux champs produit.
- S’appuyer temporairement sur le flux point de remise / date-heure si ces produits sont configurés comme point de remise ou rendez-vous.

Lot ultérieur recommandé :

```text
J5X-C — promesse produit et mode sur créneau.
```

### 5.9 UI/UX client recommandé

#### Catalogue sans commune connue

Carte produit :

```text
Livraison selon votre commune
Choisissez votre commune au panier pour voir les frais et le prochain passage.
```

Produit sur créneau :

```text
Sur créneau
Idéal accueil aéroport ou événement.
```

#### Fiche produit sans commune connue

```text
Livraison
Les jours et frais dépendent de votre commune.
Vous pourrez choisir votre commune au panier avant validation.
```

#### Panier avec commune connue

```text
Votre livraison
Commune : Labattoir
Secteur : Petite-Terre
Frais : 12 €
Passages : lundi et jeudi
Prochain passage possible : jeudi
Commande avant mercredi 10h
```

#### Si cutoff dépassé

```text
Le prochain passage est fermé.
Votre commande sera proposée pour le passage suivant.
```

#### Mention de confiance

```text
La date finale est confirmée après vérification des vendeurs par Hodina.
```

### 5.10 Tests locaux J5X-B

Tests techniques :

```powershell
php -l src\Entity\DeliveryPricingZone.php
php -l src\Controller\Admin\DeliveryPricingZoneCrudController.php
php -l src\Service\DeliveryScheduleService.php
php bin/console lint:twig templates
php bin/console lint:container
php bin/console doctrine:schema:validate
```

Tests calendrier :

```text
- PT_LOCAL lundi/jeudi ;
- MAMOUDZOU_LOCAL mercredi/samedi ;
- SUD_LOCAL mercredi/samedi ;
- NORD_LOCAL mardi/vendredi ;
- CENTRE_LOCAL mardi/vendredi ;
- cutoff 10h J-1 ;
- cutoff dépassé : prochain passage suivant ;
- zone sans calendrier actif : message neutre ;
- timezone Mayotte : Indian/Mayotte.
```

Tests UX :

```text
- panier standard connecté ;
- panier standard invité ;
- changement commune -> recalcul AJAX frais + planning ;
- mobile sticky total reste cohérent ;
- message clair sans jargon PT/GT/localPricingZone ;
- fiche produit ne montre plus l’ancien tableau PT mardi / GT jeudi.
```

### 5.11 Garde-fou J5X-B

Créer :

```text
tools/assert-j5x-b-delivery-schedules.php
```

Vérifications :

```text
- DeliveryPricingZone contient les nouveaux champs ;
- les 5 secteurs commerciaux ont des jours configurés ;
- cutoffTime = 10:00 par défaut ;
- cutoffDaysBefore = 1 ;
- GT_LOCAL reste fallback technique ;
- PETITE_TERRE_LOCAL absent ;
- product/show.html.twig ne contient plus l’ancien texte “Petit-Terre : livraison mardi” ;
- pas de calcul de prochain créneau directement dans Twig.
```

### 5.12 Documentation J5X-B

Fichiers à mettre à jour :

```text
docs/ARCHITECTURE.md
docs/DECISIONS.md
docs/ENTITIES.md
docs/WORKFLOWS.md
docs/TODO.md
docs/ROADMAP.md
docs/PILOT_STATUS_DETAILED.md
docs/DEPLOIEMENT_PREPROD.md
docs/HISTORIQUE.md
docs/README_MAJ_J5X_B_DELIVERY_SCHEDULES_20260629.md
docs/COMMIT_J5X_B_DELIVERY_SCHEDULES.md
```

À documenter explicitement :

```text
- calendrier porté par DeliveryPricingZone ;
- cutoff 10h J-1 paramétrable ;
- promesse affichée comme “prochain passage possible”, pas comme garantie ;
- date finale toujours confirmée par Hodina pendant le pilote ;
- J5V-A reste spécifique au délai produit / point de remise ;
- produit sur créneau repoussé à J5X-C si non implémenté.
```

---

## 6. Catalogue : directives pour le lot ultérieur

À ne pas inclure dans J5X-A ni J5X-B sauf décision contraire.

### 6.1 Fonctionnalités demandées

```text
- recherche produit ;
- filtre catégorie ;
- option de tri ;
- ordre d’affichage par catégorie depuis EasyAdmin ;
- possibilité de moduler l’affichage par catégorie.
```

### 6.2 Champs recommandés

Sur `Category` :

```text
displayOrder : int default 0
isFeatured : bool default false
publicDescription : text nullable
```

Sur `Product` :

```text
displayPriority : int default 0
isFeatured : bool default false
```

### 6.3 Requête catalogue recommandée

Créer dans `ProductRepository` :

```text
createCatalogueQuery(?string $search, ?Category $category, string $sort)
```

Tri recommandé :

```text
- Mis en avant
- Nouveautés
- Prix croissant
- Prix décroissant
- Livraison la plus proche, uniquement quand la commune est connue
```

### 6.4 AJAX / réactivité

Le site doit rester réactif. Approche recommandée :

- chargement initial SSR Twig pour SEO/simplicité ;
- filtres GET pour URLs partageables ;
- amélioration AJAX progressive pour éviter reload complet ;
- fallback sans JavaScript fonctionnel.

Endpoint possible plus tard :

```text
GET /catalogue/fragment?search=jasmin&category=fleurs&sort=featured
```

Retour : fragment HTML de grille produit ou JSON structuré. Pour le MVP Symfony/Twig, un fragment HTML est plus simple et cohérent.

---

## 7. Ordre recommandé des lots

```text
J5X-A — tarifs zones tarifaires
J5X-B — calendrier livraison par secteur
J5X-C — promesse produit / produit sur créneau
J5X-D — catalogue recherche/filtres/tri/priorité admin
```

Ne pas inverser J5X-B et J5X-D : un catalogue filtrable mais avec une promesse livraison fausse ou ancienne serait mauvais pour la confiance client.

---

## 8. Branche de développement recommandée

```powershell
cd E:\hodina\hodina.fr

git switch develop
git pull --ff-only origin develop
git status --short

git switch -c pilot/j5x-a-delivery-pricing-update
```

Pour J5X-B ensuite :

```powershell
git switch develop
git pull --ff-only origin develop
git switch -c pilot/j5x-b-delivery-schedules
```

---

## 9. Règles anti-régression globales

```text
- Ne jamais recommander git add .
- Ne pas créer PETITE_TERRE_LOCAL.
- Ne pas remplacer DeliveryCommune.territory PT/GT par les secteurs tarifaires.
- Ne pas coder les tarifs en dur dans Twig, JS ou contrôleur.
- Ne pas coder le calendrier en dur dans la fiche produit.
- Ne pas promettre une livraison garantie si l’admin doit encore confirmer.
- Ne pas dupliquer la logique J5V-A.
- Ne pas mélanger disponibilité produit par commune et calendrier de passage.
- Ne pas démarrer DeliveryArea dans ce cycle.
```

---

## 10. Phrase de cadrage à réutiliser dans un nouveau chat

```text
Nous démarrons J5X-A puis J5X-B. Le runtime production est validé après J5W-A. La formule de livraison à préserver est : forfait local DeliveryPricingZone de la commune client + coûts de liaisons DeliveryCommuneConnection LAND/BARGE + supplément multi-vendeurs plafonné + plafond global. J5X-A doit uniquement mettre à jour les tarifs par zone : PT_LOCAL 12, MAMOUDZOU_LOCAL 12, CENTRE_LOCAL 17, SUD_LOCAL 21, NORD_LOCAL 21, GT_LOCAL fallback 21. J5X-B doit ajouter un calendrier paramétrable sur DeliveryPricingZone : PT lundi/jeudi, Mamoudzou mercredi/samedi, Sud mercredi/samedi, Nord mardi/vendredi, Centre mardi/vendredi, cutoff paramétrable 10h J-1. Ne pas créer PETITE_TERRE_LOCAL, ne pas remplacer PT/GT, ne pas coder la promesse dans Twig, ne pas dupliquer J5V-A.
```
