# HODINA - Entités

## Historique conservé

État initial du document de référence :

```text
Customer
Address (postalCode obligatoire)
CustomerOrder
OrderItem
Seller
Product
Category
DeliveryZone
SmsLog
```

Cette liste reste la base du domaine Hodina. Elle est désormais complétée avec les champs et entités/réglages apparus en J4, ainsi que les ajouts prévus pour J5.

---

# Entités principales

## Customer

Représente un client Hodina.

Rôle dans le pilote :

- s'inscrire ;
- se connecter ;
- passer commande ;
- être contacté par téléphone / SMS.

Informations importantes :

- prénom ;
- nom ;
- email ;
- téléphone.

Utilisé dans :

- commande ;
- SmsLog ;
- fiche terrain ;
- futur dashboard livreur.

---

## Address

Adresse de livraison d'un client.

Décision conservée :

```text
postalCode obligatoire
```

Champs importants :

- client ;
- libellé ;
- ligne 1 ;
- ligne 2 ;
- code postal ;
- commune ;
- zone de livraison ;
- notes éventuelles.

Utilisée dans :

- checkout ;
- commande ;
- fiche terrain ;
- futur dashboard livreur.

---

## CustomerOrder

Entité centrale du workflow commande.

### Rôle

Représente une commande client.

Elle porte :

- le client ;
- l'adresse ;
- la zone ;
- les lignes de commande ;
- les montants ;
- le statut ;
- les dates métier ;
- le numéro métier ;
- les informations futures de livraison.

### Relations actuelles

- `customer`
- `deliveryAddress`
- `deliveryZone`
- `items`

### Champs métier J4

- `orderReference`
- `status`
- `paymentStatus`
- `subtotal`
- `deliveryFee`
- `total`
- `createdAt`
- `submittedAt`
- `confirmedAt`
- `preparingAt`
- `readyAt`
- `deliveredAt`
- `canceledAt`

### Statuts

- `STATUS_PENDING_VALIDATION`
- `STATUS_CONFIRMED`
- `STATUS_PREPARING`
- `STATUS_READY_FOR_PICKUP`
- `STATUS_OUT_FOR_DELIVERY`
- `STATUS_DELIVERED`
- `STATUS_CANCELED`

### Ajouts prévus J5

- `outForDeliveryAt`
- `courierAssignedAt`
- `assignedCourier` ou `courier`

Le nom exact sera fixé selon l'entité utilisateur existante.

Option recommandée :

```text
CustomerOrder.assignedCourier -> User
```

---

## OrderItem

Ligne de commande.

### Rôle

Représente un produit commandé dans une commande.

### Relations

- `customerOrder`
- `product`
- `seller`

### Champs importants

- quantité ;
- prix unitaire ;
- total ligne.

### J4

- CRUD EasyAdmin créé ;
- affichage en lecture principalement opérationnelle ;
- utilisé dans la fiche terrain ;
- visible dans le détail commande.

---

## Seller

Vendeur / producteur / revendeur local.

Utilisé pour :

- associer les produits ;
- savoir d'où vient chaque article ;
- préparer les étapes futures de relation vendeur.

---

## Product

Produit vendu sur Hodina.

Relié à :

- vendeur ;
- catégorie ;
- lignes de commande.

Utilisé dans :

- catalogue ;
- panier ;
- commande ;
- fiche terrain ;
- dashboard livreur.

---

## Category

Catégorie de produit.

Exemples :

- fruits ;
- légumes ;
- artisanat ;
- autres produits locaux.

---

## DeliveryZone

Zone de livraison.

Rôle :

- distinguer Petite-Terre / Grande-Terre ou d'autres zones ;
- organiser les tournées ;
- filtrer ou orienter les commandes.

Utilisée dans :

- adresse ;
- commande ;
- backoffice ;
- futur dashboard livreur.

---

## SmsLog

Trace de notification SMS.

### Rôle

SmsLog sert à historiser les messages prévus ou générés automatiquement.

Pendant le pilote, il ne représente pas forcément un SMS envoyé par un fournisseur externe.

### Décisions J4

- création automatique ;
- lecture seule ;
- bouton `Envoyer le SMS` ;
- préremplissage numéro + message testé sur iPhone ;
- plan B prévu si besoin.

### Champs / données importantes

Selon l'implémentation actuelle :

- type / événement ;
- message ;
- téléphone destinataire ou accès au téléphone via commande/client ;
- commande liée si applicable ;
- date de création.

### Évolutions possibles

- `sentManuallyAt`
- `sentBy`
- `sendStatus`
- intégration fournisseur SMS.

---

## HodinaSetting

Entité ajoutée pour les réglages génériques Hodina.

### Modèle retenu

```text
1 ligne = 1 paramètre
```

### Champs

- `label`
- `settingKey`
- `value`
- `help`
- `fieldType`
- `updatedAt`

### Réglages actuels

#### order_reference_prefix

Préfixe des numéros de commande.

Exemple :

```text
hodina
```

Produit des références comme :

```text
hodina202606041
```

#### delivered_communes

Liste des communes livrées.

Interface :

- une commune par champ ;
- bouton `Ajouter une commune` ;
- suppression commune par commune.

---

# Entité utilisateur / sécurité

## User ou entité équivalente

À préciser selon l'état exact du projet.

Rôles à gérer :

- `ROLE_ADMIN`
- `ROLE_COURIER`
- `ROLE_CUSTOMER`

Pour J5, cette entité devra permettre d'identifier le livreur connecté.

---

# Service métier prévu J5

Même s'il ne s'agit pas d'une entité Doctrine, il devient une pièce majeure du domaine :

```text
CustomerOrderWorkflowService
```

Responsabilités :

- transitions de statuts ;
- dates métier ;
- SmsLog ;
- association livreur ;
- vérifications métier ;
- non-duplication du code entre admin et livreur.

---

# Entités / données ajoutées ou impactées le 05/06/2026

## Customer — réinitialisation mot de passe

Le parcours de réinitialisation de mot de passe ajoute des informations temporaires côté client.

Champs attendus selon l'implémentation du patch :

- token de réinitialisation ;
- date d'expiration du token.

Règles métier :

- le token est temporaire ;
- le lien de reset est transmis via un SmsLog ;
- le token est supprimé ou invalidé après utilisation ;
- le reset est destiné aux clients Hodina.

## SmsLog — lien de réinitialisation

`SmsLog` est désormais utilisé aussi pour tracer l'envoi manuel d'un lien de réinitialisation de mot de passe.

Nouveau cas d'usage :

```text
Mot de passe oublié
→ token généré
→ SmsLog créé avec lien de reset
→ envoi manuel depuis l'iPhone
```

Cela confirme le rôle de `SmsLog` comme journal pilote des messages à envoyer manuellement, et non comme preuve technique d'un envoi provider.

## Pages légales

Les CGU et CGV ne nécessitent pas d'entité Doctrine dédiée pour le pilote.

Choix retenu :

```text
Pages Twig statiques versionnées dans le code
```

Routes :

- `/cgu` ;
- `/cgv`.

Évolution possible plus tard : stocker les versions en base si Hodina a besoin d'historiser l'acceptation de versions précises par client.

## Acceptation CGU/CGV

Le checkout exige l'acceptation des CGU/CGV.

État pilote :

- l'acceptation est vérifiée au moment de la validation commande ;
- le bouton de validation reste désactivé tant que la case n'est pas cochée.

Évolution future possible :

- stocker `termsAcceptedAt` ;
- stocker `termsVersion` ;
- historiser l'acceptation par commande.

## Environnement préprod

La préprod n'ajoute pas d'entité applicative, mais introduit une base dédiée :

```text
vopu3712_hodina_recette
```

Cette base est une copie de la base de développement pour tests fonctionnels.


---

# État entités / services après J5C — 06/06/2026

## Customer — rôle livreur MVP

Pour le MVP, un livreur est un `Customer` avec le rôle :

```text
ROLE_COURIER
```

Cela permet de réutiliser l'authentification existante.

## CustomerOrder — champs livraison ajoutés

J5C ajoute :

```text
assignedCourier
courierAssignedAt
outForDeliveryAt
```

### assignedCourier

Relation :

```text
CustomerOrder.assignedCourier -> Customer
```

Rôle : identifier le livreur qui a pris la commande.

### courierAssignedAt

Rôle : conserver la date d'association du livreur à la commande.

### outForDeliveryAt

Rôle : conserver la date de départ en livraison.

## CustomerOrderWorkflowService — méthodes livraison ajoutées

Méthodes ajoutées :

```text
canTakeForDelivery()
takeForDelivery()
canMarkDeliveredByCourier()
markDeliveredByCourier()
```

Ces méthodes préparent le dashboard livreur.

## CustomerOrderCrudController — affichage admin

Le détail commande affiche désormais :

```text
Livreur assigné
Livreur assigné le
Départ livraison le
```

Avant la création du dashboard livreur, ces champs restent normalement à `Null`.

## Sécurité

Le rôle suivant est préparé :

```text
ROLE_COURIER
```

La route future `/djama` est réservée à ce rôle.

## Base de données

Migrations J5C :

```text
Version20260606101500
Version20260606091936
Version20260606103000
```

État final :

```text
Mapping OK
Database schema in sync
```


---

# Entités et champs prévus après J5D

## Customer — futurs rôles

L'entité `Customer` reste l'entité authentifiable du MVP.

Rôles actuels / prévus :

```text
ROLE_CUSTOMER
ROLE_COURIER
ROLE_ADMIN
ROLE_SELLER
```

`ROLE_SELLER` est prévu pour le futur portail vendeur.

## Seller — évolution prévue

Le vendeur devient une entité centrale pour :

- les produits ;
- la marge vendeur ;
- la localisation logistique ;
- le futur portail vendeur.

Champs / relations à prévoir :

```text
marginRate
owner ou account -> Customer
deliveryCommune -> DeliveryCommune
pickupAddress ou pickupInstructions
```

Pendant le pilote, `Seller.deliveryCommune` est important pour calculer :

- commune voisine ;
- commune éloignée ;
- barge ;
- zone tarifaire.

## Product — évolution marge

Champs à clarifier ou ajouter :

```text
producerPrice
marginRate nullable
status
submittedAt
approvedAt
rejectedAt
```

Pour le pilote J5E, les champs essentiels sont :

```text
producerPrice
marginRate
```

Le prix client ne doit pas être saisi directement par le vendeur.

## OrderItem — figer les prix

Pour éviter que les anciennes commandes changent quand les marges changent, `OrderItem` devra figer :

```text
producerUnitPrice
appliedMarginRate
hodinaMarginAmount
unitPrice
lineTotal
```

Si certains champs existent déjà, il faudra les réutiliser proprement au lieu de dupliquer inutilement.

## CustomerOrder — figer la livraison

Pour la tarification livraison, `CustomerOrder` devra figer :

```text
deliveryFee
courierPayout
deliveryMargin
deliveryPricingZoneName
deliveryCommuneName
clientTerritory
requiresBarge
logisticsLevel
```

Raison : une commande ancienne ne doit pas changer si l'admin modifie une zone tarifaire.

## DeliveryPricingZone — nouvelle entité prévue J5F

Rôle : définir les montants économiques d'une zone de livraison.

Champs recommandés :

```text
id
name
code
customerDeliveryFee
courierPayout
active
internalNote
createdAt
updatedAt
```

Calcul associé :

```text
deliveryMargin = customerDeliveryFee - courierPayout
```

## DeliveryCommune — nouvelle entité prévue J5F

Rôle : remplacer progressivement les communes texte par une donnée métier administrable.

Champs recommandés :

```text
id
name
territory
active
localPricingZone
bargePricingZone
neighboringCommunes
internalNote
createdAt
updatedAt
```

Territoires :

```text
PT
GT
```

## Communes voisines

La relation `neighboringCommunes` permet à l'admin de définir les communes proches.

Règle service : la relation doit être traitée comme symétrique.

```text
Si A est voisine de B,
le service considère aussi B voisine de A.
```

## CartLogisticsPreview — objet de transfert prévu J5G

Ce n'est pas forcément une entité Doctrine.

Rôle : transporter le résultat du calcul logistique vers le panier.

Champs possibles :

```text
addressRequired
clientCommune
clientTerritory
requiresBarge
hasNeighborSeller
hasRemoteSeller
relationLevel
estimatedDeliveryFee
estimatedCourierPayout
pricingZoneName
message
```


---

# État entités / services après J5E — 07/06/2026

## Product — prix producteur et marge produit

Champs ajoutés :

```text
producerPrice
marginRate
```

`producerPrice` représente le prix demandé par le vendeur.

`marginRate` représente une marge Hodina spécifique au produit, en pourcentage.

Le champ historique `price` reste présent pour compatibilité. Dans les nouveaux développements, ne pas le réutiliser comme source principale du prix client.

Règle dans `ProductPricingService` :

```text
Si producerPrice existe et est supérieur à 0
→ utiliser producerPrice
Sinon
→ utiliser price comme secours temporaire
```

## Seller — marge vendeur

Champ ajouté :

```text
marginRate
```

Il définit une marge spécifique vendeur, utilisée uniquement si le produit n'a pas de marge spécifique.

## HodinaSetting — marge globale

Constante ajoutée :

```text
KEY_GLOBAL_MARGIN_RATE = 'global_margin_rate'
```

Réglage créé :

```text
setting_key = global_margin_rate
label = Marge globale Hodina (%)
value = 20.00
field_type = text
```

## OrderItem — snapshot économique produit

Champs ajoutés :

```text
producerUnitPrice
appliedMarginRate
hodinaMarginAmount
```

Ces champs complètent :

```text
unitPrice
lineTotal
```

Rôle : conserver les valeurs économiques réelles au moment de la commande.

## ProductPricingService

Nouveau service métier :

```text
src/Service/ProductPricingService.php
```

Méthodes :

```text
getProducerPrice()
getEffectiveMarginRate()
getGlobalMarginRate()
getCustomerPrice()
getHodinaMarginAmount()
getPriceBreakdown()
```

À ne pas faire : calculer le prix client dans Twig, dans un contrôleur ou dans le futur portail vendeur.

À faire : appeler `ProductPricingService`.

## Migration J5E

```text
Version20260607120000
```

Colonnes créées :

```text
product.producer_price
product.margin_rate
seller.margin_rate
order_item.producer_unit_price
order_item.applied_margin_rate
order_item.hodina_margin_amount
```

La migration initialise aussi `product.producer_price = product.price` si `producer_price` est vide.

## Entités prévues ensuite — J5F

```text
DeliveryPricingZone
DeliveryCommune
Seller.deliveryCommune
```

Ces entités permettront de calculer les frais de livraison client, la rémunération livreur, la marge livraison Hodina, la barge et la relation commune client / commune vendeur.

---

# Clarification entités J5F — Territory, barge et communes voisines

## DeliveryCommune.territory

Le champ `territory` est la donnée qui permettra de déterminer la traversée Petite-Terre / Grande-Terre.

Valeurs pilote :

```text
PT
GT
```

## Rôle exact de `territory`

`territory` sert à répondre à la question :

```text
La commande implique-t-elle une traversée entre Petite-Terre et Grande-Terre ?
```

Règle :

```text
client.territory !== seller.territory
→ barge requise

client.territory === seller.territory
→ pas de barge
```

## Rôle exact de `neighboringCommunes`

`neighboringCommunes` ne sert pas à calculer la barge.

Cette relation sert à savoir si, sur un même territoire, un vendeur est dans une commune proche ou non.

Exemples :

```text
Dzaoudzi et Pamandzi
→ PT / PT
→ pas de barge
→ peut être commune voisine selon paramétrage admin
```

```text
Dzaoudzi et Mamoudzou
→ PT / GT
→ barge
→ relation OTHER_TERRITORY
```

## Rôle exact de `localPricingZone`

Zone tarifaire utilisée quand tous les vendeurs du panier sont sur le même territoire que le client.

## Rôle exact de `bargePricingZone`

Zone tarifaire utilisée uniquement si au moins un vendeur est sur l'autre territoire.

## Vigilance

Ne pas créer de champ du type `requiresBarge` directement sur `DeliveryCommune`.

La barge dépend d'une comparaison entre :

```text
commune client
communes vendeurs du panier
```

Elle n'est pas une propriété fixe d'une commune.


---

# État entités après J5F-A — DeliveryPricingZone et DeliveryCommune réalisés

## DeliveryPricingZone — réalisé

Entité :

```text
src/Entity/DeliveryPricingZone.php
```

Table :

```text
delivery_pricing_zone
```

Champs réels :

```text
id
name
code
customer_delivery_fee
courier_payout
is_active
internal_note
created_at
updated_at
```

Attention : en base, le champ s'appelle `is_active`, pas `active`.

Rôle de chaque champ :

- `name` : nom lisible admin ;
- `code` : code stable, par exemple `PT_LOCAL` ;
- `customerDeliveryFee` : frais payés par le client ;
- `courierPayout` : rémunération prévue du livreur ;
- `isActive` : zone utilisable ou non ;
- `internalNote` : note admin non destinée au client.

Méthode calculée :

```text
getDeliveryMargin()
```

Formule :

```text
customerDeliveryFee - courierPayout
```

## DeliveryCommune — réalisé

Entité :

```text
src/Entity/DeliveryCommune.php
```

Table :

```text
delivery_commune
```

Champs réels :

```text
id
local_pricing_zone_id
barge_pricing_zone_id
name
territory
is_active
internal_note
created_at
updated_at
```

Attention : en base, le champ s'appelle `is_active`, pas `active`.

## Territoires

Constantes :

```text
TERRITORY_PT = PT
TERRITORY_GT = GT
```

La barge se calcule par comparaison de `territory`.

## Communes voisines

Relation ManyToMany :

```text
delivery_commune_neighbor
```

Colonnes :

```text
commune_id
neighbor_id
```

Cette relation doit être traitée comme symétrique par le service.

J5F-B vérifie les deux sens :

```text
A contient B dans neighboringCommunes
ou
B contient A dans neighboringCommunes
```

## Seller.deliveryCommune — réalisé

`Seller` possède désormais :

```text
deliveryCommune
```

Relation :

```text
Seller.deliveryCommune -> DeliveryCommune
```

Base :

```text
seller.delivery_commune_id
```

Rôle : commune de retrait / production du vendeur pour les calculs logistiques.

## Ancien champ Seller.commune

`Seller.commune` reste présent comme champ texte historique.

Il ne doit plus être utilisé pour les nouveaux calculs.

Pour J5F / J5G / J6, utiliser :

```text
Seller.deliveryCommune
```


---

# État DTO / services après J5F-B

## CartLogisticsPreview — réalisé

Fichier :

```text
src/Dto/CartLogisticsPreview.php
```

Ce n'est pas une entité Doctrine.

C'est un DTO, c'est-à-dire un objet qui transporte un résultat calculé.

Relations possibles :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
UNKNOWN
```

Données transportées :

```text
addressRequired
clientCommuneName
clientTerritory
requiresBarge
hasNeighborSeller
hasRemoteSeller
hasUnknownSellerCommune
relationLevel
estimatedDeliveryFee
estimatedCourierPayout
estimatedDeliveryMargin
pricingZoneName
pricingZoneCode
message
warnings
```

## DeliveryLogisticsService — réalisé

Fichier :

```text
src/Service/DeliveryLogisticsService.php
```

Ce service produit un `CartLogisticsPreview`.

Il est prêt à être branché dans le panier en J5G-A.

## Données à figer plus tard dans CustomerOrder

J5F-B prépare, mais ne fige pas encore.

Les futurs champs à figer ou à représenter dans `CustomerOrder` seront :

```text
clientCommuneName
clientTerritory
requiresBarge
logisticsLevel
deliveryFee
courierPayout
deliveryMargin
pricingZoneName
pricingZoneCode
```

Raison : une commande ancienne ne doit pas changer si l'admin modifie une zone tarifaire après coup.

---

# Mise à jour entités J5G — Livraison avancée

## CartLogisticsPreview enrichi

`CartLogisticsPreview` est un DTO, pas une entité Doctrine.

Il doit progressivement transporter les informations nécessaires à l'affichage panier.

Champs déjà utiles :

```text
addressRequired
clientCommuneName
clientTerritory
requiresBarge
relationLevel
estimatedDeliveryFee
estimatedCourierPayout
estimatedDeliveryMargin
message
warnings
```

Champs recommandés pour J5G-B / J5G-D :

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
```

## DeliveryCommune.neighboringCommunes

La relation de communes voisines prend une importance plus forte.

Elle sert désormais à représenter le graphe logistique.

```text
commune = nœud du graphe
voisinage = lien direct entre deux communes
```

Le service devra traiter le voisinage comme symétrique.

```text
Si A est voisine de B,
le calcul doit considérer que B est aussi voisine de A.
```

Même si en base une seule direction est enregistrée, le service doit pouvoir sécuriser la lecture.

## HodinaSetting — nouveaux réglages livraison avancée

Réglages recommandés :

```text
delivery_commune_hop_customer_fee
delivery_commune_hop_courier_payout
delivery_barge_round_trip_customer_fee
delivery_barge_round_trip_courier_payout
```

Ces réglages évitent de coder les montants en dur.

## CustomerOrder — snapshot livraison futur

Pour J5G-E, `CustomerOrder` devra conserver les valeurs calculées au checkout.

Champs de snapshot recommandés :

```text
courierPayout
deliveryMargin
requiresBarge
deliveryPricingZoneName
deliveryPricingZoneCode
clientDeliveryCommuneName
clientTerritory
logisticsLevel
logisticsHopCount
logisticsPathSummary
bargeCustomerFee
bargeCourierPayout
communeHopCustomerFee
communeHopCourierPayout
```

Le champ `deliveryFee` existe déjà et devra porter le total frais livraison client.

## Pourquoi ces champs sont importants

Les réglages logistiques changeront avec l'expérience terrain.

Sans snapshot, une ancienne commande pourrait sembler avoir des frais différents après modification des paramètres.

La règle du projet reste :

```text
calcul dynamique avant validation
snapshot figé après validation
```


---

# Entités J5G-B1 — Communes et liaisons logistiques modifiables

## Source validée

Le fichier :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

est validé comme source initiale des données de communes et voisinages.

Mais cette source doit être transformée en données Doctrine modifiables.

## Évolution de DeliveryCommune

`DeliveryCommune` ne doit plus être vue uniquement comme une commune livrée.

Elle devient un **point logistique**.

Un point logistique peut être :

```text
commune officielle
localité utile terrain
point de passage barge
point de retrait ou livraison
```

## Champs recommandés

```text
name
slug
territory
postalCode
inseeCode
parentInseeCode
isLogisticsPoint
isActive
localPricingZone
internalNote
createdAt
updatedAt
```

## Cas Labattoir

Labattoir doit être conservé pour le calcul logistique, mais avec prudence.

Décision :

```text
Labattoir = point logistique Hodina
parentInseeCode = code de Dzaoudzi si confirmé
territory = PT
postalCode = 97615 si confirmé par source officielle
```

Pourquoi : Labattoir est utile terrain, mais ne doit pas forcément être confondu avec une commune administrative indépendante.

## Nouvelle entité recommandée : DeliveryCommuneConnection

Rôle : représenter les liaisons entre points logistiques.

Champs recommandés :

```text
fromCommune
toCommune
linkType
isBidirectional
hopCount
isActive
internalNote
createdAt
updatedAt
```

## Valeurs linkType

```text
LAND
→ liaison terrestre

BARGE
→ liaison maritime par barge
```

## Pourquoi cette entité est préférable à une relation ManyToMany simple ?

Une relation ManyToMany simple dit seulement :

```text
A est voisin de B
```

Elle ne dit pas :

```text
la liaison est terrestre ou maritime
la liaison est active ou inactive
la liaison a une note terrain
la liaison est bidirectionnelle
```

Pour J5G-B, ces informations deviennent nécessaires.

## Données futures dans CartLogisticsPreview

Le DTO devra être enrichi pour transporter :

```text
path
pathSummary
landHopCount
bargeHopCount
requiresBarge
baseDeliveryFee
communeHopCustomerFee
bargeCustomerFee
estimatedDeliveryFee
estimatedCourierPayout
estimatedDeliveryMargin
```

## Données futures dans CustomerOrder

Au checkout, les valeurs devront être figées :

```text
logisticsPathSummary
landHopCount
bargeHopCount
requiresBarge
baseDeliveryFee
communeHopCustomerFee
bargeCustomerFee
deliveryFee
courierPayout
deliveryMargin
```

Cela évite qu'une commande ancienne change si l'admin modifie les liaisons ou les montants.

---

# Mise à jour entités — J5G-B2 / J5G-B3

## DeliveryCommune — enrichissement validé

`DeliveryCommune` représente désormais un point logistique Hodina, pas seulement une commune administrative.

Champs confirmés :

```text
id
name
territory
slug
postalCode
inseeCode
parentInseeCode
isLogisticsPoint
localPricingZone
bargePricingZone
neighboringCommunes
isActive
internalNote
createdAt
updatedAt
```

Notes :

- `slug` sert aux seeds et aux imports stables ;
- `postalCode` garde la donnée utile terrain ;
- `inseeCode` correspond à une commune officielle quand disponible ;
- `parentInseeCode` permet de rattacher un point terrain à une commune administrative ;
- `isLogisticsPoint` indique que Hodina peut utiliser ce point dans les calculs ;
- `neighboringCommunes` est conservé pour compatibilité J5F-A/J5F-B mais ne doit plus être la source principale du calcul avancé.

## DeliveryCommuneConnection — nouvelle entité J5G-B2

Rôle : représenter une liaison entre deux points logistiques.

Champs :

```text
id
fromCommune
toCommune
linkType
isBidirectional
hopCount
customerExtraFee
courierExtraPayout
isActive
internalNote
createdAt
updatedAt
```

Types de lien :

```text
LAND  = route terrestre
BARGE = traversée maritime
```

Pourquoi cette entité existe :

```text
DeliveryCommune.neighboringCommunes dit seulement "voisin".
DeliveryCommuneConnection dit "voisin par route" ou "voisin par barge".
```

C'est cette distinction qui permet ensuite de calculer :

- nombre de sauts terrestres ;
- présence d'une barge ;
- trajet complet ;
- supplément client ;
- supplément livreur ;
- marge logistique Hodina.

## Données seedées J5G-B3

Points logistiques seedés :

```text
Dzaoudzi
Labattoir
Pamandzi
Acoua
Bandraboua
Bandrélé
Bouéni
Chiconi
Chirongui
Dembéni
Kani-Kéli
Koungou
M'Tsangamouji
Mamoudzou
Mtsamboro
Ouangani
Sada
Tsingoni
```

Liaisons critiques seedées :

```text
Dzaoudzi → Mamoudzou       BARGE
Dzaoudzi → Labattoir       LAND
Dzaoudzi → Pamandzi        LAND
Labattoir → Pamandzi       LAND
Mamoudzou → Koungou        LAND
Mamoudzou → Dembéni        LAND
Mamoudzou → Ouangani       LAND
```

Toutes les liaisons seedées sont actives et bidirectionnelles pour le pilote.

## Impact sur Seller

`Seller.deliveryCommune` reste le rattachement vendeur utilisé par le calcul logistique.

J5G-B4 devra partir de :

```text
Product → Seller → DeliveryCommune
Address → commune client → DeliveryCommune
```

puis calculer le chemin entre les deux.

## Address — évolution support livraison / facturation

`Address` évolue pour porter un type métier.

### Types d'adresse

```text
DELIVERY = adresse de livraison
BILLING  = adresse de facturation
```

### Adresse de livraison

Une adresse de livraison doit permettre le calcul logistique. Elle doit donc être compatible avec :

```text
delivery_commune
delivery_zone PT / GT
DeliveryLogisticsService
```

Règles :

```text
commune livrable obligatoire
code postal français à 5 chiffres
cohérence commune / code postal
cohérence commune / zone PT ou GT
zone AUTRE interdite
```

### Adresse de facturation

Une adresse de facturation n'est pas forcément livrable par Hodina.

Règles :

```text
commune libre
code postal français à 5 chiffres
zone AUTRE — Autre
```

### Validation imbriquée dans Customer

Comme les adresses peuvent être créées depuis le formulaire utilisateur EasyAdmin, les relations dans `Customer` doivent déclencher la validation des adresses enfants.

Concept Symfony utilisé :

```text
#[Assert\Valid]
```

### Point technique important

Les propriétés texte d'`Address` doivent être initialisées pour éviter les erreurs EasyAdmin sur objet imbriqué incomplet :

```text
line1 = ''
postalCode = ''
commune = ''
deliveryZone temporairement nullable côté PHP pendant la validation formulaire
```

---

# Mise à jour entités — 12/06/2026 — Address / Customer finalisés pour livraison et facturation

## Address — état final

`Address` n'est plus seulement une adresse de livraison. Elle porte un usage métier.

### Constantes

```text
Address::TYPE_DELIVERY = DELIVERY
Address::TYPE_BILLING  = BILLING
```

### Champs importants

```text
customer
label
type
line1
line2
postalCode
commune
deliveryZone
notes
createdAt
```

### Rôle du champ `deliveryZone`

Le nom historique `deliveryZone` reste présent techniquement, mais il est utilisé comme zone géographique de l'adresse.

Interprétation métier :

```text
sur une adresse DELIVERY
→ zone de livraison

sur une adresse BILLING
→ zone de facturation
```

### Zone AUTRE

`AUTRE — Autre` existe dans `DeliveryZone`.

Elle ne doit pas être utilisée pour une livraison.

Elle peut être utilisée pour une facturation hors zone Hodina.

### Validation

La classe `DeliverableAddressValidator` valide l'objet complet.

Elle utilise `DeliveryCommuneMatcherService` pour comparer :

```text
commune saisie
code postal saisi
zone choisie
données seedées dans delivery_commune
```

## Customer — validation imbriquée

Les adresses sont créées et modifiées dans l'utilisateur EasyAdmin. Pour éviter que Doctrine persiste une adresse enfant invalide, `Customer` valide ses adresses imbriquées.

Concept :

```text
#[Assert\Valid]
```

## Suppression d'adresses

La relation `Customer -> addresses` doit supprimer réellement les adresses retirées de la collection EasyAdmin.

Comportement attendu :

```text
admin supprime une adresse
→ sauvegarde utilisateur
→ adresse supprimée de la base
→ elle ne réapparaît pas au rechargement
```

Si l'adresse supprimée était l'adresse de facturation sélectionnée, la référence doit être désassociée.

## Customer.email

La validation `UniqueEntity` sur `Customer.email` a été retirée pour éviter un double message côté inscription.

L'unicité métier est contrôlée dans :

```text
RegistrationController
CheckoutController
```

Le contrôle manuel est conservé pour fournir un message plus adapté au parcours.

---

# Entités prévues — 13/06/2026 — EmailLog, LaunchSubscriber, préouverture

## EmailLog — prévu J5H

Rôle : tracer les e-mails envoyés par Hodina.

Champs recommandés :

```text
id
customerOrder nullable
customer nullable
recipientEmail
subject
bodyPreview nullable
templateKey
eventKey
status: PENDING / SENT / FAILED
errorMessage nullable
sentAt nullable
createdAt
```

## LaunchSubscriber — prévu J5I

Rôle : stocker les visiteurs souhaitant être prévenus de l'ouverture des commandes Hodina.

Champs recommandés :

```text
id
email
firstName nullable
sourcePage nullable
isNotified
notifiedAt nullable
createdAt
ipHash nullable
userAgentHash nullable
```

Règle : email obligatoire, unique, sans doublon.

## SalesOpeningSetting — prévu J5I

Rôle : paramétrer la préouverture commerciale depuis EasyAdmin.

Champs recommandés :

```text
isCountdownEnabled
salesOpeningAt
countdownTitle
countdownMessage
countdownButtonLabel
isEmailCaptureEnabled
isCartLockedBeforeOpening
successMessage
updatedAt
```

Cette entité peut être dédiée ou remplacée par des clés `HodinaSetting` si l'architecture finale préfère conserver une configuration générique.


---

# Entités livrées — J5I préouverture

## LaunchSubscriber — implémenté

Rôle : enregistrer les visiteurs qui demandent à être prévenus quand Hodina ouvrira les commandes.

Différence avec `Customer` :

```text
Customer = compte client capable de commander.
LaunchSubscriber = simple e-mail de préouverture, sans compte client obligatoire.
```

Champs présents dans la migration livrée :

```text
id INT AUTO_INCREMENT
email VARCHAR(180) NOT NULL UNIQUE
source VARCHAR(80) NOT NULL
ip_address VARCHAR(45) NULL
user_agent VARCHAR(255) NULL
created_at DATETIME NOT NULL
```

Règles métier :

```text
email obligatoire
email unique
un doublon ne doit pas créer une deuxième ligne
source permet de savoir d'où vient l'inscription
created_at permet de dater la demande
```

## HodinaSetting — paramètres J5I ajoutés

La préouverture n'utilise pas une entité `SalesOpeningSetting` dédiée. Elle utilise les clés suivantes dans `hodina_setting` :

```text
is_countdown_enabled
sales_opening_at
countdown_title
countdown_message
countdown_button_label
is_email_capture_enabled
is_cart_locked_before_opening
countdown_success_message
```

Un développeur débutant doit retenir : pour modifier le comportement de préouverture, commencer par regarder `hodina_setting`, puis `SalesOpeningService`.

---

# Évolutions J5J — HodinaSetting, Customer roles et LaunchSubscriber

## HodinaSetting

`HodinaSetting` porte désormais les paramètres génériques du mode commerce :

```text
commerce_mode
commerce_reopens_at
commerce_cart_locked
commerce_allow_testers
commerce_banner_title
commerce_banner_message
commerce_banner_button_label
commerce_email_capture_enabled
commerce_success_message
```

Le champ `field_type` détermine l'affichage EasyAdmin : `text`, `textarea`, `boolean`, `choice`.

## Customer.roles

Les clients peuvent recevoir le rôle :

```text
ROLE_COMMERCE_TESTER
```

Ce rôle autorise le test du panier et du checkout pendant un blocage public.

## LaunchSubscriber

L'entité `LaunchSubscriber` reste utilisée pour stocker les e-mails capturés depuis la bannière lorsque `commerce_email_capture_enabled = 1`.

---

# Entité J5H-A — EmailLog

## Rôle

`EmailLog` journalise les e-mails transactionnels Hodina.

## Champs principaux

```text
id
customerOrder nullable
customer nullable
recipientEmail
subject
templateKey
eventKey
status
errorMessage nullable
sentAt nullable
createdAt
```

## Index

```text
customer_order_id
customer_id
status
event_key
```

## Statuts utilisés

```text
PENDING
SENT
FAILED
```

## Événement utilisé J5H-A

```text
ORDER_CREATED
```

## Règle métier

`EmailLog` n'est pas une preuve juridique de livraison e-mail. Pour le pilote, il sert à :

- savoir qu'un e-mail a été demandé ;
- voir si Symfony / Messenger l'a accepté ;
- voir les erreurs SMTP ou applicatives ;
- permettre à l'admin de relancer manuellement via son client mail.

## Limite connue

Avec Messenger async, `SENT` signifie actuellement “accepté par Symfony Mailer / Messenger”. Le vrai envoi SMTP dépend du worker Messenger.

---

# Entité CustomerOrder — Snapshot adresse commande validé J5G-E0

## Statut

Le snapshot adresse, auparavant prévu comme futur chantier, est désormais livré et validé en recette dans J5G-E0.

Migration : `Version20260615225836`.

## Champs ajoutés

### Snapshot livraison

```text
deliveryAddressLabel
deliveryAddressLine1
deliveryAddressLine2
deliveryAddressPostalCode
deliveryAddressCommune
deliveryAddressZoneCode
deliveryAddressZoneName
deliveryAddressNotes
```

### Snapshot facturation

```text
billingAddressLabel
billingAddressLine1
billingAddressLine2
billingAddressPostalCode
billingAddressCommune
billingAddressZoneCode
billingAddressZoneName
billingAddressNotes
```

## Rôle de la relation deliveryAddress

`deliveryAddress` reste présent pour compatibilité et traçabilité, mais ne doit plus être la seule source d'adresse d'une commande.

Après suppression d'une adresse client, `deliveryAddress` peut être nul. Les champs snapshot doivent rester la source de vérité historique.

## Règle de suppression des adresses

Les adresses du carnet client peuvent être supprimées. Cette suppression ne doit pas supprimer ou modifier les commandes passées.

La commande conserve :

- l'adresse de livraison utilisée ;
- l'adresse de facturation utilisée ;
- les informations de zone nécessaires à la lecture métier.

## Dette restante

J5G-E0 ne fige pas encore tous les détails logistiques financiers. Les futurs champs de frais, rémunération livreur, route et barge restent à traiter dans J5G-E.

---

# Entités — Impact prévu J5G-E1 commune livrée

## DeliveryCommune

`DeliveryCommune` doit devenir la référence fonctionnelle de la saisie d'adresse de livraison.

Champs déjà utiles :

- `name` : nom affichable ;
- `slug` : identifiant stable ;
- `postalCode` : code postal principal ;
- `territory` : `PT` ou `GT` ;
- `isActive` : commune utilisable ;
- `isLogisticsPoint` : point utile terrain ;
- `localPricingZone` / `bargePricingZone` : zones tarifaires aval.

## Address

Pour J5G-E1 MVP, `Address` peut conserver son modèle actuel :

```text
line1
line2
postalCode
commune
deliveryZone
type
```

La différence est que `postalCode`, `commune` et `deliveryZone` doivent être alimentés depuis `DeliveryCommune`, pas saisis indépendamment par le client.

## CustomerOrder

J5G-E1 ne doit pas modifier le principe livré par J5G-E0 : `CustomerOrder` garde son snapshot adresse.

Après checkout :

```text
Address = carnet vivant
CustomerOrder.delivery_address_* = historique figé
```

## Option future non prioritaire

Une relation `Address.deliveryCommune` pourrait être ajoutée plus tard si les besoins de reporting, nettoyage ou cohérence deviennent plus forts. Elle n'est pas obligatoire pour réduire la friction du checkout pilote.

---

# Mise à jour entités / services — J5G-E1 → J5G-E2-bis-A

## Entités

Aucune nouvelle entité n'a été ajoutée pendant J5G-E1 → E2-bis-A.

Le jalon réutilise les entités déjà présentes :

```text
DeliveryCommune
DeliveryCommuneConnection
DeliveryPricingZone
DeliveryZone
Address
CustomerOrder
OrderItem
Seller
Product
```

## `DeliveryCommune`

Son rôle est renforcé : elle devient la source de vérité UX pour la commune livrée.

Champs importants dans ce jalon :

```text
name
postalCode
territory
isActive
isLogisticsPoint
localPricingZone
```

## `Address`

`Address` reste compatible avec le modèle existant :

```text
commune = nom de la commune livrée
postalCode = code postal DeliveryCommune
zone = zone déduite serveur
```

Aucune relation obligatoire `Address -> DeliveryCommune` n'est ajoutée pour le moment.

## `CustomerOrder`

J5G-E1 s'appuie sur les snapshots J5G-E0.

La commande doit conserver :

```text
delivery_address_line1
delivery_address_postal_code
delivery_address_commune
delivery_address_zone_code
delivery_address_zone_name
delivery_fee
total
```

## Services

### `DeliveryCommuneMatcherService`

Utilisé pour résoudre la commune choisie et empêcher une zone incohérente.

### `DeliveryLogisticsService`

Utilisé pour calculer :

```text
frais livraison estimés
barge requise
chemin affiché
warnings
```

La règle temporaire validée est :

```text
localPricingZone + supplément fixe BARGE
```

## Pas de migration

Aucune migration n'est nécessaire pour J5G-E1 → E2-bis-A, car les structures utiles existaient déjà.

---

# État entités production — J5G-E1 → J5G-E2-bis-A

Date : **17/06/2026**
Production validée : `j5g-e1-e2bis-prod`

## Migrations production exécutées

La production a exécuté les migrations jusqu'à :

```text
DoctrineMigrations\Version20260615225836
```

Cela confirme que les entités / tables nécessaires aux jalons précédents sont disponibles en production, notamment :

```text
EmailLog
CustomerOrder snapshots adresse livraison / facturation
```

## Aucun changement de modèle pour J5G-E1 → E2-bis-A

Le jalon commune livrée / panier contractuel n'ajoute pas d'entité supplémentaire.

Il réutilise :

```text
DeliveryCommune
DeliveryCommuneConnection
DeliveryPricingZone
Address
CustomerOrder
OrderItem
```

## Point important pour la suite

Pour J5G-B4, ne pas créer une nouvelle table de graphe si `DeliveryCommuneConnection` suffit.

Le graphe logistique doit partir de :

```text
DeliveryCommune = nœud
DeliveryCommuneConnection = arête
DeliveryLogisticsService = calcul métier
```

# Entités — Complément J5G-B4

## CustomerOrder

Ajout :

```text
deliveryLogisticsSnapshot : json nullable
```

Rôle : conserver le détail du calcul logistique au moment de la commande.

Ce snapshot peut contenir :

```text
commune livrée
territoire
nombre de vendeurs
communes de collecte distinctes
trajets de collecte
liaisons LAND / BARGE
forfait local
coût trajet
supplément multicommunes
plafond global
frais final client
payout livreur estimé
marge livraison
lignes produits
```

## DeliveryCommuneConnection

Les champs de coûts sont désormais réellement utilisés dans le calcul :

```text
customerExtraFee
courierExtraPayout
connectionType LAND / BARGE
isBidirectional
isActive
```

Règle importante :

```text
customerExtraFee = null → fallback global possible pour LAND
customerExtraFee = 0    → coût forcé à 0
customerExtraFee > 0    → coût spécifique
```

Même logique pour `courierExtraPayout`.

## HodinaSetting

Nouveaux settings J5G-B4 :

```text
global_commune_crossing_customer_fee
global_commune_crossing_courier_payout
global_delivery_customer_fee_cap
global_multi_seller_extra_customer_fee
global_multi_seller_extra_customer_fee_cap
```

Ces settings sont des paramètres métier. Ils doivent être modifiables en backoffice sans migration de code.

## Note runtime — ProductImage et fichiers uploadés

Les entités `Product` / `ProductImage` conservent en base le nom du fichier image, mais le fichier physique dans :

```text
public/uploads/products
```

est une donnée runtime. Ce dossier ne doit pas être considéré comme du code source.

Décision MEP J5G-B4 v7 : le script protège et restaure ce dossier avant / après checkout de tag.

Dette technique : certains anciens fichiers images sont encore suivis par Git. Il faudra les sortir du suivi Git après sauvegarde et vérification que la prod possède bien les fichiers physiques nécessaires.

# Entités — état v11 et préparation GPS

Date : **19/06/2026**

## Aucun changement d'entité pour v11

La séquence v8 → v11 n'ajoute pas de nouvelle table métier.

Elle stabilise :

- l'UX Ajax panier ;
- le menu admin ;
- la compilation des assets ;
- la configuration mail réelle ;
- la gestion des fichiers runtime.

Entités existantes réutilisées :

```text
CustomerOrder
OrderItem
Address
EmailLog
DeliveryCommune
DeliveryCommuneConnection
DeliveryPricingZone
HodinaSetting
```

## EmailLog

`EmailLog` reste le journal applicatif des tentatives d'envoi.

Limite validée : `status = SENT` signifie que Symfony Mailer n'a pas levé d'exception. Cela ne garantit pas une réception réelle si le transport est `null://null`.

## Préparation J5K — GPS livraison

J5K devra enrichir `Address` plutôt que créer une entité parallèle.

Champs envisagés :

```text
Address.latitude decimal nullable
Address.longitude decimal nullable
Address.gpsAccuracy nullable si utile plus tard
Address.gpsCapturedAt nullable si utile plus tard
```

Snapshot commande envisagé :

```text
CustomerOrder.deliveryAddressLatitude nullable
CustomerOrder.deliveryAddressLongitude nullable
CustomerOrder.deliveryAddressGpsAccuracy nullable
```

Règle : la commune livrée reste obligatoire et prioritaire pour les frais. Le GPS aide à trouver le client, il ne remplace pas la logique tarifaire.

---

## Mise à jour 19/06/2026 — Customer adresses par défaut J5K-v8

### Customer

Champs d'adresse par défaut :

```text
billing_address_id
delivery_address_id
```

Règles :

- `billing_address_id` pointe vers l'adresse de facturation par défaut.
- `delivery_address_id` pointe vers l'adresse de livraison par défaut.
- Ces champs sont des préférences client vivantes, différentes des snapshots de commande.

### Address

Le champ `type` distingue :

```text
DELIVERY
BILLING
```

Règles terrain :

- une adresse `DELIVERY` peut porter GPS et instructions livreur ;
- une adresse `BILLING` ne doit pas exposer GPS ni instructions livreur côté panier client ;
- si Hodina crée automatiquement une adresse `BILLING` depuis une adresse existante, il copie uniquement les champs postaux utiles et ne copie pas les données terrain.

### CustomerOrder

La commande conserve son snapshot figé. Les modifications ultérieures du carnet d'adresses client ne doivent pas modifier les anciennes commandes.


---

# Entités J5M-C2/C3 — Seller, Customer, Address

## Seller

Ajouts / rôles :

```text
customerAccount : ?Customer
→ compte client lié au vendeur.

pickupAddress : ?Address
→ point de retrait vendeur.

businessName : ?string
→ nom de structure optionnel.

deliveryCommune : ?DeliveryCommune
→ source de vérité logistique.

deliveryZone : DeliveryZone
→ zone synchronisée depuis deliveryCommune.

commune : ?string
→ champ legacy conservé, non saisi manuellement.
```

Méthodes importantes :

```text
getCourierDisplayName()
getPublicDisplayName()
getEffectivePickupAddress()
```

## Customer

Un vendeur est aussi un client. Le compte lié reçoit `ROLE_SELLER`.

## Address

L’adresse de retrait vendeur est une adresse Hodina normale. Elle porte l’adresse, la commune, le code postal, les notes, les notes livreur et le GPS.

## DeliveryCommune

La commune de retrait du formulaire vendeur est choisie depuis cette entité seedée. Elle évite les saisies libres et garantit la cohérence code postal / zone / territoire.

---

# Entités — 24/06/2026 — J5O/J5P/J5Q

## CustomerOrder — code réception client et date de livraison

`CustomerOrder` porte désormais les données nécessaires à la validation de réception client :

```text
deliveryValidationCodeEncrypted
deliveryValidationCodeSentAt
deliveryValidationCodeValidatedAt
deliveryValidationCodeSendCount
deliveryValidationCodeFailedAttempts
deliveryValidationSmsLog
deliveryValidationEmailLog
```

Le champ `deliveredAt` est confirmé comme donnée métier importante : il sert à la fois à tracer la livraison et à rattacher la commande à une période de rémunération livreur.

## EmailLog

`EmailLog` est utilisé pour :

```text
ORDER_CREATED
CUSTOMER_DELIVERY_CODE
ORDER_STATUS_CONFIRMED
ORDER_STATUS_PREPARING
ORDER_STATUS_READY_FOR_PICKUP
ORDER_STATUS_PICKED_UP
ORDER_SELLER_COLLECTIONS_COMPLETED
ORDER_STATUS_DELIVERED
ORDER_STATUS_CANCELED
SELLER_COLLECTION_CODE
```

Le bouton EasyAdmin `Voir` permet de lire le corps du message et l'erreur éventuelle.

## SmsLog

`SmsLog` garde la trace des notifications SMS métier :

```text
order_pending_validation
customer_order_confirmed
customer_order_preparing
customer_order_ready_for_pickup
customer_order_picked_up
customer_order_out_for_delivery
customer_order_seller_collections_completed
customer_delivery_code
customer_order_delivered
seller_collection_code
```

## CourierPayout

Entité ajoutée par J5Q-A.

Table :

```text
courier_payout
```

Champs :

```text
id
courier_id
period_start
period_end
payment_due_date
status
total_amount
orders_count
validated_at
paid_at
payment_method
payment_reference
admin_note
created_at
updated_at
```

Relation :

```text
CourierPayout.courier -> Customer avec ROLE_COURIER
CourierPayout.lines -> CourierPayoutLine[]
```

Contrainte :

```text
courier_id + period_start + period_end unique
```

## CourierPayoutLine

Entité ajoutée par J5Q-A.

Table :

```text
courier_payout_line
```

Champs :

```text
id
courier_payout_id
customer_order_id
order_reference
delivered_at
customer_commune
courier_payout_amount
delivery_fee_customer
snapshot
created_at
```

Relations :

```text
CourierPayoutLine.courierPayout -> CourierPayout
CourierPayoutLine.customerOrder -> CustomerOrder
```

Contrainte critique :

```text
customer_order_id unique
```

Objectif : empêcher qu'une commande soit payée deux fois au livreur.

## Customer comme livreur

Le livreur reste un `Customer` avec `ROLE_COURIER`. Le contrôleur EasyAdmin dédié `CourierCrudController` filtre les clients sur ce rôle pour afficher `Livreurs > Livreurs`.

# Entités — J5Q-C-1 — HodinaSetting structuré

## HodinaSetting

J5Q-C-1 enrichit `HodinaSetting` sans créer de nouvelle table de configuration.

Nouveaux champs :

- `groupKey` : clé de groupe (`general`, `commerce`, `logistics`, `notifications`, `payments`, `technical`) ;
- `groupLabel` : libellé affiché du groupe ;
- `sortOrder` : ordre d'affichage dans le groupe ;
- `isEditable` : indique si la valeur est modifiable depuis EasyAdmin ;
- `isSensitive` : masque la valeur affichée dans les listes.

Champs existants conservés :

- `settingKey` ;
- `label` ;
- `value` ;
- `help` ;
- `fieldType` ;
- `updatedAt`.

Règle : `settingKey` reste l'identifiant stable utilisé par le code. Les champs de groupe sont des métadonnées d'organisation et ne doivent pas modifier la lecture métier des paramètres.

# Entités — J5Q-C-2 Branding e-mail

## HodinaSetting — groupe Branding e-mail

Nouveaux réglages globaux stockés dans `hodina_setting` :

| setting_key | group_key | field_type | Rôle |
| --- | --- | --- | --- |
| `email_branding_subject_prefix` | `email_branding` | `text` | Préfixe ajouté aux objets d'e-mails. |
| `email_branding_opening_formula` | `email_branding` | `text` | Formule utilisée au début des e-mails. |
| `email_branding_closing_formula` | `email_branding` | `text` | Formule utilisée avant la signature. |
| `email_branding_signature` | `email_branding` | `textarea` | Signature affichée en fin d'e-mail. |

Ces réglages sont éditables et non sensibles par défaut.

## EmailLog

`EmailLog.subject` conserve le sujet réellement envoyé, c'est-à-dire le sujet après application éventuelle du préfixe.

---

# Entités — état recette J5Q-C-2

La migration `Version20260625090000` ne modifie pas le schéma. Elle ajoute des lignes dans `hodina_setting`.

État recette vérifié après déploiement :

| setting_key | value par défaut | group_key | group_label | sort_order |
| --- | --- | --- | --- | --- |
| `email_branding_subject_prefix` | vide | `email_branding` | `Branding e-mail` | 10 |
| `email_branding_opening_formula` | `Bonjour` | `email_branding` | `Branding e-mail` | 20 |
| `email_branding_closing_formula` | `Merci,` | `email_branding` | `Branding e-mail` | 30 |
| `email_branding_signature` | `L’équipe Hodina` | `email_branding` | `Branding e-mail` | 40 |

La colonne de valeur réelle de `hodina_setting` est `value`, pas `setting_value`.

Commande SQL correcte :

```bash
php bin/console dbal:run-sql --env=prod --force-fetch "SELECT setting_key, value, group_key, group_label, sort_order FROM hodina_setting WHERE group_key = 'email_branding' ORDER BY sort_order;"
```

`EmailLog.subject` doit conserver le sujet réellement envoyé après application du préfixe par `EmailBrandingService`.


---

# J5R-A — CustomerOrderFeedback

Nouvelle entité : `CustomerOrderFeedback`.

Rôle : conserver les retours client liés à une commande, sans créer une table différente pour chaque usage.

Champs principaux :

- `customerOrder` : commande concernée ;
- `customer` : client auteur du retour ;
- `seller` : vendeur concerné, nullable ;
- `courier` : livreur concerné, nullable ;
- `targetType` : `ORDER`, `SELLER`, `COURIER`, `CANCELLATION` ;
- `targetKey` : clé unique par commande (`cancellation`, `seller:12`, `courier:4`) ;
- `rating` : note 1 à 5, nullable ;
- `reason` : motif court ;
- `comment` : commentaire libre ;
- `source` : source du feedback, par défaut `client_portal` ;
- `createdAt`, `updatedAt`.

Contrainte métier : un seul feedback par `customerOrder + targetKey`.

Utilisation J5R-A : motif/commentaire d’annulation client.

Utilisation prévue J5R-B : avis vendeur/livreur après livraison.

---

# J5S-A — Entités points de remise

## DeliveryPoint

Rôle : point fixe où Hodina peut imposer la remise d’une commande ou d’un produit.

Types prévus :

- `BARGE` ;
- `AIRPORT` ;
- `PICKUP_RELAY` ;
- `SELLER_POINT` ;
- `EVENT` ;
- `OTHER`.

Champs principaux : `name`, `code`, `type`, `isActive`, `line1`, `line2`, `postalCode`, `communeName`, `deliveryCommune`, `publicInstructions`, `courierInstructions`, GPS optionnel, `sortOrder`.

## DeliveryPointTimeWindow

Rôle : plage horaire disponible pour un point de remise.

Champs : `deliveryPoint`, `label`, `weekday`, `startTime`, `endTime`, `isActive`, `sortOrder`.

`weekday = null` signifie “Tous les jours”.

## ProductDeliveryPoint

Rôle : association entre un produit et un point de remise autorisé.

Contrainte : couple `product_id + delivery_point_id` unique.

## Product.deliveryMode

Nouveau champ :

```text
STANDARD
DELIVERY_POINT_REQUIRED
DELIVERY_POINT_OPTIONAL
```

Dans J5S-A, ce champ est administrable mais pas encore appliqué côté panier/checkout. `DELIVERY_POINT_OPTIONAL` signifie que le produit pourra être livré normalement dans une commune livrable ou remis dans un point autorisé.

## J5S-A-bis — Saisie rapide depuis Produit

Le formulaire EasyAdmin `ProductCrudController` propose des champs non persistés directement sur `Product` pour :

- associer un ou plusieurs `DeliveryPoint` existants ;
- créer un nouveau `DeliveryPoint` ;
- créer des `DeliveryPointTimeWindow` depuis une saisie simple ;
- créer automatiquement le lien `ProductDeliveryPoint` ;
- passer automatiquement le produit en `DELIVERY_POINT_OPTIONAL` lorsqu’un point est associé ou créé alors que le produit était encore en livraison standard uniquement. L’admin peut choisir `DELIVERY_POINT_REQUIRED` si le point de remise doit être imposé exclusivement.

Ces champs rapides ne créent pas de nouvelles colonnes dans `product`. Ils servent uniquement à orchestrer les entités existantes du socle J5S-A.


## J5S-A-quater — Livraison standard + points de remise

Le mode `DELIVERY_POINT_OPTIONAL` complète le socle sans migration :

- `STANDARD` : livraison classique uniquement ;
- `DELIVERY_POINT_REQUIRED` : point de remise obligatoire uniquement ;
- `DELIVERY_POINT_OPTIONAL` : livraison classique possible et points de remise proposés.

La table `ProductDeliveryPoint` reste la source des points autorisés et permet plusieurs points par produit.


## CustomerOrder — Snapshot point de remise J5S-B

J5S-B ajoute à `CustomerOrder` un snapshot du point de remise choisi : point lié, nom, code, type, adresse, commune, GPS, consignes publiques/livreur, plage horaire et instruction client.

Ce snapshot garantit qu’une commande passée reste lisible même si le point de remise est modifié ensuite en admin.

Le lien nullable vers `DeliveryPoint` sert de référence métier, mais l’affichage historique doit privilégier le snapshot.


# Mise à jour 27/06/2026 — Entités J5T à J5W

## CustomerOrder — Rendez-vous point de remise

Champs persistés par J5S-B-bis :

```text
deliveryPointScheduledDate : DATE nullable
deliveryPointScheduledTime : TIME nullable
```

Ces champs représentent la date et l’heure demandées par le client. Ils ne remplacent pas la plage Hodina. Les champs `deliveryPointStartTime`, `deliveryPointEndTime` et `deliveryPointTimeWindowLabel` restent le snapshot de la plage indicative détectée.

Règle : l’heure demandée par le client doit rester historisée même si, plus tard, Hodina ou le livreur propose une autre heure.

## EmailLog — Expéditeur historisé

Champs ajoutés par J5U-A :

```text
fromEmail
fromName
replyToEmail
replyToName
```

Rôle : conserver l’expéditeur et le Reply-To réellement utilisés au moment de l’envoi. Cela protège l’historique si les réglages EasyAdmin changent plus tard.

## HodinaSetting — Branding e-mail commande

Clés J5U-A dans le groupe `email_branding` :

```text
email_sender_name
email_sender_email
email_reply_to_name
email_reply_to_email
email_order_created_copy_email
```

Valeur pilote par défaut : `commande@hodina.fr` pour l’expéditeur, le Reply-To et la copie interne `ORDER_CREATED`.

## Product — Délai minimum de commande

Champ ajouté par J5V-A :

```text
minimumOrderLeadTimeHours : int nullable
```

Règles de persistance :

- valeur nulle ou inférieure/égale à zéro : stockée comme `null` par le setter.
- valeur positive : délai minimum en heures.
- le champ appartient au produit, pas au point de remise.

## Entités futures J5W — non présentes dans le code du 27/06/2026

### DeliveryArea

Rôle cible : sous-zone opérationnelle pour planning, exploitation et future affectation livreur. Ne doit pas remplacer `DeliveryZone`.

Champs cibles :

```text
code
name
deliveryZone
isActive
description
```

### DeliveryAreaSchedule

Rôle cible : jours de livraison et cutoff par sous-zone.

Champs cibles :

```text
deliveryArea
weekday
cutoffDayOffset
cutoffTime
isActive
```

### Product.allowedDeliveryCommunes

Rôle cible : limiter un produit à certaines `DeliveryCommune` sans créer de nouvelle entité métier. Si aucune commune n’est liée au produit, le produit reste disponible dans toutes les communes livrées compatibles.

## J5S-B-quater — Aucune nouvelle entité

Les correctifs J5S-B-ter/quater/bis/quinquies ne créent pas de nouvelle entité Doctrine.

Éléments existants concernés :

- `CustomerOrder.orderReference` : l’unicité reste protégée par l’index unique existant ; la robustesse vient de `OrderReferenceGenerator`.
- `CustomerOrder` conserve les snapshots point de remise ajoutés par J5S-B/J5S-B-bis.
- `Product.unit` est réutilisé pour l’affichage client de l’unité de vente via `Product::getUnitLabel()` ; aucune nouvelle colonne.
- `Product.minimumOrderLeadTimeHours` reste la source du délai minimum J5V-A.

Aucune migration n’est introduite par J5S-B-ter/quater/bis/quinquies. Les migrations déjà existantes restent celles des lots précédents.

## J5T-C — Aucune nouvelle entité pour le rattachement au compte existant

J5T-C ne crée aucune nouvelle entité et ne nécessite pas de migration.

Entités existantes utilisées :

- `Customer` : recherché par e-mail normalisé pour éviter les doublons ;
- `CustomerOrder` : rattaché au `Customer` existant après confirmation ;
- `EmailLog` : conserve le corps `ORDER_CREATED` contenant la mention de rattachement si applicable.

Règle de persistance : aucune commande ne doit être persistée avant la confirmation explicite du rattachement lorsque l’e-mail existe déjà.

## Mise à jour 28/06/2026 — Statut persistance J5T-C / J5V-A

J5T-C ne modifie pas le schéma. Il réutilise `Customer`, `CustomerOrder` et `EmailLog`. La règle de persistance validée recette est stricte : aucun nouveau `Customer` et aucune `CustomerOrder` ne doivent être créés avant confirmation lorsque l’e-mail existe déjà.

J5V-A repose sur la colonne `product.minimum_order_lead_time_hours`, migration `Version20260626194000`. La validation serveur checkout utilise explicitement ce champ via `DeliveryPointCartService::validateMinimumOrderLeadTime()` appelé par `CheckoutController` dans le flux point de remise. Correctif `3b508d0` validé recette sous `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Mise à jour 29/06/2026 — Statut production des entités checkout stabilisation

Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Aucune nouvelle entité n’est créée par la MEP production J5S/J5T/J5U/J5V. Les entités existantes utilisées restent :

- `Product.minimumOrderLeadTimeHours`, migration `Version20260626194000` ;
- `DeliveryPoint`, `DeliveryPointTimeWindow`, `ProductDeliveryPoint` pour les points de remise ;
- `Customer` et `CustomerOrder` pour le rattachement J5T-C ;
- `EmailLog` pour tracer `ORDER_CREATED`, y compris la mention de rattachement ;
- `HodinaSetting` pour l’expéditeur e-mails.

Règle de persistance production : aucune `CustomerOrder` ne doit être persistée avant confirmation explicite lorsque l’e-mail invité existe déjà. En point de remise, un rendez-vous trop proche du délai minimum produit doit bloquer la commande avant persistance.


## Mise à jour 29/06/2026 — J5W-A DeliveryPricingZone par secteur

Statut : **validé localement + recette + production**. Tag recette : `recette-j5w-a-local-pricing-zones-20260629`. Tag production : `prod-j5w-a-local-pricing-zones-20260629`.

### DeliveryPricingZone

J5W-A ne crée pas de nouvelle entité. Il enrichit l’usage de `DeliveryPricingZone` avec des codes locaux plus fins :

- `MAMOUDZOU_LOCAL`
- `NORD_LOCAL`
- `CENTRE_LOCAL`
- `SUD_LOCAL`
- `PT_LOCAL` existant pour Petite-Terre

`PETITE_TERRE_LOCAL` ne doit pas être créé, car `PT_LOCAL` existe déjà et représente la même réalité métier.

### DeliveryCommune

`DeliveryCommune.localPricingZone` reçoit désormais le forfait local par secteur.

Mapping local validé :

| DeliveryCommune | territory | localPricingZone |
|---|---|---|
| Mamoudzou | `GT` | `MAMOUDZOU_LOCAL` |
| Acoua, Bandraboua, Koungou, M'Tsangamouji, Mtsamboro | `GT` | `NORD_LOCAL` |
| Chiconi, Ouangani, Sada, Tsingoni | `GT` | `CENTRE_LOCAL` |
| Bandrélé, Bouéni, Chirongui, Dembéni, Kani-Kéli | `GT` | `SUD_LOCAL` |
| Dzaoudzi, Labattoir, Pamandzi | `PT` | `PT_LOCAL` |

`DeliveryCommune.territory` ne change pas : Grande-Terre reste `GT`, Petite-Terre reste `PT`.

### Migration

Migration locale + recette + production : `DoctrineMigrations\Version20260629083000` (current/latest en recette puis en production après déploiement J5W-A).

La migration crée seulement les nouvelles zones Grande-Terre par secteur, rattache les 18 communes Hodina, remappe Petite-Terre vers `PT_LOCAL`, et supprime le doublon `PETITE_TERRE_LOCAL` si celui-ci existe sans référence.


## Mise à jour 29/06/2026 — DeliveryPricingZone après J5X-A

J5X-A ne modifie pas le schéma Doctrine de `DeliveryPricingZone`. Il met uniquement à jour les données de `customerDeliveryFee`.

Champs concernés :

```text
DeliveryPricingZone.customerDeliveryFee : frais local payé par le client.
DeliveryPricingZone.courierPayout : rémunération livreur, non modifiée par J5X-A.
DeliveryPricingZone.internalNote : enrichie par la migration pour tracer la décision J5X-A.
```

Valeurs cibles J5X-A :

```text
PT_LOCAL          customerDeliveryFee = 12.00
MAMOUDZOU_LOCAL   customerDeliveryFee = 12.00
CENTRE_LOCAL      customerDeliveryFee = 17.00
SUD_LOCAL         customerDeliveryFee = 21.00
NORD_LOCAL        customerDeliveryFee = 21.00
GT_LOCAL          customerDeliveryFee = 21.00, fallback technique
```

Aucun champ calendrier n’est ajouté ici. Les jours de livraison paramétrables par secteur sont repoussés à J5X-B et devront être portés par `DeliveryPricingZone`, pas par `Product.deliveryDays`.

## J5X-B — Champs calendrier sur DeliveryPricingZone

`DeliveryPricingZone` porte désormais les champs publics de calendrier :

```text
publicLabel : libellé client du secteur.
publicDescription : explication publique optionnelle.
deliveryWeekdays : JSON, jours 1=lundi ... 7=dimanche.
cutoffTime : heure limite, valeur pilote 10:00.
cutoffDaysBefore : nombre de jours avant passage, valeur pilote 1.
isDeliveryScheduleActive : active/désactive l’affichage planning.
```

Ces champs ne changent pas `customerDeliveryFee`, `courierPayout`, ni les rattachements `DeliveryCommune.localPricingZone`.

## J5X-C — Product, promesse livraison client

J5X-C enrichit `Product` avec des champs de promesse publique :

```text
Product.deliveryPromiseMode : SECTOR_SCHEDULE ou APPOINTMENT.
Product.deliveryPromiseTitle : titre public optionnel.
Product.deliveryPromiseDescription : texte public optionnel.
Product.appointmentDeliveryWeekdays : jours possibles pour produit sur créneau.
Product.appointmentTimeWindowStart / appointmentTimeWindowEnd : plage horaire indicative.
Product.appointmentCutoffTime : heure limite indicative pour le créneau.
Product.appointmentCutoffDaysBefore : nombre de jours avant le créneau.
```

Ces champs ne remplacent pas :

- `Product.deliveryMode`, qui contrôle standard / point de remise ;
- `Product.minimumOrderLeadTimeHours`, qui reste la règle J5V-A de délai minimum produit ;
- `DeliveryPricingZone.deliveryWeekdays`, qui porte le calendrier standard par secteur ;
- `DeliveryLogisticsService`, qui calcule les frais de livraison.

## J5X-D — Champs catalogue et merchandising

### Category

Champs ajoutés :

- `displayOrder:int` : ordre manuel dans les filtres catalogue, plus petit = plus haut.
- `isFeatured:bool` : remonte la catégorie dans les filtres.
- `publicDescription:text|null` : description client optionnelle.

`Category.isActive` est désormais exposé dans EasyAdmin et utilisé pour masquer les catégories inactives du catalogue.

### Product

Champs ajoutés :

- `isFeatured:bool` : remonte le produit dans le tri “Mis en avant”.
- `displayPriority:int` : ordre manuel du produit, plus petit = plus haut.

La collection `Product.images` est ordonnée par `position ASC, id ASC` pour stabiliser l’image principale affichée dans le catalogue.

# Mise à jour entités 01/07/2026 — J5Y sans nouveau schéma majeur

## J5Y-A — Plages de point de remise

J5Y-A ne crée pas de nouvelle entité. Il améliore seulement la saisie de `DeliveryPointTimeWindow` lors de la création rapide d’un nouveau point depuis le formulaire Produit.

Entités concernées indirectement :

- `DeliveryPoint` ;
- `DeliveryPointTimeWindow` ;
- `ProductDeliveryPoint` ;
- `Product` pour les champs temporaires/non persistés utilisés par le formulaire EasyAdmin.

Règle de persistance : les raccourcis UI `jours ouvrés` et `jours ouvrables` ne doivent pas devenir des valeurs métier persistées non documentées. Ils doivent produire des plages compatibles avec le modèle existant.

## J5Y-B — Rendez-vous point de remise par créneau de 30 minutes

J5Y-B ne crée pas de nouvelle colonne. Le rendez-vous continue d’être stocké via les champs existants de `CustomerOrder` liés au point de remise : date, heure demandée, point choisi, plage associée et snapshots ajoutés par J5S/J5S-B.

La nouveauté porte sur la règle de validation : l’heure demandée doit être un début de créneau de 30 minutes compatible avec une `DeliveryPointTimeWindow` active du point choisi.

Exemple :

```text
DeliveryPointTimeWindow 08:00–12:00
→ heures acceptées : 08:00, 08:30, 09:00, ..., 11:30
→ heures refusées : 07:30, 12:00, 12:30, 09:15
```

## J5Y-C/D — Aucune entité Doctrine

Le déplacement du catalogue vers `/`, la page `/decouvrir-hodina`, la redirection legacy `/blog/decouvrir-hodina`, le logo header et les favicons ne modifient pas le modèle Doctrine.

# Mise à jour entités 01/07/2026 — J5Y-E/F/G/H sans nouveau schéma Doctrine

J5Y-E/F/G/H ne crée aucune entité et ne modifie aucune migration Doctrine.

Éléments concernés sans persistance :

```text
HomeController : routes publiques et redirections.
templates/pages/decouvrir_hodina.html.twig : page institutionnelle.
templates/pages/carnet/index.html.twig : entrée pédagogique Carnet.
templates/pages/carnet/livraison.html.twig : guide livraison.
public/images/carnet/livraison/*.webp : illustrations statiques des secteurs / jours indicatifs.
public/css/style_mobile.css : UI pages publiques, footer compact, cartes livraison.
```

Règle : les jours affichés sur `/carnet/livraison` ne sont pas des données persistées nouvelles. Ils vulgarisent l’état opérationnel et doivent rester cohérents avec les réglages logistiques, mais la source de vérité du checkout reste `DeliveryPricingZone`, `DeliveryCommune`, `DeliveryPoint`, `Product` et les services existants.

Aucune table, colonne ou relation Doctrine ne doit être ajoutée pour cette page tant qu’un vrai besoin dynamique n’est pas validé.

# Entités 02/07/2026 — J5Z et préparation J5AA

## J5Z — pas de nouvelle entité Doctrine

J5Z n’ajoute aucune table ni colonne Doctrine. Le lot ajoute des services, une commande de rattrapage, des garde-fous et des ajustements Twig/CSS.

Entités touchées indirectement par les services :

- `Customer.phone` : normalisation legacy possible par commande contrôlée.
- `CustomerSignup.phone` : normalisation legacy possible par commande contrôlée.
- `CustomerOrder.deliveryLogisticsSnapshot` : utilisé par `DeliveryFeeReasonFormatter` pour formater l’annotation des frais déjà snapshotés.

Règle : la commande `hodina:customers:normalize-phones --apply` modifie des données existantes mais ne modifie pas le schéma.

## J5AA — AddressLocality (planification — voir section réalisée plus bas)

> ⚠️ SECTION SUPERSÉDÉE — État réel : `AddressLocality` est codé, migré (`Version20260704210000`) et validé recette + production le 2026-07-04. Voir « Entités J5AA-A — Localité d’adresse » plus bas.

État initial (avant codage) : décision d’architecture validée, non encore codée.

Entité prévue :

```text
AddressLocality
```

Rôle : représenter une précision d’adresse suffisamment générique pour couvrir village, quartier, lieu-dit, hameau ou secteur.

Champs MVP envisagés :

```text
id
name
normalizedName
postalCode nullable
countryCode nullable
deliveryCommune nullable
isActive
sortOrder
createdAt
updatedAt
```

Relations envisagées :

```text
AddressLocality.deliveryCommune → DeliveryCommune nullable
Address.addressLocality → AddressLocality nullable
```

Champs `Address` envisagés :

```text
addressLocality nullable
localityText nullable
localityNameSnapshot nullable
```

Snapshots commande envisagés :

```text
CustomerOrder.deliveryAddressLocalityName
CustomerOrder.billingAddressLocalityName
```

ou intégration dans les snapshots adresse existants si le modèle actuel s’y prête mieux.

Règles de persistance prévues :

- Une localité connue peut être rattachée à une `DeliveryCommune`.
- Une localité hors Mayotte peut exister sans `DeliveryCommune`.
- Une adresse peut conserver une localité libre non reconnue via `localityText`.
- Les anciennes commandes doivent rester lisibles même si une localité est renommée ou désactivée.

Règle anti-régression : `AddressLocality` ne remplace jamais `DeliveryCommune` pour les frais, la barge, les jours ou les créneaux.

# Entités 03/07/2026 — J5AC Customer email unique nullable

## Customer

J5AC renforce l’entité `Customer` au niveau DB et Doctrine :

```php
#[ORM\Table(name: 'customer')]
#[ORM\UniqueConstraint(name: 'UNIQ_CUSTOMER_EMAIL', columns: ['email'])]
```

La colonne reste volontairement nullable :

```php
#[ORM\Column(length: 180, nullable: true)]
private ?string $email = null;
```

Règles de persistance :

- `email` est normalisé en `LOWER(TRIM(email))` lors de la migration J5AC ;
- les emails vides sont convertis en `NULL` ;
- les doublons normalisés bloquent la migration ;
- l’index unique nullable `UNIQ_CUSTOMER_EMAIL` empêche deux comptes non-null avec le même email ;
- plusieurs `NULL` restent possibles ;
- `phone` reste non unique.

Justification : `Customer.email` est l’identifiant de connexion Symfony, mais certains comptes historiques ou vendeurs incomplets peuvent encore nécessiter un email nullable. L’unicité DB est donc actée sans imposer `NOT NULL`.

## Reset password

Les champs existants restent utilisés :

```text
Customer.resetPasswordToken
Customer.resetPasswordTokenExpiresAt
```

J5AC n’ajoute pas de nouvelle entité pour le reset. La génération de lien est factorisée dans `CustomerPasswordResetLinkService` et continue de créer un `SmsLog`.

## Aucun changement entité pour J5AB

J5AB est strictement un lot Twig/CSS/JS/assert sur le catalogue public. Il ne crée aucune entité et ne modifie aucune relation Doctrine.

# Entités 04/07/2026 — corrections avant J5AA

## État réel avant J5AA

`AddressLocality` n’existe pas encore dans le code fourni. Aucune migration J5AA n’est présente.

Éléments déjà existants :

- `Address.postalCode` : chaîne persistée, actuellement obligatoire dans l’entité.
- `Address.commune` : chaîne persistée, champ métier central de l’adresse.
- `Address.deliveryZone` : zone PT / GT / AUTRE selon le type d’adresse.
- `DeliveryCommune.postalCode` : code postal principal seedé de la commune ou du point logistique.
- `DeliverableAddressValidator` : contrôle déjà format du code postal, commune livrable, correspondance code postal / commune et cohérence zone.

## Conséquence pour J5AA

J5AA doit éviter de dupliquer un référentiel postal si `DeliveryCommune.postalCode` suffit au MVP. Le besoin prioritaire est d’ajouter une précision d’adresse `AddressLocality`, puis d’homogénéiser les formulaires pour que code postal et commune viennent des données seedées.

Évolution prévue :

```text
Address.addressLocality nullable
Address.localityText nullable
Address.localityNameSnapshot nullable
```

Pour la cohérence code postal / commune, deux options restent ouvertes avant analyse détaillée du code :

1. utiliser `DeliveryCommune.postalCode` comme référentiel MVP ;
2. créer plus tard une entité dédiée seulement si plusieurs codes / plusieurs communes / maintenance back-office le justifient.

Règle : aucune adresse de livraison ne doit être validée avec un couple code postal / `DeliveryCommune` incohérent.
# Entités J5AA-0 — Commune d'adresse et audit livraison

## Address

`Address.commune` reste le champ métier central qui porte la commune d'une adresse.

Pour une adresse `DELIVERY`, la valeur attendue est le nom canonique exact d'une `DeliveryCommune` active et utilisable comme point logistique. Le champ n'est pas remplacé par une relation `Address.deliveryCommune` dans J5AA-0.

Pour une adresse `BILLING`, la valeur peut être administrative. Si l'adresse de facturation est en zone `AUTRE`, elle peut être hors référentiel Hodina.

Champs utilisés par l'audit :

```text
Address.type
Address.commune
Address.postalCode
Address.deliveryZone
```

## DeliveryCommune

`DeliveryCommune` reste le référentiel qui valide les communes livrables :

```text
DeliveryCommune.name
DeliveryCommune.slug
DeliveryCommune.postalCode
DeliveryCommune.territory
DeliveryCommune.isActive
DeliveryCommune.isLogisticsPoint
```

Pour une adresse de livraison, le couple `Address.postalCode + Address.commune` doit correspondre à une `DeliveryCommune` active/logistique.

## Aucun changement de schéma en J5AA-0

J5AA-0 ne crée aucune table, aucune colonne et aucune relation Doctrine. Le sous-lot ajoute uniquement un audit read-only dans `tools/` et documente les règles avant les futures évolutions J5AA.

# Entités J5AA-B — Pas de nouvelle entité, renforcement du couple postal/commune

J5AA-B ne modifie pas le schéma Doctrine.

Aucune table, colonne ou relation n'est ajoutée. En particulier :

- pas de `Address.deliveryCommune` ;
- pas de `AddressLocality` dans ce sous-lot ;
- pas d'entité `PostalCode`.

## Address

Pour une adresse `DELIVERY`, les champs existants restent utilisés :

- `Address.postalCode` reçoit le code postal canonique de la `DeliveryCommune` choisie ;
- `Address.commune` reçoit le nom canonique de la `DeliveryCommune` choisie ;
- `Address.deliveryZone` reçoit la zone cohérente avec le territoire de cette `DeliveryCommune`.

Pour une adresse `BILLING`, J5AA-B ne change pas les règles existantes : une facturation hors zone Hodina peut conserver une commune administrative libre.

## DeliveryCommune

`DeliveryCommune` reste le référentiel de validation des communes livrables et des couples code postal / commune. Le code postal guide la sélection, mais la commune reste la clé métier utilisée pour la livraison.

# Entités J5AA-A — Localité d’adresse

## AddressLocality

`AddressLocality` représente une localité d’adresse : village, quartier ou lieu-dit.

Elle précise l’adresse, mais ne remplace pas :

- `Address.commune` ;
- `DeliveryCommune` ;
- `DeliveryZone` ;
- les règles de frais ou de barge.

## Address

`Address` conserve `commune` comme champ métier central.

Nouveaux champs :

- `addressLocality` : localité connue nullable ;
- `localityText` : texte libre nullable.

Pour `Address::TYPE_DELIVERY`, `commune + postalCode` doivent rester cohérents avec une `DeliveryCommune` active. Pour `Address::TYPE_BILLING`, la commune peut rester administrative et hors référentiel Hodina selon la zone.

## CustomerOrder

`CustomerOrder` ajoute `deliveryAddressLocalityName` pour snapshotter la localité de livraison au moment de la commande.

# Entités J5AF — Anonymisation client

## Customer

Nouveaux champs :

- `isActive` (bool, défaut `true`) : passe à `false` lors de l'anonymisation. Bloque la connexion via `CustomerUserChecker`. Ne pas modifier manuellement hors du service d'anonymisation.
- `anonymizedAt` (`DateTimeImmutable` nullable) : date d'anonymisation. `null` = jamais anonymisé.

Champs additifs : aucun champ existant retiré. Migration `Version20260708120000` (ajout), corrigée par `Version20260708130000` (le mapping Doctrine `#[ORM\Column]` sans `options` attend `TINYINT NOT NULL` sans largeur ni défaut — l'`ALTER` initial avait créé `TINYINT(1) NOT NULL DEFAULT 1`).

Aucun nouveau champ sur `CustomerOrder`, `SupportTicket`, `ChatbotConversation` ou `CourierPayout` : l'anonymisation ne touche que l'identité du client, l'historique métier reste inchangé.
