# HODINA - Architecture

## Historique conservé

État initial du document de référence :

```text
- Symfony
- Doctrine ORM
- MariaDB
- EasyAdmin
- Twig
- PWA

Dernières évolutions :
- postalCode obligatoire
- Checkout mis à jour
- Address EasyAdmin mis à jour
```

Ces choix restent valides.

---

# Stack technique

- Symfony
- Doctrine ORM
- MariaDB
- EasyAdmin
- Twig
- PWA mobile-first
- PowerShell / Git pour le développement local

---

# Architecture fonctionnelle actuelle

## Front client

Responsabilités :

- consultation catalogue ;
- panier ;
- connexion client ;
- inscription client ;
- adresse ;
- validation de commande ;
- affichage du numéro de commande.

## Backoffice admin EasyAdmin

URL :

```text
/ouegnewe
```

Responsabilités :

- commandes clients ;
- lignes de commande ;
- produits ;
- vendeurs ;
- clients ;
- adresses ;
- zones de livraison ;
- SmsLog ;
- réglages Hodina.

## Fiche terrain commande

Interface ajoutée pour rendre le traitement commande plus lisible sur mobile.

Elle reste liée au backoffice admin.

## Dashboard livreur prévu J5

URL cible :

```text
/djama
```

Responsabilités :

- voir les commandes prêtes ;
- prendre en charge une commande ;
- voir ses commandes en livraison ;
- appeler le client ;
- ouvrir SMS client ;
- marquer une commande livrée.

Important : ce dashboard sera authentifié et séparé d'EasyAdmin, mais il reste bien un portail/dashbord livreur.

---

# Architecture J4 livrée

## Entités / relations

- `CustomerOrder` relié à ses `OrderItem`.
- `OrderItem` visible dans EasyAdmin.
- `SmsLog` utilisé comme trace système.
- `HodinaSetting` ajouté pour les réglages génériques.

## Commandes

- Numéro métier de commande.
- Dates métier.
- Statuts de traitement.
- Actions admin.
- Fiche terrain.

## SmsLog

- Génération automatique.
- Lecture seule dans EasyAdmin.
- Bouton `Envoyer le SMS` ajouté.
- Test du lien `sms:` avec numéro et message préremplis sur iPhone.

## Réglages Hodina

Architecture générique :

```text
HodinaSetting
- label
- settingKey
- value
- help
- fieldType
- updatedAt
```

Principe :

```text
1 ligne = 1 paramètre
```

---

# Architecture cible J5

## Problème identifié

La logique de changement de statut existe déjà côté admin.

Le portail livreur aura aussi besoin de changer des statuts :

```text
READY_FOR_PICKUP → OUT_FOR_DELIVERY → DELIVERED
```

Il ne faut pas dupliquer cette logique dans un nouveau contrôleur.

## Solution retenue

Créer un service métier commun :

```text
src/Service/CustomerOrderWorkflowService.php
```

## Rôle du service

Centraliser :

- les transitions autorisées ;
- les changements de statut ;
- les dates métier ;
- la création des SmsLog ;
- l'association livreur ;
- la sauvegarde Doctrine ;
- la génération de numéro si absent.

## Contrôleurs consommateurs

### Admin

```text
src/Controller/Admin/CustomerOrderCrudController.php
```

Devra appeler :

```text
CustomerOrderWorkflowService
```

### Livreur

```text
src/Controller/Courier/CourierDashboardController.php
```

ou :

```text
src/Controller/CourierDashboardController.php
```

Devra appeler le même service.

---

# Architecture de sécurité J5

## Rôles

Minimum à prévoir :

```text
ROLE_ADMIN
ROLE_COURIER
ROLE_CUSTOMER
```

## Accès

```text
/ouegnewe
→ ROLE_ADMIN

/djama
→ ROLE_COURIER
```

## Principe

Le livreur ne doit pas accéder à EasyAdmin pour traiter les livraisons.

Il doit utiliser une interface dédiée, plus simple et limitée à ses actions.

---

# Architecture data J5 prévue

## CustomerOrder

Champs / relations à ajouter ou vérifier :

- `outForDeliveryAt`
- `courierAssignedAt`
- `courier` ou `assignedCourier`

Le type de `courier` dépendra de l'entité utilisateur existante.

Option recommandée MVP :

```text
CustomerOrder.assignedCourier -> User
```

## Statuts

Statuts exploités :

- `PENDING_VALIDATION`
- `CONFIRMED`
- `PREPARING`
- `READY_FOR_PICKUP`
- `OUT_FOR_DELIVERY`
- `DELIVERED`
- `CANCELED`

---

# Architecture SMS

## Court terme

SmsLog reste une simulation / trace.

Le bouton `Envoyer le SMS` permet un envoi manuel via l'application SMS de l'iPhone.

## Moyen terme

Ajouter si besoin :

- `sentManuallyAt`
- `sentBy`
- `sendStatus`

## Long terme

Connexion à un vrai fournisseur SMS.

SmsLog deviendra alors l'historique réel d'envoi.

---

# Principes d'architecture à respecter

- Ne pas mettre de logique métier lourde dans les contrôleurs.
- Centraliser les transitions dans un service.
- Garder EasyAdmin pour l'administration.
- Créer des dashboards dédiés pour les usages terrain.
- Préserver une interface mobile-first.
- Garder le pilote simple.
- Reporter les optimisations logistiques avancées.

---

# Architecture J5A suite — Préproduction et légal

## Environnement recette

Nouvel environnement :

```text
recette.hodina.fr
```

Chemin serveur :

```text
/home/vopu3712/recette.hodina.fr
```

Document root :

```text
/home/vopu3712/recette.hodina.fr/public
```

## `.htaccess` préprod

Responsabilités :

- forcer HTTPS ;
- protéger par Basic Auth ;
- conserver les règles Symfony vers `index.php`.

Ordre important :

```text
1. Rewrite HTTP → HTTPS
2. Basic Auth
3. Rewrite Symfony
```

## `.htpasswd`

Emplacement :

```text
/home/vopu3712/recette.hodina.fr/.htpasswd
```

Règle : ne jamais le placer dans `public`.

## Base recette

Base :

```text
vopu3712_hodina_recette
```

Cette base est séparée de la base existante et sert uniquement aux tests recette.

## Encodage dump SQL

Point d'attention : PowerShell peut produire un fichier UTF-16 si la redirection ou la réécriture est mal faite.

Préférence :

```text
mysqldump via cmd.exe
ou conversion explicite UTF-8
```

## LegalController / pages légales

Architecture retenue :

```text
LegalController
├── /cgu
└── /cgv
```

Templates : pages Twig statiques, versionnées avec le code.

## Footer public

Le footer public ne contient plus de lien admin.

Architecture publique :

```text
Catalogue
CGU
CGV
```

Backoffice : accès direct par URL connue.

## EasyAdmin translation JS

Le script `public/js/hodina-easyadmin-fr.js` reste générique.

Règle : ne pas traduire globalement un libellé EasyAdmin avec une traduction spécifique métier qui pourrait apparaître dans d'autres contextes.


---

# Architecture J5C réalisée — Données livraison

## État réalisé le 06/06/2026

J5C ajoute la couche de données nécessaire au futur dashboard livreur.

## Entité livreur retenue pour le MVP

Le projet utilise `Customer` comme entité authentifiable principale.

Décision MVP :

```text
Livreur = Customer avec ROLE_COURIER
```

Relation retenue :

```text
CustomerOrder.assignedCourier -> Customer
```

Cette solution évite d'introduire trop tôt une entité `User` séparée.

## CustomerOrder enrichi

Nouveaux champs / relation :

```text
assignedCourier
courierAssignedAt
outForDeliveryAt
```

Ces champs sont affichés dans le détail commande EasyAdmin, uniquement en consultation.

## CustomerOrderWorkflowService enrichi

Méthodes ajoutées :

```text
canTakeForDelivery()
takeForDelivery()
canMarkDeliveredByCourier()
markDeliveredByCourier()
```

Le service prépare le futur contrôleur livreur :

```text
CourierDashboardController
→ CustomerOrderWorkflowService
```

## Sécurité

`security.yaml` réserve déjà :

```text
/djama → ROLE_COURIER
```

## Migrations

J5C introduit une migration principale de livraison et une migration de correction d'index sécurisée.

Point d'architecture retenu : les migrations doivent être indépendantes de l'état local et robustes en préproduction.

## Dette / vigilance technique

Incident documenté : une migration d'index générée avec un timestamp antérieur à la migration principale a échoué en préproduction.

Règle pour la suite :

```text
Toute migration corrective doit avoir un timestamp postérieur à la migration qu'elle corrige.
```

Règle patch retenue :

```text
Les patchs doivent être de vrais patchs Git applicables depuis E:\hodina\hodina.fr avec git apply sans option -p.
```


---

# Architecture J5D réalisée — Dashboard livreur

## État réalisé

J5D ajoute le contrôleur et le template du dashboard livreur :

```text
src/Controller/Courier/CourierDashboardController.php
templates/courier/dashboard.html.twig
```

Le dashboard utilise les méthodes existantes du service :

```text
CustomerOrderWorkflowService::takeForDelivery()
CustomerOrderWorkflowService::markDeliveredByCourier()
```

C'est important : l'interface livreur ne duplique pas les règles de transition.

## Navigation

Le lien `Livreur` est affiché seulement si l'utilisateur a :

```text
ROLE_COURIER
```

---

# Architecture J5E — Prix et marge produit

## Principe

Le calcul du prix client ne doit pas être dans Twig ni dans les contrôleurs.

Créer un service :

```text
src/Service/ProductPricingService.php
```

## Responsabilités

- lire la marge globale ;
- lire la marge vendeur ;
- lire la marge produit ;
- déterminer la marge effective ;
- calculer le prix client ;
- calculer la marge Hodina ;
- fournir les valeurs au catalogue, panier, checkout et futur portail vendeur.

## Architecture future compatible vendeur

Le futur portail vendeur permettra au vendeur de saisir :

```text
Product.producerPrice
```

Mais pas :

```text
Product.customerPrice
```

Le prix client est une valeur calculée.

---

# Architecture J5F — Communes et zones tarifaires

## Entités prévues

```text
DeliveryPricingZone
DeliveryCommune
```

## Relations clés

```text
DeliveryCommune.localPricingZone -> DeliveryPricingZone
DeliveryCommune.bargePricingZone -> DeliveryPricingZone
DeliveryCommune.neighboringCommunes -> DeliveryCommune
Seller.deliveryCommune -> DeliveryCommune
```

## Pourquoi une relation et pas du texte libre ?

Parce que le service logistique doit pouvoir comparer de vraies communes :

```text
commune client
commune vendeur
territoire PT / GT
voisinage
zone tarifaire
```

Un texte libre rendrait le calcul fragile.

---

# Architecture J5G — Service logistique panier

## Service prévu

```text
src/Service/DeliveryLogisticsService.php
```

## Responsabilités

- trouver la commune client ;
- trouver les communes vendeurs ;
- comparer les territoires PT / GT ;
- détecter la barge ;
- détecter les communes voisines ;
- détecter les communes éloignées ;
- choisir la zone tarifaire ;
- produire l'aperçu panier ;
- figer les données au checkout.

## DTO prévu

```text
CartLogisticsPreview
```

Ce DTO permet de transmettre au template une information propre, sans mettre toute la logique dans Twig.

---

# Architecture du futur portail vendeur

## Décision

Hodina aura un portail vendeur dédié.

Rôle prévu :

```text
ROLE_SELLER
```

## Architecture MVP recommandée

Le projet utilise déjà `Customer` comme entité authentifiable.

Pour le pilote étendu :

```text
Customer avec ROLE_SELLER
→ relié à Seller
```

Plus tard, si plusieurs personnes doivent gérer la même boutique, une entité intermédiaire pourra être créée.

## Règle d'architecture

Tout ce qui touche aux prix, marges, communes, barge et logistique doit être dans des services réutilisables.

À éviter :

```text
calcul prix dans ProductCrudController
calcul prix dans Twig
calcul barge dans CartController
calcul livraison dans CheckoutController uniquement
```

À préférer :

```text
ProductPricingService
DeliveryLogisticsService
```


---

# Architecture J5E réalisée — Prix produit et marge Hodina

## État réalisé

J5E ajoute :

```text
src/Service/ProductPricingService.php
```

Ce service est l'équivalent économique de `CustomerOrderWorkflowService`.

## Règle d'architecture

Le calcul du prix client ne doit pas être recodé dans Twig, les contrôleurs, EasyAdmin ou le futur portail vendeur.

La règle doit rester dans :

```text
ProductPricingService
```

## Flux actuel

```text
Product / Seller / HodinaSetting
→ ProductPricingService
→ ProductController
→ templates catalogue / fiche produit
```

```text
Product / Seller / HodinaSetting
→ ProductPricingService
→ CartService
→ panier
```

```text
Product / Seller / HodinaSetting
→ ProductPricingService
→ CartService
→ CheckoutController
→ OrderItem figé
```

## Données dynamiques

```text
Product.producerPrice
Product.marginRate
Seller.marginRate
HodinaSetting.global_margin_rate
```

## Données figées

```text
OrderItem.producerUnitPrice
OrderItem.appliedMarginRate
OrderItem.hodinaMarginAmount
OrderItem.unitPrice
OrderItem.lineTotal
```

## Migration

```text
migrations/Version20260607120000.php
```

La migration est défensive et vérifie l'existence des colonnes avant ajout.

## Dette technique volontaire

`Product.price` reste présent pour compatibilité mais ne doit plus être utilisé comme source principale dans les nouvelles fonctionnalités.

## Préparation du portail vendeur

Le futur portail vendeur devra permettre au vendeur de saisir `producerPrice`, puis appeler `ProductPricingService` pour afficher le prix client calculé.

## Vigilance J5F

J5F devra reproduire ce modèle avec un futur `DeliveryLogisticsService` pour éviter les calculs de livraison dans Twig ou dans les contrôleurs.

---

# Architecture J5F clarifiée — Territoire logistique et barge

## Clarification centrale

La barge est une règle de territoire, pas une règle de distance.

Le code devra donc s'appuyer sur :

```text
DeliveryCommune.territory
```

avec deux valeurs principales pour le pilote :

```text
PT
GT
```

## Entités concernées

```text
DeliveryCommune
DeliveryPricingZone
Seller.deliveryCommune
```

## DeliveryCommune.territory

Ce champ sert à déterminer si une commande traverse Petite-Terre / Grande-Terre.

Il doit être utilisé par le futur `DeliveryLogisticsService` pour calculer :

```text
requiresBarge
```

## DeliveryCommune.localPricingZone

Utilisée si :

```text
clientTerritory === sellerTerritory pour tous les vendeurs du panier
```

Exemples :

```text
client PT + vendeurs PT
→ localPricingZone

client GT + vendeurs GT
→ localPricingZone
```

## DeliveryCommune.bargePricingZone

Utilisée uniquement si :

```text
au moins un vendeur du panier a un territoire différent du client
```

Exemples :

```text
client PT + au moins un vendeur GT
→ bargePricingZone

client GT + au moins un vendeur PT
→ bargePricingZone
```

## DeliveryCommune.neighboringCommunes

Cette relation ne calcule pas la barge.

Elle sert à classer la relation logistique :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
```

Seul `OTHER_TERRITORY` correspond à une barge.

## Règle à coder dans DeliveryLogisticsService

Pseudo-code cible :

```php
$requiresBarge = false;

foreach ($sellerCommunes as $sellerCommune) {
    if ($sellerCommune->getTerritory() !== $clientCommune->getTerritory()) {
        $requiresBarge = true;
        break;
    }
}
```

À ne pas faire :

```php
// Mauvais raisonnement
$requiresBarge = !$clientCommune->isNeighboring($sellerCommune);
```

Pourquoi : une commune peut être éloignée sur le même territoire sans nécessiter la barge.

## Impact sur le modèle J5F-A

Le modèle prévu reste valable :

```text
DeliveryPricingZone
DeliveryCommune
Seller.deliveryCommune
```

Mais les libellés et aides EasyAdmin doivent être explicites :

```text
Zone locale
→ utilisée si client et vendeurs restent sur le même territoire PT/GT.

Zone avec barge
→ utilisée uniquement si la commande traverse PT/GT.
```


---

# Architecture J5F-A réalisée — Communes, zones tarifaires et vendeur

## Entités ajoutées

J5F-A ajoute deux entités Doctrine :

```text
DeliveryPricingZone
DeliveryCommune
```

Et enrichit une entité existante :

```text
Seller.deliveryCommune
```

## DeliveryPricingZone

Fichier :

```text
src/Entity/DeliveryPricingZone.php
```

Rôle : stocker les montants économiques d'une zone de livraison.

Champs :

```text
id
name
code
customerDeliveryFee
courierPayout
isActive
internalNote
createdAt
updatedAt
```

Méthode importante :

```text
getDeliveryMargin()
```

Elle calcule :

```text
customerDeliveryFee - courierPayout
```

Le montant de marge livraison n'est pas stocké. Il est calculé pour éviter une incohérence.

## DeliveryCommune

Fichier :

```text
src/Entity/DeliveryCommune.php
```

Rôle : représenter une commune livrée ou une commune de retrait / production de vendeur.

Champs / relations :

```text
name
territory
localPricingZone
bargePricingZone
neighboringCommunes
isActive
internalNote
createdAt
updatedAt
```

Territoires :

```text
PT = Petite-Terre
GT = Grande-Terre
```

## Relations

```text
DeliveryCommune.localPricingZone -> DeliveryPricingZone
DeliveryCommune.bargePricingZone -> DeliveryPricingZone
DeliveryCommune.neighboringCommunes <-> DeliveryCommune
Seller.deliveryCommune -> DeliveryCommune
```

## Tables créées

```text
delivery_pricing_zone
delivery_commune
delivery_commune_neighbor
```

La table `seller` reçoit :

```text
delivery_commune_id
```

## EasyAdmin

J5F-A ajoute :

```text
src/Controller/Admin/DeliveryPricingZoneCrudController.php
src/Controller/Admin/DeliveryCommuneCrudController.php
```

Et enrichit le menu admin via :

```text
src/Controller/Admin/DashboardController.php
```

Le CRUD vendeur affiche désormais :

```text
Commune texte historique
Commune logistique
Marge vendeur Hodina (%)
Zone de livraison
```

Le champ `Seller.commune` est conservé, mais une aide indique de préférer `Seller.deliveryCommune`.

## Migrations

Deux migrations sont impliquées :

```text
Version20260607170000
Version20260607173000
```

`Version20260607170000` crée le socle.

`Version20260607173000` aligne le schéma avec les noms exacts attendus par Doctrine : contraintes, index et colonnes datetime.

## Pourquoi une migration corrective ?

Après la première migration, `doctrine:schema:validate` indiquait que la base n'était pas synchronisée.

Le diagnostic :

```bash
php bin/console doctrine:schema:update --dump-sql
```

a montré que Doctrine voulait surtout renommer des index / contraintes et ajuster `created_at` / `updated_at`.

Décision : ne pas utiliser `schema:update --force`, mais ajouter une migration corrective versionnée.


---

# Architecture J5F-B réalisée — DeliveryLogisticsService et DTO

## Service ajouté

Fichier :

```text
src/Service/DeliveryLogisticsService.php
```

Ce service devient le pendant logistique de :

```text
CustomerOrderWorkflowService → workflow commande
ProductPricingService        → prix produit
DeliveryLogisticsService     → livraison, barge, zone tarifaire
```

## Responsabilités du service

`DeliveryLogisticsService` centralise :

- la recherche de la commune client à partir de l'adresse ;
- la lecture des communes logistiques des vendeurs du panier ;
- la classification commune client / commune vendeur ;
- la détection de la barge ;
- le choix de la zone tarifaire ;
- la production d'un aperçu logistique.

## Méthodes importantes

```text
previewForCart(?Address $address, array $detailedCart): CartLogisticsPreview
getCommuneRelation(DeliveryCommune $clientCommune, ?DeliveryCommune $sellerCommune): string
requiresBarge(DeliveryCommune $clientCommune, DeliveryCommune $sellerCommune): bool
getPricingZoneForRequirement(DeliveryCommune $clientCommune, bool $requiresBarge): DeliveryPricingZone
```

## Règle barge dans le code

La méthode `requiresBarge()` applique :

```text
clientCommune.territory !== sellerCommune.territory
```

Elle ne regarde pas les communes voisines.

## DTO ajouté

Fichier :

```text
src/Dto/CartLogisticsPreview.php
```

DTO signifie `Data Transfer Object`.

Dans Hodina, ce DTO transporte le résultat du calcul logistique vers le futur panier J5G.

Il n'est pas une entité Doctrine et ne crée pas de table.

## Pourquoi un DTO ?

Sans DTO, le service pourrait renvoyer un tableau comme :

```php
$preview['requires_barge']
```

Mais un DTO est plus lisible et plus robuste :

```php
$preview->requiresBarge
$preview->estimatedDeliveryFee
$preview->message
```

Pour un développeur débutant, c'est plus clair : le nom de la classe documente ce que le service retourne.

## État d'intégration

J5F-B ne branche pas encore le service dans le panier.

Il prépare l'étape suivante :

```text
J5G-A — Aperçu logistique panier
```


---

# Architecture navigation header après ajustement Admin / Livreur

## Règle actuelle

Le header public contient toujours :

```text
Catalogue
```

Puis un lien contextuel selon le rôle :

```text
ROLE_ADMIN → Admin
ROLE_COURIER seul → Livreur
sinon → Devenir vendeur
```

## Important

Le footer public ne contient toujours pas de lien admin.

Le lien admin est uniquement affiché dans le header quand l'utilisateur est connecté avec `ROLE_ADMIN`.

Si un utilisateur possède `ROLE_ADMIN` et `ROLE_COURIER`, le lien affiché est `Admin` seulement.


---

# État global après J5F-B

## État de référence après la session

Branche de travail active :

```text
pilot/j5-order-delivery-pricing
```

Cette branche remplace l'ancien nom trop restrictif :

```text
pilot/j5b-workflow-service
```

Raison du renommage : la branche ne contient plus uniquement J5B. Elle porte désormais les évolutions commande, livraison et pricing :

```text
J5B → refactoring workflow commande
J5C → données livraison
J5D → dashboard livreur
J5E → marge produit Hodina
J5F-A → communes et zones tarifaires
J5F-B → DeliveryLogisticsService
```

Règle pratique : toute la suite J5G et le début J6 doivent partir de `pilot/j5-order-delivery-pricing`, sauf création explicite d'une nouvelle branche.

---

# Mise à jour corrective J5G — architecture livraison avancée

## Pourquoi cette section est ajoutée

Après la livraison de J5F-B, un premier travail documentaire a été fait. En relisant les fichiers réellement présents dans le projet, un appauvrissement a été constaté : certains documents décrivaient encore surtout l'ancien modèle simple :

```text
commune client
→ zone locale ou zone barge
→ frais livraison
```

Ce modèle reste utile comme base, mais il ne suffit plus pour la règle métier décidée ensuite.

La nouvelle architecture cible doit refléter la réalité de Mayotte :

```text
commune vendeur
→ chemin de communes
→ éventuelle barge
→ commune client
→ frais client
→ rémunération livreur
→ marge livraison Hodina
```

## J5G-A réalisé — Aperçu logistique panier par périmètre vendeur

J5G-A branche `DeliveryLogisticsService` dans le panier.

Fichiers concernés :

```text
src/Controller/CartController.php
src/Dto/CartLogisticsPreview.php
templates/cart/index.html.twig
public/css/style_mobile.css
```

Objectif :

```text
Le panier affiche une estimation logistique avant le checkout.
```

Le panier ne fige rien en base. Il informe seulement le client.

## Signature logistique du panier

La règle importante décidée pendant J5G-A est :

```text
La livraison dépend des vendeurs présents dans le panier, pas du nombre de produits.
```

Donc, si le client ajoute un deuxième produit venant d'un vendeur déjà présent, les frais de livraison ne doivent pas être recalculés comme si une nouvelle livraison apparaissait.

Exemple :

```text
Panier vide
→ ajout manioc vendeur ferme houmadi
→ nouveau vendeur
→ calcul logistique

Ajout bananes vendeur ferme houmadi
→ vendeur déjà présent
→ la signature logistique ne change pas
→ les frais livraison estimés restent identiques

Ajout tomates vendeur ferme Abdallah
→ nouveau vendeur
→ la signature change
→ recalcul logistique

Suppression du dernier produit d'un vendeur
→ vendeur retiré du périmètre
→ recalcul logistique
```

La signature logique est composée de :

```text
adresse / commune client utilisée pour l'estimation
+ liste unique et triée des vendeurs présents dans le panier
```

Une quantité ne doit pas modifier cette signature.

## Constat après J5G-A

Pendant les tests, la barge était correctement détectée, mais les frais ne changeaient pas.

Cause : le jeu de test recette avait :

```text
PT_LOCAL = 6 € client / 5 € livreur
GT_LOCAL = 6 € client / 5 € livreur
```

Donc le service pouvait bien changer de zone, mais tomber sur une zone ayant le même montant.

Conclusion pédagogique :

```text
Un calcul peut être correct même si l'écran semble identique.
Il faut vérifier les données de test avant de modifier le code.
```

## Nouvelle architecture cible J5G-B / J5G-C

La décision métier finale est d'abandonner le modèle “zone barge = tarif complet” au profit d'un modèle composé.

Formule cible :

```text
frais livraison client =
frais local de la commune client
+ supplément par commune traversée
+ supplément barge aller-retour si PT ↔ GT
```

Et côté livreur :

```text
rémunération livreur =
rémunération locale de la commune client
+ rémunération par commune traversée
+ compensation barge si PT ↔ GT
```

La marge livraison Hodina reste calculable :

```text
marge livraison =
frais livraison client - rémunération livreur
```

## Graphe des communes

Les communes voisines définies dans EasyAdmin ne servent plus seulement au message client. Elles deviennent la base d'un graphe logistique.

Chaque commune est un nœud :

```text
Dzaoudzi
Labattoir
Mamoudzou
Pamandzi
Koungou
...
```

Chaque voisinage direct est une arête :

```text
Dzaoudzi ↔ Labattoir
Labattoir ↔ Pamandzi
Mamoudzou ↔ Koungou
...
```

Le service devra trouver le chemin le plus court entre la commune vendeur et la commune client.

Pour le pilote, l'algorithme recommandé est :

```text
BFS = Breadth First Search = parcours en largeur
```

Pourquoi BFS suffit :

```text
Chaque traversée de commune vaut le même poids.
On cherche le plus petit nombre de sauts entre communes.
Il n'y a pas encore de distance kilométrique réelle ni de pondération trafic.
```

Dijkstra ou un calcul GPS sont reportés après pilote.

## Exemple métier validé

Cas :

```text
vendeur = Mamoudzou
client = Labattoir
```

Lecture :

```text
Mamoudzou = Grande-Terre
Labattoir = Petite-Terre
```

Donc :

```text
barge requise = oui
```

Calcul cible :

```text
frais livraison =
frais local Labattoir
+ barge aller-retour
+ supplément traversée Dzaoudzi → Labattoir
```

Si le point d'arrivée côté Petite-Terre est Dzaoudzi, le chemin simplifié devient :

```text
Mamoudzou
→ barge
→ Dzaoudzi
→ Labattoir
```

Le supplément de communes traversées correspond au nombre de sauts terrestres réellement nécessaires après ou avant la barge.

## Règle barge conservée

La nouvelle architecture ne change pas la règle fondamentale :

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Donc :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

La distance, le nombre de communes traversées ou le fait d'être non voisin ne déclenche jamais la barge.

## Réglages Hodina à ajouter

Le modèle avancé doit être administrable via `HodinaSetting`.

Réglages recommandés :

```text
delivery_commune_hop_customer_fee
delivery_commune_hop_courier_payout
delivery_barge_round_trip_customer_fee
delivery_barge_round_trip_courier_payout
```

Exemple de valeurs de test :

```text
delivery_commune_hop_customer_fee = 2.00
delivery_commune_hop_courier_payout = 1.50
delivery_barge_round_trip_customer_fee = 6.00
delivery_barge_round_trip_courier_payout = 4.00
```

## Données à préparer dans CartLogisticsPreview

Le DTO devra évoluer pour transporter plus que le simple message.

Champs utiles à prévoir :

```text
logisticsSignature
pathCommuneNames
hopCount
localCustomerFee
localCourierPayout
hopCustomerFee
hopCourierPayout
bargeCustomerFee
bargeCourierPayout
estimatedDeliveryFee
estimatedCourierPayout
estimatedDeliveryMargin
requiresBarge
relationLevel
warnings
```

## Données à figer plus tard dans CustomerOrder

Au checkout, les valeurs doivent être recalculées puis figées.

Champs de snapshot à prévoir :

```text
deliveryFee
courierPayout
deliveryMargin
requiresBarge
deliveryPricingZoneName
deliveryPricingZoneCode
clientDeliveryCommuneName
clientTerritory
logisticsLevel
logisticsPathSummary
logisticsHopCount
bargeCustomerFee
bargeCourierPayout
communeHopCustomerFee
communeHopCourierPayout
```

Pourquoi figer ?

```text
Une commande passée ne doit pas changer si l'admin modifie demain le prix de la barge,
le prix par commune traversée ou les relations de voisinage.
```

## Découpage technique recommandé

Ne pas mélanger toutes les évolutions dans un seul patch.

Découpage conseillé :

```text
J5G-A
→ aperçu logistique panier par périmètre vendeur
→ déjà réalisé

J5G-B
→ calcul de chemin entre communes dans DeliveryLogisticsService
→ BFS / nombre de communes traversées
→ pas encore de migration si on réutilise neighboringCommunes

J5G-C
→ réglages Hodina des suppléments
→ migration éventuelle si HodinaSetting doit être enrichi
→ seed des valeurs de test

J5G-D
→ affichage panier détaillé
→ frais local + supplément communes + barge

J5G-E
→ checkout
→ recalcul définitif
→ snapshot dans CustomerOrder
→ migration de snapshot
```

## Point pédagogique pour développeur débutant

Il faut distinguer trois notions :

```text
1. territoire
   → PT ou GT
   → sert à savoir si la barge est nécessaire

2. voisinage / chemin
   → communes voisines dans EasyAdmin
   → sert à calculer combien de communes sont traversées

3. tarification
   → montants configurés dans zones et réglages Hodina
   → sert à calculer frais client, rémunération livreur et marge Hodina
```

Erreur à éviter :

```text
Ne jamais confondre “commune éloignée” avec “barge”.
```


---

# Architecture J5G-B1 — Source voisinage validée et modèle base modifiable

## Pourquoi cette étape existe

Avant de coder le calcul de plus court chemin, Hodina doit disposer d'une donnée fiable et modifiable.

La donnée validée est :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

Cette source vient corriger et structurer le document initial de voisinage en une base exploitable par l'application.

## Critique du modèle “table de hashage”

Une table de hashage est pratique pour calculer vite en PHP, mais ce n'est pas une bonne structure de stockage Doctrine.

Architecture retenue :

```text
Doctrine / MariaDB
→ stocke les communes et liaisons en tables relationnelles

DeliveryLogisticsService
→ charge les données et construit une hash map temporaire pour le BFS
```

## Architecture cible

```text
DeliveryCommune
→ point logistique ou commune officielle

DeliveryCommuneConnection
→ liaison entre deux points logistiques
```

## DeliveryCommune

Cette entité doit représenter tous les points utiles à la livraison Hodina.

Elle peut représenter :

- une commune officielle ;
- un point de livraison terrain ;
- une localité utile mais rattachée administrativement à une commune.

Cas important :

```text
Labattoir
→ point logistique utile
→ rattaché administrativement à Dzaoudzi
```

## DeliveryCommuneConnection

Cette entité doit remplacer progressivement la simple idée de “communes voisines” par une liaison exploitable.

Champs métier clés :

```text
fromCommune
toCommune
linkType = LAND ou BARGE
isBidirectional
hopCount
isActive
internalNote
```

## Pourquoi un lien typé ?

Sans type de lien, le système ne sait pas distinguer :

```text
Dzaoudzi ↔ Labattoir = route terrestre
Dzaoudzi ↔ Mamoudzou = barge
```

Or les coûts client et livreur ne sont pas les mêmes.

## Utilisation dans DeliveryLogisticsService

Le service devra :

```text
1. charger les communes actives ;
2. charger les liaisons actives ;
3. construire une hash map ;
4. exécuter BFS ;
5. retourner le chemin ;
6. compter les liens LAND ;
7. compter les liens BARGE ;
8. préparer les données pour le panier puis le checkout.
```

## Exemple cible

```text
Vendeur : Mamoudzou
Client : Labattoir

Chemin : Mamoudzou → Dzaoudzi → Labattoir

Mamoudzou → Dzaoudzi = BARGE
Dzaoudzi → Labattoir = LAND
```

Résultat :

```text
requiresBarge = true
landHopCount = 1
bargeHopCount = 1
pathSummary = Mamoudzou → Dzaoudzi → Labattoir
```

## Règle d'architecture

Le fichier Excel est une source de seed, pas une dépendance runtime.

```text
Interdit : lire le fichier Excel à chaque calcul panier.
Autorisé : importer les données Excel en base, puis administrer via EasyAdmin.
```

---

# Architecture J5G-B2 / J5G-B3 — carte logistique modifiable

## Objectif architectural

J5G-B2 et J5G-B3 transforment la logique de livraison d'une simple classification locale / voisine / éloignée vers une vraie carte logistique administrable.

Avant :

```text
DeliveryCommune.neighboringCommunes
→ voisinage simple
→ pas de type LAND / BARGE
→ insuffisant pour calculer un trajet détaillé
```

Après J5G-B2 / J5G-B3 :

```text
DeliveryCommune
→ point logistique

DeliveryCommuneConnection
→ liaison typée entre deux points
→ LAND ou BARGE
→ bidirectionnelle ou non
→ active ou inactive
→ pondérable plus tard
```

## Entités concernées

### DeliveryCommune

`DeliveryCommune` reste la source des points géographiques utilisés par Hodina.

Champs ajoutés / confirmés :

- `slug` : identifiant stable pour seed/import ;
- `postalCode` : code postal principal ;
- `inseeCode` : code INSEE officiel quand disponible ;
- `parentInseeCode` : rattachement administratif pour un point terrain ;
- `isLogisticsPoint` : distingue un point exploitable logistique d'une simple donnée administrative.

Labattoir est volontairement traité comme point logistique Hodina et non comme commune INSEE autonome.

```text
Labattoir
postalCode = 97615
inseeCode = null
parentInseeCode = 97608
territory = PT
isLogisticsPoint = true
```

### DeliveryCommuneConnection

Nouvelle entité de lien logistique.

Champs :

- `fromCommune` ;
- `toCommune` ;
- `linkType` : `LAND` ou `BARGE` ;
- `isBidirectional` ;
- `hopCount` ;
- `customerExtraFee` nullable ;
- `courierExtraPayout` nullable ;
- `isActive` ;
- `internalNote` ;
- `createdAt` ;
- `updatedAt`.

Le choix `isBidirectional = true` permet de ne pas dupliquer les lignes en base pour les liens utilisables dans les deux sens. Le futur BFS devra ajouter le lien inverse en mémoire.

## EasyAdmin

J5G-B2 a aussi exposé les données dans EasyAdmin :

```text
Logistique → Communes livrées
Logistique → Liaisons logistiques
```

Cette décision est importante : les erreurs terrain pourront être corrigées depuis le backoffice sans redéploiement.

## Données seedées J5G-B3

J5G-B3 a seedé :

```text
18 points logistiques
23 liaisons logistiques
```

Répartition :

```text
22 LAND
1 BARGE : Dzaoudzi ↔ Mamoudzou
```

## Incidents techniques conservés

### Patch corrompu

Le patch correctif J5G-B2 initial a échoué avec :

```text
error: corrupt patch
```

Décision : ne pas forcer le patch, créer le fichier migration manuellement.

### UTF-8 BOM PowerShell

La création via `Set-Content -Encoding UTF8` a généré un BOM invisible.

Erreur :

```text
strict_types declaration must be the very first statement
```

Correction : réécriture du fichier en UTF-8 sans BOM.

### Déploiement recette

Une commande a été tapée avec `hp` au lieu de `php`, ce qui a empêché l'exécution des migrations. Le `schema:validate` rouge était donc logique : le code était à jour, pas la base.

### Seed affichant 0 SQL queries

En recette, la migration de seed J5G-B3 a affiché 0 SQL queries, mais les données ont été confirmées par requêtes SQL. La validation finale d'un seed doit toujours se faire sur les tables métier.

## Prochaine architecture J5G-B4

J5G-B4 doit faire évoluer `DeliveryLogisticsService`.

Responsabilité cible :

```text
DeliveryLogisticsService
→ reçoit commune client + vendeurs du panier
→ charge les liaisons actives
→ construit un graphe
→ calcule le plus court chemin
→ enrichit CartLogisticsPreview
```

Le service ne doit pas encore écrire dans `CustomerOrder`.

## J5G-SUPPORT-ADRESSES — distinction livraison / facturation et validation logistique (en cours de tests)

Ce support a été ouvert pendant les tests de J5G-B4, avant de valider complètement le calcul de trajet réel. La raison est fonctionnelle : le calcul logistique dépend de la qualité des adresses. Si une adresse client contient une commune fausse, une mauvaise zone ou une commune non livrable, le service de trajet ne peut pas produire un résultat fiable.

### Décision structurante

On distingue désormais explicitement deux usages d'adresse :

```text
Adresse de livraison
→ doit être livrable par Hodina
→ doit correspondre à une commune présente dans Logistique > Communes livrées
→ doit être cohérente avec sa zone PT ou GT
→ code postal français à 5 chiffres obligatoire

Adresse de facturation
→ peut être hors zone de livraison Hodina
→ peut être en métropole ou dans une autre zone française
→ code postal français à 5 chiffres obligatoire
→ zone dédiée : AUTRE — Autre
```

La zone ne doit pas s'appeler `OTHER` dans l'interface, car l'application est française. Le libellé retenu est `AUTRE — Autre`.

### Pourquoi cette décision a été prise

Pendant le test EasyAdmin utilisateur, une adresse de facturation pouvait devoir être une adresse métropole, par exemple Rennes / 35000. Cette adresse ne doit pas être rejetée au motif qu'elle n'est pas livrable à Mayotte. À l'inverse, une adresse de livraison hors commune livrable doit être refusée pour éviter de casser le calcul logistique.

### Modèle cible

`Address` porte un type métier :

```text
DELIVERY = adresse de livraison
BILLING  = adresse de facturation
```

Ce type permet de ne pas appliquer les mêmes règles à toutes les adresses.

### Validation métier

Un validateur d'adresse a été ajouté autour de `Address` :

```text
DeliverableAddress
DeliverableAddressValidator
DeliveryCommuneMatcherService
```

Le validateur s'appuie sur les données J5G-B2/B3 :

```text
delivery_commune
delivery_zone
```

Il refuse les cas suivants pour une livraison :

```text
commune inconnue dans la carte logistique
code postal incompatible avec la commune connue
zone PT/GT incohérente avec la commune
zone AUTRE utilisée pour une livraison
```

Il accepte les cas suivants pour une facturation :

```text
commune non livrable
zone AUTRE
code postal français valide à 5 chiffres
```

### Tests déjà réalisés

Depuis EasyAdmin utilisateur :

```text
Adresse de livraison correcte → OK
Adresse de livraison hors commune livrable → KO propre
Adresse mauvaise → KO propre, sans erreur 500
```

Exemple de KO propre observé :

```text
La commune "La Dominelais" avec le code postal "35390" n'est pas reconnue comme commune livrable Hodina.
```

### Tests encore à terminer

```text
Adresse de facturation hors Mayotte avec zone AUTRE
Adresse de facturation avec code postal invalide
Adresse de facturation avec zone PT/GT à refuser si elle est hors zone
Inscription client avec livraison + facturation
Checkout avec livraison + facturation
Vérifications SQL des adresses créées
Déploiement recette après validation locale
```

### Statut

```text
Statut : EN COURS DE TESTS
Ne pas considérer ce support comme validé tant que inscription + checkout + facturation AUTRE ne sont pas confirmés.
```


## Incidents rencontrés pendant J5G-SUPPORT-ADRESSES

### 1. Patch appliqué partiellement / classes manquantes

Un premier patch ajoutait des références vers :

```text
App\Service\DeliveryCommuneMatcherService
App\Validator\DeliverableAddress
App\Validator\DeliverableAddressValidator
```

Mais les fichiers n'étaient pas présents. Les contrôles Symfony passaient partiellement, mais le code aurait cassé à l'exécution. Décision : ne pas commiter tant que les classes référencées n'existent pas réellement.

### 2. Erreur PowerShell avec marqueurs `@'` / `'@`

Lors de la création manuelle des fichiers PHP, les marqueurs PowerShell de here-string se sont retrouvés dans les fichiers PHP. Erreur observée :

```text
syntax error, unexpected string content "@", expecting end of file
```

Correction : fournir les fichiers corrigés en ZIP, sans marqueurs PowerShell et en UTF-8 sans BOM.

### 3. Validation non déclenchée sur les adresses imbriquées EasyAdmin

Le validateur `Address` ne suffisait pas si l'adresse était créée à l'intérieur du formulaire `Customer`. Décision : ajouter `#[Assert\Valid]` sur les relations concernées dans `Customer` afin que Symfony valide les objets enfants.

### 4. Patchs qui ne s'appliquaient plus

Plusieurs patchs ont échoué car les fichiers avaient déjà changé :

```text
src/Entity/Address.php
src/Service/DeliveryCommuneMatcherService.php
src/Validator/DeliverableAddressValidator.php
```

Décision : lorsqu'un fichier a évolué plusieurs fois dans une même session, reprendre à partir d'un extrait réel des sources et générer un patch adapté à l'état courant.

### 5. Migration manquante après ajout du type d'adresse

Le code attendait une colonne `address.type`, mais la migration n'était pas présente dans un patch intermédiaire. Symptôme :

```text
Database schema is not in sync with the current mapping file.
```

Correction : migration dédiée pour ajouter `address.type` et la zone `AUTRE — Autre`.

### 6. Typed property non initialisée dans Address

Erreur observée en EasyAdmin :

```text
Typed property App\Entity\Address::$commune must not be accessed before initialization
```

Cause : EasyAdmin peut valider une adresse imbriquée avant que tous les champs texte ne soient renseignés. Correction : initialiser les champs texte et sécuriser le validateur pour afficher une erreur formulaire au lieu d'une erreur 500.


## J5G-B4 — branchement du service sur les liaisons réelles (démarré, non clôturé)

J5G-B4 a été démarré avec l'objectif de brancher `DeliveryLogisticsService` sur `DeliveryCommuneConnection`.

### Objectif technique

```text
DeliveryLogisticsService
→ lit les communes actives
→ lit les liaisons actives
→ construit une carte en mémoire
→ calcule un plus court chemin
→ détecte LAND / BARGE
→ enrichit CartLogisticsPreview
→ affiche un aperçu dans le panier
```

### Fichiers touchés par le patch J5G-B4 en cours

```text
src/Service/DeliveryLogisticsService.php
src/Dto/CartLogisticsPreview.php
templates/cart/index.html.twig
public/css/style_mobile.css
```

### Point d'arrêt volontaire

Les tests J5G-B4 ont révélé que la qualité des adresses devait être fiabilisée avant de poursuivre. Le support adresses a donc été priorisé avant de valider définitivement l'algorithme de trajet.

### Statut

```text
J5G-B4 : démarré
Validation finale : en attente des tests adresses complets
```

---

# Architecture — mise à jour 12/06/2026 — support adresses final

## Vue d'ensemble

Le modèle adresse devient un sous-système métier transverse, utilisé par :

```text
EasyAdmin
inscription
checkout
panier / aperçu logistique
future commande avec snapshot logistique
```

## Composants

### Address

Porte les données d'adresse et le type métier.

```text
DELIVERY
BILLING
```

### Customer

Porte :

```text
billingAddress
addresses
```

La collection `addresses` contient les adresses du client, qu'elles soient de livraison ou de facturation.

### DeliveryZone

Continue à représenter les zones historiques :

```text
PT
GT
AUTRE
```

### DeliveryCommune

Source métier pour savoir si une commune est livrable, sur quel territoire et avec quel code postal.

### DeliveryCommuneMatcherService

Service de correspondance entre saisie utilisateur et commune logistique.

Rôle :

```text
normaliser la commune saisie
vérifier le code postal
retrouver la commune livrable
préparer les messages métier
```

### DeliverableAddressValidator

Point central de validation métier de l'adresse.

Il évite de disperser les règles dans chaque formulaire.

## Architecture front

Les formulaires front ne doivent pas créer une logique métier différente d'EasyAdmin.

Ils doivent seulement :

```text
afficher les champs
conserver les valeurs
afficher les erreurs au bon endroit
envoyer les données au contrôleur
```

La décision métier reste côté contrôleur / validateur / service.

## Architecture checkout

Le checkout manipule deux objets métier potentiels :

```text
Address DELIVERY
Address BILLING
```

Cas facturation identique :

```text
l'adresse de livraison peut aussi servir de facturation
```

Cas facturation séparée :

```text
une adresse BILLING distincte est créée
```

Point critique corrigé :

```text
ne jamais écraser billingZone avec deliveryZone
```

## Architecture inscription

L'inscription crée un `Customer` puis ses adresses.

Elle doit bloquer l'e-mail existant avant création effective du compte.

## Architecture UX erreurs

La couche Twig / CSS affiche les erreurs sous les champs.

Classes attendues :

```text
client-form-field
client-field-error
client-field-input-error
```

Objectif :

```text
mobile-first
lisible
pas de liste à puces envahissante
valeurs conservées
```

## Architecture sécurité e-mail

Le contrôle e-mail existant est traité manuellement dans les contrôleurs.

Raison :

```text
message contextualisé checkout / inscription
pas de double message
pas de commande invitée ambiguë
```

## Incidence sur J5G-B4

J5G-B4 doit s'appuyer sur une adresse de livraison fiable.

```text
Customer / Address DELIVERY
→ commune client
→ DeliveryCommune
→ DeliveryLogisticsService
```

La facturation reste hors calcul logistique.

---

# Architecture — mise à jour 13/06/2026 — e-mail et préouverture

## Nouveau domaine : e-mails transactionnels

Architecture cible :

```text
CheckoutController / OrderWorkflowService
        ↓
OrderEmailService
        ↓
Symfony Mailer
        ↓
SMTP o2switch
        ↓
EmailLog
```

`OrderEmailService` devra construire l'e-mail, rendre le template Twig, envoyer via SMTP et journaliser le résultat. L'échec SMTP ne doit pas bloquer la commande.

Template initial prévu :

```text
templates/emails/order_created.html.twig
```

## Nouveau domaine : préouverture commerciale

Architecture cible :

```text
SalesOpeningSetting / HodinaSetting
        ↓
SalesOpeningService
        ↓
base.html.twig + templates catalogue
        ↓
CartController / CheckoutController
        ↓
blocage serveur avant ouverture
```

`SalesOpeningService` centralisera les méthodes :

```php
isCountdownEnabled(): bool
isSalesOpen(): bool
isCartLocked(): bool
getOpeningDate(): ?DateTimeImmutable
getCountdownViewData(): array
assertSalesOpen(): void
```

La bannière globale sera incluse dans `base.html.twig`, par exemple via `templates/partials/_sales_opening_countdown.html.twig`.

La capture d'e-mail s'appuiera sur une entité `LaunchSubscriber`.

Le blocage panier / commande doit exister en template et côté serveur.


---

# Architecture réelle — J5I livré le 13/06/2026

## Branche et commit

```text
branche : pilot/j5i-preouverture-countdown
commit  : 5bf3e0e feat: add J5I sales opening countdown and launch email capture
```

## Vue d'ensemble

La préouverture est maintenant portée par l'architecture suivante :

```text
hodina_setting
        ↓
SalesOpeningService
        ↓
SalesOpeningExtension Twig
        ↓
base.html.twig + templates/launch/_countdown_banner.html.twig
        ↓
CartController / CheckoutController
        ↓
blocage serveur avant ouverture
```

## Composants ajoutés

```text
src/Service/SalesOpeningService.php
src/Twig/SalesOpeningExtension.php
src/Entity/LaunchSubscriber.php
src/Controller/LaunchSubscriberController.php
src/Controller/Admin/LaunchSubscriberCrudController.php
src/Controller/Admin/SalesOpeningSettingsController.php
templates/launch/_countdown_banner.html.twig
```

## Pourquoi un service central

Le service `SalesOpeningService` évite de disperser la règle métier dans les templates.

Il doit répondre aux questions suivantes :

```text
Les ventes sont-elles ouvertes ?
Le compte à rebours doit-il s'afficher ?
Le panier doit-il être bloqué ?
Quelle est la date d'ouverture ?
Quels textes afficher dans la bannière ?
```

Un développeur débutant doit éviter d'ajouter des conditions directement dans plusieurs templates sans passer par ce service.

## Rôle de Twig

Twig affiche uniquement l'état calculé par le service.

Le template principal est :

```text
templates/launch/_countdown_banner.html.twig
```

Il est inclus depuis :

```text
templates/base.html.twig
```

## Rôle des contrôleurs

Les contrôleurs protègent les actions sensibles :

```text
CartController     → empêche l'ajout / modification panier avant ouverture
CheckoutController → empêche la création de commande avant ouverture
```

Même si un utilisateur force une URL ou un POST manuel, le serveur doit refuser tant que la préouverture bloque le panier.

## EasyAdmin

Deux entrées sont ajoutées :

```text
Réglages préouverture / ouverture des ventes
Abonnés ouverture
```

L'URL d'administration recette est :

```text
/ouegnewe
```

## Table `launch_subscriber`

La table sert uniquement aux visiteurs qui veulent être prévenus de l'ouverture des commandes.

Elle ne remplace pas `Customer`.

Champs constatés dans la migration J5I :

```text
id
email
source
ip_address
user_agent
created_at
```

Unicité :

```text
email unique
```

## Point technique à corriger avant production

La migration corrective `Version20260613094055` est antérieure à la migration qui crée `launch_subscriber`. En recette, cela a été contourné manuellement. Pour production, corriger l'ordre dans Git avant déploiement.

---

# Architecture J5J — CommerceAvailability via SalesOpeningService

## Service central

Le service `SalesOpeningService` garde son nom historique pour éviter un renommage risqué pendant le pilote, mais son rôle est désormais plus large.

Il pilote :

```text
- le mode commerce ;
- l'affichage de la bannière ;
- le blocage public du panier ;
- le droit de contournement pour ROLE_COMMERCE_TESTER et ROLE_ADMIN ;
- les textes de bannière ;
- la date de réactivation.
```

## Extension Twig

`SalesOpeningExtension` expose l'état commerce aux templates via :

```twig
hodina_commerce_state()
hodina_sales_opening_state() // compatibilité historique
```

## Backoffice

`HodinaSettingCrudController` adapte le champ de saisie selon `field_type` :

```text
boolean  → switch EasyAdmin
choice   → liste de choix
textarea → zone texte longue
text     → champ texte court
```

`CustomerCrudController` expose les rôles sous forme de choix, dont `ROLE_COMMERCE_TESTER`.

## Sécurité

Le blocage du panier et du checkout reste appliqué côté contrôleur. Les boutons Twig ne sont qu'un confort d'affichage.
---

# Remise à plat production — 14/15 juin 2026

## Contexte

La production historique n'était pas iso préproduction. Le domaine `hodina.fr` pointait vers la racine du projet `~/hodina.fr` au lieu de pointer vers `~/hodina.fr/public`. Le dossier de production n'était pas non plus un dépôt Git exploitable : `git status` retournait que le dossier n'était pas un dépôt.

Cette situation exposait un risque structurel : une application Symfony doit publier uniquement le dossier `public/`. La racine du projet contient des fichiers sensibles ou techniques (`.env`, `composer.json`, `src/`, `config/`, `migrations/`, `vendor/`) qui ne doivent pas être accessibles via le web.

## Décision prise

La décision retenue a été de ne pas bricoler l'ancienne production. La production a été remise à plat proprement :

```text
- sauvegarde complète de l'ancien dossier production ;
- correction du DocumentRoot o2switch vers /public ;
- remplacement de l'ancien dossier par un vrai clone Git ;
- déploiement de la branche J5J ;
- remplacement de la base production par un dump de recette ;
- nettoyage des données de test ;
- maintien du mode commerce en préouverture ;
- sécurisation HTTPS via public/.htaccess ;
- retrait de .env.local du suivi Git ;
- rotation des mots de passe et secrets après exposition accidentelle dans le terminal.
```

## Résultat validé

```text
https://hodina.fr/ fonctionne en HTTP 200.
http://hodina.fr/ redirige en 301 vers https://hodina.fr/.
http://www.hodina.fr/ redirige en 301 vers https://www.hodina.fr/.
.env.local est présent sur le serveur mais retiré de Git.
Doctrine migrations est à jour.
Doctrine schema validate est OK.
Le mode commerce est configuré en preopening.
Les commandes, items, logs SMS et adresses de test ont été nettoyés.
```

## Commandes et actions réalisées

### Sauvegarde fichiers production

```bash
cd ~
tar -czf backup_hodina_prod_files_$(date +%Y%m%d_%H%M%S).tar.gz hodina.fr
cp ~/hodina.fr/.htaccess ~/backup_htaccess_prod_root_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
cp ~/hodina.fr/public/.htaccess ~/backup_htaccess_prod_public_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
```

### Correction hébergement o2switch

Le DocumentRoot du domaine `hodina.fr` a été corrigé dans o2switch pour pointer vers :

```text
/home/vopu3712/hodina.fr/public
```

### Ancienne production conservée

```bash
cd ~
mv hodina.fr hodina.fr_old_$(date +%Y%m%d_%H%M%S)
```

Ancien dossier conservé observé :

```text
/home/vopu3712/hodina.fr_old_20260614_071905
```

### Nouveau clone Git production

```bash
git clone https://github.com/chahere/hodina.git hodina.fr
cd hodina.fr
git checkout pilot/j5j-commerce-mode-role-tester
```

### Récupération configuration production

```bash
cp ~/hodina.fr_old_20260614_071905/.env.local ~/hodina.fr/.env.local
cp ~/hodina.fr_old_20260614_071905/public/.htaccess ~/hodina.fr/public/.htaccess 2>/dev/null || true
```

### Installation production

```bash
composer install --no-dev --optimize-autoloader
```

Cette commande a modifié le dossier `vendor/` parce que le dépôt suivait encore certaines dépendances. Pour éviter de polluer les futurs pulls, `vendor/` a ensuite été restauré côté Git avec :

```bash
git restore vendor
```

## Base de données production

### Décision

La base production existante était désalignée avec l'historique Doctrine. Plutôt que de baseliner migration par migration, la décision a été de remplacer la base production par un dump de la recette, puis de nettoyer les données de test.

Cette option était la plus propre car la recette était déjà validée avec J5J.

### Sauvegarde production

Un backup de la base production a été créé avant remplacement :

```text
backup_prod_before_preprod_restore_20260614_073456.sql
```

### Dump recette

Un dump recette a été créé et vérifié :

```text
dump_recette_for_prod_20260614_074824.sql
Taille observée : 161K
```

### Import recette vers production

La base production a été vidée puis alimentée avec le dump recette. Après import, les tables attendues étaient présentes :

```text
address
category
customer
customer_order
customer_signup
delivery_commune
delivery_commune_connection
delivery_commune_neighbor
delivery_pricing_zone
delivery_zone
doctrine_migration_versions
hodina_setting
launch_subscriber
messenger_messages
order_item
product
product_image
seller
sms_log
```

### Correction MariaDB / Doctrine

La version MariaDB production observée est :

```text
11.4.12-MariaDB
```

Le `DATABASE_URL` production a été ajusté pour utiliser :

```text
serverVersion=mariadb-11.4.12&charset=utf8mb4
```

Les mots de passe et secrets ont ensuite été mis à jour. Aucun secret ne doit être stocké dans Git ou dans la documentation.

### Validation Doctrine après import

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --dump-sql
```

Résultat validé :

```text
Migrations exécutées : 27
Version courante : DoctrineMigrations\Version20260613130000
Nouvelle migration : 0
Mapping files are correct.
Database schema is in sync with the mapping files.
Nothing to update.
```

## Mode commerce production

La production a été forcée en mode préouverture :

```bash
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = 'preopening' WHERE setting_key = 'commerce_mode'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_cart_locked'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_allow_testers'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_email_capture_enabled'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '2026-06-30 18:00' WHERE setting_key = 'commerce_reopens_at'"
php bin/console cache:clear --env=prod
```

Valeurs validées :

```text
commerce_allow_testers = 1
commerce_cart_locked = 1
commerce_email_capture_enabled = 1
commerce_mode = preopening
commerce_reopens_at = 2026-06-30 18:00
```

## Nettoyage des données de test importées depuis recette

Volumes avant nettoyage :

```text
customer_order = 9
order_item = 16
launch_subscriber = 0
sms_log = 25
```

Nettoyage réalisé :

```bash
php bin/console dbal:run-sql "DELETE FROM sms_log"
php bin/console dbal:run-sql "DELETE FROM order_item"
php bin/console dbal:run-sql "DELETE FROM customer_order"
php bin/console dbal:run-sql "DELETE FROM address"
php bin/console cache:clear --env=prod
```

Résultat validé :

```text
customer_order = 0
order_item = 0
sms_log = 0
address = 0
```

Les comptes clients n'ont pas été supprimés automatiquement afin de conserver les comptes utiles admin, livreur et testeur.

## HTTPS production

La redirection HTTP vers HTTPS a été ajoutée dans :

```text
public/.htaccess
```

Règle appliquée :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Force HTTPS - o2switch / cPanel
    RewriteCond %{HTTP:X-Forwarded-Proto} !https
    RewriteCond %{HTTPS} !on
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Symfony front controller
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

Tests validés :

```text
http://hodina.fr/      → 301 vers https://hodina.fr/
https://hodina.fr/     → 200
http://www.hodina.fr/  → 301 vers https://www.hodina.fr/
https://www.hodina.fr/ → 200
```

Décision restante : choisir plus tard l'URL canonique entre `hodina.fr` et `www.hodina.fr`. Recommandation actuelle : `https://hodina.fr`.

## Git production

Commit créé depuis la production pour versionner la règle HTTPS et ignorer les fichiers locaux sensibles :

```text
028b7e5 chore: force HTTPS and ignore local production files
```

Actions réalisées :

```bash
git config user.name "chahere"
git config user.email "abdamayot@hotmail.fr"
git add .gitignore public/.htaccess
git commit -m "chore: force HTTPS and ignore local production files"
git push
```

`.env.local` a été retiré du suivi Git mais reste présent sur le serveur. Il doit rester hors dépôt.

Permission recommandée :

```bash
chmod 600 .env.local
```

## État final production

```text
Production remise à plat : OK
Production sous Git : OK
DocumentRoot vers /public : OK
Base production alignée avec recette : OK
Doctrine migrations : OK
Doctrine schema : OK
J5J mode commerce : OK
Mode preopening : OK
HTTPS forcé : OK
Données de test principales nettoyées : OK
Secrets et mots de passe mis à jour : OK
```

## Procédure de déploiement production à partir de maintenant

La production étant désormais un vrai clone Git, les prochains déploiements doivent suivre cette procédure :

```bash
cd ~/hodina.fr
git status --short -- . ':!vendor'
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
curl -I http://hodina.fr/
curl -I https://hodina.fr/
```

Important : ne jamais committer `.env.local`, les dumps SQL, les backups `.htaccess`, ni les modifications de `vendor/` générées par `composer install --no-dev`.

---

# Architecture J5H-A — e-mails transactionnels

## Composants ajoutés

```text
src/Entity/EmailLog.php
src/Repository/EmailLogRepository.php
src/Service/OrderEmailService.php
src/Controller/Admin/EmailLogCrudController.php
templates/emails/order_created.html.twig
migrations/Version20260615140801.php
```

## Flux technique

```text
CheckoutController
→ CustomerOrder + OrderItem
→ flush Doctrine
→ OrderEmailService
→ DBAL email_log PENDING
→ DBAL snapshot order_item + product
→ TemplatedEmail
→ Symfony Mailer
→ Messenger async
→ Cron messenger:consume
→ SMTP o2switch
```

## Pourquoi DBAL dans OrderEmailService

DBAL est utilisé pour la journalisation et le snapshot des articles afin de rendre l'e-mail robuste :

- éviter un `EntityManager closed` qui bloquerait le log ;
- éviter de dépendre de collections Doctrine inverses non hydratées ;
- rendre le message envoyé par Messenger indépendant des entités détachées ;
- sécuriser le checkout : la commande reste créée même si l'e-mail échoue.

## EasyAdmin

`EmailLogCrudController` est consultatif :

- création désactivée ;
- modification désactivée ;
- suppression désactivée ;
- affichage de la commande, du client, du destinataire, du sujet, de l'événement, du statut, de l'erreur éventuelle ;
- bouton `Envoyer manuellement`.

## SMTP / Messenger

Le serveur SMTP o2switch utilisé en recette :

```text
mail.hodina.fr:465
SSL/TLS
utilisateur contact@hodina.fr
```

Le worker Messenger est géré par cron en recette :

```bash
* * * * * cd /home/vopu3712/recette.hodina.fr && mkdir -p var/log && flock -n /tmp/hodina_recette_messenger.lock /usr/local/bin/php bin/console messenger:consume async --env=prod --limit=10 --time-limit=50 --memory-limit=128M --no-interaction >> /home/vopu3712/recette.hodina.fr/var/log/messenger_cron.log 2>&1
```

## Limite connue acceptée pour le pilote

Le statut `SENT` de `EmailLog` signifie actuellement que l'e-mail a été accepté par Symfony Mailer / Messenger. Le vrai envoi SMTP intervient ensuite via le worker. Comme le cron est validé, cette limite est acceptée pour le pilote.

---

# Architecture J5G-E0 — Snapshot adresse dans CustomerOrder

## Problème d'architecture corrigé

Une commande ne doit pas dépendre d'une entité `Address` vivante. L'entité `Address` appartient au compte client et représente un carnet modifiable. L'entité `CustomerOrder` représente un historique métier.

J5G-E0 introduit donc des champs de snapshot dans `CustomerOrder`.

## Nouveau modèle

`CustomerOrder` conserve deux familles de champs :

```text
delivery_address_*  → adresse de livraison figée
billing_address_*   → adresse de facturation figée
```

La relation historique `deliveryAddress` reste présente mais devient facultative. La base autorise désormais la suppression de l'adresse liée sans suppression de la commande grâce à `ON DELETE SET NULL`.

## Conséquence pour le code

Les couches suivantes doivent utiliser les getters snapshot de `CustomerOrder` :

- admin commandes ;
- fiche opérationnelle ;
- portail livreur ;
- e-mails transactionnels ;
- exports futurs.

La logique de panier continue d'utiliser l'adresse client vivante pour estimer la logistique avant commande. Une fois la commande créée, les valeurs doivent être figées dans `CustomerOrder`.

## Anti-doublon

Le checkout tente de réutiliser une adresse identique déjà existante dans le carnet client avant de créer une nouvelle adresse. Cela réduit les doublons futurs sans faire de nettoyage destructif sur les anciennes données.

---

# Architecture J5G-E1 — Commune livrée comme source de vérité UX

## Constat de l'extrait code du 16/06/2026

Le checkout dispose déjà de briques backend utiles :

- `DeliveryCommune` représente les communes / points logistiques livrables ;
- `DeliveryCommuneMatcherService` sait résoudre une commune et retrouver la zone ;
- `CheckoutController` utilise déjà ce service pour valider la saisie ;
- `CustomerOrder` possède désormais des snapshots adresse grâce à J5G-E0.

La friction restante est surtout UX : `CheckoutType` expose encore `postalCode`, `commune` et `zone` comme champs manuels.

## Principe d'architecture

La couche UI doit aider la saisie, mais la couche serveur conserve l'autorité.

```text
UI : choix de commune, préremplissage code postal, affichage zone
Backend : validation, résolution DeliveryCommune, calcul DeliveryZone, snapshot commande
```

## Recommandation technique

Pour le MVP, il n'est pas obligatoire d'ajouter une relation `Address -> DeliveryCommune`. La correction peut rester compatible avec le modèle actuel :

```text
Address.commune      = nom de la commune choisie
Address.postalCode   = code postal de DeliveryCommune
Address.deliveryZone = DeliveryZone déduite
```

Une relation explicite `Address.deliveryCommune` pourra être étudiée plus tard si l'on veut des référentiels plus stricts ou des exports avancés.

## À ne pas faire

- Ne pas créer un deuxième référentiel de communes côté front.
- Ne pas coder une table statique JS déconnectée d'EasyAdmin.
- Ne pas laisser la zone client écraser la zone serveur.
- Ne pas mélanger adresse de livraison et adresse de facturation.

---

# Architecture J5G-E1 → J5G-E2-bis-A — Livraison dans le panier

## Vue d'ensemble

J5G-E1 à E2-bis-A ne crée pas une nouvelle architecture logistique. Le jalon branche mieux les composants existants.

Composants réutilisés :

```text
DeliveryCommune
DeliveryCommuneMatcherService
DeliveryLogisticsService
CartController
CheckoutController
CheckoutType
```

## Responsabilités

### `DeliveryCommune`

Référentiel admin des communes / points logistiques livrables. Il porte notamment :

```text
nom
code postal
territoire PT / GT
zone tarifaire locale
liaisons logistiques
```

### `DeliveryCommuneMatcherService`

Résout la commune choisie ou saisie et retrouve la zone cohérente. Il évite que le contrôleur interprète directement des chaînes libres.

### `DeliveryLogisticsService`

Calcule l'aperçu logistique :

```text
commune client
vendeurs uniques du panier
barge requise ou non
chemin affiché
frais livraison
avertissements
```

### `CartController`

Porte désormais l'endpoint AJAX :

```text
POST /panier/logistique/apercu
```

Il sert au recalcul dynamique quand le client change d'adresse ou de commune.

### `CheckoutController`

Conserve la création de commande, mais le parcours utilisateur visible se fait depuis le panier pendant le paiement manuel.

Il reste responsable de :

```text
recalcul serveur
vérification signature panier/adresse/frais
création CustomerOrder
création OrderItem
snapshot adresse
redirection confirmation
```

## Signature de calcul livraison

Une signature est utilisée pour vérifier que le total validé est bien celui vu par le client.

Elle dépend notamment de :

```text
adresse / commune livrée
panier
vendeurs
frais livraison
total estimé
```

Si la signature ne correspond plus, la commande est refusée et le client revient au panier.

## Règle tarifaire temporaire

Avant J5G-B4 :

```text
frais livraison = forfait local DeliveryCommune.localPricingZone + supplément BARGE éventuel
```

Le champ `bargePricingZone` reste historique / compatibilité. Pendant le pilote actuel, il ne remplace pas le forfait local.

## Ce que J5G-B4 devra ajouter

J5G-B4 devra travailler sur le graphe `DeliveryCommuneConnection` :

```text
nœuds = DeliveryCommune
arêtes = DeliveryCommuneConnection
algorithme = BFS / plus court chemin
coûts futurs = traversées terrestres + barge
```

Il ne doit pas reprendre le formulaire adresse ni recréer le calcul PT / GT.

---

# Architecture déployée production — J5G-E1 → J5G-E2-bis-A

## État de référence production

Tag : `j5g-e1-e2bis-prod`
Branche : `pilot/j5j-commerce-mode-role-tester`
Commit docs final : `36cc357`

La production utilise maintenant l'architecture suivante pour le panier :

```text
CartController
→ construit le panier détaillé
→ expose /panier/logistique/apercu
→ calcule / stocke la signature de prévisualisation livraison

CheckoutController
→ reçoit la validation
→ recalcule adresse / commune / frais / total
→ compare avec la signature
→ crée la commande uniquement si tout est cohérent
```

## Base de données production

La production est migrée jusqu'à :

```text
DoctrineMigrations\Version20260615225836
```

Le schema production est synchronisé avec le mapping Doctrine.

## Règle d'architecture figée

Ne pas déplacer le calcul de livraison dans Twig ou JavaScript.

Le JavaScript ne fait qu'appeler le backend et afficher la réponse. Le backend reste source de vérité pour :

```text
commune
code postal
zone
barge
frais
total
création commande
```

## Dette technique non bloquante

À traiter plus tard :

```text
doctrine.orm.controller_resolver.auto_mapping deprecated
DashboardController EasyAdmin sans #[AdminDashboard]
Doctrine migration implicit commit deprecation
```

Ces points ne changent pas l'architecture métier J5G.

# Architecture J5G-B4 — Calcul logistique réel et snapshot

## Position dans l'architecture

J5G-B4 transforme `DeliveryLogisticsService` en moteur central de calcul logistique basé sur le graphe Doctrine existant.

```text
DeliveryCommune
  commune livrée / commune de collecte

DeliveryCommuneConnection
  liaison entre communes
  type LAND ou BARGE
  coûts spécifiques éventuels
  bidirectionnalité éventuelle

DeliveryLogisticsService
  construit le graphe
  exécute BFS
  calcule frais client / estimation livreur
  prépare les détails panier / admin

CartLogisticsPreview
  DTO d'affichage panier
  nombre vendeurs
  communes de collecte distinctes
  liaisons LAND / BARGE
  frais estimés

CustomerOrder.deliveryLogisticsSnapshot
  JSON figé pour analyse ultérieure
```

## Données persistées

`CustomerOrder` contient maintenant :

```text
deliveryLogisticsSnapshot : JSON nullable
```

Ce champ ne remplace pas les champs métier historiques de la commande. Il complète l'historique avec un détail exploitable.

## Settings lus par le service

```text
global_commune_crossing_customer_fee
global_commune_crossing_courier_payout
global_delivery_customer_fee_cap
global_multi_seller_extra_customer_fee
global_multi_seller_extra_customer_fee_cap
```

## Algorithme

BFS est adapté au pilote car la carte est une carte de voisinage et le besoin est de trouver le plus petit nombre de sauts entre communes.

Le coût final n'est pas utilisé comme poids d'exploration dans J5G-B4. Le chemin est d'abord trouvé, puis les coûts sont calculés sur les hops obtenus.

## Limite assumée

J5G-B4 ne résout pas encore le problème du voyageur de commerce / tournée optimale.

En panier multicommunes, il retient le trajet de collecte le plus contraignant, puis ajoute un supplément paramétrable.

Cette limite est volontaire pour garder un modèle compréhensible et exploitable pendant le pilote.

# Architecture DevOps — scripts `tools` et déploiement par tag

## Objectif

Le projet Hodina adopte une architecture de mise en production plus robuste : le code applicatif n'est plus déployé directement depuis une branche mouvante, mais depuis un **tag Git créé depuis `main`**.

Flux cible :

```text
branche feature / pilot
→ tests locaux
→ merge dans branche pilote si nécessaire
→ merge dans main
→ tag Git depuis main
→ déploiement recette par tag
→ validation recette
→ déploiement production par tag
```

Cette stratégie prépare un futur CI/CD tout en restant utilisable manuellement sur o2switch.

## Dossier `tools`

Le dossier `tools/` contient les scripts opérationnels versionnés avec le projet :

```text
tools/deploy-hodina-by-tag.sh
tools/reset-commandes-hodina.ps1
```

### `tools/deploy-hodina-by-tag.sh`

Script Bash à utiliser sur o2switch pour recette et production.

Responsabilités :

- contrôler que le dossier projet est correct ;
- contrôler que le dépôt Git est propre ;
- accepter uniquement un tag existant ;
- vérifier que le tag est contenu dans `origin/main` ;
- imposer par défaut un remote Git SSH ;
- protéger `.env.local`, `.env.prod.local` et `prod.env.local` avant checkout ;
- restaurer les fichiers d'environnement locaux après checkout si Git les supprime ;
- faire un backup DB si possible avant migration ;
- lancer les migrations Doctrine ;
- vider le cache Symfony en environnement `prod` ;
- vérifier le schéma Doctrine ;
- ajouter ou vérifier le cron Messenger ;
- permettre un nettoyage optionnel des anciennes commandes ;
- fournir des messages d'erreur explicites en cas d'échec.

Variables prévues pour usage manuel ou CI/CD :

```text
RUN_COMPOSER=1
RESET_COMMANDS=1
SKIP_BACKUP=1
ASSUME_YES=1
DRY_RUN=1
ENFORCE_SSH=0
PHP_BIN=/chemin/vers/php
```

### `tools/reset-commandes-hodina.ps1`

Script PowerShell pour environnement local/dev Windows.

Responsabilités :

- compter les commandes, lignes de commande, SMS et emails liés ;
- demander confirmation explicite ;
- supprimer les `sms_log` liés aux commandes ;
- supprimer les `email_log` liés aux commandes ;
- supprimer les `order_item` ;
- supprimer les `customer_order` ;
- optionnellement réinitialiser les auto-increments ;
- afficher les compteurs après suppression.

Options :

```powershell
-ResetIds
-AssumeYes
-DryRun
-PhpBin "php"
```

## Gestion des fichiers d'environnement

Incident observé pendant la fusion de la branche pilote dans `main` : `.env.local` a été supprimé par Git, ce qui a provoqué une erreur Doctrine :

```text
could not find driver
```

La cause n'était pas le code métier, mais la perte du fichier d'environnement local contenant la bonne configuration `DATABASE_URL`.

Décision : le script de déploiement protège désormais automatiquement les fichiers :

```text
.env.local
.env.prod.local
prod.env.local
```

Ils sont sauvegardés dans :

```text
var/deploy_env_backup/<timestamp>/
```

puis restaurés après checkout du tag si nécessaire.

À terme, ces fichiers ne doivent pas être suivis par Git. Ils doivent rester propres à chaque environnement.

## Cron Messenger

Le script ajoute ou vérifie une tâche cron Messenger selon la cible.

Recette :

```bash
* * * * * cd /home/vopu3712/recette.hodina.fr && mkdir -p var/log && flock -n /tmp/hodina_recette_messenger.lock /usr/local/bin/php bin/console messenger:consume async --env=prod --limit=10 --time-limit=50 --memory-limit=128M --no-interaction >> /home/vopu3712/recette.hodina.fr/var/log/messenger_cron.log 2>&1
```

Production :

```bash
* * * * * cd /home/vopu3712/hodina.fr && mkdir -p var/log && flock -n /tmp/hodina_prod_messenger.lock /usr/local/bin/php bin/console messenger:consume async --env=prod --limit=10 --time-limit=50 --memory-limit=128M --no-interaction >> /home/vopu3712/hodina.fr/var/log/messenger_cron.log 2>&1
```

## Point d'attention architecture

Le merge dans `main` a révélé un historique technique lourd : anciens caches `var/cache/dev`, logs et fichiers de sauvegarde ont existé dans l'historique. À ne pas reproduire.

Règle d'architecture :

```text
Le code, les migrations, les templates, les docs et les scripts tools sont versionnés.
Les caches, logs, secrets et fichiers d'environnement locaux ne doivent pas être versionnés.
```

## Architecture opérationnelle — Déploiement par tag v7

Hodina dispose maintenant d'un outillage d'exploitation minimal mais robuste :

```text
GitHub main + tags
        ↓
serveur recette / production
        ↓
tools/deploy-hodina-by-tag.sh extrait depuis le tag
        ↓
backup DB + protection runtime + checkout + migrations + cache + cron
```

### Séparation code / runtime

Code versionné :

```text
src, templates, config, migrations, assets, tools, docs
```

Runtime à protéger :

```text
.env.local
.env.prod.local
prod.env.local
public/uploads/products
var/backups
var/deploy_env_backup
var/deploy_runtime_backup
var/log
var/cache
```

### Binaire DB

Le script résout automatiquement `mariadb-dump` et le préfère à `mysqldump`.

### Cache

La stratégie prod est :

```text
cache:clear --env=prod --no-warmup
cache:warmup --env=prod
```

### Cron Messenger

Deux locks séparés :

```text
/tmp/hodina_recette_messenger.lock
/tmp/hodina_prod_messenger.lock
```

# Architecture — état v11 validé et socle des prochains jalons

Date : **19/06/2026**
Tag de référence : `j5g-b4-20260618-v11`

## Front client — Ajax panier

L'ajout panier fonctionne maintenant en double mode :

```text
POST classique = fallback robuste
POST Ajax = UX fluide sans rechargement
```

Route concernée :

```text
POST /panier/ajouter/{id}
```

Contrôleur :

```text
src/Controller/CartController.php
```

Règles :

- Si le panier est verrouillé par la préouverture, la réponse Ajax renvoie `HTTP 423 Locked` avec un message JSON.
- Si le produit est absent ou inactif, la réponse Ajax renvoie `HTTP 404` avec un message JSON.
- Si tout est OK, la réponse renvoie `ok`, `message`, `cartCount`, `productId`, `qtyAdded`, `cartUrl`.
- Les caches logistiques panier sont invalidés après ajout produit.
- Le panier serveur reste la source de vérité.

Templates concernés :

```text
templates/base.html.twig
templates/product/catalogue.html.twig
templates/product/show.html.twig
```

## Backoffice — menu EasyAdmin mobile

Entrée JS admin :

```text
assets/admin.js
```

Rôle :

- démarrer Stimulus EasyAdmin ;
- enregistrer les contrôleurs `stock` et `product-images` ;
- ajouter le comportement de menu repliable ;
- mémoriser l'état des sections dans `localStorage` ;
- replier par défaut sur mobile les sections non actives ;
- conserver les vrais liens EasyAdmin cliquables.

Cas corrigé : section `Utilisateurs` + item `Utilisateurs`.

## AssetMapper en production

Le projet utilise Symfony AssetMapper. En production, les fichiers ne suffisent pas à être connus par `debug:asset-map` : ils doivent être compilés dans `public/assets`.

Le script MEP exécute maintenant :

```bash
php bin/console asset-map:compile --env=prod
```

`public/assets` est un dossier généré et non versionné.

## Runtime serveur

Données runtime à ne pas supprimer par Git :

```text
.env.local
.env.prod.local
prod.env.local
public/uploads/products
public/assets
```

Différence importante :

- `public/uploads/products` = données métier à conserver ;
- `public/assets` = sortie de build à régénérer.

## Mailer

Le service e-mail commande existe :

```text
src/Service/OrderEmailService.php
src/Entity/EmailLog.php
config/packages/mailer.yaml
config/packages/messenger.yaml
```

Le transport mail est routé via Messenger :

```text
Symfony\Component\Mailer\Messenger\SendEmailMessage: async
```

Mais si `MAILER_DSN=null://null`, aucun mail réel ne part. Le secret SMTP doit être configuré dans `.env.local` côté serveur.

## Prochains jalons architecturaux

### J5K GPS livraison

Ne pas créer une nouvelle entité si `Address` suffit. Le GPS est un enrichissement optionnel de l'adresse :

```text
Address.latitude
Address.longitude
CustomerOrder.deliveryAddressLatitude snapshot
CustomerOrder.deliveryAddressLongitude snapshot
```

La commune livrée reste la source logistique principale.

### J5L Admin terrain

Réutiliser `CustomerOrder`, `deliveryLogisticsSnapshot`, `Address`, `SmsLog`, `EmailLog` et les statuts existants.

### J5M Livreur

Réutiliser le portail livreur existant au lieu de le recréer.


## Architecture runtime pré-J5K — env, uploads, assets, mailer

Avant J5K, le projet distingue explicitement quatre familles de fichiers :

```text
1. Secrets environnement : .env.local / .env.prod.local / prod.env.local
2. Données métier runtime : public/uploads/products
3. Sorties générées : public/assets
4. Logs serveur : public/error_log
```

Règle Git :

```text
.env.local, .env.prod.local, prod.env.local : jamais suivis
public/uploads/products : seul .gitkeep suivi
public/assets : jamais suivi, régénéré par AssetMapper
public/error_log : jamais suivi
```

Le mailer reste branché sur l'environnement Symfony :

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

`MAILER_DSN=null://null` est une valeur de sécurité, pas une preuve d'envoi. La preuve d'envoi réelle combine configuration SMTP serveur et réception effective.


---

# Note architecture — 20/06/2026 — J5K-v8-quater clôturé recette

Le panier J5K-v8-quater est validé en recette. Les règles d’architecture suivantes sont confirmées :

```text
Address = carnet d’adresses vivant
CustomerOrder = snapshot figé de la commande
Livraison = terrain, GPS facultatif, instructions livreur
Facturation = administratif, sans GPS ni instructions livreur
Commune livrée = source de vérité logistique
GPS = aide terrain facultative
```

La référence recette finale est `devops-deploy-composer-before-console-v2`.

Le prochain chantier fonctionnel est `J5L-A — UX panier mobile PWA`, sans modification des règles métier J5K.

---

# Note architecture — 21/06/2026 — J5L panier mobile et sélecteur compact

## Principe

J5L améliore l'expérience panier sans modifier l'architecture métier validée en J5K.

La logique de calcul, les snapshots, les entités et les migrations restent inchangés.

## Panier client

Le panier devient un écran de décision mobile :

```text
articles
→ total / frais
→ livraison
→ facturation
→ validation
```

Les informations techniques nécessaires à l'exploitation restent côté admin.

## Sélecteur d'adresses

Les adresses enregistrées ne sont plus toutes affichées dans le flux principal du panier.

Architecture front retenue :

```text
adresse utilisée visible dans le panier
bouton Changer
panneau compact de sélection
liste interne scrollable
confirmation explicite via Utiliser cette adresse
```

Ce choix évite de rendre le panier dépendant du nombre d'adresses enregistrées.

## GPS côté livraison

Le GPS reste une donnée de livraison.

Côté front, un champ texte permet la saisie ou la récupération automatique.

Côté métier, les champs GPS existants restent ceux issus de J5K.

## Facturation admin

La fiche terrain admin et le détail EasyAdmin affichent désormais l'adresse de facturation snapshotée.

Cela prépare la suite paiement / facturation sans modifier le stockage.

## Préparation J5M

Le workflow livreur enrichi doit rester centralisé dans le service métier existant.

À éviter : multiplier les transitions directement dans les contrôleurs.

Statuts cibles :

```text
ready_for_delivery
picked_up
out_for_delivery
delivered
```

Le nom du livreur doit être associé à la commande, pas concaténé dans le statut.


---

# Architecture J5M-C2/C3 — Collecte vendeur et commune logistique

## Séparation des responsabilités

```text
Address
→ adresse terrain, instructions, GPS.

Seller.pickupAddress
→ point de retrait utilisé par le livreur.

Seller.deliveryCommune
→ commune logistique normalisée utilisée par DeliveryLogisticsService.

DeliveryCommune
→ référentiel seedé des communes de Mayotte, code postal, territoire et zone.
```

Cette séparation évite de mélanger précision terrain et calcul tarifaire.

## Service de synchronisation

`SellerPickupLogisticsSynchronizer` centralise la règle :

```text
DeliveryCommune sélectionnée
→ Address.commune
→ Address.postalCode
→ Seller.deliveryCommune
→ Seller.deliveryZone
→ Seller.commune legacy
```

## Garde-fou architecture

`tools/assert-delivery-logistics-commune-source.php` protège `DeliveryLogisticsService`.

Le service logistique doit rester dépendant de `Seller.deliveryCommune`, pas de `Seller.pickupAddress`.

---

# Architecture — 24/06/2026 — J5O/J5P/J5Q validés recette

## Vue d'ensemble

Les lots J5O-A, J5P-A et J5Q-A complètent le portail Djama et l'exploitation terrain :

```text
Djama
→ collecte vendeur sécurisée
→ démarrage livraison
→ code réception client chiffré
→ validation livraison
→ notifications client
→ historique rémunération livreur
```

## J5O-A — Code de réception client

Service :

```text
src/Service/CustomerDeliveryCodeService.php
```

Rôle : générer, chiffrer, envoyer, renvoyer et valider le code de réception client.

Stockage dans `CustomerOrder` :

```text
deliveryValidationCodeEncrypted
deliveryValidationCodeSentAt
deliveryValidationCodeValidatedAt
deliveryValidationCodeSendCount
deliveryValidationCodeFailedAttempts
deliveryValidationSmsLog
deliveryValidationEmailLog
```

Le code est généré au passage `OUT_FOR_DELIVERY`, envoyé par SMS et e-mail, puis supprimé quand la commande passe `DELIVERED`.

## J5P-A — Notifications client sur statuts

Service :

```text
src/Service/CustomerOrderNotificationService.php
```

Rôle : envoyer les e-mails client correspondant aux statuts clés et journaliser les résultats dans `EmailLog`.

Le service est appelé depuis `CustomerOrderWorkflowService` lors des transitions métier.

Décision anti-spam : pas d'e-mail générique `OUT_FOR_DELIVERY`, car J5O-A envoie déjà le code de réception client par SMS et e-mail.

## J5Q-A — Paiements livreurs

Nouvelles entités :

```text
CourierPayout
CourierPayoutLine
```

Service :

```text
src/Service/CourierPayoutService.php
```

Responsabilités :

- calculer les périodes 1→15 et 16→fin de mois ;
- générer des paiements `DRAFT` depuis les commandes `DELIVERED` ;
- utiliser `CustomerOrder.deliveredAt` comme source temporelle ;
- empêcher le double rattachement d'une commande ;
- afficher l'estimation et l'historique dans Djama.

## EasyAdmin — menu métier réorganisé

L'ordre cible du menu admin est désormais :

```text
Logistique
Catalogue
Commandes
Clients
Vendeurs
Livreurs
Logs
Réglages
```

La section `Livreurs` contient :

```text
Livreurs
Rémunérations livreurs
Lignes rémunération
```

`CourierCrudController` hérite de `CustomerCrudController` et filtre les comptes contenant `ROLE_COURIER`.

`assets/admin.js` doit connaître toutes les sections métier, sinon une section non reconnue est absorbée par la section précédente dans le menu mobile repliable.

## Routes EasyAdmin concernées

```text
/ouegnewe/courier
/ouegnewe/courier-payout
/ouegnewe/courier-payout-line
/ouegnewe/courier-payouts/generate-current
/ouegnewe/courier-payouts/generate-previous
```

## Règle d'architecture maintenue

Les calculs métier restent dans des services :

```text
CustomerOrderWorkflowService       → transitions commande
CustomerDeliveryCodeService        → code de réception client
CustomerOrderNotificationService   → notifications client
CourierPayoutService               → rémunération livreur
DeliveryLogisticsService           → frais et rémunération estimée
ProductPricingService              → prix produit et marge Hodina
```

---

# Architecture J5Q-C — Automatisation cron des paiements livreurs

## Objectif

J5Q-C complète J5Q-A sans changer le modèle financier : la machine prépare les brouillons de rémunération, mais l'admin garde la validation et le marquage payé.

## Composants ajoutés

```text
src/Command/GenerateCourierPayoutsCommand.php
src/Service/CourierPayoutAdminNotificationService.php
templates/emails/admin/courier_payout_recap.html.twig
tools/install-courier-payout-cron.sh
```

## Commande Symfony

```bash
php bin/console hodina:courier-payouts:generate
```

La commande s'appuie sur `CourierPayoutService` et ne recrée pas une logique de calcul parallèle.

Options principales :

```text
--period=current|previous
--date=YYYY-MM-DD
--timezone=Indian/Mayotte
--dry-run
--auto-due
--notify-admins
```

## Mode cron

Le cron prévu appelle la commande tous les jours. La commande décide elle-même si elle doit agir :

```text
15 du mois → période 1 à 15
dernier jour du mois → période 16 à fin de mois
autres jours → no-op propre
```

Cette architecture évite les cron fragiles de type "30 du mois" qui ne couvrent pas février et ne captent pas proprement les mois à 31 jours.

## Notification admin

`CourierPayoutAdminNotificationService` cherche les comptes `Customer` ayant `ROLE_ADMIN` et une adresse e-mail valide.

Chaque admin reçoit un récapitulatif :

```text
période
paiement prévu
paiements créés / complétés
lignes rattachées
commandes ignorées
total à contrôler
détail par livreur
action requise dans EasyAdmin
```

Les envois sont journalisés dans `EmailLog` avec :

```text
event_key = COURIER_PAYOUT_RECAP
template_key = emails/admin/courier_payout_recap.html.twig
```

## Script cron

`tools/install-courier-payout-cron.sh` installe ou remplace une ligne cron dédiée :

```text
hodina:courier-payouts:generate --auto-due --timezone=Indian/Mayotte --notify-admins
```

Il utilise un lock distinct :

```text
/tmp/hodina_recette_courier_payout.lock
/tmp/hodina_prod_courier_payout.lock
```

## Règle d'architecture

Le cron ne doit jamais contenir de logique métier. Il lance la commande. La commande appelle le service. Le service reste la source de vérité.

# Architecture J5Q-C-1 — Structuration des réglages en groupes

J5Q-C-1 conserve `HodinaSetting` comme registre central des paramètres globaux, mais enrichit l'entité avec des métadonnées d'organisation pour EasyAdmin.

## Entité centrale

`HodinaSetting` porte désormais :

- `groupKey` : clé technique du groupe ;
- `groupLabel` : libellé affiché ;
- `sortOrder` : ordre d'affichage ;
- `isEditable` : valeur modifiable depuis EasyAdmin ou non ;
- `isSensitive` : valeur masquée dans les listes si nécessaire.

Les champs existants `label`, `help`, `fieldType`, `value` et `updatedAt` sont conservés.

## EasyAdmin

Le stockage reste unique, mais l'interface expose plusieurs contrôleurs filtrés :

- `HodinaSettingCrudController` : vue experte, tous les paramètres ;
- `HodinaSettingGeneralCrudController` ;
- `HodinaSettingCommerceCrudController` ;
- `HodinaSettingLogisticsCrudController` ;
- `HodinaSettingNotificationsCrudController` ;
- `HodinaSettingPaymentsCrudController` ;
- `HodinaSettingTechnicalCrudController`.

Chaque contrôleur de groupe réutilise la même entité et filtre l'index par `groupKey`. Cela évite de multiplier les tables tout en améliorant fortement l'UX admin.

## Migration

`Version20260624233000` ajoute les colonnes de structuration à `hodina_setting` et classe les réglages existants connus dans les groupes `commerce`, `logistics` et `general`. Les réglages inconnus restent dans `general` par défaut.

## Hors périmètre

Aucun e-mail n'est modifié dans J5Q-C-1. Le branding e-mail s'appuiera sur cette structure dans J5Q-C-2.

# Architecture J5Q-C-2 — Branding e-mail centralisé

J5Q-C-2 ajoute `EmailBrandingService` comme service transversal pour tous les e-mails Hodina.

## Service central

`EmailBrandingService` lit les paramètres du groupe `Branding e-mail` dans `HodinaSetting` et fournit :

- `brandSubject()` : ajoute le préfixe d'objet si configuré ;
- `buildOpening()` : construit la formule d'ouverture avec le nom du destinataire si disponible ;
- `getClosingFormula()` ;
- `getSignature()` ;
- `buildContext()` : contexte Twig commun ;
- `buildPlainClosingLines()` : bloc de fin pour les corps texte journalisés.

## Intégrations e-mail

Tous les envois `TemplatedEmail` existants passent par le branding :

- `OrderEmailService` ;
- `CustomerOrderNotificationService` ;
- `CustomerDeliveryCodeService` ;
- `SellerCollectionCodeService` ;
- `CourierPayoutAdminNotificationService` ;
- `EmailVerifier` pour le composant de confirmation e-mail dormant.

Les templates HTML utilisent le contexte `emailBranding` pour l'ouverture, la formule de fin et la signature.

## EasyAdmin

Une nouvelle sous-section est ajoutée dans `Réglages` :

```text
Branding e-mail
```

Elle filtre les mêmes entités `HodinaSetting` sur le groupe `email_branding`.

## Données

La migration `Version20260625090000` initialise les réglages sans changer le schéma.

---

# Architecture J5Q-C-2 — état recette validé et observabilité

## Validation recette

Le tag `j5q-c2-branding-email-recette` pointe sur le commit `3586560` et a été déployé en recette.

Contrôles recette validés :

- checkout du tag ;
- migration `DoctrineMigrations\Version20260625090000` exécutée ;
- `doctrine:schema:validate --env=prod` OK ;
- `lint:twig templates/emails templates/registration/confirmation_email.html.twig --env=prod` OK ;
- cache prod `clear --no-warmup` puis `warmup` OK ;
- groupe `email_branding` présent en base ;
- `Réglages > Branding e-mail` visible dans EasyAdmin.

Valeurs de base observées en recette après migration :

```text
email_branding_subject_prefix      vide
email_branding_opening_formula     Bonjour
email_branding_closing_formula     Merci,
email_branding_signature           L’équipe Hodina
```

## Inventaire des e-mails raccordés

Les services raccordés au branding sont :

- `OrderEmailService` ;
- `CustomerOrderNotificationService` ;
- `CustomerDeliveryCodeService` ;
- `SellerCollectionCodeService` ;
- `CourierPayoutAdminNotificationService` ;
- `EmailVerifier` pour la confirmation e-mail SymfonyCasts dormant.

Les SMS ne passent pas par `EmailBrandingService`.

## Observabilité recette

Le fichier `config/packages/monolog.yaml` actuel écrit les logs prod Symfony sur `php://stderr` dans le handler `nested`, et non directement dans `var/log/prod.log`.

Conséquence sur o2switch :

- les erreurs PHP web et une partie des erreurs applicatives peuvent être visibles dans `public/error_log` ;
- `var/log/prod.log` peut rester vide si Monolog prod n'est pas explicitement redirigé vers `%kernel.logs_dir%/%kernel.environment%.log` ;
- les access logs live utiles sont dans `~/access-logs/recette.hodina.fr-ssl_log` ;
- les logs compressés mensuels sont dans `~/logs/recette.hodina.fr-ssl_log-Jun-2026.gz`.

La procédure complète est documentée dans `docs/DEBUG_RECETTE_HODINA.md`.


---

# J5R-A — Architecture Portail client MVP

## Contrôleur

`src/Controller/Client/AccountController.php` porte les routes client :

- `/mon-compte` : historiquement redirection MVP vers les commandes en J5R-A ; depuis J5AC, hub compte client rendu par `templates/client/account/index.html.twig` ;
- `/mon-compte/commandes` : liste des commandes du client connecté ;
- `/mon-compte/commandes/{id}` : détail propriétaire uniquement ;
- `/mon-compte/commandes/{id}/annuler` : annulation POST avec CSRF.

## Sécurité

Le portail est protégé par `ROLE_USER` via attribut contrôleur et `security.yaml`. Le détail de commande recherche toujours par `id + customer`, afin d’éviter l’accès par ID à la commande d’un autre client.

## Entité feedback

`CustomerOrderFeedback` est une entité générique préparée pour :

- motif d’annulation client (`targetType=CANCELLATION`, `targetKey=cancellation`) ;
- notation vendeur future (`targetType=SELLER`) ;
- notation livreur future (`targetType=COURIER`).

Le lot J5R-A utilise uniquement le cas annulation.

## Templates

- `templates/client/orders/index.html.twig` : cartes mobiles commandes en cours / historique.
- `templates/client/orders/show.html.twig` : détail commande, adresse snapshotée, GPS, code réception non affiché, annulation encadrée.

## Services réutilisés

- `CustomerOrderWorkflowService` : transition vers `CANCELED`.
- `CustomerOrder` : snapshots adresse, GPS, statuts et dates.

Aucun calcul de livraison, Djama ou panier n’est dupliqué.

---

# J5S-A — Architecture points de remise

Le socle `DeliveryPoint` introduit une brique logistique séparée des adresses client.

Responsabilités :

- `DeliveryPoint` : adresse fixe Hodina/logistique.
- `DeliveryPointTimeWindow` : créneaux disponibles pour ce point.
- `ProductDeliveryPoint` : points autorisés par produit.
- `Product.deliveryMode` : indique si un produit reste en livraison standard, impose un point de remise, ou laisse le choix entre livraison classique et point de remise.

Le rattachement logistique se fait via `DeliveryPoint.deliveryCommune`. Cela évite de dupliquer les règles PT/GT et permet aux futurs lots de réutiliser `DeliveryCommune` et `DeliveryLogisticsService`.

J5S-A n’introduit aucun service panier/checkout. L’activation client est repoussée à J5S-B.

J5S-A-bis améliore l’ergonomie admin : `ProductCrudController` devient le point d’entrée principal pour paramétrer un produit compatible points de remise. Depuis le formulaire produit, l’admin peut associer plusieurs points existants ou créer rapidement un point et ses plages. Le traitement reste côté admin et alimente uniquement `DeliveryPoint`, `DeliveryPointTimeWindow` et `ProductDeliveryPoint`.

J5S-A-quater précise le modèle produit : un produit peut être `STANDARD`, `DELIVERY_POINT_REQUIRED` ou `DELIVERY_POINT_OPTIONAL`. Le mode optionnel signifie que le futur checkout pourra laisser le choix entre une livraison standard dans une commune livrable et une remise dans un point autorisé.


## J5S-B — Activation DeliveryPoint dans panier/checkout

Le service `DeliveryPointCartService` centralise la détection des contraintes de points de remise dans le panier.

Il évite de dupliquer la logique dans les contrôleurs : calcul des points autorisés, conflit entre produits contraints, plages horaires disponibles et validation des choix client.

`CheckoutController` reste responsable de la création de commande et du snapshot dans `CustomerOrder`.

Le panier continue à utiliser les champs existants d’adresse/commune pour conserver la compatibilité avec le calcul logistique, mais lorsqu’un point de remise est choisi ces valeurs sont alimentées depuis le `DeliveryPoint` sélectionné.

## J5T-A — Checkout première commande simplifié

- Ajout d’un parcours checkout invité simplifié.
- Le client non connecté ne saisit plus de mot de passe avant validation.
- Un compte est créé automatiquement et l’e-mail de commande contient un lien sécurisé pour définir le mot de passe.
- Le checkout connecté reste inchangé.
- Aucun changement de schéma.


# Mise à jour 27/06/2026 — Architecture J5T à J5W

## J5T-A / J5T-A-bis — Checkout invité simplifié

Le checkout supporte désormais un parcours invité simplifié : informations client minimales, adresse ou point de remise selon le panier, validation de commande, création automatique du compte client et lien de création de mot de passe dans l’e-mail `ORDER_CREATED`.

Responsabilités principales :

- `CheckoutController` : validation du formulaire, création client/commande, orchestration adresse ou point de remise.
- `CheckoutType` : champs visibles ou cachés selon le contexte invité/connecté et point de remise.
- `OrderEmailService` : e-mail `ORDER_CREATED`, corps journalisé, lien de création mot de passe si un compte est créé.

## J5S-B-bis — Rendez-vous client pour point de remise

La plage horaire d’un `DeliveryPointTimeWindow` est une disponibilité proposée par Hodina. Le client saisit la date et l’heure effectives du rendez-vous. Le serveur vérifie que l’heure demandée entre dans une plage active du point.

Les champs de snapshot commande concernés sont :

```text
CustomerOrder.deliveryPointScheduledDate
CustomerOrder.deliveryPointScheduledTime
CustomerOrder.deliveryPointTimeWindowLabel
CustomerOrder.deliveryPointStartTime
CustomerOrder.deliveryPointEndTime
```

Le correctif `AlreadySubmittedException` impose une règle Symfony : après `handleRequest()`, le contrôleur ne doit pas modifier les données du formulaire soumis avec `setData()`. La plage détectée reste une variable métier utilisée pour snapshotter la commande.

## J5U-A — Expéditeur e-mails paramétrable

Les services e-mails utilisent `EmailBrandingService` et `EmailSenderSettings` pour construire `From`, `Reply-To` et la copie interne de `ORDER_CREATED`.

Entités / réglages concernés :

- `HodinaSetting` : clés `email_sender_*`, `email_reply_to_*`, `email_order_created_copy_email` dans le groupe `email_branding`.
- `EmailLog` : colonnes `fromEmail`, `fromName`, `replyToEmail`, `replyToName`.
- `OrderEmailService`, `CustomerOrderNotificationService`, `SellerCollectionCodeService`, `CustomerDeliveryCodeService` : consommateurs de l’expéditeur centralisé.

## J5V-A — Délai minimum de commande produit

`Product.minimumOrderLeadTimeHours` est lu par `DeliveryPointCartService`. Le service calcule le délai maximum du panier et valide le rendez-vous point de remise contre l’heure courante à Mayotte.

La règle est volontairement limitée au point de remise pour l’instant, car il existe une date/heure client explicite. Aucun moteur de planning standard n’est créé en J5V-A.

## J5W — Architecture cible non codée : DeliveryArea

Pour le futur planning, ne pas détourner `DeliveryZone`. La nouvelle couche cible est :

```text
DeliveryZone / DeliveryCommune.territory : garde-fous techniques Petite-Terre / Grande-Terre et compatibilité historique.
DeliveryPricingZone / DeliveryCommune.localPricingZone : forfait local de base.
DeliveryCommuneConnection : liaisons LAND/BARGE et garde-fou trajet.
DeliveryArea : future sous-zone opérationnelle, planning, cutoff, affectation future des livreurs.
```

Architecture cible :

```text
DeliveryCommune
- deliveryZone / territory : garde-fou technique PT/GT
- localPricingZone : source du forfait local de base
- deliveryArea : future source planning/exploitation

DeliveryArea
- code
- name
- deliveryZone
- isActive
- description

DeliveryAreaSchedule
- deliveryArea
- weekday
- cutoffDayOffset
- cutoffTime
- isActive
```

Ces entités ne sont pas présentes dans les sources du 27/06/2026. Elles sont documentées pour éviter de casser la séparation coûts/barge lors du prochain lot.
## J5S-B-ter — Séparation stricte point de remise / adresse standard

Le panier distingue désormais explicitement la livraison standard et la remise en point Hodina.

- `CartController::logisticsPreview()` reçoit le mode de livraison et le point choisi.
- En mode `DELIVERY_POINT`, le preview logistique construit une adresse technique depuis le `DeliveryPoint` autorisé pour le panier et calcule les frais avec la commune du point.
- En mode `STANDARD`, le preview conserve la logique existante basée sur la commune/adresse client.
- `templates/cart/index.html.twig` masque le bloc adresse standard lorsque le point de remise est actif et affiche un résumé `Point de remise choisi`.

Anti-régression : le mode point de remise ne doit pas laisser croire au client qu’il choisit une adresse libre. Les points disponibles restent ceux déclarés sur les produits via `ProductDeliveryPoint`.

## J5S-B-quater — Feedback global checkout et validation conditionnelle

Le panier mobile contient désormais deux niveaux de feedback complémentaires.

Côté Twig/JavaScript (`templates/cart/index.html.twig`) :

- `data-checkout-server-alert` affiche les erreurs serveur uniquement après un retour formulaire invalide ;
- `data-checkout-client-alert` affiche une erreur client seulement après une tentative de validation ;
- les boutons `data-checkout-submit` ne doivent pas rester verts quand les informations simples sont incomplètes : ils reçoivent un état visuel désactivé, mais le clic est intercepté pour afficher le message global ;
- l’état visuel ne remplace pas la validation serveur ;
- les contraintes métier complexes restent validées dans `CheckoutController`.

Côté formulaire (`CheckoutType`) :

- prénom, nom, téléphone et e-mail gardent des contraintes `NotBlank` avec messages français ;
- `deliveryPointTimeWindowId` est un champ technique hérité et non obligatoire ;
- `deliveryPointRequestedDate` et `deliveryPointRequestedTime` restent facultatifs au niveau formulaire et sont validés conditionnellement par le contrôleur si le mode point de remise est actif ;
- `address` et `commune` ne sont plus obligatoires globalement, car ils ne doivent pas bloquer une commande en point de remise ;
- un listener `POST_SUBMIT` ajoute les erreurs françaises `Indique l’adresse de livraison.` et `Choisis une commune livrée par Hodina.` uniquement en mode `STANDARD` sans adresse existante.

Cette séparation évite que Symfony remonte un message générique anglais du type `This value should not be blank.` pour un champ masqué ou non pertinent dans le mode choisi.

Anti-régression : en mode point de remise, l’adresse client ne doit pas bloquer le formulaire. En mode livraison standard, l’adresse et la commune restent obligatoires sauf si une adresse existante est sélectionnée.

## J5S-B-quater-bis — Masquage des points optionnels en mode standard

Pour les produits `DELIVERY_POINT_OPTIONAL`, le panneau des points de remise est désormais affiché uniquement lorsque le client choisit `Point de remise`.

- Mode `STANDARD` : affichage de l’adresse client, masquage des points/date/heure/instruction de remise.
- Mode `DELIVERY_POINT` : affichage des points/date/heure/instruction, masquage de l’adresse client.

Cette UX complète J5S-B-ter sans changer les calculs : la source de vérité logistique reste l’adresse client en mode standard et la commune du `DeliveryPoint` en mode point.

## J5S-B-quater-quinquies — Génération robuste de référence commande

`OrderReferenceGenerator` sécurise la génération de `CustomerOrder.orderReference` :

- calcule le prochain numéro avec `MAX(dailyOrderNumber) + 1` ;
- vérifie explicitement que la référence générée n’existe pas ;
- incrémente en cas de collision ;
- conserve l’index unique en base comme garde-fou.

Cette correction évite les collisions observées pendant les tests de checkout avec plusieurs commandes de test identiques. Aucun changement de schéma ni de format de référence n’est introduit.

## J5T-C — Checkout invité avec compte existant

Le checkout invité accepte désormais un e-mail déjà connu de Hodina sans créer de doublon client.

Composants concernés :

- `CheckoutType` ajoute deux champs techniques non mappés : `confirmExistingAccount` et `confirmedExistingAccountEmail`.
- `CheckoutController` cherche un `Customer` existant par e-mail normalisé après validation métier du panier et avant création de commande.
- Si le compte existe et que le rattachement n’est pas encore confirmé, le contrôleur réaffiche le panier avec un popup de confirmation sans persister de commande.
- Si le rattachement est confirmé pour le même e-mail, la commande est rattachée au `Customer` existant.
- `OrderEmailService::sendOrderCreatedToCustomer()` accepte un indicateur `attachedToExistingAccount` pour adapter le corps de `ORDER_CREATED`.
- `templates/cart/index.html.twig` gère le popup de confirmation et conserve les données déjà saisies.

Aucune migration n’est nécessaire.

## J5T-C — Précision de reprise après pause : ancien garde-fou e-mail existant

Les sources du 28/06/2026 contiennent la structure J5T-C pour le checkout invité avec compte existant.

Points techniques confirmés dans le code :

- `CheckoutType` expose `confirmExistingAccount` et `confirmedExistingAccountEmail` comme champs cachés non mappés.
- `templates/cart/index.html.twig` contient le popup de confirmation `showExistingAccountConfirmation` et conserve les données déjà saisies.
- `CheckoutController::renderCheckout()` accepte les paramètres `showExistingAccountConfirmation` et `existingAccountEmail` pour piloter le popup depuis le serveur.
- `CheckoutController::findCustomerByEmail()` cherche le `Customer` existant par e-mail normalisé, sans créer de nouveau compte.
- `OrderEmailService::sendOrderCreatedToCustomer(CustomerOrder $order, bool $attachedToExistingAccount = false)` adapte `ORDER_CREATED`.
- `templates/emails/order_created.html.twig` affiche la mention de rattachement uniquement si `attachedToExistingAccount` vaut `true`.

Correction importante : l’ancien bloc de `CheckoutController` qui faisait `addError()` sur le champ e-mail avec le message `Un compte existe déjà avec cette adresse e-mail...` ne doit plus exister dans le checkout invité. Cette logique reste acceptable dans `RegistrationController`, mais pas dans le checkout.

Anti-régression : la popup est déclenchée uniquement après soumission complète et validation métier du panier. Elle ne doit pas apparaître à la saisie de l’e-mail, afin d’éviter l’énumération d’adresses déjà inscrites.

## Mise à jour 28/06/2026 — État architecture après recette J5T-C / J5S / J5V

J5T-C est validé recette sans nouvelle entité ni migration : `CheckoutController` recherche un `Customer` existant par e-mail normalisé, rend le popup via `showExistingAccountConfirmation` / `existingAccountEmail`, puis rattache la commande au compte existant uniquement après confirmation. La règle technique reste : ne jamais appeler `setData()` sur un formulaire Symfony après `handleRequest()`.

J5S-B-ter/quater est validé recette : le checkout garde deux sources de vérité distinctes. En mode point de remise, la commune du `DeliveryPoint` sert aux frais et l’adresse client ne bloque pas. En mode standard, l’adresse et la commune client restent obligatoires et pilotent les frais. `deliveryPointTimeWindowId` reste technique/non obligatoire, la plage étant déduite de l’heure demandée.

J5V-A est présent dans l’architecture via `Product.minimumOrderLeadTimeHours`, `DeliveryPointCartService::getMaximumOrderLeadTimeHours()` et `DeliveryPointCartService::validateMinimumOrderLeadTime()`. La régression détectée le 28/06/2026 a été corrigée par le commit `3b508d0` : `CheckoutController` appelle désormais explicitement `validateMinimumOrderLeadTime()` dans le flux point de remise, après validation du point, de la date, de l’heure et de la plage horaire. Recette validée sous le tag `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Mise à jour 28/06/2026 — Correctif J5V-A checkout lead time

Le correctif `3b508d0` réactive la validation serveur J5V-A dans `CheckoutController`. Dans le flux point de remise, après validation du `DeliveryPoint`, de la date, de l’heure et de la plage horaire, le contrôleur appelle `DeliveryPointCartService::validateMinimumOrderLeadTime($cartData, $deliveryPointScheduledDate, $deliveryPointScheduledTime)`.

Responsabilités conservées :

- `Product.minimumOrderLeadTimeHours` porte la règle produit ;
- `DeliveryPointCartService` calcule le délai le plus strict du panier et compare le rendez-vous à l’heure courante Mayotte ;
- `CheckoutController` reste l’orchestrateur de validation serveur ;
- Twig/JavaScript peuvent afficher du feedback, mais ne deviennent pas source de vérité.

Périmètre volontairement limité : point de remise uniquement, car ce flux dispose d’une date/heure choisie par le client. Aucun changement sur livraison standard, frais, Djama ou barge. Recette validée sous `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Mise à jour 29/06/2026 — Architecture validée production checkout stabilisation

Le tag `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` valide en production l’architecture checkout stabilisée :

- `CheckoutController` orchestre la validation serveur standard / point de remise, J5T-C et J5V-A ;
- `DeliveryPointCartService::validateMinimumOrderLeadTime()` est appelé côté serveur pour bloquer un rendez-vous point de remise trop proche ;
- `DeliveryPoint` fournit la commune utilisée pour les frais en mode point de remise ;
- l’adresse client reste source de vérité en mode standard ;
- `Customer` existant est recherché par e-mail normalisé en checkout invité, puis rattaché seulement après confirmation ;
- `EmailBrandingService` / réglages EasyAdmin conservent `commande@hodina.fr` comme expéditeur opérationnel.

Aucune nouvelle entité n’est introduite par cette MEP. Cette formulation est ensuite clarifiée par J5W-A : `DeliveryPricingZone` porte le forfait local, `DeliveryCommuneConnection` porte les liaisons/barge, `DeliveryCommune.territory` garde PT/GT, et `DeliveryArea` reste une couche future de planning/exploitation.


## Mise à jour 29/06/2026 — J5W-A zones tarifaires locales par secteur

Statut technique J5W-A : validé localement + recette + production. Recette sous `recette-j5w-a-local-pricing-zones-20260629`, production sous `prod-j5w-a-local-pricing-zones-20260629`.

Statut : **validé fonctionnellement en local + recette + production**.

J5W-A ajoute un découpage tarifaire local plus fin **sans remplacer les territoires techniques PT/GT**.

Composants concernés :

- `migrations/Version20260629083000.php` : crée les zones tarifaires Grande-Terre par secteur et rattache les communes.
- `DeliveryPricingZone` : reste l’entité de tarification locale.
- `DeliveryCommune.localPricingZone` : devient la source du forfait local par secteur.
- `DeliveryCommune.territory` : conserve strictement `PT` ou `GT`.
- `DeliveryCommuneConnection` : conserve les liaisons `LAND` / `BARGE`.
- `DeliveryLogisticsService` : conserve `localPricingZone` comme forfait de base et les liaisons logistiques comme source du trajet.
- `templates/cart/index.html.twig` : correction de rendu du champ `deliveryPointCustomerInstructions`, qui ne doit plus apparaître perdu en bas du panier standard.

Zones tarifaires locales retenues :

| Code | Rôle |
|---|---|
| `MAMOUDZOU_LOCAL` | forfait local Mamoudzou |
| `NORD_LOCAL` | forfait local Grande-Terre Nord |
| `CENTRE_LOCAL` | forfait local Grande-Terre Centre |
| `SUD_LOCAL` | forfait local Grande-Terre Sud |
| `PT_LOCAL` | forfait local Petite-Terre historique conservé |

Décision technique : ne pas créer `PETITE_TERRE_LOCAL`. Petite-Terre réutilise `PT_LOCAL` afin d’éviter un doublon admin et une ambiguïté métier.

Règle anti-régression : J5W-A ne modifie ni le checkout, ni le calcul de barge, ni `DeliveryZone`, ni `DeliveryCommune.territory`.


## Mise à jour 29/06/2026 — J5X-A mise à jour des tarifs par zone tarifaire

Statut technique J5X-A : **implémentation préparée sur `develop`, validation locale à rejouer** avant merge `main`, recette et production.

J5X-A ne crée pas de nouvelle entité et ne modifie pas la formule de livraison validée en production avec J5W-A. Le changement porte uniquement sur les montants `DeliveryPricingZone.customerDeliveryFee`.

Formule de livraison à préserver :

```text
Frais livraison client =
forfait local DeliveryCommune.localPricingZone / DeliveryPricingZone
+ coûts de liaison DeliveryCommuneConnection LAND/BARGE
+ supplément multi-vendeurs plafonné
puis plafond global client éventuel.
```

Tarifs cibles J5X-A :

```text
PT_LOCAL          = 12 €
MAMOUDZOU_LOCAL   = 12 €
CENTRE_LOCAL      = 17 €
SUD_LOCAL         = 21 €
NORD_LOCAL        = 21 €
GT_LOCAL          = 21 € fallback technique
```

Fichiers techniques ajoutés / modifiés :

```text
migrations/Version20260629141000.php
src/Controller/Admin/DeliveryPricingZoneCrudController.php
tools/assert-j5x-a-delivery-pricing-zones.php
```

`DeliveryPricingZoneCrudController` reçoit seulement une aide admin plus explicite. Le calcul reste centralisé dans `DeliveryLogisticsService` ; aucun tarif ne doit être codé en dur dans Twig, JavaScript, contrôleur panier/catalogue ou service métier.

Règles anti-régression :

- ne pas créer `PETITE_TERRE_LOCAL` ;
- ne pas modifier `DeliveryCommune.territory` (`PT` / `GT`) ;
- ne pas modifier les rattachements communes créés par J5W-A ;
- ne pas modifier `courierPayout` dans J5X-A ;
- ne pas introduire de calendrier de livraison dans ce lot ;
- ne pas corriger la fiche produit avec un tableau statique durable : ce sujet relève de J5X-B/J5X-C.

## J5X-B — Calendrier de livraison paramétrable par secteur

J5X-B ajoute un service dédié `DeliveryScheduleService` pour calculer la promesse de passage à partir du calendrier porté par `DeliveryPricingZone`. Le calcul des frais reste dans `DeliveryLogisticsService` et n’est pas modifié.

Composants :

```text
DeliveryPricingZone : calendrier standard du secteur client.
DeliveryScheduleService : prochain passage possible + cutoff.
DeliverySchedulePreview : DTO d’affichage.
CartController::logisticsPreview() : enrichit la réponse AJAX avec deliverySchedule.
templates/cart/index.html.twig : affiche le planning sans calcul métier Twig.
templates/product/show.html.twig : affiche une information secteur non garantie.
```

Règle : le calendrier standard dépend du secteur/commune client, donc il est porté par `DeliveryPricingZone`, pas par `Product`. Les produits sur créneau restent hors J5X-B.

## J5X-C — Promesse produit et produits sur créneau

J5X-C ajoute une couche de présentation métier dédiée à la fiche produit, sans modifier la formule de calcul logistique ni le checkout.

Nouveaux composants :

- `Product.deliveryPromiseMode` distingue la promesse publique : `SECTOR_SCHEDULE` ou `APPOINTMENT`.
- `ProductDeliveryPromiseService` construit le message client affiché sur la fiche produit.
- `ProductDeliveryPromise` est un DTO de présentation, indépendant du calcul des frais.
- `ProductController::show()` résout, si possible, la zone tarifaire connue depuis la commune en query string ou l’adresse de livraison par défaut du client connecté.
- `templates/product/show.html.twig` affiche :
  - commune connue : uniquement la promesse pertinente du secteur ;
  - commune inconnue : résumé + tableau des secteurs repliable ;
  - produit sur créneau : jours possibles, plage horaire, cutoff et délai produit éventuel.

Règle d’architecture : J5X-C ne doit pas dépendre de `DeliveryLogisticsService`. Les frais restent calculés par le panier/checkout. La promesse produit ne doit pas devenir une garantie de livraison.

## J5X-D — Catalogue recherche, filtres, tri et priorité admin

J5X-D enrichit le catalogue public sans toucher à la livraison.

Composants ajoutés ou renforcés :

- `ProductRepository::findCatalogueProducts()` : requête catalogue dédiée avec produits actifs, catégories actives, recherche, filtre catégorie, préchargement vendeur/catégorie/images et tris non-prix.
- `CategoryRepository::findActiveForCatalogue()` : catégories actives triées par mise en avant, ordre admin, puis nom.
- `ProductController::catalogue()` : lit les paramètres GET `q`, `categorie`, `tri`, calcule les prix via `ProductPricingService`, applique le tri prix en PHP et rend soit la page complète, soit le fragment résultats.
- `templates/product/_catalogue_filters.html.twig` : formulaire GET avec fallback sans JavaScript.
- `templates/product/_catalogue_results.html.twig` et `_catalogue_product_card.html.twig` : grille catalogue réutilisable par rendu initial et AJAX.

Règle anti-régression : J5X-D ne doit pas dépendre de `DeliveryLogisticsService` ni de `DeliveryScheduleService`. Le catalogue ne calcule ni frais, ni prochain passage, ni disponibilité commune.

# Architecture 01/07/2026 — J5Y, homepage catalogue et points de remise

## J5Y-A — Interface guidée EasyAdmin pour les plages de point de remise

J5Y-A améliore uniquement l’ergonomie du formulaire Produit EasyAdmin lors de la création rapide d’un nouveau point de remise.

Composants :

```text
assets/admin.js
assets/controllers/delivery_point_windows_controller.js
src/Controller/Admin/ProductCrudController.php
```

Principe technique : le champ Symfony `quickDeliveryPointTimeWindows` reste un textarea soumis au backend, mais il est transformé côté EasyAdmin par Stimulus en lignes lisibles : libellé, jours concernés, heure de début, heure de fin.

Choix important : les options `Jours ouvrés` et `Jours ouvrables` sont des raccourcis UI. Elles doivent être développées par le backend en jours réels existants, pas devenir de nouveaux codes métier persistés.

Règle anti-régression : ne pas réintroduire un template EasyAdmin fragile pour remplacer le rendu du champ. Le branchement fiable repose sur `row_attr`, un attribut source sur le textarea et le contrôleur Stimulus.

## J5Y-B — Créneaux panier point de remise par demi-heure

J5Y-B remplace la saisie libre de l’heure de rendez-vous point de remise par une sélection de créneaux de 30 minutes calculés à partir des plages actives du point choisi.

Composants :

```text
src/Form/CheckoutType.php
src/Service/DeliveryPointCartService.php
src/Controller/CheckoutController.php
templates/cart/index.html.twig
public/css/style_mobile.css
tools/assert-j5y-b-delivery-point-half-hour-slots.php
```

Règle : pour une plage `08:00–12:00`, les créneaux visibles sont `08:00–08:30` jusqu’à `11:30–12:00`. L’heure de fin `12:00` ne doit pas être proposée comme début de créneau.

Validation serveur : `DeliveryPointCartService::findMatchingTimeWindow()` refuse une heure non alignée sur 30 minutes, hors plage, égale à la fin de plage ou débordant après la fin. Le JavaScript améliore l’UX, mais ne porte pas la sécurité métier.

## J5Y-C — Catalogue en homepage et page Découvrir Hodina

Routes publiques actuelles :

```text
/                         → ProductController::catalogue(), route product_catalogue et app_home
/catalogue                → redirection permanente vers /
/decouvrir-hodina         → HomeController::discover(), page institutionnelle publique
/blog/decouvrir-hodina    → redirection permanente vers /decouvrir-hodina
/blog                     → redirection permanente vers /decouvrir-hodina
```

`templates/product/catalogue.html.twig` devient la première impression client. `templates/pages/decouvrir_hodina.html.twig` porte l’histoire, l’explication du pilote et les messages pour futurs clients, vendeurs et livreurs. Le terme `blog` reste uniquement compatible en redirection legacy, pas comme libellé UX public.

Décision navigation validée recette : le lien `Catalogue` a été retiré du header après passage du catalogue sur `/`. Le logo pointe vers `product_catalogue`. Le lien public principal du header est désormais `Infos livraison`, qui pointe vers `app_carnet_livraison`. La page institutionnelle `Découvrir Hodina` est déplacée dans le footer pour ne pas surcharger l’en-tête mobile.

## J5Y-D — Logo header et favicon

J5Y-D ajoute une variante horizontale du logo pour le header :

```text
public/images/logo_hodina_header.png
```

Le header n’utilise plus la version carrée `logo_hodina_mobile.png`, qui rendait le texte Hodina trop petit.

Fichiers favicon présents dans la source actuelle :

```text
public/favicon.ico
public/images/favicon-16x16.png
public/images/favicon-32x32.png
public/images/apple-touch-icon.png
```

État validé recette : le favicon transparent J5Y-D-ter et le logo header optimisé sont retenus pour le MVP. Le logo `logo_hodina_header.png` a été compressé avant recette pour passer d’environ 160 Ko à environ 4,5 Ko, sans modification Twig ni CSS supplémentaire. Ne pas réintroduire une image header lourde dans le header mobile.

Ne pas versionner les fichiers temporaires éventuels : `.old`, `.bak`, archives `.zip`, patchs locaux ou images de test.

## J5Y-F — Routes publiques Carnet Hodina

Routes publiques ajoutées :

```text
/carnet             → HomeController::carnet(), page d’entrée pédagogique du Carnet Hodina
/carnet/livraison   → HomeController::carnetLivraison(), page pédagogique livraison Hodina
```

`/carnet` n’est pas un blog généraliste : il sert à regrouper des contenus utiles pour comprendre Hodina, les produits locaux, la livraison et les partenaires. Au MVP, seule la page `Livraison Hodina` est active. Les pages `Fruits, légumes et saisons` et `Nos vendeurs et producteurs partenaires` restent indiquées comme contenus à venir, sans lien actif.

La page `/carnet/livraison` reste volontairement statique et pédagogique. Elle ne remplace pas le calcul métier du panier : les dates, frais et créneaux exacts restent déterminés par l’adresse, le point de remise, les produits et les règles logistiques en vigueur.

# Architecture 01/07/2026 — J5Y-E/F/G/H validés recette puis production

## Routes publiques finales du lot J5Y

Les routes publiques stabilisées en recette sont :

```text
/                         → catalogue public, route ProductController::catalogue(), noms app_home et product_catalogue
/catalogue                → redirection permanente vers /
/decouvrir-hodina         → page institutionnelle publique, HomeController::discover()
/blog/decouvrir-hodina    → redirection permanente legacy vers /decouvrir-hodina
/blog                     → redirection permanente legacy vers /decouvrir-hodina
/carnet                   → page d’entrée pédagogique du Carnet Hodina, HomeController::carnet()
/carnet/livraison         → guide livraison public, HomeController::carnetLivraison()
```

Règle : le terme `blog` ne doit plus être exposé côté UX publique. Il reste seulement une compatibilité de redirection pour les anciens liens.

## Templates publics concernés

```text
templates/product/catalogue.html.twig
templates/pages/decouvrir_hodina.html.twig
templates/pages/carnet/index.html.twig
templates/pages/carnet/livraison.html.twig
templates/base.html.twig
```

Le dossier `templates/pages` porte désormais les pages institutionnelles ou pédagogiques. Le déplacement depuis `templates/blog/decouvrir_hodina.html.twig` est volontaire : la page Découvrir Hodina n’est pas un article de blog.

## Navigation publique J5Y-G

Header public :

```text
Logo Hodina → catalogue /
Infos livraison → /carnet/livraison
Compte / Connexion
Panier
```

Footer public :

```text
Réassurance : Produits locaux / Vendeurs de Mayotte / Livraison selon commune / Paiement manuel pilote
Explorer : Catalogue / Découvrir Hodina / Carnet Hodina
Livraison : Infos livraison / Points de remise / Dates confirmées au panier
Pratique : CGV / CGU / contact@hodina.fr
```

Règle UI : le footer doit rassurer et orienter, mais rester compact. Il ne doit pas redevenir une zone trop haute qui concurrence le catalogue sur mobile.

## Page `/carnet/livraison` J5Y-F/H

La page livraison est statique, pédagogique et illustrée par quatre images WebP optimisées :

```text
public/images/carnet/livraison/livraison-petite-terre.webp
public/images/carnet/livraison/livraison-mamoudzou.webp
public/images/carnet/livraison/livraison-nord-centre.webp
public/images/carnet/livraison/livraison-sud.webp
```

Les jours affichés sont des repères indicatifs par secteur. La source de vérité opérationnelle reste le panier, qui calcule les frais, les dates et les créneaux selon l’adresse, les produits, le mode de remise et les règles logistiques.

## Garde-fous J5Y

```text
tools/assert-j5y-c-homepage-catalogue-discover.php
tools/assert-j5y-d-header-logo-favicon.php
tools/assert-j5y-f-carnet-livraison.php
```

Ces asserts verrouillent : catalogue sur `/`, redirections legacy, absence publique de Djama, header orienté livraison, footer réassurance, pages Carnet et images livraison légères.


## Statut production J5Y

Les routes publiques et templates ci-dessus ont été déployés et validés en production le 01/07/2026 sous le tag `prod-j5y-carnet-livraison-footer-20260701`.

Anti-régression : ne pas déplacer à nouveau ces routes sans prévoir redirections et asserts. La production valide `/`, `/decouvrir-hodina`, `/carnet`, `/carnet/livraison`, les redirections `/blog*`, le header `Infos livraison` et le footer de réassurance compact.

# Architecture 02/07/2026 — J5Z checkout/admin UX

## Services ajoutés

### PhoneNumberNormalizer

`App\Service\PhoneNumberNormalizer` assemble un indicatif explicite avec le numéro local. Il ne devine pas le pays à partir du numéro saisi dans les nouveaux formulaires.

Responsabilités :

- fournir les choix d’indicatifs ;
- assembler `phoneCountryCode` + `phone` ;
- nettoyer espaces et séparateurs ;
- conserver les numéros internationaux déjà saisis ;
- fournir `splitForForm()` pour préremplir les formulaires ;
- fournir `normalizeLegacy()` pour le rattrapage contrôlé des anciennes données.

Le service est utilisé par les formulaires / contrôleurs d’inscription et checkout. Il ne crée aucune migration.

### DeliveryFeeReasonFormatter

`App\Service\DeliveryFeeReasonFormatter` produit l’annotation lisible des frais de livraison à partir d’un preview logistique ou du snapshot commande.

Responsabilités :

- transformer `landHopCount`, `collectionPointCount`, `requiresBarge`, `bargeHopCount` en raisons client ;
- formater `Inclus : ...` ;
- être utilisé par Twig, AJAX, email et récapitulatif commande ;
- ne jamais calculer les frais.

Règle d’architecture : `DeliveryFeeReasonFormatter` explique, `DeliveryLogisticsService` calcule.

## Commande ajoutée

`App\Command\NormalizeCustomerPhonesCommand` expose :

```bash
php bin/console hodina:customers:normalize-phones
php bin/console hodina:customers:normalize-phones --apply
```

La commande est en simulation par défaut. Elle porte uniquement le rattrapage legacy `Customer.phone` et `CustomerSignup.phone` connu au moment de J5Z : Mayotte `0639/0269` et métropole `01..07/09`.

## CartController — preview logistique enrichi

`CartController` est enrichi pour :

- injecter `DeliveryFeeReasonFormatter` ;
- ajouter `deliveryFeeReason` dans la réponse JSON du recalcul logistique ;
- versionner le cache session `cart_logistics_preview` avec `LOGISTICS_PREVIEW_CACHE_VERSION = j5z-delivery-fee-reason-v1` ;
- éviter qu’une session ancienne réutilise un preview sans annotation ;
- maintenir la cohérence entre premier affichage, changement d’adresse AJAX et rafraîchissement de page.

Aucune règle tarifaire n’est déplacée dans le contrôleur : il orchestre l’affichage du résultat.

## Templates et front panier

`templates/cart/index.html.twig` couvre deux parcours distincts :

```text
client connecté  → champs client techniques cachés
client invité     → indicatif + téléphone visibles
```

Le champ `phoneCountryCode` est rendu explicitement dans le bloc caché du client connecté pour éviter un rendu automatique par `form_end(form)` en bas du panier.

Le JavaScript panier utilise `payload.deliveryFeeReason` fourni par le serveur après recalcul AJAX. Un fallback reste présent côté front à partir du preview, mais le serveur est la source privilégiée de l’annotation.

## Email / SMS

`OrderEmailService` reçoit `DeliveryFeeReasonFormatter` et ajoute l’annotation aux emails de création de commande. Le récapitulatif SMS de commande reprend aussi l’information quand elle est disponible. Les SMS opérationnels de statut ne sont pas alourdis par cette annotation.

## CSS mobile

`public/css/style_mobile.css` contient les correctifs J5Z :

- flash frais recalculés visible en haut, opaque marron clair, supprimable ;
- champ `Date de rendez-vous` contraint dans sa carte pour checkout invité et connecté ;
- protections Safari/iPhone : `box-sizing`, `min-width`, `max-width`, `appearance` ciblés.

## Assert / garde-fous J5Z

Garde-fous ajoutés ou renforcés :

```text
tools/assert-admin-product-form-order.php
tools/assert-customer-phone-prefix.php
tools/assert-checkout-delivery-fee-reason.php
tools/assert-delivery-fee-update-flash.php
tools/assert-delivery-point-date-field-mobile-width.php
tools/assert-cart-logistics-preview-cache-version.php
```

Ces scripts protègent des régressions statiques sur l’ordre EasyAdmin, l’indicatif téléphone, l’annotation frais, le flash, le champ date mobile et le cache logistique.

# Architecture prévue 02/07/2026 — J5AA AddressLocality

> ⚠️ SECTION SUPERSÉDÉE — Cette section décrivait la cible avant codage. J5AA a été réalisé et validé recette + production le 2026-07-04. Voir les sections réalisées « Architecture J5AA-0 », « Architecture J5AA-A — AddressLocality » et « Architecture J5AA-B » plus bas.

J5AA (à l’époque de cette note) n’était pas encore codé. La décision d’architecture retenue est de modéliser la précision `village / quartier / lieu-dit` par une entité générique `AddressLocality`, et non par `DeliveryVillage`.

Responsabilité prévue :

```text
AddressLocality = précision d’adresse.
DeliveryCommune = source logistique et tarifaire.
Code postal = aide de sélection et cohérence d’adresse.
```

Si une localité connue est sélectionnée, elle peut préremplir la commune associée. Si le client tape une localité libre non reconnue, Hodina conserve le texte mais ne déduit pas la commune.

Complément d’architecture à intégrer en J5AA : le couple `code postal + DeliveryCommune` doit être cohérent avec les données seedées. Le code actuel possède déjà `DeliveryCommune.postalCode` et un `DeliverableAddressValidator` qui vérifie la cohérence commune/code postal/zone côté serveur. En revanche, l’UX n’est pas homogène : le checkout utilise une commune sélectionnée et un code postal déduit, tandis que l’inscription conserve encore des champs texte `postalCode` / `commune` validés après soumission.

Règle : J5AA doit unifier progressivement cette expérience sans créer de logique tarifaire par code postal. Aucune nouvelle entité `PostalCode` ne doit être créée avant d’avoir vérifié si `DeliveryCommune.postalCode` suffit pour le MVP.

Aucun calcul de frais ne doit dépendre directement de `AddressLocality` ou du code postal sans décision métier future.

# Architecture 03/07/2026 — J5AB catalogue achat-first et J5AC espace client

## J5AB — Catalogue mobile orienté achat

J5AB modifie uniquement la présentation du catalogue public. La route reste portée par `ProductController` :

```text
/          → ProductController::catalogue(), routes app_home et product_catalogue
/catalogue → redirection permanente vers /
```

Fichiers structurants :

- `templates/product/catalogue.html.twig` ;
- `templates/product/_catalogue_filters.html.twig` ;
- `templates/product/_catalogue_results.html.twig` ;
- `templates/product/_catalogue_product_card.html.twig` ;
- `public/css/style_mobile.css` ;
- `tools/assert-j5ab-catalogue-mobile-buy-first.php`.

Le moteur catalogue J5X-D reste inchangé : paramètres GET `q`, `categorie`, `tri`, recherche produit/vendeur, tri, rendu fragment AJAX si `X-Requested-With` ou `fragment=1`.

Règles d’architecture :

- J5AB ne modifie pas `ProductRepository` ;
- J5AB ne modifie pas les routes ;
- J5AB n’ajoute pas de pagination ;
- J5AB ne dépend pas de `DeliveryLogisticsService` ;
- J5AB ne modifie ni panier, ni checkout, ni Djama, ni EasyAdmin.

## J5AC — Espace client finalisé

Routes client stabilisées :

```text
GET  /mon-compte
GET  /mon-compte/commandes
GET  /mon-compte/commandes/{id}
POST /mon-compte/commandes/{id}/annuler
GET|POST /mon-compte/profil
GET|POST /mon-compte/mot-de-passe
POST /mon-compte/mot-de-passe/lien-reinitialisation
```

Contrôleurs :

- `App\Controller\Client\AccountController` : hub compte, liste/détail commandes, annulation client encadrée.
- `App\Controller\Client\ProfileController` : modification profil client.
- `App\Controller\Client\PasswordController` : changement mot de passe connecté et demande de lien reset.
- `App\Controller\PasswordResetController` : reset public existant, factorisé via `CustomerPasswordResetLinkService`.

Formulaires :

- `App\Form\ClientProfileType` ;
- `App\Form\ClientChangePasswordType`.

Service ajouté :

- `App\Service\CustomerPasswordResetLinkService`.

Responsabilité du service : générer le token de reset, fixer l’expiration, produire l’URL absolue `app_reset_password`, créer le `SmsLog` de transmission manuelle. Il ne change pas le canal opérationnel pilote : l’envoi reste traité via le mécanisme SMS/iPhone déjà existant.

Templates :

- `templates/client/account/index.html.twig` ;
- `templates/client/profile/edit.html.twig` ;
- `templates/client/security/password.html.twig` ;
- `templates/client/_account_nav.html.twig` ;
- `templates/client/_account_ajax.html.twig` ;
- templates commandes existants enrichis : `templates/client/orders/index.html.twig`, `templates/client/orders/show.html.twig`.

AJAX progressif :

`templates/client/_account_ajax.html.twig` intercepte les liens internes `/mon-compte/*` et les formulaires du portail client. Il remplace uniquement le bloc `data-client-account-page`, conserve `pushState`/`popstate`, ajoute un feedback discret `.is-ajax-pending`, et garde un fallback complet sans JavaScript.

## DB J5AC — email unique nullable

`Customer` porte désormais :

```php
#[ORM\Table(name: 'customer')]
#[ORM\UniqueConstraint(name: 'UNIQ_CUSTOMER_EMAIL', columns: ['email'])]
```

La colonne reste nullable :

```php
#[ORM\Column(length: 180, nullable: true)]
private ?string $email = null;
```

Migration :

```text
DoctrineMigrations\Version20260703093000
```

Règles :

- emails vides convertis en `NULL` ;
- emails existants normalisés en `LOWER(TRIM(email))` ;
- migration bloquée si doublons normalisés ;
- index unique nullable `UNIQ_CUSTOMER_EMAIL` ;
- aucune contrainte unique sur `customer.phone` ;
- migration déclarée `isTransactional(): false` pour éviter le warning MariaDB/MySQL lié aux commits implicites.

## Garde-fous J5AC

Outils :

```bash
php tools/assert-j5ac-customer-email-db-readiness.php
php tools/assert-j5ac-client-account-finalization.php
php tools/assert-j5ac-client-account-ajax.php
```

Ces asserts verrouillent : email unique nullable, absence d’unique téléphone, migration non transactionnelle, hub `/mon-compte`, profil, sécurité, reset connecté via `SmsLog`, commandes protégées, AJAX progressif discret et fallback sans JavaScript.

# Alignement architecture 04/07/2026 — incohérences corrigées avant J5AA

## Portail client

L’ancien bloc J5R-A indiquait `/mon-compte` comme redirection MVP. Cette information est historiquement vraie pour le premier portail client, mais elle est obsolète dans le code actuel. Depuis J5AC, `/mon-compte` est un hub compte client, porté par `AccountController::index()` et `templates/client/account/index.html.twig`.

Routes présentes dans le code actuel :

```text
GET  /mon-compte
GET  /mon-compte/commandes
GET  /mon-compte/commandes/{id}
POST /mon-compte/commandes/{id}/annuler
GET|POST /mon-compte/profil
GET|POST /mon-compte/mot-de-passe
POST /mon-compte/mot-de-passe/lien-reinitialisation
```

Route absente :

```text
/mon-compte/adresses
```

Décision : ne pas présenter le portail client MVP comme prochaine priorité. Le compte client est finalisé en J5AC ; seule la page autonome d’adresses reste à cadrer plus tard, séparément de J5AA.

## Adresse / code postal / commune avant J5AA

État actuel du code :

- `Address.postalCode` existe comme chaîne persistée.
- `DeliveryCommune.postalCode` existe et porte le code postal principal seedé.
- `DeliverableAddressValidator` contrôle déjà le format 5 chiffres, la commune livrable, la cohérence code postal / commune et la zone.
- `CheckoutType` propose `commune` en `ChoiceType` depuis les `DeliveryCommune` actives et déduit `postalCode` côté UI.
- `RegistrationFormType` utilise encore `postalCode` et `commune` en `TextType`, avec validation serveur dans `RegistrationController`.

J5AA doit donc améliorer l’UX et la cohérence sans repartir de zéro : la source métier commune existe déjà, mais les formulaires ne sont pas tous homogènes.
# Architecture J5AA-0 — Audit commune des adresses DELIVERY

## Principe

J5AA-0 ne modifie pas l'architecture applicative. Il ajoute un outil d'audit en lecture seule :

```bash
php tools/assert-j5aa-delivery-address-commune-audit.php
```

Cet outil vérifie la donnée existante autour de `Address.commune`, sans changer les entités, les formulaires, les templates ni les services métier.

## Rôle des composants existants

- `Address.commune` : champ métier central de l'adresse. Pour `DELIVERY`, il doit porter une commune livrable canonique.
- `Address.postalCode` : code postal de l'adresse, utilisé pour contrôler la cohérence avec la commune.
- `Address.deliveryZone` : zone `PT`, `GT` ou `AUTRE` selon le type et le contexte de l'adresse.
- `DeliveryCommune` : référentiel de validation logistique, avec `name`, `postalCode`, `territory`, `isActive`, `isLogisticsPoint`.
- `DeliveryCommuneMatcherService` : service runtime de résolution, volontairement souple pour ne pas bloquer certains parcours existants.
- `DeliveryLogisticsService` : calcule les frais à partir de la commune de livraison résolue via le référentiel logistique.

## Différence runtime / audit

Le runtime peut encore utiliser une résolution souple pour limiter les blocages utilisateur.

L'audit J5AA-0, lui, est strict :

- une adresse `DELIVERY` doit matcher exactement une `DeliveryCommune` active/logistique ;
- une résolution uniquement fuzzy est une anomalie ;
- une valeur composite comme `Dzaoudzi-Labattoir` est une anomalie si plusieurs communes candidates existent ;
- une adresse `BILLING` en zone `AUTRE` est listée à titre informatif mais ne bloque pas l'audit.

## Hors périmètre architectural

J5AA-0 ne crée pas :

- `AddressLocality` ;
- `Address.deliveryCommune` ;
- `PostalCode` ;
- nouvelle migration Doctrine ;
- logique de tarification par code postal ou localité.

# Architecture J5AA-B — Sélection code postal + commune au checkout

J5AA-B étend le checkout sans migration et sans nouvelle relation Doctrine.

## Formulaire

`CheckoutType` reçoit toujours la liste des `DeliveryCommune` actives/logistiques via l'option `delivery_communes`.

Il expose maintenant :

- `postalCode` en `ChoiceType`, construit depuis les codes postaux uniques présents dans les `DeliveryCommune` actives/logistiques ;
- `commune` en `ChoiceType`, construit depuis les `DeliveryCommune` actives/logistiques ;
- les options de commune portent les attributs HTML `data-postal-code` et `data-zone` pour le filtrage mobile-first côté client.

## Contrôleur checkout

`CheckoutController::validateDeliveryAddressData()` valide désormais explicitement le couple `postalCode + commune` :

- code postal obligatoire ;
- format 5 chiffres ;
- commune obligatoire ;
- commune résolue en mode canonique strict via `DeliveryCommuneMatcherService::resolveCanonicalActiveLogisticsCommune()` ;
- code postal soumis identique au code postal de la `DeliveryCommune` résolue.

La persistance reste inchangée : l'adresse stocke toujours le code postal et le libellé de commune dans `Address.postalCode` et `Address.commune`.

## Aperçu logistique panier

`CartController::logisticsPreview()` reçoit aussi le `postalCode` soumis par le front et refuse un couple incohérent avant de calculer les frais. L'aperçu AJAX utilise la même résolution canonique stricte que le checkout final. Le calcul continue ensuite d'utiliser une adresse temporaire dont la commune est canonisée depuis `DeliveryCommune.name`.

## JavaScript Twig

Le template `templates/cart/index.html.twig` filtre les options de commune selon le code postal sélectionné. Si un code postal correspond à une seule commune, la commune peut être sélectionnée automatiquement. Si le code postal correspond à plusieurs communes, l'utilisateur doit choisir la commune exacte.

Cette logique reste une aide UX. La sécurité métier est portée par le serveur.

# Architecture J5AA-A — AddressLocality

## Entité AddressLocality

`AddressLocality` est une entité de précision d’adresse. Elle est rattachable à une `DeliveryCommune`, mais elle n’est pas une source de calcul logistique.

Champs principaux :

- `name` : libellé visible ;
- `normalizedName` : nom normalisé pour résolution stricte ;
- `deliveryCommune` : commune livrée associée, nullable ;
- `postalCode` : code postal indicatif ;
- `countryCode` : code pays indicatif ;
- `isActive` : proposition aux nouveaux clients ;
- `sortOrder` : ordre d’affichage.

## Address

`Address` reçoit deux champs complémentaires :

- `addressLocality` : relation nullable vers `AddressLocality` ;
- `localityText` : texte libre nullable.

`Address.commune` reste le champ métier central de commune de livraison. J5AA-A n’ajoute pas `Address.deliveryCommune`.

## Checkout

Le checkout propose un champ optionnel `Localité` avec l’aide `Village / quartier / lieu-dit`.

Si une localité connue est reconnue, le serveur conserve la relation `Address.addressLocality` et le nom canonique dans `Address.localityText`. Si la localité est libre, seule la valeur texte est conservée.

Le formulaire peut préremplir le code postal et la commune depuis une localité connue, mais le serveur continue de valider strictement le couple `postalCode + commune` via `DeliveryCommune`.

## Back-office

EasyAdmin expose `AddressLocality` dans Logistique > Localités d’adresse. `AddressCrudController` expose aussi la localité connue et la localité libre d’une adresse.

## Commande de seed

`php bin/console hodina:address-localities:seed` simule le seed. `--apply` applique les créations/mises à jour.
