# HISTORIQUE — Hodina, de J1 à J5Y-D

> Dernière rédaction : 01/07/2026
> Version enrichie : J5X recette, J5Y local, homepage catalogue, points de remise UX et identité header
> Archive analysée : `docs_2026-06-29_10-32-57.zip`
> Objectif : donner à un développeur débutant une vision claire de ce qui existe déjà, de pourquoi cela existe, de ce qu’il ne faut pas refaire, et du point exact de reprise.

---

## 0. À quoi sert ce fichier

Ce fichier est le fil chronologique du développement Hodina.

Il ne remplace pas les fichiers spécialisés comme `TODO.md`, `ROADMAP.md`, `DECISIONS.md`, `ARCHITECTURE.md`, `WORKFLOWS.md` ou les `README_MAJ_*`. Il sert plutôt de porte d’entrée.

Un développeur qui reprend le projet doit lire ce fichier en premier pour comprendre :

- la vision métier du projet ;
- l’ordre des jalons J1 → J5X-A ;
- les décisions déjà prises ;
- les fonctionnalités déjà construites ;
- les incidents déjà rencontrés ;
- les pièges à éviter ;
- le point exact où le développement s’est arrêté ou vient d’être validé.

Règle importante : **ne pas recréer ce qui existe déjà**. Quand une fonctionnalité est déjà là, on la corrige, on la teste, on l’étend ou on la documente.

---

## 1. Vision générale du projet

Hodina est une marketplace locale dédiée à Mayotte.

L’objectif n’est pas seulement d’afficher un catalogue. Hodina doit structurer un circuit local complet :

```text
vendeurs / producteurs / revendeurs locaux
→ produits visibles dans un catalogue
→ panier client
→ validation humaine par l’admin
→ préparation
→ collecte chez les vendeurs
→ livraison au client
→ suivi administratif et terrain
```

Le pilote reste volontairement simple :

- paiement manuel ;
- validation admin obligatoire ;
- disponibilité confirmée humainement ;
- pas de paiement en ligne au départ ;
- pas de SMS provider au départ ;
- SMS manuel via iPhone ;
- couverture logistique administrable ;
- interfaces mobile-first pour les usages terrain à Mayotte.

La logique est de tester le marché sans ajouter trop tôt de complexité technique.


### Vision enrichie issue des échanges projet

Hodina ne doit pas être lu comme un simple site e-commerce. Le projet est né d’un besoin terrain : rendre plus simple, plus visible et plus fiable l’accès aux produits locaux à Mayotte, tout en diminuant la fatigue opérationnelle des personnes qui organisent déjà la vente, la préparation, les appels, les disponibilités et les livraisons.

Le projet hérite de l’expérience **Hodidagoni**, l’activité portée auparavant par le frère de Chahere. Hodidagoni a prouvé qu’un marché existe, mais son fonctionnement reposait fortement sur l’énergie humaine, l’organisation manuelle et la confiance directe. Hodina reprend cette preuve terrain et cherche à la transformer en outil plus robuste : un système capable d’aider l’exploitation sans déshumaniser la relation locale.

La vision produit peut se résumer ainsi :

```text
Hodina = marketplace locale + outil d’exploitation + support logistique terrain + média de confiance.
```

Le cœur de la valeur n’est donc pas uniquement le catalogue. La valeur vient de l’enchaînement complet :

```text
rendre les vendeurs visibles
→ simplifier la prise de commande
→ sécuriser la validation humaine
→ organiser la collecte chez les vendeurs
→ guider le livreur sur le terrain
→ donner au client une expérience simple et rassurante
→ garder une trace administrative exploitable
```

### Problème terrain que Hodina cherche à résoudre

Les échanges projet ont fait ressortir plusieurs problèmes récurrents à Mayotte :

- on ne sait plus toujours où trouver certains produits locaux ;
- les vendeurs sont parfois connus oralement, mais peu visibles en ligne ;
- certains produits changent vite de disponibilité ;
- les adresses ne sont pas toujours suffisamment fiables pour une livraison fluide ;
- la différence Petite-Terre / Grande-Terre crée une vraie contrainte logistique ;
- la barge, les communes, les distances et les collectes multi-vendeurs rendent le coût de livraison difficile à expliquer ;
- l’administrateur peut vite être épuisé si tout repose sur des appels, des messages et des suivis manuels ;
- un client veut surtout savoir : quoi acheter, combien cela coûte, quand il sera livré, et qui le livre.

C’est pour cela que beaucoup de développements ont porté sur la logistique, les statuts, les snapshots, les adresses, le GPS et le portail livreur. Ce ne sont pas des détails techniques : ce sont des réponses à la réalité du terrain.

### Positionnement du pilote

Le pilote Hodina doit rester contrôlé. Il ne cherche pas encore à tout automatiser.

Décision structurante : **ne pas automatiser trop tôt ce qui n’est pas encore stabilisé humainement**.

Cela explique les choix suivants :

```text
paiement manuel
validation admin
SMS manuel via iPhone
confirmation humaine des disponibilités
livreur qui prend lui-même une commande prête
préouverture paramétrable
mode testeur
recette protégée
production uniquement après validation par tag
```

Cette approche évite de construire trop tôt un système lourd, coûteux ou rigide. Le rôle du code est d’abord de réduire la fatigue et les erreurs, pas de remplacer le bon sens opérationnel.

### Vision économique de départ

Les échanges initiaux mentionnent une logique de marketplace avec marge :

```text
vendeur → fixe ou communique son prix producteur
Hodina → ajoute une marge produit et des frais de livraison
client → paie un prix lisible
livreur → est rémunéré à la course ou selon une règle définie
```

Les hypothèses évoquées au début du projet incluaient notamment une commission / marge autour de 10 % sur certains cas simples, puis une structuration plus fine via :

```text
marge globale Hodina
marge spécifique vendeur
marge spécifique produit
frais livraison PT / GT
barge éventuelle
plafond livraison
supplément multi-vendeur
```

Le code actuel reflète cette évolution : la marge produit est centralisée dans `ProductPricingService`, et la livraison dans `DeliveryLogisticsService`.

### Vision de gouvernance et de méthode

Une partie des échanges a aussi porté sur les principes de gouvernance inspirés de *Principles* de Ray Dalio. L’idée importante pour le développement est la suivante : Hodina doit garder une mémoire claire des décisions, des raisons et des erreurs corrigées.

Cela justifie l’existence des fichiers :

```text
TODO.md
ROADMAP.md
DECISIONS.md
ARCHITECTURE.md
WORKFLOWS.md
PILOT_STATUS_DETAILED.md
README_MAJ_*.md
COMMIT_*.md
HISTORIQUE.md
```

Règle de gouvernance documentaire :

```text
Quand une décision métier ou technique est prise, elle doit être documentée.
Quand une erreur est corrigée, elle doit rester visible.
Quand une solution est rejetée, la raison doit être conservée.
```

C’est particulièrement important parce que le projet avance par petits jalons. Sans historique, un développeur débutant risque de recréer une fonctionnalité déjà faite ou de casser une règle métier stabilisée.

### Vision long terme

À maturité, Hodina peut devenir plusieurs choses à la fois :

```text
1. une marketplace locale pour Mayotte ;
2. un outil de gestion de commandes pour l’équipe Hodina ;
3. un portail vendeur pour structurer l’offre locale ;
4. un portail livreur pour fiabiliser les tournées ;
5. un média local qui raconte les produits, les vendeurs et l’île ;
6. une base future pour paiement en ligne, statistiques, optimisation de tournées et extension géographique.
```

Mais au point J5M-C3-ter, il faut rester lucide : le projet n’est pas encore une plateforme automatisée complète. C’est un MVP avancé, orienté terrain, proche d’une ouverture contrôlée.

La priorité n’est donc pas d’ajouter de grandes fonctionnalités abstraites. La priorité est :

```text
stabiliser → tester en recette → documenter → ouvrir petit → apprendre → automatiser progressivement.
```

---

## 2. Stack et conventions actuelles

### Stack principale

```text
Symfony / Twig / Doctrine ORM / MariaDB / EasyAdmin / PWA mobile-first
```

### Environnements connus

```text
Local Windows : E:\hodina\hodina.fr
Recette       : https://recette.hodina.fr
Production    : https://hodina.fr
Backoffice    : /ouegnewe
Portail livreur : /djama
Ancienne route avant J5M-C4 : /livreur
```

### Règles de développement

- Le backoffice admin reste EasyAdmin.
- Le portail livreur n’est pas EasyAdmin : c’est un dashboard dédié et mobile-first.
- Les règles métier complexes doivent être dans des services, pas dans Twig.
- Les changements de statut commande doivent passer par `CustomerOrderWorkflowService`.
- Les calculs de livraison doivent rester dans `DeliveryLogisticsService`.
- Les règles de préouverture / maintenance doivent rester dans `SalesOpeningService`.
- Les règles de synchronisation vendeur / point de retrait doivent rester dans `SellerPickupLogisticsSynchronizer`.
- Les fichiers runtime, secrets, assets compilés et uploads réels ne doivent pas être versionnés.

---

## 3. Lexique métier minimal

### Client

Un client est une personne qui s’inscrit, ajoute des produits au panier, choisit une adresse, puis soumet une commande.

Dans le code, l’entité `Customer` sert aussi d’utilisateur authentifiable.

### Vendeur

Un vendeur est un producteur, artisan, revendeur ou structure locale qui fournit des produits.

Depuis J5M-C3-ter, la règle est stabilisée : **un vendeur est aussi un client Hodina**. Il peut donc être rattaché à un compte `Customer` via `Seller.customerAccount`.

### Livreur

Un livreur est un `Customer` ayant le rôle `ROLE_COURIER`.

Une commande peut lui être assignée via `CustomerOrder.assignedCourier`.

### Admin

L’admin gère Hodina depuis `/ouegnewe` avec EasyAdmin.

Il valide les commandes, suit les statuts, consulte les fiches terrain, configure les réglages et prépare les opérations.

### Commune livrée

La commune livrée est la commune du client pour la livraison.

Elle sert à déterminer la zone, les frais, la barge éventuelle et les trajets.

### Commune logistique vendeur

La commune logistique vendeur est `Seller.deliveryCommune`.

C’est la source de vérité pour les calculs logistiques : trajet, coût, barge, BFS et snapshot.

### Point de retrait vendeur

Le point de retrait vendeur est `Seller.pickupAddress`.

Il sert au livreur pour trouver où récupérer les produits. Il ne doit pas remplacer `Seller.deliveryCommune` dans les calculs.

### GPS

Le GPS aide le livreur à trouver un client ou un vendeur. Il ne remplace ni la commune livrée, ni la commune logistique, ni la zone tarifaire.

---

## 4. Synthèse de l’état actuel au point J5M-C3-ter

Au moment de l’archive fournie, le projet contient notamment :

```text
src/
templates/
config/
migrations/
assets/
public/
docs/
tools/
```

Les entités principales présentes sont :

```text
Address
Category
Customer
CustomerOrder
CustomerSignup
DeliveryCommune
DeliveryCommuneConnection
DeliveryPricingZone
DeliveryZone
EmailLog
HodinaSetting
LaunchSubscriber
OrderItem
Product
ProductImage
Seller
SmsLog
```

Les services structurants présents sont :

```text
CartService
CustomerOrderWorkflowService
CustomerPilotCascadeDeleter
DeliveryCommuneMatcherService
DeliveryLogisticsService
ImageResizer
OrderEmailService
OrderReferenceGenerator
ProductPricingService
SalesOpeningService
SellerPickupLogisticsSynchronizer
SmsService / LogSmsSender / OrderSmsMessageBuilder
```

Le dernier état fonctionnel documenté est :

```text
J5M-C3-ter : validé localement
Recette    : à faire
Production : non déployé
```

Les dernières migrations du jalon J5M sont :

```text
Version20260621143500 — Seller.customerAccount + Seller.pickupAddress
Version20260621145500 — correctif index/FK vendeur vers customer/address
Version20260621215500 — Seller.businessName
```

---


## 4.1 — Comment comprendre Hodina en 10 minutes

Cette section sert à donner une lecture simple à un développeur qui arrive sans connaître l’histoire.

### Les cinq surfaces produit

Hodina est composé de cinq surfaces principales :

```text
1. Public
   Catalogue, fiche produit, panier, inscription, connexion, confirmation.

2. Client
   Choix des adresses, facturation, GPS, historique futur, portail client futur.

3. Admin
   EasyAdmin sur /ouegnewe : produits, vendeurs, commandes, réglages, zones, logs.

4. Livreur
   Dashboard mobile /djama : commandes prêtes, prises en charge, livraison, GPS, appels, SMS.

5. Exploitation
   Outils, migrations, scripts de déploiement, docs, logs, recette, production.
```

Un bug peut donc toucher plusieurs surfaces. Exemple : modifier une adresse peut impacter le panier, la commande, la fiche admin, le livreur et le calcul logistique.

### Les trois sources de vérité à respecter

Le projet a plusieurs données proches mais volontairement séparées.

```text
CustomerOrder.deliveryAddress snapshot
→ ce que le client a choisi au moment de la commande.

Seller.deliveryCommune
→ source de vérité pour les calculs logistiques vendeur.

Seller.pickupAddress
→ adresse terrain pour guider le livreur chez le vendeur.
```

Ces trois notions ne doivent pas être fusionnées.

### Parcours métier complet

Le parcours cible actuel est :

```text
Client consulte le catalogue
→ ajoute au panier
→ choisit / crée une adresse de livraison
→ vérifie la facturation
→ valide la commande
→ Hodina snapshot l’adresse et la logistique
→ Admin vérifie la disponibilité
→ Admin valide la commande
→ Admin passe en préparation
→ Admin marque prête
→ Livreur prend en charge
→ Livreur collecte chez les vendeurs
→ Livreur démarre la livraison
→ Livreur marque livrée
→ Admin garde une trace de bout en bout
```

### Pourquoi les snapshots existent

Les snapshots empêchent qu’une commande passée change après coup si un client modifie son adresse ou si un vendeur modifie son point de retrait.

Règle : une commande doit garder la vérité du moment où elle a été validée.

### Pourquoi le GPS ne remplace pas les communes

Le GPS aide à trouver une personne ou un lieu précis. Il ne suffit pas à déterminer les règles métier : zone, barge, commune, frais ou trajet.

C’est pourquoi la commune reste une donnée structurée, choisie depuis `DeliveryCommune`, et le GPS reste une aide terrain.

### Pourquoi le paiement en ligne n’est pas prioritaire

Le paiement en ligne sera utile plus tard. Mais dans le pilote, le risque principal n’est pas le paiement : c’est la capacité à confirmer la disponibilité, collecter les produits, livrer correctement et garder la confiance client.

Le paiement manuel permet d’ouvrir plus vite avec moins de dépendances externes.

### Pourquoi le SMS reste manuel

Le SMS manuel via iPhone est volontaire. Il permet de tester les messages, les statuts et les usages sans payer ni intégrer trop tôt un provider SMS.

La présence de `SmsLog` prépare la future automatisation.

### Pourquoi le portail livreur est séparé d’EasyAdmin

EasyAdmin est adapté à l’administration. Il n’est pas adapté à un livreur sur téléphone, en déplacement, avec peu de temps.

Le portail `/djama` est donc une interface terrain : compacte, actionnable, limitée à ce qui sert à livrer.

---

## 5. Frise chronologique complète

## J1 — Socle technique et backoffice initial

### Objectif

Créer une base Symfony exploitable avec une base de données, des entités, un backoffice et une sécurité minimale.

### Acquis

- Projet Symfony opérationnel en local Windows.
- MariaDB / MySQL retenu pour compatibilité avec o2switch.
- Doctrine ORM et migrations installés.
- EasyAdmin installé.
- Backoffice admin mis en place sur `/ouegnewe`.
- Sécurité admin configurée.
- Premières entités métier créées.
- Commande de test CI locale créée pour valider le socle J1/J2.

### Entités socle

```text
DeliveryZone
Seller
Category
Product
Customer
Address
CustomerOrder
OrderItem
SmsLog
```

### Décisions importantes

- MariaDB est retenue pour éviter les surprises sur l’hébergement o2switch.
- EasyAdmin est retenu pour aller vite côté administration.
- Le pilote se construira par jalons courts, testables, documentés.

---

## J2 — Catalogue public et fiche produit

### Objectif

Permettre à un client de voir les produits et de commencer à se projeter dans un parcours d’achat.

### Acquis

- Page catalogue publique.
- Fiche produit par slug.
- Images produit.
- Ajout au panier depuis le catalogue.
- Ajout au panier depuis la fiche produit.
- UX mobile améliorée.
- Header mobile avec accès connexion / panier.

### Décisions importantes

- Le catalogue doit rester simple pour le pilote.
- Les filtres, la recherche avancée et l’optimisation commerciale fine sont repoussés.
- Le produit reste lié au vendeur et à la catégorie dès le départ.

---

## J3 — Panier et checkout socle

### Objectif

Passer d’un simple catalogue à une vraie commande enregistrée.

### Acquis

- `CartService`.
- `CartController`.
- Page `/panier`.
- Quantités modifiables.
- Boutons retirer / vider panier.
- Checkout.
- Création de commande `CustomerOrder`.
- Création des lignes `OrderItem`.
- Statut initial `PENDING_VALIDATION`.
- Panier vidé après validation.
- Page confirmation commande.
- Adresses client.
- Adresse de facturation séparée progressivement introduite.

### Décision majeure

Le paiement en ligne n’est pas intégré pendant le pilote. Une commande est soumise, puis validée humainement.

Workflow client socle :

```text
Client connecté
→ Panier
→ Adresse
→ Création CustomerOrder
→ Création OrderItem
→ SmsLog initial
→ PENDING_VALIDATION
```

---

## J4 — Backoffice commandes opérationnel

### Objectif

Transformer EasyAdmin en outil réel de traitement des commandes.

Avant J4, une commande existait surtout en base. Après J4, elle devient un dossier opérationnel.

### Acquis

- Commandes visibles dans EasyAdmin.
- Lignes de commande visibles.
- CRUD `OrderItem` ajouté.
- Numéro métier de commande.
- Dates métier.
- Actions admin.
- SmsLog automatiques.
- Fiche terrain commande.
- Réglages Hodina.

### Workflow admin validé

```text
PENDING_VALIDATION
→ CONFIRMED
→ PREPARING
→ READY_FOR_PICKUP
→ DELIVERED
```

Annulation :

```text
PENDING_VALIDATION → CANCELED
CONFIRMED → CANCELED
```

### Dates métier

```text
confirmedAt
preparingAt
readyAt
deliveredAt
canceledAt
```

### Numéro métier commande

Format retenu :

```text
préfixe + AAAAMMJJ + numéro du jour
```

Exemple :

```text
hodina202606041
```

Le préfixe est paramétrable via `HodinaSetting`.

### SmsLog

Les SmsLog deviennent des traces système en lecture seule.

Ils sont générés lors des changements de statut importants.

Les SMS sont envoyés manuellement via iPhone avec un lien de type :

```text
sms:{numero_client}&body={message}
```

### Réglages Hodina

Passage à une logique générique :

```text
1 ligne = 1 paramètre
```

Premiers réglages importants :

```text
order_reference_prefix
delivered_communes
```

### Décision structurante de fin J4

Pour la livraison, le modèle B est choisi :

```text
Le livreur voit les commandes prêtes et les prend lui-même.
```

Cela prépare J5.

---

## J5A — SMS pilote, inscription, reset password, légal et préproduction

### Objectif

Stabiliser le pilote avant d’aller plus loin sur le portail livreur.

### Acquis fonctionnels

- SMS manuel admin enrichi.
- `SmsLog` amélioré.
- Bouton `Envoyer le SMS`.
- Page intermédiaire SMS.
- Fusion changement de statut + SMS.
- Inscription client améliorée.
- Redirection après inscription vers le catalogue.
- Nom client obligatoire.
- Correction checkout nouvel utilisateur.
- Suppression cascade pilote des comptes de test.
- Reset password par lien logué dans SmsLog.
- CGU / CGV publiques.
- Acceptation CGU/CGV au checkout.
- Retrait du lien `Admin` du footer public.

### Préproduction

Création de la recette :

```text
https://recette.hodina.fr
```

Caractéristiques :

- hébergement o2switch ;
- base recette séparée ;
- Basic Auth ;
- HTTPS / AutoSSL ;
- import DB depuis dev ;
- attention à l’encodage des dumps SQL PowerShell.

### Décisions importantes

- La préprod doit être protégée.
- Les secrets ne doivent pas être committés.
- Les CGU/CGV doivent refléter le paiement manuel, la validation humaine et le pilote.

---

## J5B — Refactoring `CustomerOrderWorkflowService`

### Objectif

Ne pas dupliquer la logique de changement de statut entre admin et livreur.

### Branche connue

```text
pilot/j5b-workflow-service
```

### Commit principal connu

```text
3692490 refactor(order): extract workflow service for admin transitions
```

### Acquis

Création du service :

```text
src/Service/CustomerOrderWorkflowService.php
```

Méthodes admin livrées :

```text
confirm()
cancel()
markPreparing()
markReady()
markDeliveredByAdmin()
```

Méthodes de contrôle livrées :

```text
canConfirm()
canCancel()
canPrepare()
canMarkReady()
canMarkDeliveredByAdmin()
```

### Décision importante

Les contrôleurs orchestrent. Ils ne doivent pas porter la règle métier complète.

```text
CustomerOrderCrudController
→ appelle CustomerOrderWorkflowService
```

---

## J5C — Données livraison et préparation dashboard livreur

### Objectif

Préparer techniquement le portail livreur avant de coder son interface.

### Acquis

Ajout dans `CustomerOrder` :

```text
assignedCourier
courierAssignedAt
outForDeliveryAt
```

Relation retenue :

```text
CustomerOrder.assignedCourier → Customer
```

Rôle préparé :

```text
ROLE_COURIER
```

Méthodes ajoutées côté workflow :

```text
canTakeForDelivery()
takeForDelivery()
canMarkDeliveredByCourier()
markDeliveredByCourier()
```

### Migration

```text
Version20260606101500 — Add courier assignment fields to customer orders
```

### Décision importante

Ne pas créer une entité utilisateur séparée pour le livreur dans le MVP. Un livreur est un `Customer` avec `ROLE_COURIER`.

---

## J5D — Dashboard livreur MVP

### Objectif

Créer le premier portail livreur accessible initialement sur `/livreur`.

Note : l'accès public du portail est renommé en `/djama` en J5M-C4, avant recette J5M, pour adopter un nom mahorais plus aligné avec la vision terrain du projet.

### Acquis

- Route protégée par `ROLE_COURIER`.
- Affichage des commandes prêtes.
- Prise en charge d’une commande.
- Affichage des livraisons en cours du livreur connecté.
- Appel client.
- SMS client.
- Marquage livré.
- Utilisation du service `CustomerOrderWorkflowService`.

### Décisions importantes

- Le livreur n’utilise pas EasyAdmin.
- Le portail livreur est mobile-first.
- Le livreur ne voit que les informations utiles terrain.

---

## J5E — Marge produit Hodina

### Objectif

Séparer le prix producteur du prix client affiché.

### Acquis

- Ajout d’une logique de marge produit.
- `ProductPricingService` créé.
- Hiérarchie de marge : global → vendeur → produit.
- Le panier et le checkout utilisent le prix client calculé.
- Les lignes de commande conservent les prix nécessaires.

### Migration

```text
Version20260607120000 — J5E - Add product margin pricing fields and initialize global margin setting
```

### Décision importante

La marge produit ne doit pas être mélangée avec les frais de livraison. Ce sont deux sujets séparés.

---

## J5F-A — Communes, zones tarifaires et commune logistique vendeur

### Objectif

Rendre la couverture géographique plus propre et administrable.

### Acquis

- `DeliveryPricingZone`.
- `DeliveryCommune`.
- `Seller.deliveryCommune`.
- Zones tarifaires.
- Communes administrables.
- Commune logistique vendeur.

### Migrations

```text
Version20260607170000 — J5F-A - Add delivery pricing zones, delivery communes and seller logistics commune
Version20260607173000 — J5F-A - Align delivery logistics schema with Doctrine metadata
```

### Décision importante

Une commune logistique vendeur est nécessaire pour préparer les frais, trajets et livraisons. Ne pas se contenter d’un texte libre vendeur.

---

## J5F-B — `DeliveryLogisticsService` et aperçu panier

### Objectif

Centraliser les règles logistiques dans un service dédié.

### Acquis

- `DeliveryLogisticsService`.
- `CartLogisticsPreview`.
- Détection vendeur / client sur Petite-Terre ou Grande-Terre.
- Détection barge.
- Gestion des cas incomplets.
- Préparation de l’affichage panier.

### Décision importante

Ne pas mettre les règles de livraison directement dans Twig, `CartController` ou `CheckoutController`.

---

## J5G-A — Aperçu logistique panier par vendeur

### Objectif

Brancher `DeliveryLogisticsService` dans le panier.

### Acquis

- Aperçu des frais logistiques dans le panier.
- Recalcul selon les vendeurs présents.
- Signature logistique pour détecter les changements.
- Préparation des étapes avancées J5G.

### Limite identifiée

La logistique réelle à Mayotte nécessite plus qu’une simple zone : il faut gérer les communes, les liaisons, la barge et les trajets.

---

## J5G-B1 — Source communes / voisinage validée

### Objectif

Valider la source des communes et du voisinage avant de coder les liaisons.

### Décision

Les communes / points logistiques doivent être maîtrisés dans le projet et administrables, pas improvisés à chaque calcul.

---

## J5G-B2 — Modèle modifiable communes / liaisons

### Objectif

Créer un modèle Doctrine pour gérer les communes et les connexions logistiques.

### Acquis

- `DeliveryCommune` enrichi.
- `DeliveryCommuneConnection` créé.
- CRUD EasyAdmin associé.
- Liaisons administrables.

### Migrations

```text
Version20260607213000 — J5G-B2 - Enrich delivery communes and add editable logistics connections
Version20260607214500 — J5G-B2 schema alignment for editable commune connections model
```

---

## J5G-B3 — Seed communes et liaisons initiales

### Objectif

Insérer les points logistiques et les premières liaisons.

### Acquis

- Seed des communes / points logistiques.
- Seed des liaisons.
- Labattoir traité comme point logistique Hodina, rattaché administrativement à Dzaoudzi.

### Migration

```text
Version20260607220000 — J5G-B3 seed initial communes and logistics connections from validated Hodina source
```

---

## J5G — Support adresses, livraison/facturation et zone AUTRE

### Objectif

Renforcer le modèle d’adresse avant d’aller plus loin dans le checkout.

### Acquis

- Séparation adresse de livraison / adresse de facturation.
- Gestion plus sûre du carnet d’adresses.
- Zone `AUTRE` ajoutée pour éviter les blocages incohérents.
- Préparation des snapshots d’adresse.

### Migration importante

```text
Version20260607225500 — J5G support - split billing and delivery addresses and add AUTRE delivery zone
```

---

## J5I — Préouverture commerciale, compte à rebours et capture e-mail

> Ce jalon a été ensuite refondu / généralisé par J5J. Il reste important historiquement.

### Objectif

Afficher une bannière de préouverture et bloquer les commandes publiques avant l’ouverture contrôlée.

### Acquis

- Compte à rebours.
- Capture e-mail.
- Table `LaunchSubscriber`.
- Blocage ajout panier avant ouverture si activé.
- Réglages de préouverture.

### Migration

```text
Version20260613110000 — J5I/J5J: abonnés ouverture et réglages génériques du mode commerce
```

---

## J5J — Mode commerce durable avec rôle testeur

### Objectif

Remplacer la simple préouverture par un mode commerce durable.

### Branche / commit connus

```text
Branche : pilot/j5j-commerce-mode-role-tester
Commit final : 0c2b357 feat: add J5J commerce mode with tester role
```

### Acquis

Modes commerce :

```text
open
preopening
maintenance
closed
```

Réglages :

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

Rôle ajouté :

```text
ROLE_COMMERCE_TESTER
```

### Règle importante

Quand `commerce_mode = open`, aucune bannière ni chrono ne doivent s’afficher, même si une date future existe.

### Migration

```text
Version20260613130000 — J5J: fusionne préouverture et maintenance en mode commerce avec ROLE_COMMERCE_TESTER, puis nettoie les anciens réglages J5I
```

---

## J5H — E-mail récapitulatif commande

### Objectif

Envoyer un e-mail récapitulatif après création de commande.

### Acquis

- `EmailLog`.
- `OrderEmailService`.
- Template e-mail commande.
- Journalisation `PENDING`, `SENT`, `FAILED`.
- Cron Messenger recette validé.
- Envoi après checkout.

### Migration

```text
Version20260615140801 — J5H-A: journalisation des e-mails de commande
```

### Décision importante

`EmailLog = SENT` ne prouve pas à lui seul que le client a reçu l’e-mail. Il faut vérifier la réception réelle lors des tests.

---

## J5G-E0 — Snapshots adresses commande

### Objectif

Figer les adresses utilisées par une commande.

### Acquis

- Snapshot adresse livraison.
- Snapshot adresse facturation.
- Suppression plus sûre des adresses du carnet client.
- Conservation de la commande même si l’adresse d’origine est modifiée ou supprimée.

### Migration

```text
Version20260615225836 — J5G-E0: add immutable order address snapshots and allow deleting customer address book entries safely
```

---

## J5G-B4 — BFS, coûts logistiques, plafonds et snapshot

### Objectif

Faire de la logistique un vrai socle du pilote.

### Branche connue

```text
pilot/j5g-b4-bfs-link-costs
```

Branche consolidée :

```text
pilot/j5j-commerce-mode-role-tester
```

### Acquis

- Calcul du plus court chemin via BFS.
- Coûts LAND.
- Coût BARGE.
- Trajet vendeur le plus contraignant.
- Supplément multicommunes vendeurs.
- Plafond global frais client.
- Snapshot logistique commande.
- Affichage panier.
- Affichage admin.

### Formule métier simplifiée

```text
frais client = forfait local commune livrée
             + coût trajet vendeur le plus contraignant
             + barge si nécessaire
             + supplément multi-vendeurs éventuel
             plafonné par global_delivery_customer_fee_cap
```

### Réglages globaux ajoutés

```text
global_commune_crossing_customer_fee
global_commune_crossing_courier_payout
global_delivery_customer_fee_cap
global_multi_seller_extra_customer_fee
global_multi_seller_extra_customer_fee_cap
```

### Migrations

```text
Version20260617143000 — global commune crossing cost settings
Version20260617150000 — global customer delivery fee cap setting
Version20260617153000 — global multi-seller customer delivery fee settings
Version20260617162000 — delivery logistics snapshot on customer orders
```

### Décisions à retenir

- Le snapshot logistique est indispensable pour analyser une commande plus tard.
- Les réglages futurs ne doivent pas modifier l’historique d’une commande déjà passée.
- Le calcul client ne doit pas exposer trop de détails techniques côté UX.

---

## J5G-E1 → J5G-E2-bis — Commune livrée, recalcul Ajax et panier contractuel

### Objectif

Rendre la saisie de livraison plus fiable et contractualiser le total avant validation.

### Acquis

- Saisie par commune livrée.
- `DeliveryCommune` devient la source de vérité côté adresse client.
- Recalcul Ajax des frais de livraison.
- Verrouillage du total avant validation.
- Tarif local + barge fixe.
- Confirmation enrichie.
- Panier réordonné : `Livraison et validation` avant récapitulatif.

### Route importante

```text
POST /panier/logistique/apercu
```

### Référence production connue

```text
Tag : j5g-e1-e2bis-prod
Commit final docs : 36cc357
Production : migrations jusqu’à Version20260615225836, schema synchronisé, tests fonctionnels OK
```

---

## Dette technique pré-J5K — runtime, uploads, assets, mailer

### Objectif

Éviter que les déploiements cassent les images, les assets ou les secrets.

### Acquis / décisions

- Ne pas versionner `.env.local`, `.env.prod.local`, `prod.env.local`.
- Ne pas versionner `public/assets`.
- Ne pas versionner les images uploadées en production.
- Garder `public/uploads/products/.gitkeep`.
- Documenter `MAILER_DSN` côté serveur.
- Restaurer les uploads si un déploiement les écrase.
- Préférer un déploiement par tag stable.

### Outils / docs associés

```text
tools/deploy-hodina-by-tag.sh
tools/reset-commandes-hodina.ps1
tools/clear-dev-cache.ps1
```

---

## J5K — GPS livraison, adresses, facturation et production

### Objectif

Adapter Hodina à la réalité des adresses à Mayotte, sans casser les calculs logistiques.

### Acquis

- GPS facultatif sur les adresses de livraison.
- Snapshot GPS sur la commande.
- Bouton navigateur pour récupérer la position actuelle.
- Lien carte côté admin et livreur.
- Commentaire terrain livreur sur adresse.
- Adresse de livraison par défaut.
- Adresse de facturation par défaut.
- Correction du carnet d’adresses dans le panier.
- Case `Utiliser cette adresse par défaut` visible uniquement lors de création / édition d’adresse.

### Migrations

```text
Version20260619102000 — GPS facultatif sur adresses et snapshot commande
Version20260619135000 — commentaire terrain livreur et snapshot commande
Version20260619170000 — adresse de livraison client par défaut
```

### Référence production

```text
Tag prod : prod-j5k-v8-quater-20260620
Commit   : 48dae1d
Production : HTTP/2 200
```

### Décisions importantes

- Le GPS ne remplace pas la commune livrée.
- Le GPS ne sert pas au calcul des frais.
- Le GPS est une donnée personnelle précise : ne pas l’afficher partout.
- Une commande doit rester possible sans GPS.

---

## J5L — UX panier mobile, sélecteur compact et facturation admin

### Objectif

Rendre le panier mobile réellement exploitable avant de continuer le portail client / livreur.

### Statut

```text
Validé en recette le 21/06/2026
```

Référence recette :

```text
Tag : recette-j5l-b-selecteur-adresses-20260621
Commit : 235a51f
```

### J5L-A — UX panier mobile PWA

Acquis :

- Panier réorganisé en flux linéaire.
- Articles affichés en premier.
- Total et frais juste après les articles.
- Livraison puis facturation puis CGV / validation.
- Détails BFS / barge masqués côté client.
- Sélection visuelle des adresses corrigée.
- `aria-pressed` synchronisé.
- `.is-default` ne colore plus une carte non sélectionnée.
- Champ GPS visible à côté du bouton `Utiliser ma position actuelle`.
- Suppression du bouton `Retirer la position GPS`.

### J5L-B — Sélecteur compact d’adresses

Acquis :

- Suppression des sous-menus longs.
- Panneau compact livraison.
- Panneau compact facturation.
- Liste scrollable.
- Sélection sans fermeture immédiate.
- Bouton explicite `Utiliser cette adresse de livraison`.
- Bouton explicite `Utiliser cette adresse de facturation`.
- GPS ajoutable avant confirmation.
- Case défaut utilisable avant confirmation.

### J5L-C — Facturation admin

Acquis :

- Bloc `Facturation` dans la fiche terrain admin.
- Adresse de facturation visible dans EasyAdmin.
- `billingAddressSummary` affiché.

### Décision de clôture

J5L est clôturé côté recette. Ne plus le remettre comme chantier global à faire. Les futures interventions panier doivent être des bugfixs ciblés.

---

## Images catalogue — Optimisation manuelle de démarrage

### Objectif

Avoir un catalogue performant visuellement avant d’automatiser l’optimisation d’images.

### Images générées / optimisées

```text
ananas_600.webp          ~ 48 Ko
canne_a_sucre_600.webp   ~ 35 Ko
mangues_600.webp         ~ 24 Ko
manioc_600.webp          ~ 42 Ko
jackfruit_600.webp       ~ 65 Ko
```

### Décision

Cette optimisation manuelle règle le besoin immédiat mais ne remplace pas le futur pipeline automatique : upload, conversion WebP, redimensionnement, compression, cache et multi-formats.

---

## J5M-A — Workflow livreur enrichi

### Objectif

Ajouter une étape claire entre commande prête et départ réel en livraison.

### Statut

```text
Validé localement fonctionnellement
Recette à faire
```

### Décision métier

Ne pas stocker le nom du livreur dans le statut.

Règle retenue :

```text
status = PICKED_UP
assignedCourier = livreur connecté
courierAssignedAt = date de prise en charge
```

Affichage :

```text
Prise en charge par {livreur}
```

Puis :

```text
status = OUT_FOR_DELIVERY
outForDeliveryAt = date de départ réel
```

Affichage :

```text
En cours de livraison
```

### Workflow cible

```text
PENDING_VALIDATION
→ CONFIRMED
→ PREPARING
→ READY_FOR_PICKUP
→ PICKED_UP
→ OUT_FOR_DELIVERY
→ DELIVERED
```

### Acquis

- `STATUS_PICKED_UP` ajouté dans `CustomerOrder`.
- `takeForDelivery()` passe en `PICKED_UP`.
- `startDelivery()` passe de `PICKED_UP` à `OUT_FOR_DELIVERY`.
- Le portail livreur affiche les commandes `PICKED_UP` et `OUT_FOR_DELIVERY` assignées.
- Actions terrain : `Prendre en charge`, `Démarrer la livraison`, `Marquer livrée`.
- Admin affiche `Prise en charge par {livreur}`.
- Les SMS manuels incluent prise en charge et en cours de livraison.
- Aucune migration Doctrine nécessaire.

---

## J5M-B1 — Portail livreur terrain synthétique

### Objectif

Rendre le portail livreur lisible sur mobile. Route initiale : `/livreur`, renommée ensuite en `/djama` avant recette.

### Statut

```text
Validé localement fonctionnellement
Recette à faire
```

### Acquis

Le portail est organisé en trois blocs :

```text
1. À prendre en charge
2. Prises en charge / en cours
3. Livrées cette semaine
```

Bloc 1 :

```text
READY_FOR_PICKUP
assignedCourier IS NULL
```

Bloc 2 :

```text
PICKED_UP / OUT_FOR_DELIVERY
assignedCourier = livreur connecté
```

Bloc 3 :

```text
DELIVERED
assignedCourier = livreur connecté
deliveredAt >= aujourd’hui - 6 jours
```

Données préparées côté contrôleur :

- label commande ;
- commune client ;
- vendeurs distincts ;
- nombre total d’articles ;
- total commande ;
- frais livraison ;
- téléphone client nettoyé ;
- message SMS ;
- total transport estimé de la semaine.

---

## J5M-B1-bis — Cartes repliables

### Objectif

Améliorer la vue d’ensemble sans ajouter de JS ni de migration.

### Acquis

- Cartes repliables via `<details>/<summary>`.
- Mode replié minimal.
- Mode déplié avec les détails et actions.
- Libellé `Commune` remplacé par `Commune Client`.

### Décision technique

Utiliser le HTML natif plutôt qu’un JavaScript custom.

---

## J5M-B2 — Compactage des cartes livreur

### Objectif

Transformer le mode replié en ligne de tournée compacte.

### Statut

```text
Validé localement fonctionnellement
Recette à faire
```

### Acquis

Mode replié :

```text
06191 · Mamoudzou · 3 pdts · 61,70 €
```

Mode déplié :

```text
numéro complet
vendeurs concernés
actions
```

Ajouts techniques :

```text
shortLabel
summaryLine
```

Aucune migration Doctrine.

---

## J5M-C1 — Détails terrain utiles dans les cartes livreur

### Objectif

Ajouter les informations opérationnelles sans alourdir le résumé compact.

### Statut

```text
Validé localement fonctionnellement
Recette à faire
```

### Acquis

Dans le mode déplié :

- client ;
- adresse complète de livraison ;
- lien GPS livraison si renseigné ;
- instructions client ;
- commentaire terrain ;
- vendeurs concernés ;
- actions livreur.

Aucune migration Doctrine.

---

## J5M-C — Suivi des collectes vendeurs

### Objectif

Préparer la vraie réalité terrain : le livreur ne livre pas seulement au client, il doit d’abord récupérer les produits chez les vendeurs.

### Décision importante

On ne crée pas tout de suite une checklist persistante de collecte par produit. Pour le MVP, le portail livreur affiche les informations utiles par vendeur.

Les évolutions suivantes sont repoussées :

- checklist persistante ;
- validation produit par produit ;
- preuve photo de collecte ;
- optimisation de tournée ;
- portail vendeur complet.

---

## J5M-C2 — Collecte vendeurs opérationnelle

### Objectif

Afficher au livreur où récupérer les produits et quoi récupérer chez chaque vendeur.

### Statut

```text
Validé localement
Recette à faire
Production non déployée
```

### Première idée rejetée

Ne pas ajouter directement dans `Seller` :

```text
pickup_address
pickup_gps_latitude
pickup_gps_longitude
pickup_gps_accuracy_meters
```

Pourquoi : `Address` possède déjà les champs nécessaires.

### Décision finale

Réutiliser l’entité existante `Address`.

Relations ajoutées :

```text
Seller.customerAccount → Customer
Seller.pickupAddress   → Address
```

### Règle logistique centrale

```text
Seller.deliveryCommune
→ source de vérité pour coût, trajet, barge, BFS, zone et snapshot.

Seller.pickupAddress
→ aide terrain pour guider le livreur vers le point réel de collecte.
```

Ne jamais remplacer `Seller.deliveryCommune` par `Seller.pickupAddress` dans `DeliveryLogisticsService`.

### Garde-fou ajouté

```text
tools/assert-delivery-logistics-commune-source.php
```

Commande :

```powershell
php tools/assert-delivery-logistics-commune-source.php
```

Résultat attendu :

```text
[J5M-C2][OK] DeliveryLogisticsService reste verrouillé sur Seller::deliveryCommune pour les trajets/coûts/barge/BFS.
```

### Service ajouté

```text
src/Service/SellerPickupLogisticsSynchronizer.php
```

Responsabilités :

- créer ou mettre à jour l’adresse de retrait ;
- synchroniser `Address.commune` ;
- synchroniser `Address.postalCode` ;
- synchroniser `Seller.deliveryCommune` ;
- synchroniser `Seller.deliveryZone` ;
- conserver le champ legacy `Seller.commune` ;
- retourner warnings / errors.

### Commande de rattrapage ajoutée

```powershell
php bin/console hodina:j5m:c2:sync-seller-pickup
php bin/console hodina:j5m:c2:sync-seller-pickup --apply
php bin/console hodina:j5m:c2:sync-seller-pickup --apply --seller-id=12
php bin/console hodina:j5m:c2:sync-seller-pickup --apply --create-missing-pickup-address
```

### Migrations

```text
Version20260621143500 — seller.customer_account_id + seller.pickup_address_id
Version20260621145500 — index/FK correctifs
```

### Incident corrigé

La migration initiale a ajouté les colonnes mais pas tous les index/FK attendus. Une migration corrective a été ajoutée au lieu de forcer le schéma à la main.

---

## J5M-C3 — Création vendeur avec adresse de retrait

### Objectif

Rendre la création vendeur cohérente depuis EasyAdmin.

### Décision

Lors de la création d’un vendeur, l’admin ne doit pas choisir une adresse existante dans un sélecteur peu naturel. Il saisit directement le point de retrait du vendeur.

À la sauvegarde, Hodina crée ou rattache :

```text
Customer vendeur
Address de retrait
Seller.customerAccount
Seller.pickupAddress
```

---

## J5M-C3-bis — Commune de retrait depuis `DeliveryCommune`

### Problème constaté

Le formulaire vendeur affichait encore :

```text
Code postal — champ texte libre
Commune — champ texte libre
```

Cela ouvre la porte aux erreurs :

```text
Labattoir / Labatoir / Labattoire
mauvais code postal
commune mal orthographiée
```

### Règle corrigée

```text
L’admin choisit la commune de retrait depuis DeliveryCommune.
Hodina déduit Address.commune et Address.postalCode.
Hodina stocke Seller.deliveryCommune depuis la commune sélectionnée.
DeliveryLogisticsService continue d’utiliser Seller.deliveryCommune.
```

### Acquis

- Suppression de la commune texte libre dans le formulaire vendeur.
- Suppression du code postal texte libre dans le formulaire vendeur.
- Utilisation d’une commune seedée.
- Synchronisation via `SellerPickupLogisticsSynchronizer`.
- Aucune migration Doctrine.

---

## J5M-C3-ter — Identité vendeur client et nom de structure optionnel

### Objectif

Finaliser la distinction entre personne vendeuse et identité commerciale.

### Statut final au point d’arrêt

```text
Local : OK
Recette : à faire
Production : non déployé
```

### Règles fonctionnelles validées

- Le prénom vendeur est obligatoire.
- Le nom vendeur est obligatoire.
- Le nom de structure est optionnel.
- Si un nom de structure est renseigné, il est prioritaire pour l’affichage vendeur.
- Si aucun nom de structure n’est renseigné :
  - le portail livreur affiche prénom + nom ;
  - la boutique / catalogue affiche le nom de famille.
- Le compte client vendeur est créé ou rattaché automatiquement depuis l’e-mail.
- Le rôle `ROLE_SELLER` est ajouté au compte client lié.
- La commune de retrait reste issue de `DeliveryCommune` seedée.
- `Seller.deliveryCommune` reste la source de vérité logistique.

### Ajouts techniques

Dans `Seller` :

```text
businessName nullable
sellerFirstName non persisté
sellerLastName non persisté
getCourierDisplayName()
getPublicDisplayName()
```

Dans `SellerCrudController` :

```text
Identité du vendeur
- Prénom
- Nom
- Téléphone
- Email

Structure / affichage commercial
- Nom de structure optionnel

Adresse / point de retrait vendeur
- Adresse de retrait
- Complément
- Commune de retrait seedée
- Instructions
- Note terrain
- GPS
```

### Migration

```text
Version20260621215500 — seller.business_name
```

Cette migration initialise `business_name` avec l’ancien `seller.name` pour conserver l’affichage existant.

### Affichages modifiés

- Catalogue : `seller.publicDisplayName`.
- Fiche produit : `seller.publicDisplayName`.
- Panier : `seller.publicDisplayName`.
- Confirmation : `seller.publicDisplayName`.
- Portail livreur : `seller.courierDisplayName`.

---

## 6. État exact de reprise après J5M-C3-ter

### Ce qui est validé localement

- Création vendeur avec prénom + nom.
- Nom de structure optionnel.
- Création / rattachement automatique du compte client vendeur.
- Ajout automatique du rôle `ROLE_SELLER`.
- Création / mise à jour de l’adresse de retrait vendeur.
- Commune de retrait choisie depuis `DeliveryCommune`.
- Code postal déduit automatiquement.
- `Seller.deliveryCommune` et `Seller.deliveryZone` synchronisés.
- Portail livreur enrichi avec collecte vendeurs.
- Catalogue avec affichage structure ou fallback.
- Garde-fou PHP OK.

### Ce qui reste à faire juste après l’archive

```text
1. Vérifier git status.
2. Vérifier les migrations locales.
3. Rejouer les tests techniques.
4. Déployer J5M-C2/C3/C3-ter en recette.
5. Appliquer les migrations recette.
6. Tester création vendeur en recette.
7. Tester portail livreur en recette.
8. Tester commande multi-vendeur jusqu’à livraison.
9. Taguer uniquement après validation recette.
10. Ne pas déployer en production avant validation complète.
```

### Commandes de vérification recommandées

```powershell
php -l src/Entity/Seller.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Controller/CheckoutController.php
php -l src/Service/DeliveryLogisticsService.php
php -l src/Service/SellerPickupLogisticsSynchronizer.php
php -l src/Command/J5mC2SyncSellerPickupCommand.php
php -l tools/assert-delivery-logistics-commune-source.php

php bin/console lint:twig templates/product/catalogue.html.twig templates/product/show.html.twig templates/cart/index.html.twig templates/checkout/confirmation.html.twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
php bin/console cache:clear
php bin/console cache:warmup
```

---

## 7. Architecture métier actuelle à ne pas casser

## Commande

Entité centrale :

```text
CustomerOrder
```

Statuts actuels :

```text
DRAFT
PENDING_VALIDATION
CONFIRMED
PREPARING
READY_FOR_PICKUP
PICKED_UP
OUT_FOR_DELIVERY
DELIVERED
CANCELED
```

Workflow complet :

```text
DRAFT
→ PENDING_VALIDATION
→ CONFIRMED
→ PREPARING
→ READY_FOR_PICKUP
→ PICKED_UP
→ OUT_FOR_DELIVERY
→ DELIVERED
```

Annulation :

```text
PENDING_VALIDATION / CONFIRMED → CANCELED
```

### Règle

Les transitions doivent passer par :

```text
CustomerOrderWorkflowService
```

Ne pas recréer des transitions directement dans les contrôleurs.

---

## Logistique

Services centraux :

```text
DeliveryCommuneMatcherService
DeliveryLogisticsService
SellerPickupLogisticsSynchronizer
```

Règle fondamentale :

```text
DeliveryLogisticsService utilise Seller.deliveryCommune.
```

Ne pas utiliser `Seller.pickupAddress` pour calculer les frais, trajets, barge ou BFS.

---

## Prix produit

Service central :

```text
ProductPricingService
```

La marge produit est séparée des frais de livraison.

---

## Préouverture / maintenance

Service central :

```text
SalesOpeningService
```

Modes :

```text
open
preopening
maintenance
closed
```

Rôle testeur :

```text
ROLE_COMMERCE_TESTER
```

---

## E-mails

Service central :

```text
OrderEmailService
```

Journal :

```text
EmailLog
```

---

## SMS pilote

Services centraux :

```text
SmsService
LogSmsSender
OrderSmsMessageBuilder
```

Journal :

```text
SmsLog
```

Pendant le pilote, l’envoi réel reste manuel via iPhone.

---

## 8. Décisions structurantes à garder en tête

### Produit / marché

- Hodina démarre comme pilote contrôlé.
- Le paiement reste manuel.
- L’admin valide les commandes.
- Le catalogue reste visible même si les commandes sont bloquées en préouverture, selon mode commerce.

### Technique

- MariaDB est conservée.
- EasyAdmin reste le backoffice admin.
- Le portail livreur est séparé d’EasyAdmin.
- Les services portent les règles métier.
- Les migrations sont préférées aux corrections SQL manuelles non versionnées.
- Les patches doivent conserver l’UTF-8.

### Logistique

- `DeliveryCommune` structure les communes.
- `DeliveryCommuneConnection` structure les liaisons.
- BFS calcule le chemin.
- La barge est une notion séparée des liaisons terrestres.
- Les frais client sont plafonnables.
- Le snapshot logistique fige la commande.

### Adresses / GPS

- Le GPS aide le terrain, mais ne calcule pas les frais.
- Le GPS ne doit pas bloquer une commande.
- Le GPS doit être affiché seulement là où il est utile.
- Les adresses de commande sont snapshotées.

### Vendeur

- Un vendeur est aussi un client.
- Le vendeur peut avoir un nom de structure optionnel.
- L’identité personnelle vendeur et l’identité commerciale sont séparées.
- Le point de retrait vendeur réutilise `Address`.
- La commune de retrait est choisie depuis `DeliveryCommune`.

---

## 9. Incidents déjà rencontrés et corrections à retenir

### Encodage SQL PowerShell

Un dump SQL généré sous PowerShell a posé problème car il était encodé en UTF-16.

Règle : pour les dumps SQL, utiliser `cmd.exe` ou forcer explicitement l’UTF-8.

### Migrations incomplètes

La migration J5M-C2 a ajouté les colonnes vendeur mais pas tous les index / clés étrangères attendus.

Correction : migration corrective `Version20260621145500`.

Règle : ne pas corriger uniquement à la main en base. Versionner la correction.

### Patches / fichiers tronqués

Des fichiers ou migrations ont déjà été tronqués lors de patches.

Règle : toujours exécuter `php -l`, `lint:twig`, `doctrine:schema:validate` et vérifier les fichiers critiques après patch.

### Uploads / assets

Les images uploadées et assets compilés peuvent être perdus si les dossiers runtime sont mal gérés.

Règle : ne pas versionner les uploads réels ni les assets compilés, mais conserver les dossiers nécessaires avec `.gitkeep`.

### Recette / production

Le déploiement par branche mouvante est risqué.

Règle : préférer des tags stables validés.

### Messenger / e-mails

`EmailLog = SENT` ne garantit pas la réception client.

Règle : tester la réception réelle.

---

## 10. Ce qu’il ne faut surtout pas refaire

Ne pas refaire :

```text
- le panier complet ;
- le workflow commande admin ;
- le service CustomerOrderWorkflowService ;
- le calcul BFS J5G-B4 ;
- la séparation livraison / facturation ;
- les snapshots adresse / logistique ;
- le mode commerce J5J ;
- le GPS J5K ;
- le sélecteur compact J5L ;
- le portail livreur J5M-B ;
- le rattachement vendeur → Customer / Address J5M-C2/C3.
```

À faire à la place :

```text
corriger → tester → étendre → documenter
```

---

## 11. Prochaine reprise recommandée

La reprise logique après J5M-C3-ter est :

```text
J5M-C2/C3/C3-ter — validation recette
```

Checklist de reprise :

```text
1. git status
2. vérifier la branche courante
3. vérifier que les migrations J5M existent
4. lancer les tests techniques locaux
5. créer un tag recette uniquement si local propre
6. déployer en recette
7. migrer la base recette
8. créer / modifier un vendeur en recette
9. vérifier compte client vendeur + rôle ROLE_SELLER
10. vérifier point de retrait vendeur
11. vérifier catalogue
12. créer une commande avec ce vendeur
13. passer la commande jusqu’à READY_FOR_PICKUP
14. se connecter livreur
15. vérifier collecte vendeurs dans /djama
16. prendre en charge
17. démarrer livraison
18. marquer livrée
19. taguer si tout est OK
```

Ne pas passer en production tant que la recette n’est pas validée.

---

## 12. Documents sources utiles

Pour approfondir, lire dans cet ordre :

```text
docs/TODO.md
docs/ROADMAP.md
docs/DECISIONS.md
docs/ARCHITECTURE.md
docs/WORKFLOWS.md
docs/ENTITIES.md
```

Puis pour le point d’arrêt actuel :

```text
docs/README_MAJ_J5M_C2_C3_COLLECTE_VENDEURS_IDENTITE.md
docs/COMMIT_J5M_C2_C3_COLLECTE_VENDEURS_IDENTITE.md
docs/README_MAJ_J5M_C3_TER_IDENTITE_VENDEUR_STRUCTURE.md
docs/README_MAJ_J5M_C3_BIS_COMMUNE_RETRAIT_SEEDEE.md
docs/README_MAJ_J5M_C2_BIS_SYNC_COMMUNE_LOGISTIQUE_VENDEUR.md
docs/README_MAJ_J5M_C2_COLLECTE_VENDEURS_ADRESSES_EXISTANTES.md
```

Pour la logistique :

```text
docs/COMMIT_J5G_B4.md
docs/README_MAJ_J5G_B4.md
docs/README_MAJ_J5G_E1_E2_BIS.md
docs/README_MAJ_J5G_E1_COMMUNE_LIVREE.md
```

Pour les adresses / GPS / panier :

```text
docs/COMMIT_J5K_GPS_LIVRAISON.md
docs/COMMIT_J5K_V8_QUATER_RECETTE_VALIDEE_20260620.md
docs/COMMIT_J5L.md
docs/README_MAJ_J5L_PANIER_SELECTEUR_FACTURATION.md
```

---


## 13. Vision stratégique et produit à garder pour la suite

### Ce que Hodina doit réussir avant d’être “gros”

Avant d’ajouter du paiement en ligne, un portail vendeur complet ou de l’optimisation avancée, Hodina doit réussir les fondamentaux :

```text
1. un client comprend le catalogue et commande sans friction ;
2. l’admin comprend quoi valider et quoi préparer ;
3. les vendeurs sont identifiés proprement ;
4. les points de retrait sont fiables ;
5. le livreur sait quoi collecter, où aller, qui appeler ;
6. la livraison PT / GT est expliquable ;
7. les commandes gardent une trace fiable ;
8. les tests recette valident le parcours complet.
```

### Ce qui différencie Hodina d’un e-commerce classique

Un e-commerce classique part souvent du stock, du paiement et de l’expédition.

Hodina part plutôt de la réalité locale :

```text
vendeurs dispersés
produits parfois disponibles selon arrivage
adresses difficiles
communes importantes
barge structurante
livraison humaine
relation de confiance
besoin de pédagogie client
```

C’est pour cela que les fonctions “terrain” sont aussi importantes que les fonctions “catalogue”.

### Rapport entre développement et communication

Les réseaux sociaux Hodina ne sont pas seulement un canal marketing. Ils servent aussi à construire la confiance autour du projet.

Les échanges ont posé une logique de contenu : raconter la construction du marché digital de Mayotte, montrer que le projet avance, expliquer les problèmes terrain, valoriser l’île, les vendeurs et la proximité.

Cette dimension n’est pas dans le code, mais elle explique pourquoi le produit doit rester simple, lisible et crédible. Un client qui découvre Hodina via une vidéo doit retrouver dans l’application la même promesse : proximité, simplicité, confiance.

### Gouvernance recommandée pour les prochains développeurs

Pour chaque nouveau jalon, conserver cette méthode :

```text
1. lire HISTORIQUE.md ;
2. lire TODO.md ;
3. vérifier DECISIONS.md ;
4. comprendre les services existants ;
5. faire un patch minimal ;
6. lancer les tests techniques ;
7. faire un test fonctionnel réel ;
8. documenter ce qui a changé ;
9. documenter les pièges rencontrés ;
10. taguer seulement après validation.
```

### Critères d’ouverture contrôlée

Une ouverture contrôlée doit être envisagée uniquement si :

```text
- le panier mobile est validé ;
- les adresses livraison/facturation sont stables ;
- les vendeurs ont des points de retrait fiables ;
- le portail livreur affiche les bonnes informations terrain ;
- le workflow READY_FOR_PICKUP → PICKED_UP → OUT_FOR_DELIVERY → DELIVERED est validé ;
- les e-mails et SMS logs sont cohérents ;
- la recette reproduit le parcours complet ;
- l’admin sait traiter une commande sans intervention développeur.
```

---

## J5M-C4 — Renommage du portail livreur en `/djama`

### Décision

Avant la recette J5M-C3-ter, le portail livreur a été renommé :

```text
Ancienne route : /livreur
Nouvelle route : /djama
```

`Djama` signifie ici l'idée d'assembler / rassembler en mahorais. Le choix est cohérent avec le rôle terrain du portail : rassembler les commandes prêtes, les points de collecte vendeurs, les informations client, les notes GPS et les actions du livreur dans une seule interface mobile.

### Ce qui change techniquement

- La route GET du dashboard livreur devient `/djama`.
- Les actions POST du portail deviennent `/djama/commande/{id}/...`.
- La protection Symfony `ROLE_COURIER` passe de `^/livreur` à `^/djama`.
- Les noms de routes Symfony restent inchangés (`courier_dashboard`, `courier_order_take`, etc.) pour éviter de casser les templates qui utilisent `path(...)`.
- Aucune migration Doctrine n'est nécessaire.
- Aucun changement métier du workflow livreur n'est introduit.

### Tests fonctionnels à refaire en recette

```text
1. Se connecter avec un compte ROLE_COURIER.
2. Ouvrir /djama.
3. Vérifier qu'une commande prête est visible.
4. Prendre en charge la commande.
5. Démarrer la livraison.
6. Modifier une note terrain si la commande le permet.
7. Marquer la commande livrée.
8. Vérifier qu'un utilisateur non livreur ne peut pas accéder à /djama.
9. Vérifier que les liens du menu génèrent bien /djama via path('courier_dashboard').
```

### Point d'attention

Les anciens documents de jalons J5B/J5C/J5D peuvent encore mentionner `/livreur` parce qu'ils décrivent l'état historique au moment où le portail a été créé. Pour l'état courant du projet, utiliser `/djama`.

---

## 14. Résumé très court pour un développeur qui arrive

Hodina est déjà un MVP avancé.

Le catalogue, le panier, le checkout, les commandes, l’admin, les SMS manuels, les e-mails, la préouverture, les adresses, le GPS, la logistique BFS, le panier mobile et le portail livreur existent déjà.

Le projet s’est arrêté sur la structuration des vendeurs : un vendeur est aussi un client, il a un point de retrait basé sur `Address`, une commune de retrait seedée, une commune logistique synchronisée, et un nom de structure optionnel.

Le prochain travail n’est pas de reconstruire. Le prochain travail est de **valider J5M-C4 en recette** : J5M-C3-ter + le renommage du portail livreur en `/djama`, puis seulement ensuite continuer vers le portail client MVP, l’exploitation admin et l’ouverture contrôlée.

---

# Complément historique — J5N à J5Q-A validés recette

## J5N — Sécurisation collecte vendeur, AJAX Djama et hotfix GPS panier

J5N a consolidé le portail Djama : validation des collectes vendeur par code, envois SMS/e-mail vendeur, actions AJAX sans rechargement complet, conservation de l'état ouvert des cartes, timezone commande et hotfix GPS panier.

Décision importante : `contact@hodina.fr` ne doit jamais être utilisé comme destinataire de secours d'un code vendeur.

## J5O-A — Code de réception client chiffré

La livraison client est sécurisée par un code de réception. Le code est généré au démarrage livraison, stocké chiffré, envoyé par SMS/e-mail, renvoyable si besoin, puis supprimé après validation de la livraison.

Recette validée avec :

```text
j5o-code-reception-client-recette-v2
```

## J5P-A — Notifications client sur statuts

Les étapes de commande importantes envoient maintenant des e-mails client en plus des SMS existants. Une notification spécifique est envoyée quand toutes les collectes vendeurs sont terminées.

Décision anti-spam : pas d'e-mail générique `OUT_FOR_DELIVERY`, car le code client J5O-A couvre cette étape.

Recette validée avec :

```text
j5p-notifications-statuts-client-recette
```

## J5Q-A — Paiements livreurs

Le portail livreur gagne une section `Mes paiements`. Le backoffice EasyAdmin gagne la section métier `Livreurs` avec :

```text
Livreurs
Rémunérations livreurs
Lignes rémunération
```

J5Q-A ajoute :

```text
CourierPayout
CourierPayoutLine
CourierPayoutService
CourierCrudController
Version20260624140000
```

Règle validée : seules les commandes `DELIVERED`, rattachées à un livreur et avec `deliveredAt`, peuvent entrer dans une rémunération.

Recette validée avec :

```text
Tag : j5q-paiements-livreurs-recette
Commit : 12bb402
```

Test réel validé : un paiement `PAID` de 30,00 € sur deux commandes livrées, visible dans Djama dans l'historique payé.

## Point de reprise après J5Q-A

Le socle terrain admin/livreur est robuste. La prochaine reprise doit éviter de recréer ces briques et se concentrer sur :

```text
1. portail client MVP ;
2. tests bout en bout multi-commandes ;
3. procédures support ;
4. ajustements anti-spam si nécessaires ;
5. suivi financier vendeur / export plus tard.
```

---

# 24/06/2026 — Préparation J5Q-C automatisation paiements livreurs

Après validation recette de J5Q-A et mise à jour documentaire, le jalon suivant a été recadré.

Découpage confirmé :

```text
J5Q-A — modèle/admin
J5Q-B — Mes paiements Djama, intégré dans J5Q-A
J5Q-C — automatisation cron + récap admin
J5Q-D — ajustements / export
```

Demande métier : préparer rapidement le cron avec récap admin, car l'exploitation ne doit pas dépendre d'une présence locale ou d'un clic manuel oublié.

Patch J5Q-C v2 préparé :

```text
commande hodina:courier-payouts:generate
mode --auto-due
mode --dry-run
notification --notify-admins
template récap admin
script tools/install-courier-payout-cron.sh
```

Décision conservée : le cron ne paie jamais automatiquement.

# 24/06/2026 — Préparation J5Q-C-1 structuration des réglages

Après l'installation du cron J5Q-C en recette, le besoin de branding e-mail a fait apparaître une dette UX plus large : `HodinaSetting` regroupe de plus en plus de paramètres globaux hétérogènes.

Décision prise : découper la suite en deux lots :

```text
J5Q-C-1 — Structuration des réglages en groupes
J5Q-C-2 — Branding e-mail
```

J5Q-C-1 enrichit `HodinaSetting` avec des métadonnées de groupe et réorganise EasyAdmin pour afficher des vues spécialisées sans créer une table par famille de paramètres.

J5Q-C-2 viendra ensuite ajouter les paramètres et services de branding e-mail.

# 25/06/2026 — Préparation J5Q-C-2 branding e-mail

Après validation recette de J5Q-C-1, la suite est cadrée ainsi :

```text
J5Q-C-2 — Branding e-mail paramétrable
```

Le besoin métier est d'identifier immédiatement l'origine d'un e-mail reçu : dev, recette ou production.

Inventaire effectué : tous les envois `TemplatedEmail` existants sont pris en compte :

```text
ORDER_CREATED
ORDER_STATUS_*
ORDER_SELLER_COLLECTIONS_COMPLETED
CUSTOMER_DELIVERY_CODE
SELLER_COLLECTION_CODE
COURIER_PAYOUT_RECAP
confirmation e-mail SymfonyCasts dormant
```

Le lot ajoute `EmailBrandingService`, une sous-section EasyAdmin `Branding e-mail`, les réglages `email_branding_*` et applique le branding aux templates HTML d'e-mail.

---

# 25/06/2026 — Validation recette J5Q-C-2 et diagnostic logs recette

## Développement local

Le patch `J5Q-C-2 — Branding e-mail paramétrable` a été appliqué localement puis contrôlé :

- lint PHP OK sur `EmailBrandingService`, services e-mails, `EmailVerifier`, `HodinaSetting`, contrôleur EasyAdmin et migration ;
- migration `Version20260625090000` jouée localement ;
- `doctrine:schema:validate` OK ;
- `lint:twig` OK sur les 6 templates d'e-mail ;
- cache dev clear/warmup OK ;
- `git diff --check` OK.

Commit créé :

```text
3586560 feat(j5q): add configurable email branding
```

Tag créé :

```text
j5q-c2-branding-email-recette
```

## Déploiement recette

Le tag a été déployé avec le script extrait du tag.

Résultat :

- checkout `3586560` OK ;
- migration `Version20260625090000` exécutée ;
- Doctrine schema OK ;
- Twig e-mails OK ;
- cache prod OK ;
- groupe `email_branding` présent en base ;
- EasyAdmin `Réglages > Branding e-mail` disponible.

## Valeurs branding initiales observées

```text
email_branding_subject_prefix      vide
email_branding_opening_formula     Bonjour
email_branding_closing_formula     Merci,
email_branding_signature           L’équipe Hodina
```

Décision : le préfixe `[Recette]` doit être configuré en base recette, pas imposé par défaut dans le code.

## Diagnostic incident intermittent recette

Un `ERR_CONNECTION_CLOSED` a été observé sur mobile. Les contrôles effectués ont montré :

- PHP web réel : `8.4.21` ;
- `memory_limit=512M` ;
- `max_execution_time=600` ;
- accès `/` et `/ouegnewe` OK via `curl` ;
- access logs récents avec réponses majoritairement `200` et `302` ;
- `public/error_log` actif pour les logs PHP web ;
- `var/log/prod.log` créé mais la configuration Monolog prod actuelle écrit sur `php://stderr`, pas directement dans ce fichier ;
- aucun élément ne justifie un rollback J5Q-C-2 à ce stade.

Document ajouté pour les futures investigations :

```text
docs/DEBUG_RECETTE_HODINA.md
```


---

# 25/06/2026 — J5Q-D0 Stabilisation Djama avant Portail client MVP

Après arbitrage, la suite du développement est fixée ainsi :

```text
1. J5Q-D0 — Stabilisation Djama
2. Portail client MVP
```

Le lot J5Q-D0 ne crée aucune nouvelle entité. Il s’appuie sur les structures existantes du portail livreur : `Customer` avec rôle livreur, `CustomerOrder.assignedCourier`, `sellerCollectionSnapshot`, codes vendeur, code réception client chiffré et paiements livreurs.

Actions préparées localement :

- correction de l’injection `EmailBrandingService` dans `SellerCollectionCodeService` ;
- suppression du SMS générique `customer_order_out_for_delivery` au démarrage livraison ;
- conservation du flux indispensable `customer_delivery_code` ;
- ajout de badges d’alerte terrain côté client et vendeur dans Djama ;
- ajout de raccourcis appel/e-mail vendeur dans les blocs collecte ;
- documentation du lot dans `COMMIT_J5Q_D0_STABILISATION_DJAMA.md`.

Contrôles locaux : lints PHP OK sur les fichiers modifiés. Le zip fourni ne contient pas `bin/console`, donc `lint:twig` et `doctrine:schema:validate` restent à rejouer dans l’environnement complet.


---

# 25/06/2026 — J5R-A Portail client commandes + annulation client encadrée

Après validation recette de `J5Q-D0 — Stabilisation Djama`, démarrage du Portail client MVP.

Décision produit : inclure dès le MVP l’annulation client encadrée, car le client ne doit pas se sentir piégé après validation d’un panier. Hodina doit aussi apprendre dès les premières annulations.

Patch préparé :

- routes `/mon-compte`, `/mon-compte/commandes`, `/mon-compte/commandes/{id}` ;
- détail commande propriétaire uniquement ;
- cartes mobiles commandes en cours / historique ;
- messages de statut client ;
- affichage adresse snapshotée, consignes et GPS ;
- information code réception sans affichage du code ;
- route POST d’annulation client avec CSRF ;
- annulation uniquement avant préparation ;
- entité `CustomerOrderFeedback` pour motif/commentaire d’annulation ;
- entrée EasyAdmin `Retours clients` en lecture seule.

Non inclus : notation vendeur/livreur, profil/adresses éditables, paiement, remboursement, litige, messagerie, suivi GPS live.

---

# 25/06/2026 — Préparation J5S-A DeliveryPoint

Après la validation recette de J5R-A, décision de faire une pause sur le portail client pour préparer un cas commercial concret : vendre des colliers de fleurs sur Hodina.

Besoin initial : imposer la remise des colliers uniquement à l’accueil de la barge Petite-Terre ou à l’accueil passager de l’aéroport de Pamandzi.

Décision élargie : créer une vraie brique `DeliveryPoint` pour gérer aussi les futurs relais pickup, points vendeurs et points événementiels. J5S-A reste un socle admin sans impact panier/checkout.


## J5S-B — Panier/checkout points de remise

Après validation recette de J5S-A, le lot J5S-B active le choix client des points de remise dans le panier/checkout.

Le besoin initial vient des colliers de fleurs, qui peuvent être remis à l’accueil de la barge Petite-Terre ou à l’accueil passager de l’aéroport de Pamandzi. Le lot reste générique pour les futurs relais pickup et points fixes Hodina.

## J5T-A — Checkout première commande simplifié

- Ajout d’un parcours checkout invité simplifié.
- Le client non connecté ne saisit plus de mot de passe avant validation.
- Un compte est créé automatiquement et l’e-mail de commande contient un lien sécurisé pour définir le mot de passe.
- Le checkout connecté reste inchangé.
- Aucun changement de schéma.


## J5T-A-bis — Nettoyage checkout invité + corps e-mail première commande

Après test mobile du checkout invité simplifié, deux cases à cocher sans libellé ont été repérées avant le bloc “Qui recevra cette commande ?”. Elles correspondaient à des champs techniques encore rendus visiblement. Le lot les conserve dans le formulaire, mais les rend cachées pour préserver la soumission sans bruit UX.

Le journal e-mail `ORDER_CREATED` affichait également un corps `Null`, contrairement aux autres e-mails. Le service `OrderEmailService` journalise désormais un corps texte lisible contenant le récapitulatif de commande et le lien de création du mot de passe lorsque disponible.


# 27/06/2026 — Documentation J5T/J5U/J5V et cadrage J5W

## J5T-A / J5T-A-bis

Le formulaire checkout simple nouveau client a été validé en recette. Le parcours invité ne demande pas de mot de passe avant commande ; Hodina crée automatiquement le compte client et inclut dans `ORDER_CREATED` un lien sécurisé de création de mot de passe.

Le correctif J5T-A-bis a supprimé les cases parasites du checkout invité et a rétabli la journalisation du corps de `ORDER_CREATED`.

## J5S-B-bis

Le flux point de remise a été ajusté : le client indique lui-même sa date et son heure de rendez-vous. Les plages du point restent des horaires proposés et une validation serveur. Un incident `AlreadySubmittedException` a été corrigé en supprimant la modification `setData()` sur un formulaire déjà soumis.

## J5U-A

L’expéditeur de l’ensemble des e-mails commande/statut/collecte/code réception est devenu paramétrable dans EasyAdmin, groupe `Branding e-mail`. Le pilote utilise `commande@hodina.fr` comme expéditeur et copie interne `ORDER_CREATED`. Les tests recette ont confirmé l’envoi avec cette adresse.

## J5V-A

Le code source contient le délai minimum de commande par produit via `Product.minimumOrderLeadTimeHours`. La règle s’applique au point de remise, car c’est le seul flux où le client renseigne une date/heure explicite.

## Cadrage J5W

Nouveaux besoins exprimés : limiter un produit à certaines communes, découper les jours de livraison en sous-zones, permettre la livraison express hors créneau et permettre au livreur de proposer une heure différente pour un point de remise.

Décision historique avant J5W-A : ne pas casser `DeliveryZone`. Après J5W-A, la formulation est clarifiée : `DeliveryCommune.localPricingZone` porte le forfait local, `DeliveryCommuneConnection` et `DeliveryCommune.territory` protègent la barge, et les futures `DeliveryArea` serviront au planning et à l’exploitation.

Répartition initiale issue de Hodidagoni :

- `PT` : Dzaoudzi, Labattoir, Pamandzi — lundi/jeudi.
- `MAMOUDZOU_AGGLO` : Mamoudzou, Koungou, Dembéni — mercredi/samedi.
- `GT_SUD` : Bandrélé, Chirongui, Bouéni, Kani-Kéli, Sada — mercredi/samedi.
- `GT_NORD_CENTRE` : Acoua, Bandraboua, Mtsamboro, M'Tsangamouji, Tsingoni, Ouangani, Chiconi — mardi/vendredi.

Règle anti-régression : Mamoudzou, Sud et Nord/Centre restent en Grande-Terre pour la barge. Petite-Terre reste Petite-Terre. Labattoir reste une `DeliveryCommune` seedée.
## 2026-06-28 — J5S-B-ter : séparation point de remise / adresse standard

Suite aux tests recette du panier point de remise, une confusion UX a été identifiée : le panier affichait encore `Adresse de livraison utilisée` alors que le client avait choisi un point de remise. Le correctif J5S-B-ter sépare la source de vérité : en point de remise, le client voit le point choisi et les frais sont recalculés sur la commune du point ; en livraison standard, l’adresse client reste utilisée.

Le lot ne touche pas aux e-mails, SMS, statuts ni à Djama.

## 2026-06-28 — J5S-B-quater : feedback global checkout point de remise

Après J5S-B-ter, les tests mobiles ont montré une limite UX : quand une identité client, une date, une heure ou une contrainte de point de remise bloquait la validation, l’erreur apparaissait trop bas dans le formulaire et le bouton `Valider` restait visuellement vert. J5S-B-quater ajoute un feedback global sous le header et un état visuel désactivé pour les boutons de validation tant que les informations obligatoires simples ne sont pas complètes, y compris prénom, nom, téléphone et e-mail.

Le lot ne change pas les règles serveur : les contraintes métier restent vérifiées par Symfony. Après retour mobile, le feedback global client est corrigé pour n’apparaître qu’après une tentative de validation, et non dès l’arrivée sur la page. Aucun changement Doctrine, e-mail, Djama, SMS, statut ou calcul de frais n’est introduit.

## 2026-06-28 — J5S-B-quater-bis : masquage points optionnels et unité produit

Après les tests mobiles de J5S-B-quater, un point UX a été identifié sur les produits permettant les deux modes : quand le client sélectionnait `Livraison à mon adresse`, les cartes de points de remise restaient visibles. Cela créait une confusion entre livraison standard et remise en point Hodina. Le correctif masque désormais le panneau point de remise en mode standard et le réaffiche uniquement en mode point.

Le même lot améliore l’affichage produit côté client : l’unité de vente issue de `Product.unit` est visible dans le catalogue, la fiche produit et le panier. Aucun changement de calcul, aucune migration, aucun impact e-mail/SMS/Djama.


## 2026-06-28 — J5S-B-quater-ter/quater : timing, formulaire conditionnel et référence commande

Pendant les tests mobiles du checkout point de remise, plusieurs irritants ont été corrigés successivement :

- le message rouge global apparaissait dès l’arrivée sur le panier ; il est maintenant affiché seulement après une tentative de validation ou après retour serveur invalide ;
- les champs prénom, nom, téléphone et e-mail sont intégrés dans le feedback global et les messages serveur sont en français ;
- les produits autorisant standard + point masquent les points de remise lorsque `Livraison à mon adresse` est actif ;
- le bouton sticky et le bouton principal ne doivent pas bloquer silencieusement : si les champs simples manquent, ils affichent le message global ; si tout est complet, la soumission part au serveur ;
- une collision `customer_order.order_reference` a été corrigée par `OrderReferenceGenerator` via `MAX(dailyOrderNumber) + 1` et vérification d’existence ;
- `CheckoutType` a été corrigé pour ne plus exiger globalement `address` et `commune` en mode point de remise ; ces champs sont validés conditionnellement uniquement en mode standard ;
- `deliveryPointTimeWindowId` reste un champ technique non obligatoire, car la plage est déduite de l’heure demandée par le client.

État historique supersédé : la recette complète J5S-B-ter/quater restait à jouer à ce moment-là. Elle est ensuite annoncée validée le 28/06/2026 sous le tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628`.

## 2026-06-28 — J5T-C : checkout invité avec compte existant

Après la stabilisation du checkout point de remise / standard, une nouvelle règle est cadrée : si un client invité utilise un e-mail déjà connu de Hodina, la commande ne doit plus être bloquée ni provoquer de doublon client. Le serveur détecte le compte existant après validation métier du panier, affiche un popup de confirmation, puis rattache la commande au compte existant si le client confirme.

L’e-mail `ORDER_CREATED` est enrichi d’une mention explicite : `Cette commande a été rattachée à ton espace client Hodina.` Aucun changement de frais, Djama, SMS, statuts ou migration.

## 2026-06-28 — Pause J5T-C : diagnostic du checkout avec e-mail existant

Pendant J5T-C, le premier test avec e-mail nouveau a été annoncé comme passé. Le test avec e-mail existant a révélé deux régressions successives :

1. `AlreadySubmittedException` : le contrôleur tentait de modifier les données du formulaire après `handleRequest()`. La règle retenue est de piloter la popup avec des variables de vue et de ne pas appeler `setData()` sur un formulaire soumis.
2. Ancien comportement encore actif : le checkout affichait `Un compte existe déjà avec cette adresse e-mail. Connecte-toi...` au lieu du popup. La cause était un ancien bloc `addError()` dans `CheckoutController`.

Les sources transmises au moment de la pause montrent la logique cible : recherche d’un `Customer` existant par e-mail normalisé, affichage du popup via `showExistingAccountConfirmation`, confirmation liée à `confirmedExistingAccountEmail`, rattachement au compte existant et mention spécifique dans `ORDER_CREATED`.

État historique supersédé : J5T-C devait être repris à ce moment-là. Il est ensuite annoncé validé localement puis recette le 28/06/2026 sous le tag `recette-j5t-c-checkout-existing-account-20260628`. La production reste non actée.

## 2026-06-28 — Validation recette J5S-B-ter/quater, J5T-C et J5V-A

Les tests recette sont annoncés bons pour J5S-B-ter/quater et J5T-C.

J5S-B-ter/quater est validé sous le tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628`. Le checkout distingue correctement livraison standard et point de remise : en point, la commune du point sert aux frais et l’adresse client ne bloque pas ; en standard, l’adresse/commune client restent obligatoires et les points sont masqués.

J5T-C est validé localement puis recette avec le commit `38f9e23` et le tag `recette-j5t-c-checkout-existing-account-20260628`. Un invité peut commander avec un e-mail existant : aucun doublon `Customer`, aucune commande avant confirmation, rattachement au compte existant après popup et mention de rattachement dans `ORDER_CREATED`.

J5V-A est d’abord annoncé validé en local et recette, puis une régression est détectée : le champ produit et le service existaient, mais la validation serveur n’était plus appelée dans le checkout. Le correctif `3b508d0` rebranche `DeliveryPointCartService::validateMinimumOrderLeadTime()` dans `CheckoutController`. Recette validée sous `recette-j5v-a-checkout-lead-time-fix-20260628` avec blocage d’un rendez-vous trop proche sur produit à délai 48 h. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Production : validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` pour ces lots dans cette mise à jour.

## 2026-06-28 — Correctif J5V-A revalidé recette

Après validation fonctionnelle initiale, une régression est constatée : le champ `Product.minimumOrderLeadTimeHours` reste présent dans EasyAdmin et le service `DeliveryPointCartService::validateMinimumOrderLeadTime()` existe, mais la règle n’est plus appliquée au checkout.

Correctif appliqué : `3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout`. Le contrôleur checkout appelle de nouveau la validation serveur dans le flux point de remise, après validation du point, de la date, de l’heure et de la plage horaire.

Validation :

- dev local : produit à délai 48 h, rendez-vous trop proche refusé ;
- recette : même scénario validé sur `recette.hodina.fr` ;
- tag recette : `recette-j5v-a-checkout-lead-time-fix-20260628` ;
- production : non actée.

Décision conservée : ne pas déplacer la règle côté JavaScript, ne pas créer de migration, ne pas modifier standard/Djama/frais/barge.

## 2026-06-29 — Production checkout stabilisation J5S / J5T-C / J5U / J5V

Après validation recette complète du tag candidat `prod-candidate-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`, le tag production `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` est créé sur `d5466fe` et poussé.

La MEP production stabilise le bloc checkout : séparation standard / point de remise, checkout invité avec e-mail existant, expéditeur e-mail paramétrable et délai minimum produit corrigé. Les tests minimum production sont annoncés fonctionnels.

État après MEP : J5S-B-ter/quater, J5T-C, J5U-A et J5V-A sont validés production. J5W / `DeliveryArea` reste prévu/non codé.

Décision de reprise : ne pas lancer J5W avant d’avoir documenté la production. Cette règle est ensuite clarifiée par J5W-A : `DeliveryZone` / `DeliveryCommune.territory` gardent le rôle technique PT/GT, `DeliveryPricingZone` porte le forfait local, `DeliveryCommuneConnection` porte les liaisons/barge, et `DeliveryArea` reste réservé au planning opérationnel.


## 2026-06-29 — J5W-A zones tarifaires locales par secteur sur `develop`

Après la validation production du bloc checkout, une branche `develop` est créée depuis `main` pour éviter de réutiliser les anciennes branches `pilot/*`.

J5W-A démarre avec une décision importante : ne pas remplacer `PT` / `GT`. Le découpage tarifaire local est ajouté via `DeliveryPricingZone` et `DeliveryCommune.localPricingZone`, tandis que `DeliveryCommune.territory` continue à porter la séparation technique Petite-Terre / Grande-Terre.

Découpage local retenu :

- Mamoudzou → `MAMOUDZOU_LOCAL`
- Nord → `NORD_LOCAL`
- Centre → `CENTRE_LOCAL`
- Sud → `SUD_LOCAL`
- Petite-Terre → `PT_LOCAL` existant

Une première version avait créé un doublon `PETITE_TERRE_LOCAL`. Cette option est rejetée car elle crée une confusion admin avec `PT_LOCAL`. La donnée locale est corrigée : Dzaoudzi, Labattoir et Pamandzi pointent vers `PT_LOCAL`, et `PETITE_TERRE_LOCAL` est supprimée.

Un défaut Twig indépendant du calcul tarifaire est aussi détecté : le champ d’instructions point de remise apparaissait perdu en bas du panier standard. Il est replacé dans le bloc attendu.

État initial à ce moment-là : validation locale fonctionnelle actée sur `develop`, avant recette. Le garde-fou `tools/assert-j5w-a-local-pricing-zones.php` a été corrigé avant commit pour ne pas confondre une suppression défensive de `PETITE_TERRE_LOCAL` avec une création/rattachement réel. La recette a ensuite été validée sous `recette-j5w-a-local-pricing-zones-20260629`, puis la production sous `prod-j5w-a-local-pricing-zones-20260629`.



## 2026-06-29 — J5W-A validé recette

J5W-A est mergé dans `main` puis tagué recette sous `recette-j5w-a-local-pricing-zones-20260629`.

Commit recette : `162fcb4 merge(j5w-a): local pricing zones by sector`.

Contrôles serveur recette validés : dépôt propre, schéma Doctrine synchronisé, migration `Version20260629083000` exécutée/current/latest, garde-fou J5W-A OK.

Contrôles données validés : `PETITE_TERRE_LOCAL` absent ; `PT_LOCAL` conservé pour Dzaoudzi, Labattoir et Pamandzi ; Grande-Terre découpée en `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL`.

Tests fonctionnels recette annoncés OK : zones tarifaires, communes livrées, panier standard, champ instructions point de remise rangé au bon endroit.

État avant MEP production : J5W-A validé localement + recette. La production a ensuite été validée sous `prod-j5w-a-local-pricing-zones-20260629`.



## 2026-06-29 — J5W-A validé production

J5W-A est déployé en production sous `prod-j5w-a-local-pricing-zones-20260629` sur le commit `cea4d19`.

Déploiement réalisé via `tools/deploy-hodina-by-tag.sh --target prod` avec backup base, environnement et uploads runtime.

Contrôles production validés : dépôt propre, tag positionné, schéma Doctrine synchronisé, migration `Version20260629083000` current/latest, garde-fou J5W-A OK, `PETITE_TERRE_LOCAL` absent, zones tarifaires locales présentes et mapping Petite-Terre conservé sur `PT_LOCAL`.

Tests navigateur production annoncés OK : EasyAdmin zones tarifaires, EasyAdmin communes livrées, panier standard sans champ point de remise perdu.

État : J5W-A validé localement + recette + production.

## 2026-06-29 — Nettoyage documentaire après production J5W-A

Après la MEP production, les anciens états intermédiaires avant MEP sont reformulés pour ne plus être lus comme l’état courant. Les anciennes formulations sur la responsabilité tarifaire de `DeliveryZone` sont également précisées : depuis J5W-A, le forfait local est porté par `DeliveryCommune.localPricingZone` / `DeliveryPricingZone`, tandis que `DeliveryZone` et `DeliveryCommune.territory` restent des garde-fous techniques PT/GT.

## 2026-06-29 — J5X-A tarifs zones tarifaires préparé sur `develop`

Après validation production de J5W-A, J5X-A démarre avec un périmètre volontairement limité : mettre à jour les frais client par zone tarifaire sans changer la formule logistique.

Tarifs cibles décidés : Petite-Terre 12 €, Mamoudzou 12 €, Centre 17 €, Sud 21 €, Nord 21 €, `GT_LOCAL` 21 € comme fallback technique.

Le lot ajoute une migration de données `Version20260629141000`, un garde-fou `tools/assert-j5x-a-delivery-pricing-zones.php` et une aide EasyAdmin plus explicite dans `DeliveryPricingZoneCrudController`.

Décision maintenue : les frais de livraison restent calculés par `DeliveryLogisticsService` depuis `DeliveryCommune.localPricingZone`, les liaisons `DeliveryCommuneConnection` et les suppléments existants. J5X-A ne modifie pas les jours de livraison, la fiche produit, le catalogue, les produits sur créneau ni la disponibilité produit par commune.

État : implémentation préparée, validation locale à faire avant merge `main`, recette et production.

## 2026-06-29 — J5X-B — Calendrier livraison paramétrable par secteur

Après commit J5X-A `974e2df`, démarrage de J5X-B sur `develop`. Le calendrier standard est porté par `DeliveryPricingZone` afin de rester aligné avec les secteurs de livraison et la tarification J5W-A/J5X-A.

Calendriers cibles : Petite-Terre lundi/jeudi, Mamoudzou mercredi/samedi, Sud mercredi/samedi, Nord mardi/vendredi, Centre mardi/vendredi, cutoff 10h J-1.

État : livré pour validation locale ciblée, non validé recette, non validé production.

## 2026-06-29 — J5X-C — Promesse produit / produits sur créneau

Après validation locale ciblée de J5X-B sur `develop`, J5X-C démarre pour rendre la fiche produit plus rassurante avant l’ouverture publique.

Décision UI/UX retenue : si la commune est connue, afficher uniquement la promesse pertinente ; si elle est inconnue, afficher un résumé et un tableau repliable. Les produits broche de jasmin, collier de fleurs, accueil aéroport ou événement sont traités comme produits sur créneau, pas comme de simples produits “livrables tous les jours”.

Le lot ajoute `ProductDeliveryPromiseService`, un DTO dédié et des champs sur `Product`. Il ne modifie pas la formule tarifaire, ne remplace pas J5V-A et ne touche pas encore au catalogue recherche/filtres/tri.

## 2026-06-29 — J5X-C-bis — Clarification formulaire produit

Pendant le test EasyAdmin de J5X-C, une confusion UX est repérée : les champs de plage indicative des produits sur créneau et les plages horaires du nouveau point de remise apparaissent trop proches dans le formulaire produit.

Correction : le bloc J5X-C est renommé en `Fiche produit — message de livraison client`, les plages produit deviennent des plages indicatives, et les points de remise passent dans des sections `Avancé — points de remise`. La logique métier n’est pas modifiée : J5X-C reste un affichage fiche produit, tandis que les plages de point de remise restent des `DeliveryPointTimeWindow`.

### J5X-D — Catalogue recherche, filtres, tri et priorité admin

Après stabilisation de J5X-A/B/C sur `develop`, le catalogue public est enrichi pour préparer l’ouverture Hodina : recherche, filtre catégorie, tri, priorités administrables et AJAX progressif.

Décision importante : ce lot ne touche pas à la livraison. Les frais, calendriers de passage et promesses produit restent portés par les lots J5X-A/B/C.

## 2026-06-30 — J5X groupé déployé en recette

Les lots J5X-A/B/C/D sont regroupés pour recette sous `recette-j5x-livraison-catalogue-20260630-1440`.

J5X-A ajuste les tarifs zones : PT 12 €, Mamoudzou 12 €, Centre 17 €, Sud 21 €, Nord 21 €, GT fallback 21 €, sans changer la formule logistique.

J5X-B ajoute le calendrier de livraison par secteur dans `DeliveryPricingZone` et `DeliveryScheduleService`.

J5X-C ajoute la promesse produit et les produits sur créneau, en distinguant clairement message fiche produit et vraie logistique point de remise.

J5X-D améliore le catalogue : recherche, filtre catégorie, tri, ordre Hodina et AJAX progressif.

État : déployé recette, validation navigateur complète encore à terminer avant production.

## 2026-06-30 — J5Y-A interface guidée plages horaires point de remise

Une première tentative basée sur un template EasyAdmin fragile ne rendait pas correctement l’interface. Le correctif J5Y-A-bis branche réellement le formulaire via `row_attr`, un textarea caché et un contrôleur Stimulus.

L’admin peut créer des lignes lisibles : libellé, jours concernés, heure début, heure fin. Les raccourcis `Jours ouvrés` et `Jours ouvrables` sont ajoutés pour éviter la saisie technique.

Commit connu : `b2087c4 feat(j5y-a): improve delivery point time window admin form` sur `develop`.

État historique au moment du lot : validé localement, non recette, non production. Statut supersédé par la validation recette J5Y du 01/07/2026.

## 2026-06-30 — J5Y-B créneaux panier point de remise par demi-heure

Le panier point de remise ne doit plus laisser le client taper une heure libre. J5Y-B génère des créneaux de 30 minutes depuis les plages du point choisi. Un créneau ne peut pas démarrer à l’heure de fin de plage.

Une première version avait la logique mais pas un select visible. J5Y-B-bis rend le select fiable. J5Y-B-ter harmonise la date et le créneau pour éviter un rendu formulaire brut.

Commits connus :

```text
1511e0b feat(j5y-b): add half-hour delivery point slot selection
2fc1f3e docs(j5y-b): clean trailing whitespace
```

État historique au moment du lot : validé localement, non recette, non production. Statut supersédé par la validation recette J5Y du 01/07/2026.

## 2026-06-30 — J5Y-C catalogue en homepage et Découvrir Hodina

Décision : le catalogue devient `/`. L’ancienne homepage est déplacée et enrichie sur `/decouvrir-hodina`. `/catalogue`, `/blog/decouvrir-hodina` et `/blog` restent redirigés pour éviter les liens cassés.

La page Découvrir Hodina parle désormais aux futurs clients, vendeurs et livreurs. Le futur contenu éditorial est présenté comme une rubrique à venir, sans appeler la page publique actuelle `Blog` ou `Le Carnet Hodina`.

Le lien `Catalogue` a été retiré du header public car il devenait redondant avec le logo et la homepage.

État historique au moment du lot : tests techniques locaux OK et validation visuelle locale positive, non recette, non production. Statut supersédé par la validation recette J5Y du 01/07/2026.

## 2026-06-30 / 2026-07-01 — J5Y-D logo header et favicon

Le logo header était trop petit parce que la version carrée était compressée dans l’en-tête. Une version horizontale `logo_hodina_header.png` est ajoutée et jugée plus lisible.

Le favicon a connu plusieurs itérations : version trop petite, version avec carré blanc rejetée, version transparente testée, puis génération séparée de fichiers 16x16 et 32x32 depuis deux images fournies.

Décision de reprise : ne pas confondre ces tests visuels avec une validation recette. Avant tag J5Y, il faudra soit choisir/appliquer le favicon final, soit le sortir explicitement du périmètre bloquant.

État historique au moment du lot : logo header local validé visuellement ; favicon en arbitrage ; non recette, non production. Statut supersédé par la validation recette J5Y-D-ter et le logo optimisé sous le tag clean du 01/07/2026.

# 2026-07-01 — J5Y-E/F/G/H : Carnet, livraison, footer et validation recette

## Contexte

Après validation de la homepage catalogue, du logo header et de la page Découvrir Hodina, la navigation publique a été affinée pour mieux répondre aux questions réelles des clients : comment Hodina livre, quelles communes sont concernées et comment vérifier les frais/dates au panier.

## Actions réalisées

- `/decouvrir-hodina` devient la route canonique de la page institutionnelle.
- `/blog` et `/blog/decouvrir-hodina` deviennent des redirections legacy.
- Création de `/carnet` comme espace pédagogique Hodina.
- Création de `/carnet/livraison` comme première page du Carnet.
- Ajout de 4 visuels WebP de zones de livraison : Petite-Terre, Mamoudzou, Nord/Centre, Sud.
- Simplification du texte de la page livraison pour éviter les répétitions.
- Header public orienté `Infos livraison`.
- `Découvrir Hodina` déplacé dans le footer.
- Footer réassurance rendu plus compact après test visuel.
- Assert `tools/assert-j5y-f-carnet-livraison.php` ajouté puis réaligné.
- Nettoyage d’un backup de template `.bk` embarqué par erreur avant le tag propre.

## Commits / tags

- `0a5cf84 feat(j5y): add Carnet delivery guide and public navigation`.
- `019420b docs(j5y): document Carnet and public delivery guide`.
- `1051263 merge: j5y carnet delivery guide and public navigation`.
- `b1bbab6 chore(j5y): remove delivery guide backup template`.
- Tag supersédé : `recette-j5y-carnet-livraison-footer-20260701`.
- Tag recette propre validé : `recette-j5y-carnet-livraison-footer-clean-20260701`.

## Validation recette

MEP recette réalisée avec succès sur `recette-j5y-carnet-livraison-footer-clean-20260701`, commit `b1bbab6`.

Contrôles automatiques : checkout tag, working tree propre, sauvegarde DB, migrations à jour, assets compilés, cache warmup, Doctrine schema OK, cron Messenger OK.

Tests navigateur recette annoncés validés : `/`, `/decouvrir-hodina`, redirections `/blog*`, `/carnet`, `/carnet/livraison`, catalogue AJAX, panier standard, point de remise, GPS, admin/livreur minimal.

## Décisions retenues

- Le Carnet existe désormais, mais reste limité à des contenus utiles au MVP.
- Les autres rubriques Carnet restent `À venir` tant que leur contenu réel n’est pas développé.
- La livraison est expliquée publiquement, mais le panier reste la source de vérité.
- Djama reste privé.
- Ne plus modifier J5Y avant production sauf bug bloquant.

## Production

MEP production réalisée et validée le 01/07/2026.

Tag production :

```text
prod-j5y-carnet-livraison-footer-20260701
```

Commit production :

```text
200d84b merge: document j5y recette validation
```

Le script de déploiement production a confirmé le checkout propre du tag, la sauvegarde DB, la restauration des uploads runtime, la compilation des assets, le warmup cache, le schéma Doctrine synchronisé, les migrations à jour, le cron Messenger et l’URL publique `https://hodina.fr` en HTTP 200.

Tests navigateur production annoncés validés.

État final : J5Y-A/B/C/D/E/F/G/H est clos pour le MVP public. Ne plus modifier ce périmètre sauf bug bloquant.

# 2026-07-02 — J5Z : checkout/admin UX, frais expliqués et validation production

## Contexte

Après clôture J5Y, un mini-lot J5Z a été ouvert avant recette puis production pour corriger plusieurs irritants réels du MVP : saisie téléphone, ordre du formulaire produit, explication des frais de livraison, lisibilité mobile et cohérence AJAX du panier.

La ligne de conduite a été de ne pas rouvrir le noyau panier/logistique : les évolutions ajoutent de la lisibilité et des garde-fous sans changer les règles tarifaires validées.

## Actions réalisées

### J5Z-A — UX formulaire produit EasyAdmin

Le formulaire Produit EasyAdmin est réordonné pour placer les champs opérationnels immédiatement après `Marge produit Hodina (%)` : stock illimité, stock, unité de vente, description, précommande, jours fabrication, mode de remise au client, jours livraison puis délai minimum.

Décision UX : l’admin doit renseigner rapidement les informations qui conditionnent la vente, la disponibilité et la remise au client, sans chercher ces champs plus bas dans le formulaire.

### J5Z-B — Phrase catalogue

La phrase catalogue est clarifiée :

```text
Choisis tes produits et ajoute-les au panier. Avant de valider, Hodina t’indique les frais, les jours de livraison possibles et les créneaux disponibles selon ta commune.
```

Décision marketing : éviter le mot interne `passages`, éviter de laisser croire que les jours sont garantis, et rappeler que le panier reste la source de vérité.

### J5Z-C — Téléphone client avec indicatif explicite

Un champ `Indicatif` est ajouté avant le téléphone en inscription et checkout invité. Les choix initiaux sont : Mayotte / La Réunion `+262`, France métropolitaine `+33`, Comores `+269`, Madagascar `+261`.

Le premier patch envisagé déduisait trop de choses depuis le numéro (`0639`, `06`, etc.). Il a été abandonné car il ne couvrait pas correctement les numéros fixes et les cas hors mobile Mayotte/métropole. La décision finale est plus robuste : l’utilisateur choisit l’indicatif, puis Hodina assemble le numéro.

Le service `PhoneNumberNormalizer` porte cette règle. La commande `hodina:customers:normalize-phones` reste séparée pour le rattrapage des anciennes données connues. En recette, la simulation puis l’application ont modifié 84 numéros, 0 non normalisable.

### J5Z-D — Annotation et flash frais livraison

Un service `DeliveryFeeReasonFormatter` factorise l’annotation des frais :

```text
Inclus : barge.
Inclus : 1 commune traversée + barge.
Inclus : X communes traversées + barge.
Inclus : plusieurs communes de collecte + barge.
```

Décision marketing : utiliser `commune traversée`, plus parlant pour un client non initié, plutôt que `liaison terrestre`, trop technique.

Le libellé initial `Peut inclure` a été remplacé par `Inclus`, car le système sait détecter les cas de barge ou de communes traversées. En revanche, si le trajet est simple, aucune annotation n’est affichée. Exemple validé : produit Petit Labattoir, vendeur Petite-Terre / Labattoir = pas d’annotation.

Un message flash supprimable est ajouté quand un changement d’adresse recalcule les frais :

```text
Frais de livraison mis à jour
Selon ta nouvelle adresse, Hodina a recalculé les frais. Le détail apparaît sous “Frais de livraison”.
```

Le message a été déplacé en haut du panier, rendu opaque en marron clair et masquable par une croix. Le placement initial dans la section total était trop discret.

### J5Z-E — Correctifs mobile panier

Sur mobile, le champ `Date de rendez-vous` débordait dans certains parcours, surtout Safari/iPhone. Le correctif a d’abord couvert le checkout invité, puis a été étendu au client connecté. Un assert dédié `assert-delivery-point-date-field-mobile-width.php` protège le comportement.

Un champ `Indicatif` parasite apparaissait en bas du panier pour les clients connectés. Cause : le champ `phoneCountryCode` était rendu automatiquement par `form_end(form)` car il n’était pas consommé dans le bloc caché. Le champ est désormais rendu dans le bloc caché du parcours connecté.

### J5Z-F — Hotfix annotation après refresh AJAX

En production, l’annotation frais était absente au premier affichage pour certaines sessions connectées, puis apparaissait après changement d’adresse. Le cache `cart_logistics_preview` ne portait pas encore la nouvelle donnée d’annotation. Le cache de prévisualisation logistique est donc versionné avec `LOGISTICS_PREVIEW_CACHE_VERSION = j5z-delivery-fee-reason-v1`.

Un second bug a été corrigé : après changement d’adresse AJAX, le flash apparaissait mais l’annotation disparaissait jusqu’au rafraîchissement de la page. La réponse AJAX renvoie maintenant explicitement `deliveryFeeReason`, et le JavaScript l’utilise avant de recourir à un fallback.

## Tags et validations

Tag recette initial supersédé :

```text
recette-j5z-checkout-admin-ux-20260702
```

Tag recette mobile supersédé :

```text
recette-j5z-checkout-admin-ux-fix-mobile-20260702
```

Tag recette final validé :

```text
recette-j5z-delivery-fee-reason-refresh-20260702
```

Tag production final :

```text
prod-j5z-delivery-fee-reason-refresh-20260702
```

Commits structurants :

```text
b58cc91 ux(admin): reorder product form operational fields
780306f content(catalogue): clarify delivery information message
f6b99b1 ux(checkout): add phone dial code selector and delivery fee reason
4a16c15 ux(checkout): explain recalculated delivery fees
9ca4df7 fix(cart): polish delivery point and phone prefix mobile UI
ed2e873 fix(cart): keep delivery fee reason after logistics refresh
09243d2 merge: fix j5z delivery fee reason refresh
```

Tests locaux, recette et production annoncés validés. J5Z est clos fonctionnellement.

# 2026-07-02 — Réflexion J5AA : AddressLocality et extension contrôlée du MVP

## Contexte stratégique

Le MVP Hodina fonctionne et ne doit pas être réécrit. La stratégie retenue est d’étendre le socle par petits modules contrôlés, sans refaire les fonctionnalités validées production.

Règle d’architecture retenue :

```text
On ne réécrit pas une fonctionnalité validée.
On l’encadre, on la documente, on la teste, puis on ajoute autour.
```

## Problématique terrain

À Mayotte, une commune comme Mamoudzou contient plusieurs localités connues : Kavani, Kawéni, Mtsapéré, Passamaïnty, Vahibé, Tsoundzou I, Tsoundzou II. Pour le client et le livreur, la commune seule est insuffisante.

## Décision de nommage

Le nom `DeliveryVillage` est rejeté car trop lié à la livraison et à Mayotte. La donnée peut aussi concerner une adresse de facturation, une adresse de retrait vendeur ou une adresse hors Mayotte.

Décision retenue :

```text
Entité future : AddressLocality
Libellé UI : Localité
Aide affichée : Village / quartier / lieu-dit
```

## Règles prévues

- `AddressLocality` précise l’adresse, mais ne remplace pas la commune.
- Si une localité connue est sélectionnée, Hodina peut préremplir la commune associée.
- Si une localité est tapée librement mais non reconnue, elle est conservée comme précision terrain, sans déduction automatique de commune.
- `DeliveryCommune` reste la seule source de vérité pour les frais, la barge, les jours et les créneaux.
- Le champ doit être extensible : livraison, facturation, retrait vendeur et futurs territoires.

État : réflexion et décision d’architecture validées ; non codé, non recette, non production.

# 2026-07-03 — J5AB : Catalogue mobile orienté achat

## Contexte

Après J5Z, le catalogue est déjà la homepage. Un retour terrain signale que sur mobile le client voit trop de contenu institutionnel avant les produits.

## Décision

Le catalogue devient pleinement une page d’achat :

```text
Header Hodina
Recherche + loupe + Filtres
Compteur produits
Produits
Footer
```

Le contenu pédagogique Hodina reste disponible dans `/decouvrir-hodina`, le footer et le Carnet.

## Réalisation

Fichiers principaux :

- `templates/product/catalogue.html.twig` ;
- `templates/product/_catalogue_filters.html.twig` ;
- `public/css/style_mobile.css` ;
- `tools/assert-j5ab-catalogue-mobile-buy-first.php` ;
- `tools/assert-j5y-c-homepage-catalogue-discover.php`.

Le moteur catalogue J5X-D n’est pas modifié. Aucune pagination n’est ajoutée.

## Validation

```text
Commit : bab469e feat(j5ab): compact mobile catalogue filters
Tag recette : recette-j5ab-catalogue-mobile-achat-20260703
Tag production : prod-j5ab-catalogue-mobile-achat-20260703
Statut : validé local + recette + production
```

Tests validés : lint Twig catalogue, asserts J5X-D/J5Y/J5AB, contrôles HTTP `/`, `/catalogue`, `/decouvrir-hodina`, validation mobile visuelle.

# 2026-07-03 — J5AC : Finalisation espace client avec AJAX discret

## Contexte

Le suivi de commandes client existait déjà depuis J5R. Le manque réel était un hub compte, un profil modifiable, une sécurité mot de passe et une navigation plus fluide.

## Réalisation fonctionnelle

J5AC ajoute ou stabilise :

- `/mon-compte` comme hub compte ;
- `/mon-compte/commandes` et détail commande existants ;
- `/mon-compte/profil` ;
- `/mon-compte/mot-de-passe` ;
- `POST /mon-compte/mot-de-passe/lien-reinitialisation` ;
- navigation compte compacte ;
- AJAX progressif discret dans `/mon-compte/*`.

Le suivi commande n’est pas refait. Les règles propriétaire, exclusion `DRAFT` et non-affichage du code de réception restent protégées.

## Décision DB

Avant modification profil, l’email client est sécurisé :

- `customer.email` unique nullable ;
- `customer.phone` non unique ;
- emails vides convertis en `NULL` ;
- emails normalisés ;
- migration bloquée si doublons ;
- migration `Version20260703093000` non transactionnelle (`isTransactional(): false`) pour éviter le warning MariaDB/MySQL.

## Validation recette

Tag recette initial :

```text
recette-j5ac-espace-client-ajax-20260703
Commit : 60d3dee
```

La recette fonctionnelle est validée, mais un warning Doctrine lié aux commits implicites de migration est observé.

Correction propre :

```text
Commit : 0966429 fix(j5ac): mark email migration non transactional
Tag recette v2 : recette-j5ac-espace-client-ajax-v2-20260703
```

La recette v2 est validée : migration déjà à jour, schema synchronisé, asserts J5AC/J5AC-B/J5AC-DB OK.

## Validation production

Tag production :

```text
prod-j5ac-espace-client-ajax-20260703
Commit : 0966429
```

Avant migration production, l’audit DB montre :

```text
9 customers
0 email null
0 email vide
0 doublon email normalisé
1 email invalide simple : customer.id=13, chahere.kdu
```

La migration est appliquée avec succès car l’unicité n’est pas compromise. Après MEP, la donnée invalide isolée est corrigée :

```text
customer.id=13
chahere.kdu → chahere.kdu@outlook.fr
```

Contrôles finaux production :

- `assert-j5ac-customer-email-db-readiness.php` OK ;
- `assert-j5ac-client-account-finalization.php` OK ;
- `assert-j5ac-client-account-ajax.php` OK ;
- `doctrine:schema:validate --env=prod` OK ;
- tests navigateur production OK.

## État final

J5AC est clos production. Ne pas rouvrir sauf bug bloquant. Les futures améliorations de compte client doivent rester séparées du checkout, de Djama et du calcul livraison.

# 2026-07-04 — Alignement documentation avant J5AA

Une revue documentaire a relevé une incohérence dans `TODO.md` : un ancien bloc présentait encore `Portail client MVP` comme prochaine priorité alors que le code actuel et la documentation J5AC montrent que l’espace client est finalisé en production.

Corrections documentaires :

- `/mon-compte` est désormais documenté comme hub compte client, et non comme redirection MVP.
- `/mon-compte/profil`, `/mon-compte/mot-de-passe` et le reset connecté sont marqués faits.
- `/mon-compte/adresses` reste explicitement non codé.
- Le carnet `Address` utilisé par panier/checkout est distingué de la future page autonome d’adresses.
- Le cadrage J5AA est complété avec le sujet code postal / commune cohérents avec le seed.

État après correction : J5AA reste prévu/non codé. Il ne doit pas réécrire le panier, le checkout, J5AB, J5AC ou J5Z.

# 2026-07-04 — J5AA codé et validé recette + production

Après l’alignement documentaire ci-dessus, J5AA a été implémenté en trois sous-lots puis déployé recette et production le même jour.

- **J5AA-0** (`d34242a`) — Audit strict des communes de livraison : ajout de `tools/assert-j5aa-delivery-address-commune-audit.php` (read-only). Aucune migration, aucune entité, aucun champ. Garde-fou avant les évolutions.
- **J5AA-B** (`8f79fee`) — Sécurisation du couple code postal / commune au checkout : contrôle serveur strict dans `CheckoutController`, `CartController`, `CheckoutType` et `DeliveryCommuneMatcherService`, plus l’aperçu AJAX des frais. Sans migration. Persistance inchangée (`Address.postalCode` / `Address.commune`).
- **J5AA-A** (`7bb9c10`) — Localité d’adresse : entité `AddressLocality` + repository, `Address.addressLocality` / `Address.localityText`, snapshot `CustomerOrder.deliveryAddressLocalityName`, CRUD EasyAdmin, commande idempotente `hodina:address-localities:seed` (seed initial Mamoudzou), champ optionnel `Localité` au checkout. Migration `Version20260704210000`.

Invariants respectés : `DeliveryCommune` reste la seule source de vérité logistique/tarifaire ; la localité et le code postal ne calculent jamais frais/barge/jours/créneaux ; pas de `Address.deliveryCommune`.

Validation : recette puis production le 2026-07-04.

Tags : `recette-j5aa-address-locality-20260704`, `prod-j5aa-address-locality-20260704`.

# 2026-07-05 — Réalignement documentaire post-J5AA

Correction d’une incohérence : plusieurs fichiers de suivi indiquaient encore « J5AA prévu / non codé / non recette / non production » alors que J5AA était déployé en production depuis le 2026-07-04. Fichiers corrigés : `TODO.md`, `PILOT_STATUS_DETAILED.md`, `DEPLOIEMENT_PREPROD.md`, `HISTORIQUE.md`, et annotation des sections de planification supersédées dans `ARCHITECTURE.md`, `ENTITIES.md`, `WORKFLOWS.md`. Ajout du fichier racine `CLAUDE.md` (guide de travail pour l’assistant, dont la procédure de mise à jour docs).
