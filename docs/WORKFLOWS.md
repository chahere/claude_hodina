# Workflows Hodina

## Historique conservé

État initial du document de référence :

```text
Checkout -> Address -> CustomerOrder -> OrderItem -> SmsLog -> PENDING_VALIDATION
```

Ce workflow reste la base du tunnel client.

---

# 1. Workflow checkout client

## Objectif

Permettre au client de créer une commande à partir de son panier.

## Workflow

```text
Client connecté
→ Panier
→ Adresse / zone de livraison
→ Création CustomerOrder
→ Création OrderItem
→ Génération numéro métier si applicable
→ Création SmsLog initial
→ Statut PENDING_VALIDATION
```

## Statut obtenu

```text
PENDING_VALIDATION
```

Signification :

```text
La commande est soumise, mais elle doit encore être validée par l'admin.
```

---

# 2. Workflow admin J4

## Objectif

Permettre à l'admin de traiter une commande de bout en bout depuis EasyAdmin.

## Workflow principal

```text
PENDING_VALIDATION
→ CONFIRMED
→ PREPARING
→ READY_FOR_PICKUP
→ DELIVERED
```

## Annulation

```text
PENDING_VALIDATION → CANCELED
CONFIRMED → CANCELED
```

## Signification des statuts

### PENDING_VALIDATION

Commande envoyée par le client, en attente de vérification admin.

### CONFIRMED

Commande validée par l'admin. La disponibilité est considérée comme confirmée.

### PREPARING

Commande en cours de préparation.

### READY_FOR_PICKUP

Commande prête. À partir de J5, c'est le point d'entrée du dashboard livreur.

### OUT_FOR_DELIVERY

Commande prise en charge par un livreur et en cours de livraison. Ce statut sera exploité en J5.

### DELIVERED

Commande livrée.

### CANCELED

Commande annulée.

---

# 3. Workflow SmsLog J4

## Objectif

Garder une trace des notifications prévues et faciliter l'envoi manuel.

## Étapes générant un SmsLog

```text
Commande créée
→ SmsLog order_pending_validation

Commande confirmée
→ SmsLog customer_order_confirmed

Commande en préparation
→ SmsLog customer_order_preparing

Commande prête
→ SmsLog customer_order_ready_for_pickup

Commande livrée
→ SmsLog customer_order_delivered

Commande annulée
→ SmsLog customer_order_canceled
```

## Format des messages

Les messages commencent par :

```text
Gégé {prénom client}, ...
```

Ils incluent le numéro métier de commande :

```text
hodinaAAAAMMJJN
```

## Envoi manuel via iPhone

Plan A testé :

```text
Bouton Envoyer le SMS
→ ouverture de l'application SMS
→ numéro client prérempli
→ message prérempli
```

Plan B prévu si besoin :

```text
Copier le message
→ ouvrir SMS avec le numéro
→ coller manuellement
```

---

# 4. Workflow Réglages Hodina

## Objectif

Administrer certains comportements sans modifier le code.

## Modèle retenu

```text
1 ligne = 1 paramètre
```

## Paramètres actuels

```text
order_reference_prefix
→ préfixe du numéro métier de commande

delivered_communes
→ communes livrées
```

## Communes livrées

Workflow de modification :

```text
Admin ouvre Réglages Hodina
→ Modifier Communes livrées
→ Ajouter une commune
→ Enregistrer
→ La commune est conservée dans le paramètre
```

---

# 5. Workflow livraison J5

## Décision métier

Modèle B retenu :

```text
Le livreur voit les commandes prêtes et les prend lui-même.
```

## Workflow cible

```text
Admin marque la commande prête
→ READY_FOR_PICKUP

Livreur se connecte
→ Dashboard livreur
→ voit les commandes READY_FOR_PICKUP
→ clique Prendre en charge
→ OUT_FOR_DELIVERY
→ commande associée au livreur

Livreur livre
→ clique Marquer livrée
→ DELIVERED
```

## Point d'entrée J5

```text
READY_FOR_PICKUP
```

Une commande prête devient disponible pour les livreurs.

---

# 6. Workflow technique centralisé J5

## Problème à éviter

Ne pas dupliquer le code de changement de statut entre :

```text
CustomerOrderCrudController
CourierDashboardController
```

## Solution retenue

Créer un service commun :

```text
CustomerOrderWorkflowService
```

## Utilisation

```text
Admin EasyAdmin
→ CustomerOrderCrudController
→ CustomerOrderWorkflowService

Livreur dashboard
→ CourierDashboardController
→ CustomerOrderWorkflowService
```

## Responsabilités du service

- vérifier les transitions ;
- changer les statuts ;
- remplir les dates métier ;
- associer le livreur si besoin ;
- créer les SmsLog ;
- générer le numéro de commande si absent ;
- sauvegarder avec Doctrine.

## Transitions centralisées

```text
PENDING_VALIDATION → CONFIRMED
PENDING_VALIDATION → CANCELED
CONFIRMED → PREPARING
CONFIRMED → CANCELED
PREPARING → READY_FOR_PICKUP
READY_FOR_PICKUP → OUT_FOR_DELIVERY
READY_FOR_PICKUP → DELIVERED, admin uniquement si maintenu
OUT_FOR_DELIVERY → DELIVERED
```

---

# 7. Workflow sécurité J5

## Admin

Accès backoffice :

```text
/ouegnewe
```

Rôle :

```text
ROLE_ADMIN
```

## Livreur

Accès dashboard livreur :

```text
/djama
```

Rôle :

```text
ROLE_COURIER
```

## Règles

- un utilisateur non connecté ne peut pas accéder au dashboard livreur ;
- un utilisateur sans rôle livreur ne peut pas accéder au dashboard livreur ;
- un livreur ne peut marquer livrée qu'une commande qu'il a prise ;
- une commande déjà livrée ne peut pas être reprise ;
- une commande annulée ne peut pas être livrée.

---

# Workflow reset password par SMS

## Objectif

Permettre à un client de réinitialiser son mot de passe sans email transactionnel, en restant cohérent avec le pilote SMS manuel.

## Workflow

```text
Client clique “Mot de passe oublié”
→ saisit son email
→ Hodina vérifie le compte
→ Hodina génère un token temporaire
→ Hodina crée un SmsLog avec le lien de reset
→ Admin ouvre SmsLog
→ Admin clique Envoyer le SMS
→ Messages iPhone s'ouvre
→ Admin envoie le SMS
→ Client ouvre le lien
→ Client saisit un nouveau mot de passe
→ Token invalidé
→ Client peut se reconnecter
```

---

# Workflow préproduction o2switch

## Déploiement recette

```text
Code local validé
→ copie / déploiement vers /home/vopu3712/recette.hodina.fr
→ document root public
→ .env.local recette
→ base recette
→ cache clear prod
→ test navigateur
```

## Base de données

```text
Base dev locale
→ mysqldump
→ correction UTF-8 si nécessaire
→ import phpMyAdmin dans vopu3712_hodina_recette
→ vérification via dbal:run-sql
```

## Protection

```text
HTTP
→ redirection HTTPS
→ Basic Auth
→ application Symfony
```

---

# Workflow légal client

## Consultation

```text
Footer public
→ CGU ou CGV
→ page légale mobile-first
→ sommaire compact
→ lecture articles
```

## Acceptation au checkout

```text
Panier
→ checkout
→ case “J'accepte les CGU/CGV”
→ bouton Valider activable uniquement si case cochée
→ commande créée
```


---

# Workflow J5C — Données livraison prêtes

## Objectif

Permettre à Hodina de mémoriser la prise en charge d'une commande par un livreur, avant même de créer l'écran `/djama`.

## Workflow data préparé

```text
Commande READY_FOR_PICKUP
→ aucun livreur assigné
→ assignedCourier = null
→ courierAssignedAt = null
→ outForDeliveryAt = null
```

Puis, demain avec le dashboard :

```text
Livreur clique “Prendre en charge”
→ CustomerOrderWorkflowService::takeForDelivery()
→ status = OUT_FOR_DELIVERY
→ assignedCourier = livreur connecté
→ courierAssignedAt = maintenant
→ outForDeliveryAt = maintenant
→ SmsLog customer_order_out_for_delivery créé
```

Puis :

```text
Livreur clique “Marquer livrée”
→ CustomerOrderWorkflowService::markDeliveredByCourier()
→ vérification livreur assigné
→ status = DELIVERED
→ deliveredAt = maintenant
→ SmsLog customer_order_delivered créé
```

## Règles préparées

- Une commande non prête ne peut pas être prise.
- Une commande déjà prise ne peut pas être reprise.
- Une commande en livraison est liée à un livreur.
- Seul le livreur assigné peut marquer la commande comme livrée côté livreur.
- L'admin conserve son workflow existant côté EasyAdmin.

## Workflow sécurité préparé

```text
/djama
→ accès réservé ROLE_COURIER
```

L'interface n'est pas encore créée, mais sa protection est déjà prévue.

## Workflow de vérification admin

Dans EasyAdmin, détail commande :

```text
Livreur assigné
Livreur assigné le
Départ livraison le
```

Avant dashboard livreur, ces valeurs peuvent rester `Null`. C'est l'état attendu.

## Workflow migration J5C

Ordre final retenu :

```text
Version20260606091936
→ no-op de compatibilité

Version20260606101500
→ ajout champs livraison + relation livreur

Version20260606103000
→ alignement sécurisé du nom d'index
```

Règle retenue : une migration corrective doit être postérieure à la migration qu'elle corrige.


---

# Workflow J5D — Dashboard livreur livré

## Workflow validé

```text
Admin marque commande prête
→ status READY_FOR_PICKUP
→ commande visible dans /djama

Livreur clique Prendre en charge
→ CustomerOrderWorkflowService::takeForDelivery()
→ status OUT_FOR_DELIVERY
→ assignedCourier = livreur connecté
→ courierAssignedAt = maintenant
→ outForDeliveryAt = maintenant

Livreur clique Marquer livrée
→ CustomerOrderWorkflowService::markDeliveredByCourier()
→ vérification livreur assigné
→ status DELIVERED
→ deliveredAt = maintenant
```

## Rappel de test

Si aucune commande n'apparaît côté livreur, vérifier d'abord :

```text
status = READY_FOR_PICKUP
assignedCourier = NULL
```

---

# Workflow J5E — Calcul marge produit

## Workflow prix produit

```text
Admin ou vendeur renseigne prix producteur
→ Hodina détermine marge effective
→ Hodina calcule prix client
→ client voit prix client
```

## Détermination marge effective

```text
Si Product.marginRate existe
→ utiliser Product.marginRate

Sinon si Seller.marginRate existe
→ utiliser Seller.marginRate

Sinon
→ utiliser HodinaSetting.global_margin_rate
```

## Checkout

```text
Panier avec prix calculés
→ checkout
→ recalcul par ProductPricingService
→ création OrderItem
→ prix producteur figé
→ marge appliquée figée
→ prix client figé
→ marge Hodina figée
```

## Pourquoi figer ?

Une commande passée hier ne doit pas changer si l'admin modifie une marge demain.

---

# Workflow J5F — Tarification livraison

## Paramétrage admin

```text
Admin crée zone tarifaire
→ définit frais client
→ définit rémunération livreur

Admin crée commune
→ définit PT ou GT
→ définit zone locale
→ définit zone barge
→ définit communes voisines

Admin associe vendeur à une commune
```

## Calcul barge

```text
commune client → territoire client
vendeurs panier → territoires vendeurs

Si au moins un vendeur a un territoire différent
→ barge requise
```

## Choix zone tarifaire

```text
Si barge requise
→ utiliser zone barge de la commune client

Sinon
→ utiliser zone locale de la commune client
```

## Calcul livraison

```text
frais livraison client = zone.customerDeliveryFee
rémunération livreur = zone.courierPayout
marge livraison Hodina = frais livraison client - rémunération livreur
```

---

# Workflow J5G — Aperçu logistique panier

## Panier

```text
Client ouvre panier
→ si adresse absente : demander adresse pour estimer
→ si adresse présente : DeliveryLogisticsService calcule l'aperçu
→ panier affiche message logistique
→ panier affiche frais estimés
```

## Relations communes

```text
même commune
→ pas d'alerte forte

commune voisine
→ message doux

commune éloignée
→ message distance

autre territoire
→ message barge
```

## Checkout

```text
Client valide commande
→ recalcul logistique définitif
→ figer frais livraison
→ figer rémunération livreur
→ figer marge livraison
→ figer barge requise
→ figer zone tarifaire
```

---

# Workflow futur J6 — Portail vendeur

## Principe

```text
Vendeur se connecte avec ROLE_SELLER
→ complète profil vendeur
→ choisit commune de retrait / production
→ crée produit
→ saisit prix producteur
→ soumet à validation
→ Hodina calcule prix client
→ admin garde contrôle
```

## Règle importante

Le portail vendeur devra utiliser :

```text
ProductPricingService
DeliveryLogisticsService
```

Il ne devra pas réimplémenter les calculs.


---

# Workflow J5E validé — Prix produit, panier et checkout

## Workflow admin produit

```text
Admin ouvre un produit dans EasyAdmin
→ renseigne Prix producteur
→ optionnellement renseigne Marge produit Hodina (%)
→ enregistre
```

## Workflow admin vendeur

```text
Admin ouvre un vendeur dans EasyAdmin
→ optionnellement renseigne Marge vendeur Hodina (%)
→ enregistre
```

## Workflow réglage global

```text
Admin ouvre Réglages Hodina
→ vérifie global_margin_rate
→ valeur par défaut : 20.00
```

## Workflow prix catalogue

```text
Client ouvre /catalogue
→ ProductController charge les produits actifs
→ ProductPricingService calcule les prix
→ le template affiche le prix client calculé
```

## Workflow panier

```text
Client ajoute un produit au panier
→ CartService lit le panier session
→ CartService charge chaque Product
→ ProductPricingService fournit un breakdown
→ CartService calcule unitPrice et lineTotal
→ le panier affiche les prix calculés
```

## Workflow checkout J5E

```text
Client valide le panier
→ CheckoutController récupère le panier détaillé
→ les prix ont été recalculés via ProductPricingService
→ CustomerOrder est créée
→ chaque OrderItem reçoit les valeurs figées
```

Valeurs figées :

```text
producerUnitPrice
appliedMarginRate
hodinaMarginAmount
unitPrice
lineTotal
```

## Workflow test J5E

```text
1. Mettre global_margin_rate = 20.00
2. Mettre producerPrice = 10.00 sur un produit
3. Laisser Product.marginRate vide
4. Laisser Seller.marginRate vide
5. Ouvrir le catalogue
6. Vérifier 12.00 €
7. Ajouter au panier
8. Valider la commande
9. Vérifier que l'ancienne commande n'a pas changé
10. Vérifier que la nouvelle ligne de commande porte les valeurs figées
```

---

# Workflow J5F clarifié — Barge uniquement PT ↔ GT

## Objectif de clarification

Avant d'appliquer J5F-A, la règle de barge est clarifiée pour éviter une erreur métier.

Une livraison entre deux communes de Petite-Terre ne prend jamais la barge.

Une livraison entre deux communes de Grande-Terre ne prend jamais la barge.

La barge intervient seulement quand la commande implique à la fois Petite-Terre et Grande-Terre.

## Workflow de calcul barge

```text
Client choisit / possède une adresse
→ Hodina identifie la commune client
→ Hodina lit le territoire de la commune client : PT ou GT
→ Hodina lit les communes des vendeurs du panier
→ Hodina lit le territoire de chaque commune vendeur : PT ou GT
→ Hodina compare les territoires
```

Règle :

```text
Si tous les vendeurs sont sur le même territoire que le client
→ requiresBarge = false

Si au moins un vendeur est sur l'autre territoire
→ requiresBarge = true
```

## Cas sans barge

```text
client PT + vendeur PT
→ pas de barge

client GT + vendeur GT
→ pas de barge
```

Exemples :

```text
Dzaoudzi → Pamandzi
PT → PT
requiresBarge = false

Pamandzi → Labattoir
PT → PT
requiresBarge = false

Mamoudzou → Koungou
GT → GT
requiresBarge = false

Sada → Ouangani
GT → GT
requiresBarge = false
```

## Cas avec barge

```text
client PT + vendeur GT
→ barge

client GT + vendeur PT
→ barge
```

Exemples :

```text
Dzaoudzi → Mamoudzou
PT → GT
requiresBarge = true

Mamoudzou → Dzaoudzi
GT → PT
requiresBarge = true
```

## Choix zone tarifaire

Le choix de la zone tarifaire ne doit pas être basé sur la distance seule.

```text
Si requiresBarge = true
→ utiliser la zone barge de la commune client

Sinon
→ utiliser la zone locale de la commune client
```

## Rôle des communes voisines

Les communes voisines servent uniquement au message client et au niveau logistique.

Elles ne servent pas à décider la barge.

```text
même commune
→ relation SAME_COMMUNE

commune voisine
→ relation NEIGHBOR_COMMUNE

commune non voisine mais même territoire
→ relation REMOTE_COMMUNE

autre territoire
→ relation OTHER_TERRITORY
→ barge requise
```

## Workflow multi-vendeurs

```text
Client PT
→ vendeur A PT
→ vendeur B PT
→ vendeur C PT
= pas de barge
```

```text
Client PT
→ vendeur A PT
→ vendeur B GT
= barge requise
```

```text
Client GT
→ vendeur A GT
→ vendeur B PT
= barge requise
```

## Règle de test J5F / J5G

Pour tester correctement, créer au minimum :

```text
Dzaoudzi = PT
Pamandzi = PT
Mamoudzou = GT
```

Tests attendus :

```text
Dzaoudzi client + Pamandzi vendeur
→ pas de barge

Dzaoudzi client + Mamoudzou vendeur
→ barge

Mamoudzou client + Dzaoudzi vendeur
→ barge
```


---

# Workflow J5F-A réalisé — Paramétrage admin logistique

## Objectif

Permettre à l'admin de paramétrer la donnée nécessaire au calcul logistique avant de brancher le panier.

## Workflow admin zones tarifaires

```text
Admin ouvre /ouegnewe
→ menu Logistique
→ Zones tarifaires
→ crée PT_LOCAL ou GT_LOCAL
→ renseigne frais client
→ renseigne rémunération livreur
→ active la zone
→ enregistre
```

Exemple validé :

```text
PT_LOCAL
frais client = 6.00
rémunération livreur = 5.00
marge livraison = 1.00
```

## Workflow admin communes

```text
Admin ouvre Communes livrées
→ crée Dzaoudzi
→ choisit territoire PT
→ choisit zone locale PT_LOCAL
→ choisit zone barge GT_LOCAL
→ active la commune
→ enregistre
```

Exemples validés :

```text
Dzaoudzi  → PT → PT_LOCAL / GT_LOCAL
Labattoir → PT → PT_LOCAL / GT_LOCAL
Mamoudzou → GT → GT_LOCAL / PT_LOCAL
```

## Workflow communes voisines

```text
Admin ouvre une commune
→ sélectionne ses communes voisines
→ enregistre
```

Test recette validé :

```text
Dzaoudzi ↔ Labattoir
```

Le voisinage est ajouté dans les deux sens pour rendre la donnée claire dans EasyAdmin.

## Workflow vendeur

```text
Admin ouvre un vendeur
→ renseigne Commune logistique
→ enregistre
```

Test recette validé :

```text
ferme houmadi → Mamoudzou → GT
```

## Point important

Le paramétrage J5F-A ne change pas encore le panier ni le checkout.

Il prépare seulement la donnée.


---

# Workflow J5F-B réalisé — Calcul logistique service

## Objectif

Centraliser dans un service le calcul de la relation client / vendeur et de la zone tarifaire.

## Entrées du service

```text
Address client
panier détaillé CartService::getDetailedCart()
```

Le panier détaillé contient les produits. Chaque produit permet de retrouver le vendeur, puis `Seller.deliveryCommune`.

## Workflow global

```text
DeliveryLogisticsService::previewForCart(address, detailedCart)
→ si aucune adresse : retourner addressRequired
→ trouver DeliveryCommune correspondant à Address.commune
→ parcourir les produits du panier
→ lire Seller.deliveryCommune
→ calculer relation client/vendeur
→ détecter requiresBarge
→ choisir zone tarifaire
→ retourner CartLogisticsPreview
```

## Cas adresse absente

```text
address = null
→ addressRequired = true
→ pas de frais estimés
→ message demandant de choisir une adresse
```

## Cas commune client non paramétrée

```text
Address.commune introuvable dans DeliveryCommune active
→ relation UNKNOWN
→ hasUnknownSellerCommune = true
→ message indiquant que la commune doit être paramétrée
```

## Cas vendeur sans commune logistique

```text
Seller.deliveryCommune = null
ou commune inactive
→ warning
→ relation UNKNOWN
→ Hodina devra confirmer les frais
```

## Calcul relation

```text
territoire différent
→ OTHER_TERRITORY
→ requiresBarge = true

même id commune
→ SAME_COMMUNE

même territoire + voisinage
→ NEIGHBOR_COMMUNE

même territoire + non voisin
→ REMOTE_COMMUNE
```

## Choix zone tarifaire

```text
requiresBarge = true
→ clientCommune.bargePricingZone

requiresBarge = false
→ clientCommune.localPricingZone
```

## Sortie

Le service retourne un `CartLogisticsPreview` avec :

```text
message
estimatedDeliveryFee
estimatedCourierPayout
estimatedDeliveryMargin
pricingZoneCode
requiresBarge
relationLevel
warnings
```

## Étape suivante

J5G-A branchera ce résultat dans le panier pour afficher :

- le message logistique ;
- les frais estimés ;
- éventuellement les alertes de configuration.


---

# Workflow navigation header — Admin / Livreur / Devenir vendeur

## Règle

```text
Admin connecté → lien Admin
Livreur non admin → lien Livreur
Utilisateur sans rôle admin/livreur → Devenir vendeur
```

## Workflow

```text
Utilisateur ouvre une page
→ Twig vérifie ROLE_ADMIN
→ si oui : affiche Admin
→ sinon vérifie ROLE_COURIER
→ si oui : affiche Livreur
→ sinon : affiche Devenir vendeur
```

## Pourquoi Admin est prioritaire ?

Un compte admin peut aussi avoir `ROLE_COURIER` pour tester.

Dans ce cas, le header reste simple et affiche seulement `Admin`.

---

# Workflow J5G-A réalisé — Aperçu logistique panier par périmètre vendeur

## Objectif

Afficher au client un aperçu logistique dans le panier sans encore figer les montants dans `CustomerOrder`.

## Workflow actuel

```text
Client ouvre le panier
→ CartController récupère le panier détaillé
→ CartController identifie l'adresse / commune client utilisable
→ CartController appelle DeliveryLogisticsService
→ le service retourne CartLogisticsPreview
→ le template panier affiche le message et les frais estimés
```

## Signature logistique

Pour éviter des recalculs inutiles, la logique tient compte du périmètre vendeur.

```text
signature =
adresse client
+ vendeurs uniques présents dans le panier
```

## Cas de recalcul

```text
nouveau vendeur ajouté
→ recalcul

dernier produit d'un vendeur retiré
→ recalcul

adresse client changée
→ recalcul
```

## Cas sans recalcul logistique utile

```text
ajout d'un produit venant d'un vendeur déjà présent
→ pas de changement de périmètre vendeur

modification de quantité
→ pas de changement de périmètre vendeur
```

Le total produits change, mais les frais de livraison estimés restent liés au même périmètre logistique.

---

# Workflow J5G-B prévu — Plus court chemin entre communes

## Objectif

Calculer combien de communes doivent être traversées entre la commune vendeur et la commune client.

## Données utilisées

```text
DeliveryCommune
DeliveryCommune.neighboringCommunes
Seller.deliveryCommune
Address.commune
```

## Workflow

```text
Client a une commune de livraison
→ chaque vendeur du panier a une commune logistique
→ DeliveryLogisticsService construit ou parcourt le graphe des communes
→ BFS cherche le plus court chemin
→ le service retourne :
   - la liste des communes du chemin
   - le nombre de sauts
   - un warning si aucun chemin n'est trouvé
```

## Exemple

```text
Dzaoudzi voisine de Labattoir
Labattoir voisine de Pamandzi
```

Alors :

```text
Dzaoudzi → Labattoir = 1 saut
Dzaoudzi → Pamandzi = 2 sauts si le chemin passe par Labattoir
```

## Cas barge

La barge reste calculée avant ou en parallèle :

```text
clientTerritory !== sellerTerritory
```

Si barge requise, le service doit ajouter :

```text
supplément barge aller-retour
```

et calculer les sauts de communes pertinents côté départ / arrivée selon le modèle pilote retenu.

---

# Workflow J5G-C / J5G-D prévu — Calcul détaillé des frais

## Formule cible

```text
frais client =
frais local client
+ hopCount × delivery_commune_hop_customer_fee
+ bargeFee si requiresBarge
```

```text
rémunération livreur =
payout local client
+ hopCount × delivery_commune_hop_courier_payout
+ bargePayout si requiresBarge
```

```text
marge livraison =
frais client - rémunération livreur
```

## Affichage panier cible

Le panier pourra afficher :

```text
Livraison locale : 6,00 €
Communes traversées : 1 × 2,00 €
Barge aller-retour : 6,00 €
Total livraison estimé : 14,00 €
```

L'affichage peut rester simple côté client, mais la donnée doit être calculée proprement côté service.

---

# Workflow J5G-E prévu — Checkout et snapshot livraison

## Objectif

Au moment de valider la commande, recalculer la livraison puis figer les valeurs.

## Workflow

```text
Client valide le checkout
→ CheckoutController récupère panier + adresse
→ DeliveryLogisticsService recalcule
→ CustomerOrder est créée
→ les montants livraison sont figés
→ les détails logistiques utiles sont figés
```

## Valeurs à figer

```text
deliveryFee
courierPayout
deliveryMargin
requiresBarge
logisticsLevel
logisticsHopCount
logisticsPathSummary
bargeCustomerFee
bargeCourierPayout
communeHopCustomerFee
communeHopCourierPayout
```

## Pourquoi figer

Si demain l'admin change le prix de la barge ou les communes voisines, l'ancienne commande doit garder les frais appliqués au moment de sa validation.


---

# Workflow J5G-B1 — De la source Excel vers une base modifiable

## Objectif

Transformer la source validée de voisinage en données applicatives administrables.

## Workflow de référence

```text
Fichier Excel validé
→ lecture par le développeur
→ création ou mise à jour des entités Doctrine
→ migration
→ seed initial
→ contrôle EasyAdmin
→ corrections terrain en base
```

## Ce qu'il ne faut pas faire

```text
Panier
→ lire directement un fichier Excel
→ calculer la livraison
```

Pourquoi : ce serait fragile, lent, difficile à corriger et non adapté à la production.

## Workflow cible J5G-B

```text
Admin / seed crée les communes
→ Admin / seed crée les liaisons
→ DeliveryLogisticsService charge les liaisons actives
→ construit une hash map
→ BFS trouve le chemin le plus court
→ le service compte LAND et BARGE
→ panier affiche une estimation
```

## Workflow de correction terrain

```text
Un voisinage est faux
→ admin ouvre EasyAdmin
→ modifie la liaison
→ sauvegarde
→ le prochain calcul panier utilise la nouvelle donnée
```

## Exemple Mamoudzou vers Labattoir

```text
Vendeur Mamoudzou
→ liaison BARGE vers Dzaoudzi
→ liaison LAND vers Labattoir
→ client Labattoir
```

Le workflow produit :

```text
pathSummary = Mamoudzou → Dzaoudzi → Labattoir
requiresBarge = true
bargeHopCount = 1
landHopCount = 1
```

## Workflow pédagogique BFS

Pour un développeur débutant :

```text
1. Je pars de la commune vendeur.
2. Je regarde toutes ses voisines directes.
3. Si la commune client est trouvée, j'arrête.
4. Sinon je regarde les voisines des voisines.
5. Le premier chemin trouvé est le plus court si chaque liaison vaut 1.
```

Le BFS n'est pas un GPS. C'est un calcul de graphe simple basé sur les voisinages saisis.

---

# Workflow J5G-B2 / J5G-B3 — carte logistique modifiable

## Workflow de création du modèle

```text
Source validée
→ modèle Doctrine enrichi
→ migration modèle
→ migration corrective schéma si nécessaire
→ validation locale
→ commit
→ déploiement recette
→ migration recette
→ validation recette
```

## Workflow de seed initial

```text
Fichier source validé
→ traduction en migration de seed
→ insertion / mise à jour des communes
→ insertion / mise à jour des liaisons
→ vérification SQL locale
→ commit
→ déploiement recette
→ vérification SQL recette
```

## Workflow de validation d'un seed

Un seed est considéré validé seulement si les 4 conditions sont remplies :

```text
1. migration exécutée
2. schema:validate OK
3. données présentes dans les tables métier
4. EasyAdmin permet de les consulter / modifier
```

## Workflow cible J5G-B4

```text
Panier
→ produits
→ vendeurs
→ communes vendeurs
→ adresse client
→ commune client
→ DeliveryLogisticsService
→ graphe DeliveryCommuneConnection
→ plus court chemin
→ CartLogisticsPreview enrichi
→ affichage panier
```

## Cas à gérer en J5G-B4

```text
même commune
commune voisine directe
commune éloignée sur le même territoire
trajet PT ↔ GT avec barge
vendeur sans commune logistique
client avec commune non paramétrée
liaison inactive
aucun chemin trouvé
```

## Règle de sécurité fonctionnelle

Si le chemin ne peut pas être calculé, Hodina ne doit pas bloquer brutalement le panier.

Comportement attendu :

```text
warning interne / message utilisateur
frais à confirmer par Hodina
commande possible si le reste du tunnel le permet
```

## Workflow adresses — état cible support J5G

### Création / édition utilisateur EasyAdmin

```text
Admin ouvre un utilisateur
→ ajoute une adresse
→ choisit Type d'adresse
   - Adresse de livraison
   - Adresse de facturation
→ renseigne adresse / code postal / commune / zone
→ Symfony valide l'objet Address imbriqué
→ sauvegarde seulement si les règles du type sont respectées
```

### Adresse de livraison

```text
Formulaire
→ Address(type=DELIVERY)
→ DeliverableAddressValidator
→ DeliveryCommuneMatcherService
→ recherche dans delivery_commune active et point logistique
→ contrôle code postal
→ contrôle zone PT/GT
→ OK ou erreur formulaire
```

### Adresse de facturation

```text
Formulaire
→ Address(type=BILLING)
→ contrôle format code postal 5 chiffres
→ zone AUTRE attendue
→ commune libre
→ OK ou erreur formulaire
```

### Checkout

Le checkout doit utiliser l'adresse de livraison pour la logistique. L'adresse de facturation ne doit pas influencer le calcul de trajet.

```text
Livraison → logistique / trajet / frais
Facturation → administratif / facture / justificatif
```

### Inscription

L'inscription doit créer au minimum une adresse de livraison exploitable par Hodina. Si l'utilisateur fournit une facturation différente, elle doit être créée avec `type=BILLING` et zone `AUTRE` si hors zone livrable.

---

# Mise à jour workflows — 12/06/2026 — adresses client

## Workflow EasyAdmin utilisateur

```text
Admin ouvre un client
→ édite "Adresse de facturation"
→ édite "Adresses du client"
→ chaque adresse a un type DELIVERY/BILLING
→ validation Symfony de l'adresse imbriquée
→ sauvegarde seulement si l'adresse respecte ses règles
```

### Livraison EasyAdmin

```text
Type DELIVERY
→ PT ou GT uniquement
→ commune livrable Hodina
→ code postal cohérent
→ zone cohérente
```

### Facturation EasyAdmin

```text
Type BILLING
→ AUTRE possible
→ PT/GT possible
→ si AUTRE : code postal 5 chiffres
→ si PT/GT : commune livrable + cohérence code postal / zone
```

## Workflow checkout invité

```text
Client non connecté
→ panier
→ checkout
→ saisit identité
→ saisit livraison
→ choisit facturation identique ou séparée
→ si e-mail existe déjà : blocage
→ validation des adresses
→ création Customer
→ création Address DELIVERY
→ création Address BILLING si nécessaire
→ création commande
→ statut PENDING_VALIDATION
```

### Cas e-mail existant

```text
email déjà présent dans Customer
→ erreur sous le champ e-mail
→ pas de création de commande
→ pas de création de client
→ pas de création d'adresse
```

## Workflow checkout connecté

```text
Client connecté
→ choisit une adresse existante ou saisit une nouvelle livraison
→ la logistique utilise uniquement l'adresse de livraison
→ la facturation reste administrative
```

## Workflow inscription

```text
Client ouvre /caribou
→ saisit identité + mot de passe
→ saisit livraison
→ choisit facturation identique ou séparée
→ e-mail déjà existant : erreur unique
→ validation adresses
→ création Customer
→ création adresses
→ connexion / redirection selon fonctionnement existant
```

## Règle d'affichage des erreurs front

Les erreurs doivent être affichées sous le champ concerné :

```text
email → sous e-mail
line1 → sous adresse
postalCode → sous code postal
commune → sous commune
deliveryZone → sous zone
```

Style attendu :

```text
message rouge
champ encadré rouge
valeurs conservées
pas de liste à puces énorme
pas de formulaire vidé
```

## Lien avec J5G-B4

Le calcul de trajet réel ne doit utiliser que la livraison.

```text
Address(type=DELIVERY)
→ DeliveryCommuneMatcherService
→ DeliveryLogisticsService
→ DeliveryCommuneConnection
→ CartLogisticsPreview
```

La facturation ne doit pas influencer :

```text
barge
nombre de liaisons
zone tarifaire de livraison
rémunération livreur
```

---

# Workflows — mise à jour 13/06/2026 — préouverture et e-mails

## Préouverture

```text
Visiteur arrive sur Hodina
→ voit la bannière de préouverture
→ voit le compte à rebours
→ peut consulter le catalogue
→ peut laisser son e-mail pour être prévenu
→ ne peut pas ajouter au panier
→ ne peut pas commander
```

Si le visiteur appelle directement une URL panier, le contrôleur doit refuser la demande côté serveur.

## Ouverture automatique

```text
Date actuelle >= salesOpeningAt
→ isSalesOpen() devient true
→ boutons panier actifs
→ checkout actif
```

## E-mail de création de commande

```text
Commande créée en base
→ numéro métier généré
→ lignes et adresses enregistrées
→ OrderEmailService prépare l'e-mail HTML
→ Symfony Mailer envoie via SMTP o2switch
→ EmailLog SENT ou FAILED
```

En cas d'échec SMTP, la commande reste créée.


---

# Workflow réel validé — J5I préouverture en recette

## 1. Affichage de la bannière

```text
Visiteur ouvre Hodina
→ base.html.twig charge le bloc de préouverture
→ SalesOpeningService lit hodina_setting
→ si is_countdown_enabled = 1 et sales_opening_at est dans le futur
→ la bannière s'affiche
```

## 2. Capture e-mail

```text
Visiteur saisit son e-mail
→ POST vers le contrôleur LaunchSubscriberController
→ vérification e-mail
→ insertion dans launch_subscriber si absent
→ affichage du message de succès
→ consultation possible dans EasyAdmin > Abonnés ouverture
```

Test validé en local :

```text
abdamayot@hotmail.fr enregistré dans launch_subscriber
```

## 3. Blocage panier

```text
Client clique Ajouter au panier
→ CartController interroge SalesOpeningService
→ si panier bloqué avant ouverture
→ refus serveur + message clair
```

Même si le client contourne le bouton désactivé, le contrôleur reste la protection principale.

## 4. Blocage checkout

```text
Client tente de valider son panier
→ CheckoutController interroge SalesOpeningService
→ si les commandes ne sont pas ouvertes
→ aucune CustomerOrder n'est créée
```

## 5. Ouverture automatique

```text
Date actuelle >= sales_opening_at
→ les ventes sont considérées ouvertes
→ bannière masquée ou non bloquante selon configuration
→ panier actif
→ checkout actif
```

## 6. Administration

```text
Admin ouvre /ouegnewe
→ Réglages Hodina / préouverture
→ modifie la date, les textes ou les interrupteurs
→ recharge le front
→ le comportement change sans nouveau déploiement
```

---

# Workflow J5J — Commandes publiques bloquées mais testeurs autorisés

## Workflow public en mode restrictif

```text
Visiteur ou client sans ROLE_COMMERCE_TESTER
→ catalogue visible
→ bannière visible si mode restrictif
→ ajout panier refusé côté serveur
→ checkout refusé côté serveur
```

Modes restrictifs :

```text
preopening
maintenance
closed
```

## Workflow testeur

```text
Client connecté avec ROLE_COMMERCE_TESTER
→ catalogue visible
→ ajout panier autorisé
→ panier utilisable
→ checkout utilisable
→ commande de test possible
```

Les admins sont aussi autorisés à tester.

## Workflow mode ouvert

```text
commerce_mode = open
→ aucune bannière
→ aucun chrono
→ comportement normal du portail
```
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

# Workflow J5H-A — e-mail de création de commande

## Workflow automatique

```text
Client valide le checkout
→ CustomerOrder est créée
→ OrderItem sont persistés
→ EntityManager flush
→ OrderEmailService::sendOrderCreatedToCustomer()
→ insertion EmailLog PENDING via DBAL
→ construction snapshot articles via DBAL
→ création TemplatedEmail
→ Symfony Mailer accepte l'e-mail
→ EmailLog passe à SENT
→ message SendEmailMessage entre dans messenger_messages
→ cron Messenger consomme async
→ SMTP o2switch envoie le mail réel
→ client reçoit l'e-mail
```

## Règles critiques

- La création de commande passe avant l'e-mail.
- L'e-mail ne doit jamais bloquer le checkout.
- Le checkout capture les erreurs e-mail et continue la redirection confirmation.
- Le contenu e-mail doit être indépendant des collections Doctrine lazy au moment du rendu async.
- Les articles du mail sont préparés en snapshot simple : nom, quantité, prix unitaire, total ligne.
- Les frais affichés proviennent de `CustomerOrder.deliveryFee`.
- Le total affiché provient de `CustomerOrder.total`.

## Workflow manuel de secours

```text
Admin ouvre EasyAdmin
→ Journaux e-mails
→ bouton Envoyer manuellement
→ le navigateur ouvre mailto:
→ client mail du téléphone/PC se lance
→ destinataire, sujet, corps sont préremplis
→ admin vérifie
→ admin clique Envoyer
→ le message part depuis contact@hodina.fr
→ le message apparaît dans Messages envoyés du client mail
```

## Points de contrôle recette

```bash
php bin/console dbal:run-sql --force-fetch "SELECT id, queue_name, created_at, available_at, delivered_at FROM messenger_messages ORDER BY id DESC LIMIT 10"
tail -30 var/log/messenger_cron.log
php bin/console dbal:run-sql --force-fetch "SELECT id, recipient_email, subject, status, error_message, sent_at, created_at FROM email_log ORDER BY id DESC LIMIT 5"
```

Résultat attendu après cron :

```text
messenger_messages vide
email_log avec statut SENT
mail reçu côté client
```

---

# Workflow J5G-E0 — Commande avec snapshot adresse

## Règle générale

Le carnet d'adresses client est vivant. La commande est figée.

```text
Client choisit une adresse de livraison
Client choisit une adresse de facturation ou coche facturation identique
Checkout crée / réutilise l'adresse du carnet client
Checkout copie les valeurs utiles dans CustomerOrder
Commande créée
L'adresse client peut ensuite être supprimée sans casser la commande
```

## Création de commande

Au moment du checkout :

1. l'adresse de livraison est contrôlée comme adresse `DELIVERY` exploitable ;
2. l'adresse de facturation peut être `BILLING`, y compris hors zone Hodina (`AUTRE`) ;
3. si une adresse identique existe déjà dans le carnet, elle est réutilisée ;
4. `CustomerOrder` reçoit un snapshot livraison ;
5. `CustomerOrder` reçoit un snapshot facturation ;
6. l'e-mail, l'admin et le livreur lisent les valeurs de commande.

## Suppression d'adresse

```text
Admin / client supprime une adresse du carnet
→ address supprimée
→ customer_order.delivery_address_id peut devenir NULL
→ les champs delivery_address_* restent remplis
→ la commande reste exploitable
```

Cette règle est validée en recette avec une commande dont l'adresse liée a été supprimée : l'ID relationnel est devenu nul, mais `delivery_address_line1`, `postal_code`, `commune` et `zone_code` sont restés disponibles.

## Affichages à respecter

Les vues opérationnelles doivent lire les snapshots commande en priorité :

- fiche terrain admin ;
- détail commande EasyAdmin ;
- dashboard livreur ;
- e-mail automatique client ;
- e-mail manuel depuis `EmailLog`.

La relation `deliveryAddress` ne doit servir que de compatibilité ou d'aide technique, pas de source principale pour l'historique.

---

# Workflow J5G-E1 — Saisie adresse simplifiée par commune livrée

## Objectif

Réduire la friction du checkout et fiabiliser les données qui alimentent la logistique.

## Workflow cible côté client

```text
Client arrive au checkout
→ il saisit la ligne d'adresse
→ il choisit sa commune de livraison dans une liste de communes livrées
→ Hodina préremplit le code postal
→ Hodina affiche éventuellement la zone en lecture seule : Petite-Terre / Grande-Terre
→ le client valide
→ le serveur recalcule la commune et la zone
→ la commande est créée
→ J5G-E0 copie l'adresse dans le snapshot CustomerOrder
```

## Workflow avec adresse existante

```text
Client choisit une adresse existante
→ les champs sont hydratés depuis Address
→ la commune / zone restent recalculées côté serveur
→ si l'adresse n'est plus cohérente avec une commune livrée active, le checkout bloque avec un message clair
```

## Workflow facturation différente

```text
Client décoche “facturation identique à livraison”
→ il peut saisir une adresse de facturation différente
→ cette adresse peut être hors zone livrable
→ elle peut être classée AUTRE
→ elle est copiée dans le snapshot facturation de CustomerOrder
```

## Garde-fous

- Ne jamais faire confiance à une zone envoyée par le navigateur.
- Ne pas laisser le client forcer `PT` ou `GT`.
- Continuer à valider côté serveur.
- Conserver les snapshots J5G-E0 après création de commande.

---

# Workflow J5G-E1 → J5G-E2-bis-A — Panier livraison et validation

## Règle générale

Pendant le pilote avec paiement manuel, le panier est l'écran où le client voit et valide le total.

```text
Produits du panier
→ adresse de livraison utilisée
→ modification adresse si besoin
→ recalcul livraison
→ récapitulatif
→ validation commande
```

## Workflow client

```text
Client ouvre le panier
→ le panier affiche les produits
→ Hodina affiche l'adresse de livraison utilisée
→ le bloc de modification adresse est replié
→ le client peut le déplier s'il veut changer d'adresse
→ il choisit une commune livrée Hodina
→ le code postal est prérempli
→ la zone est affichée en lecture seule
→ les frais sont recalculés en AJAX
→ le récapitulatif affiche le nouveau total
→ le client valide la commande
→ le serveur recalcule et compare avec le total affiché
→ si cohérent, la commande est créée
→ la page confirmation affiche le récapitulatif
```

## Workflow en cas d'écart de total

```text
Client voit un total au panier
→ panier / adresse / frais changent avant validation
→ serveur recalcule au moment de valider
→ total différent détecté
→ commande non créée
→ retour panier
→ message expliquant la raison
→ client revalide après lecture du nouveau total
```

## Workflow tarifaire actuel

```text
commune livrée PT sans barge → forfait PT_LOCAL
commune livrée GT sans barge → forfait GT_LOCAL
commune livrée PT avec barge → PT_LOCAL + coût fixe BARGE
commune livrée GT avec barge → GT_LOCAL + coût fixe BARGE
```

## Workflow futur paiement en ligne

Quand le paiement en ligne sera ajouté :

```text
Panier
→ livraison et total
→ checkout paiement
→ adresse de facturation si différente
→ paiement
→ confirmation
```

Le checkout ne devra pas redevenir l'écran principal de choix de livraison.

---

# Workflow déploiement validé — J5G-E1 → J5G-E2-bis-A

## Recette

```text
Déployer la branche pilot/j5g-e1-commune-livree
→ vérifier qu'elle contient l'historique J5J
→ composer install --no-dev --optimize-autoloader
→ cache:clear --env=prod
→ cache:warmup --env=prod
→ doctrine:migrations:status --env=prod
→ doctrine:schema:validate --env=prod
→ tests navigateur recette
```

Résultat : OK.

## Production

```text
Déployer pilot/j5j-commerce-mode-role-tester jusqu'au commit 36cc357
→ composer install --no-dev --optimize-autoloader
→ cache:clear --env=prod
→ cache:warmup --env=prod
→ doctrine:migrations:status --env=prod
→ migrations en attente détectées
→ doctrine:migrations:migrate --env=prod
→ doctrine:migrations:status --env=prod : New = 0
→ doctrine:schema:validate --env=prod : OK
→ cache clear + warmup
→ tests navigateur production
```

Résultat : OK.

## Tests production validés

```text
Accueil OK
Catalogue OK
Ajout panier OK
Panier OK
Livraison avant récapitulatif OK
Changement commune PT / GT OK
Frais recalculés OK
Total affiché cohérent OK
Validation commande OK
Confirmation avec récapitulatif OK
EasyAdmin commande / adresse / zone / total OK
```

## Workflow Git validé

```text
git tag j5g-e1-e2bis-prod
git push origin j5g-e1-e2bis-prod
```

Ce tag marque la version production validée de J5G-E1 à E2-bis-A.

# Workflow J5G-B4 — Calcul logistique panier et snapshot commande

## Workflow panier avec calcul BFS

```text
Client ouvre le panier
→ Hodina lit la commune livrée sélectionnée
→ Hodina identifie les vendeurs du panier
→ Hodina identifie les communes de collecte distinctes
→ pour chaque commune de collecte, DeliveryLogisticsService cherche un chemin vers la commune livrée
→ BFS renvoie le plus court chemin sur DeliveryCommuneConnection
→ Hodina compte les liaisons LAND et BARGE
→ Hodina calcule le coût de chaque trajet
→ Hodina retient le trajet de collecte le plus contraignant
→ Hodina ajoute le forfait local de la commune livrée
→ Hodina ajoute le supplément multicommunes de collecte
→ Hodina applique le plafond global client si nécessaire
→ le panier affiche les frais estimés et le détail de collecte
```

## Workflow coûts LAND / BARGE

```text
Hop LAND
→ coût spécifique liaison si renseigné
→ sinon coût global traversée commune
→ 0 explicite = pas de fallback global

Hop BARGE
→ coût spécifique liaison BARGE
→ pas de fallback traversée commune
```

## Workflow multicommunes

```text
1 commune de collecte
→ aucun supplément multicommunes

2 communes de collecte
→ 1 supplément

3 communes de collecte
→ 2 suppléments
→ plafonnement du supplément si configuré
```

## Workflow snapshot commande

```text
Client valide le panier
→ serveur recalcule le total
→ serveur vérifie la signature / cohérence du total
→ si cohérent, CustomerOrder est créée
→ CustomerOrder reçoit l'adresse snapshotée
→ CustomerOrder reçoit le snapshot logistique JSON
→ OrderItem est créé
→ confirmation commande affichée
→ admin peut consulter Commande > Logistique
```

## Workflow admin analyse logistique

```text
Admin ouvre EasyAdmin > Commandes
→ clique sur Logistique
→ si snapshot présent : afficher les données figées
→ sinon : recalcul dynamique avec les paramètres actuels
→ afficher résumé, frais client, payout livreur, marge, trajets, JSON brut
```

## Workflow déploiement J5G-B4

```text
git pull origin pilot/j5j-commerce-mode-role-tester
→ composer install si nécessaire
→ doctrine:migrations:migrate
→ cache:clear
→ cache:warmup en prod
→ doctrine:schema:validate
→ vérifier settings globaux
→ créer commande test
→ vérifier panier et admin Logistique
```

# Workflow DevOps — livraison par tag

## Workflow manuel actuel

```text
développer sur branche pilote / feature
→ tester localement
→ fusionner dans branche pilote si nécessaire
→ fusionner dans main
→ créer un tag depuis main
→ pousser main et le tag
→ lancer tools/deploy-hodina-by-tag.sh sur recette
→ valider recette
→ lancer le même script sur production
→ contrôler logs et cron Messenger
```

## Commandes locales type

```powershell
git checkout main
git pull origin main
git merge --no-ff pilot/j5j-commerce-mode-role-tester -m "merge(j5g): release BFS delivery logistics"
php bin/console cache:clear
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
git push origin main
git tag j5g-b4-20260618
git push origin j5g-b4-20260618
```

## Workflow recette

```bash
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618   --target recette
```

## Workflow production

```bash
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/hodina.fr   --tag j5g-b4-20260618   --target prod
```

## Workflow nettoyage commandes

En local Windows :

```powershell
.	ools
eset-commandes-hodina.ps1
```

Sur serveur via le script Bash :

```bash
RESET_COMMANDS=1 bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618   --target recette
```

## Workflow — Déploiement par tag J5G-B4 v7

Workflow cible :

```text
1. Développer sur branche pilote / feature
2. Merger dans main
3. Créer un tag depuis main
4. Fetcher main + tags sur recette/prod
5. Extraire le script depuis le tag
6. Vérifier que le script n'est pas vide
7. Vérifier la syntaxe Bash
8. Lancer le script avec --target recette ou --target prod
9. Tester fonctionnellement
```

Commande sécurisée d'extraction :

```bash
git fetch origin main --tags --force
rm -f /tmp/deploy-hodina-by-tag.sh
git show <tag>:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh
```

Le script prend ensuite le relais : env, uploads, backup DB, migration, cache, cron, validations.

# Workflow v11 — Ajax panier, admin mobile et e-mail réel

Date : **19/06/2026**
Tag validé : `j5g-b4-20260618-v11`

## Workflow client — ajout produit Ajax

Depuis le catalogue ou la fiche produit :

```text
Client clique Ajouter au panier
→ JS intercepte le formulaire data-ajax-cart-form
→ POST /panier/ajouter/{id} avec X-Requested-With: XMLHttpRequest
→ CartController vérifie préouverture, produit actif, quantité
→ CartService ajoute au panier serveur
→ caches logistiques panier supprimés
→ réponse JSON
→ pastille panier mise à jour
→ toast de confirmation
```

Fallback : si JavaScript est absent ou cassé, le formulaire POST classique continue de rediriger.

## Workflow panier verrouillé

```text
Préouverture active / panier verrouillé
→ tentative Ajax d'ajout produit
→ réponse JSON ok=false, HTTP 423
→ message affiché côté client
→ aucun ajout panier
```

## Workflow admin — menu repliable

```text
Admin ouvre /ouegnewe
→ assets admin compilés depuis AssetMapper
→ assets/admin.js chargé
→ sections longues détectées
→ section active laissée ouverte sur mobile
→ autres sections repliables
→ état mémorisé dans localStorage
```

Sections :

```text
Logistique
Catalogue
Commandes
Utilisateurs
Pilote
Réglages
```

Cas particulier : l'entrée `Utilisateurs` est un lien et ne doit pas devenir une section.

## Workflow e-mail commande réel

```text
Commande créée
→ OrderEmailService crée EmailLog
→ Symfony Mailer envoie via MAILER_DSN
→ Messenger async transporte SendEmailMessage
→ EmailLog passe SENT si l'envoi Symfony ne lève pas d'exception
→ validation réelle = e-mail reçu dans la boîte
```

Attention : avec `MAILER_DSN=null://null`, `EmailLog` peut passer `SENT` sans e-mail réel. La validation prod doit inclure une réception réelle.

## Workflow MEP conseillé

```text
ne pas naviguer dans l'admin pendant le script
→ attendre [OK] Déploiement terminé avec succès
→ ouvrir une fenêtre privée
→ tester accueil, admin, panier, email, miniatures
```


## Workflow pré-J5K — Dette runtime / mailer

### Objectif

Nettoyer les règles Git avant d'ajouter le GPS de livraison.

### Flux

```text
Développeur local
→ ajoute bloc .gitignore runtime
→ git rm --cached env/uploads/assets
→ garde public/uploads/products/.gitkeep
→ vérifie git ls-files
→ commit docs + règles Git
→ déploie recette par tag
→ vérifie env/uploads/assets côté serveur
→ teste MAILER_DSN si e-mails réels attendus
→ déploie production
→ démarre J5K
```

### Point de contrôle obligatoire

```bash
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

La seule sortie acceptable est :

```text
public/uploads/products/.gitkeep
```

---

# Workflow J5K-bis — GPS, instructions et carnet d'adresses client

## Objectif métier

À Mayotte, l'adresse texte seule n'est pas suffisante. Hodina doit combiner :

```text
adresse écrite
commune livrée
zone logistique
instructions client
géolocalisation GPS facultative
notes terrain internes éventuelles
```

Le GPS n'est pas obligatoire. Les instructions ne sont pas obligatoires. Mais quand ces données existent, elles doivent être conservées et réutilisables.

## Séparation des responsabilités

### Address

`Address` représente le carnet d'adresses vivant du client.

Il peut évoluer entre deux commandes et contient notamment :

```text
line1
postalCode
commune
deliveryZone
notes / deliveryInstructions
courierNotes
gpsLatitude
gpsLongitude
gpsAccuracyMeters
```

### CustomerOrder

`CustomerOrder` garde une copie figée au moment de la validation de commande.

Le snapshot protège l'historique : une ancienne commande ne doit pas changer si le client modifie son adresse plus tard.

## Parcours panier attendu

```text
Client connecté
→ panier
→ adresse utilisée affichée
→ possibilité de modifier l'adresse sélectionnée
→ possibilité de choisir une adresse enregistrée
→ possibilité d'utiliser l'adresse proposée par défaut
→ possibilité d'utiliser une nouvelle adresse
→ instructions facultatives
→ GPS facultatif
→ validation commande
```

## Adresse proposée par défaut

Le modèle ne possède pas encore de champ `is_default`.

Pour le pilote, Hodina propose par défaut l'adresse la plus utile pour le terrain :

```text
1. adresse avec GPS ;
2. adresse avec instructions ;
3. adresse avec notes terrain internes ;
4. adresse la plus récente.
```

Cette règle évite qu'une adresse récente mais vide masque une adresse enrichie plus utile.

## Réutilisation d'adresse de livraison

Une adresse de livraison ne doit être réutilisée automatiquement que si les champs de base et les données terrain correspondent.

Champs comparés :

```text
line1
line2
postalCode
commune
deliveryZone
instructions
gpsLatitude
gpsLongitude
gpsAccuracyMeters
```

Objectif : une commande sans GPS / sans instruction ne doit pas effacer une adresse enrichie GPS + instructions.

## Modification vs nouvelle adresse

Deux intentions doivent rester séparées :

```text
Modifier l'adresse sélectionnée
= mettre à jour l'adresse existante pointée par existingAddressId.

Utiliser une nouvelle adresse
= vider existingAddressId et créer / réutiliser une adresse séparée.
```

## Tests fonctionnels obligatoires

```text
[ ] Commande avec GPS + instructions.
[ ] Commande sans GPS.
[ ] Commande sans instructions.
[ ] Réutilisation de l'adresse enrichie après une commande vide.
[ ] Bouton Utiliser l'adresse par défaut.
[ ] Admin : GPS et instructions visibles si présents.
[ ] Admin : absence GPS / instructions affichée proprement.
[ ] Portail livreur : bouton carte si GPS présent.
[ ] Portail livreur : aucune erreur si GPS absent.
```

---

## Workflow panier — sélection et adresses par défaut J5K-v8 final local

### Sélection pour la commande en cours

Dans le panier, le client sélectionne une adresse en appuyant directement sur sa carte. Cette action ne change pas l'adresse par défaut du client ; elle sert uniquement à la commande en cours.

Le bouton `Sélectionner cette adresse` a été supprimé : sur mobile, le clic / toucher de la carte est l'action naturelle.

### Définition d'une adresse par défaut

La définition d'une adresse par défaut ne se fait plus depuis les cartes ni depuis les blocs `Adresse utilisée`.

Elle se fait uniquement dans le formulaire d'ajout ou de modification d'adresse, avec une case unique :

```text
Utiliser cette adresse par défaut
```

Le contexte du formulaire détermine la cible :

- formulaire livraison → mise à jour de `customer.delivery_address_id` à la validation du panier ;
- formulaire facturation → mise à jour de `customer.billing_address_id` à la validation du panier.

### Livraison

Le bloc livraison affiche l'adresse, la commune, les instructions livreur et le GPS si présents. La modification de livraison conserve l'accès au GPS et aux instructions.

Si aucune adresse de livraison par défaut n'est définie, Hodina sélectionne automatiquement une adresse de livraison disponible. Si aucune adresse de livraison n'existe, le panier propose d'ajouter une adresse de livraison.

### Facturation

Le bloc facturation affiche uniquement les informations utiles à la facturation. Aucun GPS, aucune instruction livreur et aucune zone métier ne doivent apparaître dans ce bloc côté client.

Si aucune adresse de facturation par défaut n'est définie, Hodina cherche une adresse `BILLING`. S'il n'en existe pas mais qu'une autre adresse client existe, Hodina crée une vraie adresse `BILLING` séparée en copiant uniquement les champs postaux utiles. Si aucune adresse n'existe, le panier propose d'ajouter une adresse de facturation.


## Workflow — Adresse de facturation au panier J5K-v8-bis

À l’ouverture du panier client connecté, Hodina sélectionne l’adresse de facturation ainsi :

1. `customer.billing_address_id` si renseignée ;
2. première adresse de type `BILLING` si disponible ;
3. création automatique d’une adresse `BILLING` depuis la première adresse client disponible ;
4. sinon affichage d’un état vide avec ajout d’adresse de facturation.

La création automatique ne copie jamais les champs de livraison terrain : GPS, précision GPS, instructions livreur et notes livreur.

---

## Validation locale — 19/06/2026 soir — J5K-v8-quater

Les tests locaux sont considérés comme bons avant reprise recette :

- cartes d'adresses cliquables ;
- sélection livraison fonctionnelle ;
- sélection facturation fonctionnelle après correction ;
- absence de bouton redondant `Sélectionner cette adresse` ;
- case `Utiliser cette adresse par défaut` visible uniquement dans les formulaires d'ajout/modification ;
- case prise en compte à la validation du panier ;
- facturation sans GPS, sans instructions livreur et sans zone affichée côté client ;
- livraison avec GPS et instructions ;
- validation Symfony locale : syntaxe PHP, cache clear et `doctrine:schema:validate`.

À la reprise, ne pas considérer la recette validée tant que le tag final n'a pas été déployé et testé.

---

# Workflow J5L — Panier mobile et sélecteur compact d'adresses

## Workflow client panier simplifié

```text
Client ouvre le panier
→ voit les articles
→ voit sous-total, frais de livraison et total
→ vérifie l'adresse de livraison
→ vérifie l'adresse de facturation
→ coche les CGV
→ valide la commande
```

Les détails techniques de route logistique ne sont pas affichés dans le panier client.

## Workflow changement adresse de livraison

```text
Client clique sur Changer l'adresse de livraison
→ panneau compact livraison s'ouvre
→ client sélectionne une adresse
→ panneau reste ouvert
→ client peut ajouter la position GPS si nécessaire
→ client peut cocher Utiliser cette adresse par défaut
→ client clique Utiliser cette adresse de livraison
→ panneau se ferme
→ panier affiche l'adresse retenue
```

## Workflow changement adresse de facturation

```text
Client clique sur Changer l'adresse de facturation
→ panneau compact facturation s'ouvre
→ client sélectionne une adresse
→ panneau reste ouvert
→ client peut corriger les champs si nécessaire
→ client peut cocher Utiliser cette adresse par défaut
→ client clique Utiliser cette adresse de facturation
→ panneau se ferme
→ panier affiche l'adresse retenue
```

## Workflow ajout GPS dans le panneau

```text
Adresse de livraison sans GPS
→ bouton Utiliser ma position actuelle visible
→ client clique
→ navigateur demande l'autorisation de géolocalisation
→ coordonnées récupérées
→ champ texte GPS alimenté
→ champs techniques GPS alimentés
→ message flash affiché
→ adresse considérée comme complétée côté panier
```

Message attendu :

```text
La position GPS actuelle a été affectée à l'adresse de livraison.
```

## Workflow admin après commande

```text
Admin ouvre la commande
→ voit le bloc Client
→ voit le bloc Livraison
→ voit le bloc Facturation
→ voit les articles à préparer
```

La facturation affichée est le snapshot de commande, pas une lecture dynamique du carnet d'adresses client.

# Workflow cible J5M-A — Livreur enrichi

À développer après J5L :

```text
Admin marque la commande prête
→ ready_for_delivery

Livreur prend la commande
→ picked_up
→ affichage : Prise en charge par {livreur}

Livreur démarre la livraison
→ out_for_delivery
→ affichage : En cours de livraison

Livreur termine
→ delivered
```

Règle : le statut reste technique et stable. Le nom du livreur est une information associée, pas une partie du statut.


---

# Workflow J5M-C2/C3 — Création vendeur et collecte livreur

## Création vendeur backoffice

```text
Admin → EasyAdmin > Vendeurs > Créer
→ renseigne prénom vendeur
→ renseigne nom vendeur
→ renseigne téléphone / email
→ renseigne nom de structure si besoin
→ saisit adresse de retrait
→ choisit commune de retrait depuis DeliveryCommune
→ saisit instructions / GPS si disponible
→ enregistre
```

À la sauvegarde :

```text
SellerCrudController
→ normalise identité vendeur
→ crée ou rattache Customer vendeur
→ ajoute ROLE_SELLER
→ crée ou met à jour Address de retrait
→ synchronise Address.commune et Address.postalCode
→ synchronise Seller.deliveryCommune
→ synchronise Seller.deliveryZone
```

## Affichages vendeur

```text
Portail livreur : Seller::getCourierDisplayName()
→ businessName si renseigné
→ sinon prénom + nom

Catalogue : Seller::getPublicDisplayName()
→ businessName si renseigné
→ sinon nom de famille
```

## Collecte vendeur dans le portail livreur

```text
Livreur ouvre /djama
→ déplie une commande
→ consulte Collecte vendeurs
→ voit chaque vendeur distinct
→ voit le point de retrait
→ voit le GPS si renseigné
→ voit les produits et quantités à récupérer
```

## Calcul logistique

```text
DeliveryLogisticsService
→ lit Seller.deliveryCommune
→ calcule trajets/coûts/barge/BFS
```

Il ne doit pas lire l’adresse de retrait vendeur pour calculer les frais.

---

# Workflows — 24/06/2026 — J5O/J5P/J5Q

## Workflow livraison client avec code de réception

```text
Commande prête
→ livreur prend en charge
→ collectes vendeurs validées
→ livreur démarre la livraison
→ Hodina passe la commande en OUT_FOR_DELIVERY
→ Hodina génère / réutilise le code client
→ SMS + e-mail envoyés au client
→ livreur saisit le code donné par le client
→ bon code : commande DELIVERED
→ mauvais code : refus
→ code absent : renvoi du même code
```

Règle : la commande ne passe pas `DELIVERED` côté Djama sans code client valide.

## Workflow notifications client

Les changements de statut importants produisent des traces SMS et/ou e-mail.

```text
PENDING_VALIDATION → CONFIRMED              → SMS + e-mail
CONFIRMED → PREPARING                       → SMS + e-mail
PREPARING → READY_FOR_PICKUP                → SMS + e-mail
READY_FOR_PICKUP → PICKED_UP                → SMS + e-mail
Toutes collectes vendeurs terminées         → SMS + e-mail
PICKED_UP → OUT_FOR_DELIVERY                → code réception J5O
OUT_FOR_DELIVERY → DELIVERED                → SMS + e-mail
Annulation                                  → SMS + e-mail
```

Anti-spam : pas d'e-mail générique `OUT_FOR_DELIVERY` ajouté par J5P-A, car le code J5O couvre déjà cette étape.

## Workflow rémunération livreur admin

```text
Commandes DELIVERED avec assignedCourier
→ EasyAdmin > Livreurs > Rémunérations livreurs
→ Générer période en cours ou précédente
→ CourierPayout DRAFT
→ CourierPayoutLine par commande livrée non encore payée
→ contrôle admin
→ validation
→ paiement réel hors plateforme
→ marquer payé
→ statut PAID
```

Une commande déjà présente dans `CourierPayoutLine` est ignorée par les générations suivantes.

## Workflow Djama — Mes paiements

```text
Livreur connecté /djama
→ section Mes paiements
→ estimation période en cours
→ paiements à venir / validés
→ historique payé
→ carte paiement repliée
→ ouverture de la carte
→ détail commandes, dates, communes, montants
```

Le style replié/déplié suit le principe déjà validé pour les cartes de commandes Djama.

## Workflow EasyAdmin menu métier

Le menu admin doit être lu par métiers :

```text
Logistique       → zones, communes, liaisons
Catalogue        → catégories, produits
Commandes        → commandes, lignes
Clients          → comptes clients
Vendeurs         → vendeurs
Livreurs         → livreurs et rémunérations
Logs             → SMS, e-mails, abonnés ouverture, adhésions
Réglages         → HodinaSetting, préouverture
```

---

# Workflow J5Q-C — Génération automatique des paiements livreurs

## Parcours cron quotidien

```text
Cron serveur quotidien
→ hodina:courier-payouts:generate --auto-due --notify-admins
→ vérification date métier Indian/Mayotte
→ si jour non dû : arrêt propre
→ si 15 ou dernier jour du mois : génération des DRAFT
→ e-mail de récap aux admins
→ contrôle admin dans EasyAdmin
→ validation manuelle
→ paiement réel manuel
→ marquage PAID manuel
```

## Parcours admin manuel

```bash
php bin/console hodina:courier-payouts:generate --period=current --dry-run
php bin/console hodina:courier-payouts:generate --period=current --notify-admins
```

## Parcours recette conseillé

```text
1. Lancer un dry-run période courante.
2. Lancer un dry-run auto-due avec une date due.
3. Lancer un dry-run auto-due avec une date non due.
4. Lancer une génération réelle si les données de test sont prêtes.
5. Vérifier EmailLog COURIER_PAYOUT_RECAP.
6. Vérifier réception e-mail admin si MAILER_DSN réel.
7. Installer le cron recette.
8. Vérifier crontab et log courier_payout_cron.log.
```

# Workflow J5Q-C-1 — Administration des réglages groupés

## Vue experte

```text
Admin ouvre EasyAdmin
→ Réglages
→ Tous les paramètres
→ voit l'ensemble des réglages Hodina avec leur groupe
```

Cette vue sert aux admins avancés et aux développeurs lors des validations.

## Vue métier

```text
Admin ouvre EasyAdmin
→ Réglages
→ Commerce & commandes
→ ne voit que les paramètres du groupe commerce
```

Même logique pour Livraison & logistique, Paiements, Notifications, Général et Technique / maintenance.

## Modification d'un paramètre

```text
Admin ouvre le groupe concerné
→ modifie la valeur
→ enregistre
→ le paramètre reste dans son groupe
```

Si `isEditable = false`, la valeur est affichée mais ne doit pas être modifiée depuis EasyAdmin. Si `isSensitive = true`, la valeur est masquée dans les listes.

# Workflow J5Q-C-2 — Branding e-mail

## Paramétrage recette

```text
Admin ouvre EasyAdmin
→ Réglages
→ Branding e-mail
→ configure Préfixe objet e-mail = [Recette]
→ configure Formule début / Formule fin / Signature
→ enregistre
```

## Envoi e-mail

```text
Service métier prépare un sujet brut
→ EmailBrandingService ajoute le préfixe si nécessaire
→ EmailLog enregistre le sujet final
→ le template reçoit emailBranding
→ l'e-mail est envoyé avec ouverture, fin et signature paramétrées
```

## Contrôle opérationnel

```text
Admin reçoit un e-mail
→ l'objet indique l'environnement si le préfixe est configuré
→ le corps utilise la formule et la signature du groupe Branding e-mail
```

---

# Workflow support — debug recette après ERR_CONNECTION_CLOSED

## Objectif

Capturer un incident intermittent sans conclure trop vite à une régression applicative.

## Parcours de diagnostic

```text
Navigateur affiche ERR_CONNECTION_CLOSED
→ noter heure exacte, navigateur, réseau, URL, action
→ consulter public/error_log
→ consulter var/log/prod.log si alimenté
→ consulter ~/access-logs/recette.hodina.fr-ssl_log
→ vérifier si la requête apparaît avec HTTP 200 / 302 / 500 / 503
→ corréler avec EasyAdmin, auth, Djama ou checkout
```

## Commande mémo

```bash
cd ~/recette.hodina.fr && \
echo "=== DATE ===" && date && \
echo "=== GIT ===" && git log --oneline -1 && git status --short && \
echo "=== PHP WEB ===" && tail -n 120 public/error_log 2>/dev/null && \
echo "=== SYMFONY PROD ===" && tail -n 120 var/log/prod.log 2>/dev/null && \
echo "=== ACCESS LIVE ===" && tail -n 120 ~/access-logs/recette.hodina.fr-ssl_log 2>/dev/null
```

## Interprétation rapide

- access log absent au moment exact : requête peut ne pas atteindre le serveur ;
- access log `200` ou `302` : côté serveur, la requête a probablement abouti ;
- access log `500` : chercher exception PHP/Symfony ;
- `public/error_log` alimenté : erreur PHP web ou stderr ;
- `prod.log` vide : cohérent avec la configuration Monolog prod actuelle si elle écrit sur `php://stderr`.


---

# J5R-A — Workflow annulation client

## Condition

Le client peut annuler directement une commande uniquement si le statut est :

- `PENDING_VALIDATION` ;
- `CONFIRMED`.

À partir de `PREPARING`, l’annulation directe est refusée. Le client reçoit un message l’invitant à contacter Hodina rapidement.

## Flux

1. Client connecté ouvre `/mon-compte/commandes/{id}`.
2. Le contrôleur vérifie que la commande appartient au client connecté.
3. Si le statut est annulable, le formulaire d’annulation est affiché.
4. POST `/mon-compte/commandes/{id}/annuler` avec CSRF.
5. Le motif/commentaire est enregistré dans `CustomerOrderFeedback`.
6. `CustomerOrderWorkflowService::cancelByCustomer()` passe la commande en `CANCELED`.
7. Les notifications existantes du workflow restent centralisées.

## Anti-régression

Le portail client ne déclenche aucun statut livreur, ne touche pas aux collectes vendeur, ne recalcule pas la livraison, et ne modifie pas les snapshots de commande.

---

# J5S-A — Workflow admin points de remise

Workflow administrateur :

1. Créer ou vérifier un `DeliveryPoint`.
2. Rattacher le point à une `DeliveryCommune`.
3. Configurer ses plages horaires dans `DeliveryPointTimeWindow`.
4. Choisir le mode produit : `STANDARD`, `DELIVERY_POINT_REQUIRED` ou `DELIVERY_POINT_OPTIONAL`.
5. Associer le produit à un ou plusieurs points autorisés via `ProductDeliveryPoint`.

Dans J5S-A, le client ne voit pas encore ce workflow dans le panier. La validation côté panier/checkout est prévue pour J5S-B. Le mode `DELIVERY_POINT_OPTIONAL` devra laisser le choix entre adresse classique et point de remise, alors que `DELIVERY_POINT_REQUIRED` devra imposer un point.


## J5S-B — Workflow client avec point de remise

1. Le client ajoute un produit au panier.
2. Si le produit impose ou autorise un point de remise, le panier affiche le bloc correspondant.
3. Le client choisit, selon le mode produit : livraison standard ou point de remise.
4. En point de remise, il choisit un lieu, indique la date et l’heure réelle de rendez-vous, puis peut ajouter une instruction libre. Les plages du point restent une aide et une validation serveur.
5. Le checkout valide côté serveur que le point et la plage sont autorisés.
6. La commande snapshotte le point, la plage et l’instruction.
7. Admin, Djama, confirmation et portail client affichent les informations de remise.

La modification après commande est repoussée à un lot ultérieur.

## J5T-A — Checkout première commande simplifié

- Ajout d’un parcours checkout invité simplifié.
- Le client non connecté ne saisit plus de mot de passe avant validation.
- Un compte est créé automatiquement et l’e-mail de commande contient un lien sécurisé pour définir le mot de passe.
- Le checkout connecté reste inchangé.
- Aucun changement de schéma.


## J5T-A-bis — Workflow e-mail première commande

Lorsqu’un nouveau client valide une première commande depuis le checkout invité simplifié, Hodina crée la commande, crée ou rattache le client, prépare le lien de création du mot de passe si un token est disponible, puis envoie l’e-mail `ORDER_CREATED`. Le journal `EmailLog` conserve maintenant un corps texte permettant de contrôler le récapitulatif et le lien de création du mot de passe depuis le backoffice.


## J5S-B-bis — Rendez-vous client sur point de remise

Pour une commande utilisant un point de remise, le client choisit le point puis indique la date et l’heure réelle où il sera sur place. Les plages configurées dans l’admin restent des horaires proposés et servent à valider l’heure demandée. Le rendez-vous client est affiché dans la confirmation, le portail client, l’admin et Djama.


---

# J5U-A — Workflow expéditeur e-mails

Tous les e-mails de commande, statut, collecte vendeur et code réception utilisent l’expéditeur configuré dans EasyAdmin :

```text
Réglages → Branding e-mail → Adresse expéditeur des e-mails commande
```

Valeur pilote recommandée :

```text
commande@hodina.fr
```

Règles :

```text
1. Le service e-mail lit les réglages Branding e-mail.
2. From est construit avec email_sender_name + email_sender_email.
3. Reply-To est construit avec email_reply_to_name + email_reply_to_email.
4. ORDER_CREATED ajoute une copie cachée à email_order_created_copy_email.
5. EmailLog conserve l’expéditeur réellement utilisé.
6. Les modèles affichent une mention demandant de ne pas répondre directement à l’e-mail.
```

## J5V-A — Délai minimum de commande par produit

Un produit peut imposer un délai minimum de commande avant remise/livraison via `Product.minimumOrderLeadTimeHours`.

Au checkout avec point de remise, Hodina compare la date/heure de rendez-vous choisie par le client avec l’heure courante à Mayotte. Si le panier contient plusieurs produits, le délai le plus strict est appliqué.

Exemple : si un produit demande 48 h de préparation, le client doit choisir un rendez-vous au moins 48 h après la validation du panier.

La livraison standard n’est pas encore bloquée par cette règle tant que le client ne choisit pas une date/heure standard explicite.



# Mise à jour 27/06/2026 — Workflows J5T à J5W

## Checkout invité simplifié validé recette

1. Le client non connecté valide son panier.
2. Le formulaire lui demande seulement les informations nécessaires : prénom, nom, téléphone, e-mail, adresse ou point de remise selon les produits.
3. Aucun mot de passe n’est demandé avant commande.
4. À la validation, Hodina crée le compte client si nécessaire.
5. `ORDER_CREATED` est envoyé au client avec le récapitulatif et le lien sécurisé de création de mot de passe.
6. Le corps de l’e-mail est journalisé dans `EmailLog`.

## Point de remise avec rendez-vous client

1. Le client choisit un point autorisé.
2. Il saisit une date et une heure de rendez-vous.
3. Hodina affiche les horaires proposés du point comme aide.
4. Le serveur vérifie que l’heure demandée tombe dans une plage active.
5. Le serveur vérifie le délai minimum produit si `Product.minimumOrderLeadTimeHours` est renseigné.
6. La commande snapshotte le point, la plage indicative, la date/heure client et l’instruction.
7. Confirmation, admin, Djama et portail client affichent le rendez-vous.

## E-mails de commande avec expéditeur paramétrable

1. L’admin configure l’expéditeur dans `Réglages → Branding e-mail`.
2. Les services e-mails lisent `EmailBrandingService`.
3. Tous les e-mails de commande/statut/collecte/code réception partent avec l’expéditeur configuré.
4. `ORDER_CREATED` ajoute une copie cachée à `email_order_created_copy_email`.
5. Les templates indiquent de ne pas répondre directement à l’e-mail.
6. `EmailLog` conserve expéditeur et Reply-To utilisés.

## Workflows futurs J5W/J5X — non codés

### J5Y-A — Disponibilité produit par commune livrée

1. L’admin limite un produit à certaines `DeliveryCommune`.
2. Si aucune commune n’est sélectionnée, le produit reste disponible partout où Hodina livre.
3. Au checkout, Hodina bloque la commande si la commune client n’est pas autorisée pour un produit du panier.

### Planning par DeliveryArea

1. Le client choisit une commune.
2. Hodina récupère la future `DeliveryArea` de cette commune.
3. Hodina calcule le prochain jour de livraison standard selon les plannings actifs.
4. Si la commande est passée avant 10h00 la veille du créneau, le prochain créneau est disponible.
5. Sinon, Hodina propose le créneau suivant.

### Livraison express

1. Le client peut demander une livraison express hors créneau standard.
2. Le supplément est paramétrable.
3. Pendant le pilote, la demande reste à confirmer humainement par Hodina.

### Proposition d’heure livreur pour point de remise

1. Le client conserve son heure demandée initiale.
2. Si nécessaire, le livreur propose une heure différente depuis Djama.
3. La proposition est affichée à l’admin et au client sans écraser l’heure initiale.
## J5S-B-ter — Parcours panier point de remise vs adresse standard

### Point de remise imposé

1. Le panier détecte que le produit impose un point de remise.
2. Le client choisit un point Hodina parmi les points autorisés.
3. Le client renseigne date et heure de rendez-vous.
4. Le panier affiche `Point de remise choisi` au lieu de `Adresse de livraison utilisée`.
5. Les frais de livraison sont recalculés avec la commune du point.
6. La commande snapshotte le point et le rendez-vous.

### Produit standard + point de remise

1. Le client choisit `Livraison à mon adresse` ou `Point de remise`.
2. En mode standard, l’adresse client reste visible et utilisée pour les frais.
3. En mode standard, les cartes de points de remise, horaires, date, heure et instruction de remise sont masquées.
4. En mode point, l’adresse standard est masquée et le point choisi devient la source de calcul.
5. Le retour au mode standard réactive l’adresse client comme source de vérité.


## Affichage unité produit côté client

1. Le catalogue affiche l’unité de vente dans la carte produit.
2. La fiche produit affiche l’unité dans les métadonnées et près du prix.
3. Le panier affiche l’unité près du prix unitaire.
4. L’unité affichée vient de `Product.unit`. Si elle est absente ou inconnue, l’affichage retombe sur `À l’unité`.

## J5S-B-quater — Feedback global de validation checkout

### Informations manquantes simples

1. Le client choisit un point de remise ou un mode de livraison.
2. Tant que les champs obligatoires simples ne sont pas remplis, y compris prénom, nom, téléphone et e-mail, le bouton sticky `Valider` reste désactivé et grisé.
3. Avant tentative de validation, aucun message global rouge n’est affiché : le bouton est seulement grisé et le texte d’aide reste neutre.
4. Après appui sur `Valider`, un message global sous le header indique l’information manquante : prénom, nom, téléphone, e-mail, CGV, point, date, heure, adresse, commune ou facturation selon le cas.
5. Les erreurs de champ restent affichées au niveau du champ concerné après soumission serveur.

### Contraintes métier serveur

1. Si les champs sont remplis mais qu’une règle métier est violée, le bouton peut être cliquable.
2. Symfony bloque la validation au serveur.
3. Le panier revient avec un message global sous le header et l’erreur détaillée au niveau du champ.
4. Exemples : heure hors plage du point, délai minimum produit non respecté, point non autorisé pour le panier.

Cette UX ne remplace pas la validation serveur. Elle réduit seulement la confusion mobile avant validation.


## J5S-B-quater-bis — Workflow standard + point optionnel

Pour un produit qui autorise à la fois la livraison standard et le point de remise :

1. Le panier affiche deux choix : `Livraison à mon adresse` et `Point de remise`.
2. Si le client choisit `Livraison à mon adresse` :
   - les cartes de points de remise sont masquées ;
   - la date, l’heure et l’instruction de remise sont masquées ;
   - l’adresse client devient la source de vérité pour les frais et la commande ;
   - adresse et commune sont obligatoires si aucune adresse existante n’est sélectionnée.
3. Si le client choisit `Point de remise` :
   - l’adresse standard est masquée ;
   - le client choisit un point autorisé ;
   - il indique date et heure de rendez-vous ;
   - la commune du point sert au calcul des frais ;
   - l’heure est validée dans une plage active du point.
4. Le retour d’un mode à l’autre ne doit pas laisser un champ caché bloquer la validation du mauvais mode.

## J5S-B-quater-ter — Workflow feedback validation mobile

1. Le client arrive sur le panier : aucun message global rouge n’est affiché tant qu’il n’a pas tenté de valider.
2. Si des champs simples manquent, les boutons de validation sont visuellement grisés.
3. Au clic sur `Valider`, si une information simple manque, le formulaire n’est pas soumis et un message global apparaît sous le header.
4. Si les informations simples sont complètes, la soumission part au serveur.
5. Si une règle métier serveur bloque la commande, le panier revient avec le message global serveur et l’erreur champ correspondante.
6. Les messages doivent rester en français pour les cas maîtrisés par Hodina.

## J5T-C — Checkout invité avec e-mail existant

1. Le client non connecté remplit le checkout.
2. Il clique sur `Valider ma commande`.
3. Le serveur valide d’abord les règles métier du panier : adresse standard ou point de remise, date, heure, frais et CGV.
4. Si l’e-mail n’existe pas, Hodina crée le compte client automatiquement comme prévu par J5T-A.
5. Si l’e-mail existe déjà, aucune commande n’est créée immédiatement.
6. Le panier revient avec un popup : `Cette adresse e-mail est déjà connue de Hodina`.
7. Le client peut :
   - confirmer et valider la commande ;
   - ou modifier son e-mail.
8. Si le client confirme, la commande est créée et rattachée au compte existant.
9. L’e-mail `ORDER_CREATED` est envoyé avec la mention de rattachement à l’espace client Hodina.

Anti-régression : le popup ne doit pas faire perdre les choix du panier, du point de remise, de la date, de l’heure, de l’adresse standard ou des CGV.

## J5T-C — Reprise du workflow checkout invité avec compte existant

### E-mail nouveau

1. Le client invité remplit ses informations.
2. Le serveur ne trouve aucun `Customer` avec l’e-mail normalisé.
3. Hodina crée le compte client automatiquement.
4. La commande est créée et rattachée au nouveau compte.
5. `ORDER_CREATED` contient le lien sécurisé de création de mot de passe.

### E-mail existant

1. Le client invité remplit ses informations et clique sur `Valider`.
2. Le serveur valide d’abord les règles métier du panier : mode standard ou point de remise, adresse ou point, date/heure, frais, CGV.
3. Le serveur détecte ensuite un `Customer` existant avec l’e-mail normalisé.
4. Si `confirmExistingAccount` n’est pas confirmé pour ce même e-mail, le panier est réaffiché avec un popup de confirmation.
5. Aucune commande n’est créée avant cette confirmation.
6. Aucun nouveau `Customer` n’est créé.
7. Si le client clique sur `Modifier mon e-mail`, le popup se ferme et la commande n’est pas créée.
8. Si le client clique sur `Confirmer et valider ma commande`, le formulaire est soumis avec `confirmExistingAccount=1` et `confirmedExistingAccountEmail`.
9. Le serveur rattache la commande au `Customer` existant.
10. `ORDER_CREATED` mentionne que la commande a été rattachée à l’espace client Hodina.

Point technique : ne jamais appeler `setData()` sur le formulaire après `handleRequest()`. Le popup doit être piloté par des variables de rendu, pas par une mutation du formulaire soumis.

## Mise à jour 28/06/2026 — Workflow J5T-C validé recette

Le workflow invité avec e-mail existant est validé en recette : au premier clic, le serveur affiche le popup sans créer de commande ; après confirmation, la commande est créée et rattachée au `Customer` existant. Le parcours conserve les choix point de remise ou standard, les frais, les CGV et les informations client. L’e-mail `ORDER_CREATED` contient la mention de rattachement.

## Mise à jour 28/06/2026 — Workflow standard / point de remise validé recette

En mode point de remise, le client choisit point, date et heure ; l’adresse client ne bloque pas ; la commune du point sert aux frais ; `deliveryPointTimeWindowId` reste non obligatoire. En mode standard, le point est masqué ; l’adresse et la commune client restent obligatoires et servent aux frais.

## Mise à jour 28/06/2026 — Workflow J5V-A corrigé puis promu

Le délai minimum produit a été revalidé en local et recette après correction. Le serveur bloque désormais explicitement un rendez-vous point de remise trop proche en s’appuyant sur `Product.minimumOrderLeadTimeHours` et le délai le plus strict du panier. Correctif `3b508d0`, tag recette `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Mise à jour 28/06/2026 — Workflow J5V-A corrigé et revalidé recette

Parcours point de remise avec produit à délai minimum :

1. L’admin renseigne `minimumOrderLeadTimeHours` sur le produit, par exemple `48`.
2. Le client ajoute le produit au panier.
3. Le client choisit le mode point de remise, un point, une date et une heure.
4. Le checkout vérifie d’abord le point, la date, l’heure et la plage horaire active.
5. Le serveur appelle ensuite `DeliveryPointCartService::validateMinimumOrderLeadTime()`.
6. Si le rendez-vous est trop proche, la commande est refusée et le message global checkout indique la première date/heure possible.
7. Aucune commande n’est créée tant que le délai minimum n’est pas respecté.

Validation recette : produit à délai 48 h, rendez-vous trop proche refusé, panier conservé, message global affiché. Tag : `recette-j5v-a-checkout-lead-time-fix-20260628`.

## Mise à jour 29/06/2026 — Workflows validés production

Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Workflows validés :

1. Livraison standard : adresse/commune client obligatoires et source de vérité des frais.
2. Point de remise : point/date/heure obligatoires, adresse client non bloquante, frais basés sur la commune du point.
3. J5V-A : produit avec délai minimum, rendez-vous trop proche refusé par le serveur, rendez-vous valide accepté.
4. J5T-C : invité avec e-mail existant, popup au premier clic, aucune commande avant confirmation, rattachement après confirmation.
5. E-mail `ORDER_CREATED` : expéditeur Hodina paramétrable et mention de rattachement uniquement si la commande est liée à un compte existant.

Ces workflows deviennent les anti-régressions minimum avant toute reprise J5W.


## Mise à jour 29/06/2026 — Workflow J5W-A zones tarifaires locales

Statut J5W-A : validé recette sous `recette-j5w-a-local-pricing-zones-20260629`, puis validé production sous `prod-j5w-a-local-pricing-zones-20260629`.

Statut : **validé localement + recette + production**.

Parcours admin :

1. L’admin ouvre **Logistique > Zones tarifaires**.
2. Les zones visibles doivent inclure `GT_LOCAL`, `PT_LOCAL`, `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL`.
3. Aucun doublon `PETITE_TERRE_LOCAL` ne doit apparaître.
4. L’admin ouvre **Logistique > Communes livrées**.
5. Chaque commune conserve son territoire `GT` ou `PT`.
6. Chaque commune est rattachée à sa zone tarifaire locale.
7. Les frais affichés dans le panier continuent à être calculés automatiquement.

Parcours client non régressé :

- panier standard : le champ d’instructions point de remise ne doit pas apparaître perdu en bas de page ;
- livraison standard : frais basés sur l’adresse client ;
- point de remise : frais basés sur la commune du point ;
- trajet Grande-Terre ↔ Petite-Terre : barge conservée via les liaisons logistiques ;
- trajet intra Petite-Terre : pas de barge si les liaisons ne la justifient pas.

Anti-régression : J5W-A ne doit pas modifier la validation J5T-C e-mail existant, ni J5V-A délai minimum produit.


## Mise à jour 29/06/2026 — Workflow J5X-A tarifs par secteur

J5X-A modifie le montant de base affiché/calculé pour la livraison selon la commune client.

Parcours client inchangé :

1. Le client ajoute des produits au panier.
2. Il choisit ou saisit son adresse de livraison.
3. Hodina déduit la commune livrée.
4. `DeliveryLogisticsService` lit la zone tarifaire locale via `DeliveryCommune.localPricingZone`.
5. Le panier affiche les frais calculés : forfait local + éventuels coûts de liaison + éventuel supplément multi-vendeurs plafonné.
6. Le checkout recalcule côté serveur avant création de commande.

Ce que J5X-A ne change pas :

- pas de nouveau parcours client ;
- pas de calendrier de livraison affiché ;
- pas de nouvelle promesse de date ;
- pas de disponibilité produit par commune ;
- pas de changement des statuts commande ;
- pas de changement Djama.

Message UX recommandé en attendant J5X-B :

```text
Les frais de livraison sont calculés selon votre commune et confirmés avant validation.
```

## J5X-B — Parcours calendrier livraison

### Admin

1. Ouvrir EasyAdmin > Zones tarifaires.
2. Configurer le libellé public, les jours de livraison, l’heure limite et le nombre de jours avant passage.
3. Laisser `GT_LOCAL` comme fallback technique avec planning inactif.

### Client panier

1. Le client choisit ou modifie sa commune/adresse.
2. Le panier appelle `/panier/logistique/apercu` en AJAX.
3. La réponse met à jour les frais J5X-A et le calendrier J5X-B.
4. Le client voit les passages, le prochain passage possible et la limite de commande.
5. La date finale reste confirmée par Hodina après vérification vendeur.

## J5X-C — Parcours fiche produit avec promesse livraison

Parcours client standard :

1. Le client ouvre une fiche produit.
2. Si sa commune est connue, Hodina affiche uniquement le secteur concerné : jours de passage, prochain passage possible et cutoff.
3. Si sa commune n’est pas connue, Hodina affiche un message simple et un tableau repliable des secteurs.
4. Le client ajoute au panier ; les frais et le planning restent recalculés côté panier.

Parcours produit sur créneau :

1. L’admin configure le produit en promesse `Sur créneau / rendez-vous`.
2. Il renseigne les jours possibles, la plage indicative et le cutoff.
3. La fiche produit affiche une promesse adaptée : accueil aéroport, cérémonie, événement, fleurs fraîches.
4. Le client indique l’heure souhaitée dans le panier lorsque le flux le permet.
5. Hodina confirme ensuite le créneau final après vérification vendeur/terrain.

Formulation client : “prochain passage possible” ou “créneau à confirmer”, jamais “livraison garantie”.

## J5X-C-bis — Workflow admin Produit clarifié

Dans le formulaire produit, l’admin distingue désormais trois zones :

1. `Mode de remise au client` : détermine le parcours panier, adresse classique ou point Hodina.
2. `Fiche produit — message de livraison client` : configure uniquement le texte visible sur la fiche produit.
3. `Avancé — points de remise` : associe ou crée un vrai point de remise logistique.

Les plages indicatives d’un produit sur créneau ne sont pas des plages de point de remise. Elles servent à rassurer le client et restent confirmées humainement par Hodina. Pour modifier les plages d’un point existant, l’admin doit utiliser le menu `Plages points de remise`.

## J5X-D — Parcours catalogue client

Le client arrive sur `/catalogue` et peut :

1. rechercher un produit ou un vendeur ;
2. filtrer par catégorie ;
3. trier par mise en avant, nouveauté ou prix ;
4. ouvrir une fiche produit ;
5. ajouter rapidement au panier avec le formulaire AJAX existant.

Le formulaire fonctionne en GET sans JavaScript. Avec JavaScript, Hodina recharge uniquement le bloc résultats et met à jour l’URL, afin de garder une expérience mobile plus réactive.

Le catalogue ne promet pas de disponibilité par commune. Les frais et passages restent confirmés sur fiche produit et panier selon les lots J5X-B/C.

# Workflows 01/07/2026 — J5Y points de remise et entrée publique

## J5Y-A — Admin crée les plages d’un nouveau point de remise

1. L’admin ouvre EasyAdmin > Produit.
2. Dans `Avancé — points de remise`, il peut créer rapidement un nouveau point.
3. Le formulaire affiche une interface guidée : libellé, jours concernés, heure début, heure fin.
4. L’admin peut choisir `Tous les jours`, `Jours ouvrés`, `Jours ouvrables` ou un jour précis.
5. Le textarea technique reste masqué mais soumis au backend.
6. À l’enregistrement, Hodina crée le point et ses plages horaires selon le format historique attendu.

Règle : cette interface sert uniquement à créer les plages d’un nouveau point depuis un produit. Pour modifier un point existant, utiliser le menu `Plages points de remise`.

## J5Y-B — Client choisit un créneau de remise

1. Le client ajoute au panier un produit qui impose ou autorise un point de remise.
2. Il choisit le mode `Point de remise` si le produit le permet ou si ce mode est imposé.
3. Il sélectionne un point Hodina autorisé pour les produits du panier.
4. Il choisit une date de rendez-vous.
5. Le panier affiche les créneaux disponibles par demi-heure, calculés depuis les plages actives du point choisi.
6. Le client choisit un créneau visible, par exemple `11:30 – 12:00`.
7. Au checkout, le serveur revalide le point, la date, le créneau, la plage horaire et le délai minimum produit J5V-A.

Si aucun créneau n’est disponible, l’interface doit l’indiquer clairement et le serveur doit refuser la commande.

## J5Y-C — Parcours public après déplacement du catalogue

1. Le visiteur arrive sur `/` et voit directement le catalogue.
2. Il peut chercher, filtrer, trier et ajouter au panier.
3. S’il veut comprendre le projet, il clique sur `Découvrir Hodina`.
4. `/decouvrir-hodina` présente Hodina pour les clients, vendeurs et livreurs.
5. `/catalogue`, `/blog/decouvrir-hodina` et `/blog` restent des redirections pour éviter les liens cassés.

Anti-régression : le déplacement de route ne doit pas casser les formulaires GET du catalogue, l’AJAX, l’ajout panier, le panier standard ou le point de remise.

## J5Y-D — Validation visuelle header/favicon

1. Le logo header doit être lisible à côté des liens.
2. Le header ne doit pas devenir trop haut, surtout sur mobile.
3. Le favicon doit être testé en navigation privée, car les navigateurs le mettent fortement en cache.
4. Le favicon final ne doit pas imposer un carré blanc jugé disgracieux si une version transparente ou mieux recadrée est retenue.

## J5Y-E/F/G/H — Parcours public Découvrir, Carnet et Infos livraison

### Visiteur qui veut acheter

1. Il arrive sur `/`.
2. Il voit directement le catalogue.
3. Il peut rechercher, filtrer, trier et ajouter au panier.
4. S’il veut comprendre la livraison avant commande, il clique sur `Infos livraison` dans le header.
5. Il arrive sur `/carnet/livraison`.
6. Il consulte les zones, jours indicatifs, points de remise et rappels de prudence.
7. Il revient au catalogue ou au panier pour obtenir les frais, dates et créneaux exacts.

### Visiteur qui veut comprendre Hodina

1. Il accède au footer.
2. Il clique sur `Découvrir Hodina`.
3. Il arrive sur `/decouvrir-hodina`.
4. Il comprend la vision, le fonctionnement pilote, les publics concernés et la logique de proximité.

### Visiteur qui explore le Carnet

1. Il clique sur `Carnet Hodina` dans le footer.
2. Il arrive sur `/carnet`.
3. Il voit le rôle du Carnet : expliquer les produits, la livraison et les partenaires.
4. Il peut ouvrir `Livraison Hodina`.
5. Les entrées `Fruits, légumes et saisons` et `Nos vendeurs et producteurs partenaires` restent affichées comme contenus à venir et non cliquables.

### Redirections legacy

1. `/catalogue` redirige vers `/`.
2. `/blog` redirige vers `/decouvrir-hodina`.
3. `/blog/decouvrir-hodina` redirige vers `/decouvrir-hodina`.

### Règles de communication

- Ne pas exposer Djama sur les pages publiques.
- Ne pas parler de blog dans l’UX publique.
- Ne pas présenter les jours de livraison comme garantis.
- Toujours rappeler que le panier confirme les frais, dates et créneaux exacts.
- Garder le footer compact pour ne pas surcharger l’expérience mobile.

# Workflows 02/07/2026 — J5Z checkout/admin UX

## Formulaire Produit EasyAdmin

1. L’admin ouvre EasyAdmin > Produits > Ajouter ou Modifier.
2. Il renseigne les champs prix / marge.
3. Les champs opérationnels apparaissent immédiatement : stock illimité, stock, unité, description, précommande, jours fabrication, mode de remise, jours livraison.
4. Les champs plus éditoriaux ou avancés viennent ensuite.

Règle : l’ordre du formulaire doit aider l’exploitation quotidienne et ne doit pas cacher les champs qui conditionnent la vente réelle.

## Inscription / checkout invité avec indicatif

1. Le client arrive sur inscription ou checkout invité.
2. Le champ `Indicatif` apparaît avant `Téléphone`.
3. Mayotte / La Réunion `+262` est proposé en premier.
4. Le client saisit son numéro local.
5. Le serveur assemble l’indicatif et le numéro via `PhoneNumberNormalizer`.
6. Si le client colle déjà un numéro international, Hodina le nettoie mais ne le réinterprète pas depuis l’indicatif choisi.

Règle : ne pas deviner le pays depuis le numéro dans les nouveaux formulaires.

## Panier client connecté

1. Le client connecté ajoute un produit.
2. Il ouvre son panier.
3. Les champs client déjà connus sont cachés ou préremplis.
4. Le champ technique `phoneCountryCode` ne doit pas apparaître en bas du formulaire.
5. Si les frais comportent barge ou communes traversées, l’annotation est visible dès le premier affichage.
6. Si les frais sont standards, aucune annotation n’est affichée.

## Changement d’adresse et flash frais recalculés

1. Le client change d’adresse de livraison.
2. Hodina recalcule la logistique en AJAX.
3. Si les frais changent, un flash apparaît en haut du panier :

```text
Frais de livraison mis à jour
Selon ta nouvelle adresse, Hodina a recalculé les frais. Le détail apparaît sous “Frais de livraison”.
```

4. Le client peut fermer le message avec la croix.
5. L’annotation sous frais reste visible immédiatement, sans attendre un refresh.
6. Après refresh, l’annotation reste cohérente.

## Annotation frais livraison

1. Le panier reçoit ou calcule le preview logistique.
2. `DeliveryFeeReasonFormatter` produit une annotation si nécessaire.
3. Le panier affiche l’annotation sous `Frais de livraison`.
4. La confirmation commande, le détail client, l’email et le récapitulatif SMS reprennent l’information quand elle existe.

Règles :

```text
Trajet simple / frais standard → pas d’annotation.
Barge ou commune(s) traversée(s) → annotation affichée.
```

## Date de rendez-vous mobile

1. En mode point de remise, le client choisit une date de rendez-vous.
2. Le champ date doit rester aligné dans sa carte sur mobile, en client invité comme connecté.
3. Le correctif CSS protège Safari/iPhone contre les débordements natifs de `input[type=date]`.

# Workflow prévu — J5AA Localité d’adresse

> ⚠️ SECTION SUPERSÉDÉE — Réalisé et validé recette + production le 2026-07-04. Voir « Workflow J5AA-A — Localité d’adresse » plus bas.

État initial : prévu (avant codage). Désormais livré.

## Localité connue

1. Le client saisit dans le champ `Localité`.
2. Hodina propose des localités connues : `Kawéni — Mamoudzou`, `Kavani — Mamoudzou`, etc.
3. Le client sélectionne une proposition.
4. Hodina remplit automatiquement la commune associée.
5. Le client voit la commune et peut la corriger si nécessaire.
6. La commune reste utilisée pour les frais, la barge, les jours et les créneaux.

## Localité libre non reconnue

1. Le client tape une localité qui n’est pas dans la liste Hodina.
2. Hodina conserve ce texte comme précision terrain.
3. Hodina ne déduit pas automatiquement la commune.
4. Le client choisit manuellement la commune.
5. Les frais restent calculés depuis la commune.

Règle : la localité aide le client et le livreur, mais ne doit pas devenir une source tarifaire implicite.

# Workflows 03/07/2026 — J5AB / J5AC

## J5AB — Parcours catalogue mobile achat-first

Parcours client cible :

1. Le client arrive sur `/`.
2. Il voit le header Hodina.
3. Il voit immédiatement la barre `Rechercher un produit, un vendeur…`, la loupe et le bouton `Filtres` sur une même ligne.
4. Il peut rechercher sans ouvrir les filtres.
5. Il peut ouvrir `Filtres` pour choisir catégorie et tri.
6. Il voit rapidement le compteur `X produits trouvés`.
7. Il voit les cartes produits et peut ajouter au panier.

Fallback :

- sans JavaScript, le formulaire reste en GET ;
- la loupe reste un vrai bouton `submit`.

Hors parcours : J5AB ne modifie pas la livraison, le panier, le checkout ni Djama.

## J5AC — Parcours compte client

### Accueil compte

1. Le client connecté ouvre `/mon-compte`.
2. Il voit un tableau de bord : commandes, profil, sécurité.
3. Il peut aller vers `Commandes`, `Profil` ou `Sécurité`.

Si le client n’est pas connecté, `^/mon-compte` reste protégé par `ROLE_USER` et le flux de connexion Symfony s’applique.

### Suivi commandes

1. Le client ouvre `/mon-compte/commandes`.
2. Les commandes en cours et passées sont séparées.
3. Il ouvre une commande via `/mon-compte/commandes/{id}`.
4. Le contrôleur recherche par `id` et par `customer` courant.
5. Les commandes `DRAFT` restent exclues.
6. Le code de réception n’est jamais affiché en clair.

### Profil

1. Le client ouvre `/mon-compte/profil`.
2. Il modifie prénom, nom, email, indicatif et téléphone.
3. Le téléphone est normalisé via `PhoneNumberNormalizer`.
4. L’email est normalisé et vérifié contre les doublons.
5. La contrainte DB `UNIQ_CUSTOMER_EMAIL` protège l’unicité finale.

### Mot de passe connecté

1. Le client ouvre `/mon-compte/mot-de-passe`.
2. Il saisit son ancien mot de passe.
3. Il saisit le nouveau mot de passe et sa confirmation.
4. Le backend vérifie l’ancien mot de passe avec `isPasswordValid()`.
5. Le nouveau mot de passe est hashé avec `hashPassword()`.
6. Le client reste connecté.

### Lien de réinitialisation connecté

1. Depuis `/mon-compte/mot-de-passe`, le client demande un lien de réinitialisation.
2. `CustomerPasswordResetLinkService` génère token, expiration et URL.
3. Un `SmsLog` est créé pour le canal pilote manuel.
4. Aucun nouveau canal d’envoi automatique n’est introduit.

### AJAX progressif J5AC-B

Les liens internes `/mon-compte/*` et formulaires du portail sont interceptés par `templates/client/_account_ajax.html.twig`.

Avec JavaScript :

- remplacement du bloc `data-client-account-page` ;
- URL mise à jour avec `history.pushState` ;
- bouton retour navigateur géré ;
- feedback discret `.is-ajax-pending`.

Sans JavaScript :

- toutes les routes et formulaires fonctionnent en navigation classique.

# Workflows 04/07/2026 — état réel portail client et cadrage J5AA

## Portail client après J5AC

1. Le client connecté ouvre `/mon-compte`.
2. Hodina affiche un hub compte avec résumé des commandes et accès aux actions utiles.
3. Le client peut accéder à ses commandes, son profil, son mot de passe ou demander un lien de réinitialisation.
4. Les adresses ne disposent pas encore d’une page autonome `/mon-compte/adresses`.

Règle : les adresses restent gérées dans le panier/checkout tant qu’un lot dédié n’a pas cadré le carnet autonome.

## Workflow prévu J5AA — code postal, commune, localité

> ⚠️ SECTION SUPERSÉDÉE — Réalisé via J5AA-B (couple code postal + commune sécurisé au checkout) et J5AA-A (localité), validé recette + production le 2026-07-04. Voir « Workflow J5AA-B — Checkout livraison code postal + commune » plus bas.

État initial : prévu (avant codage). Désormais livré.

1. Le client choisit un code postal connu Hodina ou une commune livrée active.
2. Si le code postal est non ambigu, Hodina peut préremplir la commune compatible.
3. Si le code postal correspond à plusieurs communes, Hodina limite la liste aux communes compatibles.
4. Le client renseigne la localité : `Localité` avec l’aide `Village / quartier / lieu-dit`.
5. S’il sélectionne une localité connue, Hodina peut préremplir la commune associée.
6. S’il tape une localité libre, Hodina conserve le texte mais ne déduit pas la commune.
7. À la validation, le serveur vérifie le couple code postal / commune.
8. Les frais restent calculés depuis la commune, jamais depuis la localité ou le code postal.

Anti-régression : le comportement J5Z du panier, des frais expliqués, du flash recalculé et de l’indicatif téléphone ne doit pas être modifié par J5AA.
# Workflow J5AA-0 — Audit avant localité

## But

Avant d'ajouter la notion de localité ou d'améliorer la sélection code postal / commune, Hodina vérifie que les adresses de livraison existantes reposent sur une commune exploitable.

Commande locale :

```bash
php tools/assert-j5aa-delivery-address-commune-audit.php
```

## Adresse de livraison

Pour chaque adresse `DELIVERY`, l'audit contrôle :

1. `commune` non vide ;
2. `postalCode` non vide et au format 5 chiffres ;
3. `deliveryZone` présente, active et différente de `AUTRE` ;
4. correspondance exacte avec une `DeliveryCommune` active/logistique ;
5. cohérence entre `Address.postalCode` et `DeliveryCommune.postalCode` ;
6. cohérence entre `Address.deliveryZone.code` et `DeliveryCommune.territory`.

Une commune seulement résoluble par matching souple n'est pas acceptée comme OK dans l'audit. Elle doit être corrigée ou assumée comme anomalie avant les lots J5AA suivants.

## Adresse de facturation

Les adresses `BILLING` en zone `AUTRE` sont listées à titre informatif et ignorées volontairement par l'audit. Elles ne doivent pas bloquer J5AA-0, car une adresse de facturation peut être hors zone Hodina.

## Effet utilisateur

Aucun. J5AA-0 ne modifie pas le panier, le checkout, l'inscription, l'espace client, l'admin ni Djama.

# Workflow J5AA-B — Checkout livraison code postal + commune

## Parcours client livraison standard

Dans le checkout standard, le client choisit d'abord un code postal connu par Hodina, puis une commune compatible.

- Si le code postal correspond à une seule commune active/logistique, la commune peut être préremplie automatiquement.
- Si le code postal correspond à plusieurs communes, seules les communes compatibles restent proposées.
- Si le client change le code postal, une commune devenue incompatible est réinitialisée.
- Si le client change la commune, le code postal est resynchronisé depuis la `DeliveryCommune` choisie.

Le serveur valide à nouveau le couple code postal + commune au moment de la validation de commande.

## Frais et planning

Les frais, la barge, les jours et les créneaux ne sont pas calculés depuis le code postal. Ils restent calculés depuis la commune de livraison résolue dans le référentiel `DeliveryCommune`, puis stockée dans `Address.commune`.

## Facturation

Le workflow de facturation n'est pas durci dans J5AA-B. Une adresse de facturation peut rester hors zone Hodina et ne doit pas être bloquée par le référentiel des communes livrables lorsque la zone métier le permet.

# Workflow J5AA-A — Localité d’adresse

## Client checkout

Le client peut renseigner une localité optionnelle dans le formulaire de livraison.

Libellé principal : `Localité`.
Aide : `Village / quartier / lieu-dit`.

Si la localité existe dans Hodina, le formulaire peut aider à préremplir le code postal et la commune. Si elle est libre, le texte est conservé comme précision terrain.

La commande reste validée sur la commune livrée : la localité n’est jamais obligatoire et ne calcule jamais les frais.

## Admin

L’admin peut maintenir les localités dans le back-office et désactiver une localité sans casser les anciennes adresses.

## Livreur / Djama

Les résumés d’adresse peuvent afficher la localité snapshotée ou enregistrée afin d’aider le livreur à retrouver l’adresse. La tournée et les frais restent basés sur la commune de livraison.
