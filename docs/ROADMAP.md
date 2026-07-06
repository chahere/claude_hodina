# Roadmap Hodina

## Historique conservé

État initial du document de référence :

```text
J4 en cours : OrderItem + actions admin + SmsLog automatiques.
```

Cet historique est conservé pour garder la trace du point de départ : J4 avait pour objectif de rendre le backoffice commandes réellement opérationnel, en partant de l'affichage des lignes de commande, des actions admin et des SmsLog automatiques.

---

## Vision de progression

Hodina avance par jalons courts, testables et orientés terrain.

Le principe retenu est :

1. construire un socle fonctionnel simple ;
2. tester immédiatement dans l'interface ;
3. sécuriser les règles métier ;
4. documenter les décisions ;
5. préparer le jalon suivant sans surdimensionner.

---

# J1 à J3 — Socle initial client / commande

## Objectif général

Mettre en place les bases de la marketplace pilote : entités principales, backoffice initial, tunnel client minimal, panier, adresse et création de commande.

## Éléments acquis avant J4

- Symfony utilisé comme socle applicatif.
- Doctrine ORM utilisé pour le mapping entités / base.
- MariaDB retenue comme base de données.
- EasyAdmin utilisé pour le backoffice interne.
- Twig utilisé pour le rendu front.
- PWA mobile-first prévue pour l'usage terrain.
- Entités principales créées : Customer, Address, CustomerOrder, OrderItem, Seller, Product, Category, DeliveryZone, SmsLog.
- Checkout client capable de créer une commande.
- Address mis à jour avec `postalCode` obligatoire.
- Paiement manuel retenu pour le pilote.
- Validation admin obligatoire retenue avant traitement réel de la commande.

---

# J4 — Backoffice commandes opérationnel

## Statut

**Terminé, testé, validé et commité.**

J4 a transformé le backoffice commandes d'un simple espace de consultation en un véritable outil de traitement opérationnel.

## Objectif J4

Permettre à l'administrateur, notamment l'admin terrain à Mayotte, de :

- comprendre rapidement une commande ;
- voir son contenu ;
- suivre son client ;
- changer son statut ;
- créer des traces SmsLog ;
- disposer d'un numéro métier de commande ;
- utiliser une fiche terrain mobile ;
- gérer certains réglages Hodina depuis EasyAdmin.

## Fonctionnalités livrées

### Commandes et lignes de commande

- Affichage des commandes clients dans EasyAdmin.
- Affichage des lignes de commande.
- Relation `CustomerOrder -> OrderItem` opérationnelle.
- CRUD EasyAdmin `OrderItemCrudController` créé.
- Menu EasyAdmin enrichi avec les lignes de commande.
- Affichage produit, vendeur, quantité, prix unitaire et total ligne.

### Workflow admin

Workflow J4 validé :

```text
PENDING_VALIDATION
→ CONFIRMED
→ PREPARING
→ READY_FOR_PICKUP
→ DELIVERED
```

Annulation validée :

```text
PENDING_VALIDATION → CANCELED
CONFIRMED → CANCELED
```

Actions admin validées :

- Valider commande.
- Annuler commande.
- Passer en préparation.
- Marquer prête.
- Marquer livrée.

### Dates métier

Champs métier ajoutés / utilisés :

- `confirmedAt`
- `preparingAt`
- `readyAt`
- `deliveredAt`
- `canceledAt`

Ces champs permettent de tracer les étapes réelles de traitement.

### SmsLog automatiques

SmsLog générés automatiquement lors des étapes importantes :

- création de commande ;
- validation admin ;
- passage en préparation ;
- commande prête ;
- commande livrée ;
- commande annulée.

Les messages commencent par :

```text
Gégé {prénom client}, ...
```

Ils contiennent le numéro métier de commande.

### SmsLog en lecture seule

Les SmsLog sont désormais considérés comme des traces système.

Décision appliquée :

- suppression de l'ajout manuel ;
- suppression de la modification ;
- suppression de la suppression ;
- consultation uniquement ;
- détail SmsLog disponible.

### Envoi SMS manuel depuis iPhone

Décision ajoutée en fin J4 : tester un bouton `Envoyer le SMS` dans chaque SmsLog.

Objectif :

- ouvrir l'application SMS de l'iPhone ;
- préremplir le numéro du client ;
- préremplir le message avec le contenu du SmsLog.

Plan A retenu :

```text
sms:{numero_client}&body={message}
```

Plan B documenté si le préremplissage du message ne fonctionne pas selon iOS / navigateur / PWA :

- bouton `Copier le message` ;
- bouton `Envoyer le SMS` avec uniquement le numéro ;
- collage manuel dans l'application Messages.

### Numéro métier de commande

Numéro généré automatiquement selon la forme :

```text
{préfixe}{AAAAMMJJ}{numéro du jour}
```

Exemple :

```text
hodina202606041
hodina202606042
```

Décisions :

- ne pas se limiter à l'ID technique Doctrine ;
- afficher un numéro métier lisible côté client et côté admin ;
- inclure le numéro dans les SmsLog ;
- générer un numéro si une ancienne commande n'en possède pas encore.

### Réglages Hodina

Un espace `Réglages Hodina` a été créé dans EasyAdmin.

Évolution importante : passage d'une logique :

```text
1 ligne = tous les réglages Hodina
```

vers :

```text
1 ligne = 1 paramètre
```

Réglages actuels :

- `order_reference_prefix` : préfixe des numéros de commande ;
- `delivered_communes` : communes livrées.

### Communes livrées

Le paramètre `delivered_communes` est administrable depuis EasyAdmin.

Décisions :

- ne pas saisir les communes sous forme de texte à virgules ;
- utiliser une interface avec une commune par champ ;
- ajouter un bouton `Ajouter une commune` ;
- permettre la suppression commune par commune ;
- stocker proprement la liste ;
- conserver la compatibilité avec les anciennes valeurs saisies avec virgules.

### Fiche terrain commande

Ajout d'une fiche terrain destinée à faciliter le traitement sur mobile.

Elle met en avant :

- numéro de commande ;
- statut ;
- paiement ;
- client ;
- téléphone ;
- adresse ;
- zone ;
- articles ;
- total ;
- dates métier ;
- actions disponibles.

### Login client

Le formulaire de connexion client a été amélioré :

- passage en français ;
- meilleure disposition ;
- bouton `Créer mon compte Hodina` ;
- lien retour catalogue ;
- rendu plus propre pour le pilote.

## Tests J4 validés

Validé et testé :

- création d'une commande côté client ;
- affichage de la commande en backoffice ;
- génération du numéro métier ;
- validation admin ;
- passage en préparation ;
- passage en prête ;
- passage en livrée ;
- annulation ;
- SmsLog à chaque étape ;
- affichage du numéro dans les messages ;
- SmsLog en lecture seule ;
- modification du préfixe en condition réelle ;
- modification des communes livrées ;
- anciennes commandes ;
- contrôle Doctrine ;
- contrôle Git ;
- commit propre.

---

# J4 fin — Décision structurante avant J5

## Décision métier

Le portail livraison utilisera le **modèle B** :

```text
Le livreur voit les commandes prêtes et les prend lui-même.
```

Cela signifie :

- une commande devient disponible pour les livreurs lorsqu'elle est en `READY_FOR_PICKUP` ;
- le livreur se connecte à son espace ;
- il voit les commandes prêtes ;
- il prend en charge une commande ;
- la commande passe en `OUT_FOR_DELIVERY` ;
- le livreur la marque ensuite comme livrée.

## Clarification importante

Le portail livreur ne sera pas un CRUD EasyAdmin.

La formulation correcte est :

```text
Le portail livreur sera un dashboard authentifié dédié, mobile-first, séparé du backoffice EasyAdmin admin.
```

Il sera donc bien un dashboard, mais conçu pour les livreurs et limité à leurs besoins terrain.

---

# J5 — Logistique & Livraison

## Statut

**À démarrer.**

## Objectif J5

Créer un dashboard livreur authentifié permettant aux livreurs de :

- voir les commandes prêtes à livrer ;
- prendre eux-mêmes une commande ;
- passer la commande en cours de livraison ;
- consulter les informations utiles ;
- appeler ou contacter le client par SMS ;
- marquer la commande comme livrée.

## Décision technique majeure J5

Avant de coder le portail livreur, il faut éviter de dupliquer la logique de changement de statut déjà créée pour l'admin.

Décision : créer un service métier commun :

```text
src/Service/CustomerOrderWorkflowService.php
```

Ce service centralisera :

- vérification des transitions ;
- changement de statut ;
- remplissage des dates métier ;
- création des SmsLog ;
- génération du numéro de commande si absent ;
- sauvegarde Doctrine ;
- logique commune admin / livreur.

## Architecture J5 cible

```text
CustomerOrderWorkflowService
├── confirm()
├── cancel()
├── markPreparing()
├── markReady()
├── takeForDelivery()
├── markDelivered()
├── canConfirm()
├── canCancel()
├── canPrepare()
├── canMarkReady()
├── canTakeForDelivery()
├── canMarkDelivered()
└── createSmsLog()
```

Utilisation côté admin :

```text
CustomerOrderCrudController
→ utilise CustomerOrderWorkflowService
```

Utilisation côté livreur :

```text
CourierDashboardController
→ utilise CustomerOrderWorkflowService
```

## J5.1 — Refactoring workflow commande

Objectif : sortir la logique métier du contrôleur EasyAdmin.

À faire :

- créer `CustomerOrderWorkflowService` ;
- déplacer la logique de changement de statut ;
- déplacer la création des SmsLog ;
- déplacer la mise à jour des dates métier ;
- déplacer les règles de transition ;
- adapter `CustomerOrderCrudController` pour appeler le service ;
- retester tout le workflow J4 après refactoring.

## J5.2 — Préparation livraison

À faire :

- exploiter `STATUS_OUT_FOR_DELIVERY` ;
- ajouter `outForDeliveryAt` si absent ;
- ajouter une association entre commande et livreur ;
- ajouter `courierAssignedAt` ;
- ajouter ou préparer le rôle `ROLE_COURIER` ;
- créer les règles de transition livraison :

```text
READY_FOR_PICKUP → OUT_FOR_DELIVERY → DELIVERED
```

## J5.3 — Dashboard livreur

Créer une interface dédiée :

```text
/djama
```

Fonctions attendues :

- accès réservé aux livreurs authentifiés ;
- affichage des commandes `READY_FOR_PICKUP` ;
- affichage des commandes prises en charge par le livreur ;
- bouton `Prendre en charge` ;
- bouton `Marquer livrée` ;
- téléphone client cliquable ;
- bouton SMS client ;
- affichage adresse / zone / total / articles.

## J5.4 — Tests J5

Tests attendus :

- admin marque une commande prête ;
- commande apparaît dans le dashboard livreur ;
- livreur prend la commande ;
- commande passe `OUT_FOR_DELIVERY` ;
- commande est associée au livreur ;
- SmsLog de prise en charge créé ;
- livreur marque livrée ;
- commande passe `DELIVERED` ;
- `deliveredAt` rempli ;
- commande disparaît des commandes à livrer ;
- accès refusé à un utilisateur non livreur.

---

# Après J5 — Évolutions possibles

À reporter après MVP livraison :

- géolocalisation livreur ;
- preuve photo de livraison ;
- signature client ;
- optimisation de tournée ;
- vrai prestataire SMS ;
- paiement en ligne ;
- statistiques livreur ;
- historique détaillé par livreur ;
- application mobile dédiée.

---

# J5A suite — Préproduction, reset password et légal

## Statut

**Réalisé et validé fonctionnellement le 05/06/2026. À commiter / pousser selon état Git.**

## Objectif

Avant de poursuivre vers J5B et le refactoring workflow, stabiliser l'environnement de recette et les éléments indispensables à un test externe :

- réinitialisation de mot de passe ;
- préproduction o2switch ;
- base recette ;
- Basic Auth ;
- SSL / HTTPS ;
- CGU / CGV ;
- retrait du lien admin public ;
- correction traduction EasyAdmin.

## Réinitialisation de mot de passe

Fonctionnalité livrée et validée :

```text
Mot de passe oublié
→ génération token
→ création SmsLog avec lien
→ envoi manuel SMS depuis SmsLog
→ nouveau mot de passe
```

Cette étape rend le pilote plus robuste pour les tests à distance.

## Préproduction recette.hodina.fr

Environnement créé :

```text
https://recette.hodina.fr
```

Caractéristiques :

- hébergement o2switch ;
- document root sur `public` ;
- base recette séparée ;
- protection Basic Auth ;
- certificat AutoSSL présent ;
- redirection HTTPS à forcer avant Basic Auth.

## Import base dev vers recette

Étapes réalisées :

- création base recette ;
- création utilisateur MySQL recette ;
- dump base dev ;
- correction encodage SQL ;
- import phpMyAdmin réussi ;
- vérification de données avec `dbal:run-sql`.

## CGU / CGV

Pages ajoutées :

```text
/cgu
/cgv
```

Intégration :

- liens footer ;
- acceptation obligatoire au checkout ;
- mise en forme mobile-first ;
- sommaire compact mobile ;
- contenu adapté au pilote.

## Sécurité / UX public

Corrections :

- retrait du lien `Admin` du footer ;
- Basic Auth réservé à la préprod ;
- footer public limité à Catalogue / CGU / CGV ;
- traduction EasyAdmin rendue générique.

## État avant J5B

Le socle pilote est désormais prêt pour test préprod :

```text
Client
→ inscription
→ checkout
→ commande
→ admin
→ statut + SMS
→ reset password
→ CGU/CGV
→ préprod protégée
```

Prochaine étape après commit :

```text
J5B — Refactoring CustomerOrderWorkflowService
```


---

# J5C — Données livraison et préparation du dashboard livreur

## Statut

**Réalisé, testé localement, déployé en préproduction et validé fonctionnellement le 06/06/2026.**

Cette étape correspond à la préparation technique du portail livreur avant la création de l'interface `/djama`.

L'objectif n'était pas encore de livrer un écran livreur, mais de préparer correctement :

- les données nécessaires dans `CustomerOrder` ;
- la relation entre une commande et un livreur ;
- les dates métier de prise en charge ;
- les règles métier dans `CustomerOrderWorkflowService` ;
- la sécurité future de la route `/djama` ;
- l'affichage admin permettant de vérifier les nouveaux champs.

## Décision structurante J5C

Après vérification de l'architecture actuelle, le projet utilise `Customer` comme entité authentifiable principale.

Décision MVP :

```text
CustomerOrder.assignedCourier → Customer
```

Cela signifie qu'un livreur est représenté par un `Customer` disposant du rôle :

```text
ROLE_COURIER
```

Cette décision évite d'introduire une deuxième entité utilisateur trop tôt dans le pilote.

## Données ajoutées dans CustomerOrder

Champs / relation ajoutés :

```text
assignedCourier
courierAssignedAt
outForDeliveryAt
```

Rôle de chaque champ :

- `assignedCourier` : client/utilisateur livreur ayant pris la commande ;
- `courierAssignedAt` : date à laquelle le livreur est associé à la commande ;
- `outForDeliveryAt` : date à laquelle la commande passe réellement en livraison.

Ces champs sont visibles dans le détail commande EasyAdmin.

État normal avant dashboard livreur :

```text
Livreur assigné      Null
Livreur assigné le   Null
Départ livraison le  Null
```

## Workflow service étendu

`CustomerOrderWorkflowService` a été préparé pour la livraison avec :

```text
canTakeForDelivery()
takeForDelivery()
canMarkDeliveredByCourier()
markDeliveredByCourier()
```

Règles métier préparées :

- seule une commande `READY_FOR_PICKUP` peut être prise en charge ;
- une commande déjà assignée ne peut pas être reprise ;
- la prise en charge passe la commande en `OUT_FOR_DELIVERY` ;
- la prise en charge remplit `assignedCourier`, `courierAssignedAt` et `outForDeliveryAt` ;
- un SmsLog est créé lors de la prise en charge ;
- seul le livreur assigné peut marquer la commande comme livrée côté livreur.

## Sécurité préparée

La route future `/djama` est réservée à :

```text
ROLE_COURIER
```

Cela prépare le dashboard sans encore le créer.

## Migrations J5C

Migrations impliquées :

```text
Version20260606101500
Version20260606091936
Version20260606103000
```

Rôle :

- `Version20260606101500` ajoute les champs livraison et la relation `assignedCourier` ;
- `Version20260606091936` a été transformée en migration no-op de compatibilité ;
- `Version20260606103000` aligne le nom d'index de façon sûre après l'ajout des champs.

## Incident migration documenté

Une migration complémentaire d'index avait été générée avec un timestamp antérieur à la migration principale de livraison.

Conséquence en préproduction :

```text
Key 'idx_3cf0a31e4b1e148f' doesn't exist in table 'customer_order'
```

Cause : Doctrine exécutait la migration de renommage d'index avant la migration qui créait l'index.

Correction appliquée :

- rendre la migration trop ancienne non destructive / no-op ;
- créer une migration plus récente ;
- vérifier l'existence de l'ancien et du nouvel index avant de renommer.

Règle retenue pour la suite :

```text
Ne jamais ajouter une migration corrective avec un timestamp antérieur à une migration déjà créée mais non encore déployée.
```

## Tests J5C validés

Validé localement :

- application du patch Git standard ;
- migration locale ;
- `doctrine:schema:validate` OK ;
- `lint:container` OK.

Validé en préproduction :

- `git pull` sur `/home/vopu3712/recette.hodina.fr` ;
- migrations appliquées jusqu'à `Version20260606103000` ;
- `doctrine:schema:validate --env=prod` OK ;
- cache prod clear / warmup OK ;
- nouveaux champs visibles dans EasyAdmin ;
- tests fonctionnels confirmés.

## État après J5C

Le socle de données livraison est prêt.

Le dashboard livreur n'existe pas encore, mais les briques nécessaires sont en place :

```text
Customer avec ROLE_COURIER
CustomerOrder.assignedCourier
CustomerOrder.courierAssignedAt
CustomerOrder.outForDeliveryAt
CustomerOrderWorkflowService prêt pour la prise en charge
/djama réservé à ROLE_COURIER
```

## Prochaine étape

```text
J5D — Dashboard livreur /djama
```

À faire :

- créer `CourierDashboardController` ;
- créer le template `templates/courier/dashboard.html.twig` ;
- afficher les commandes `READY_FOR_PICKUP` ;
- afficher les commandes `OUT_FOR_DELIVERY` du livreur connecté ;
- permettre `Prendre en charge` ;
- permettre `Marquer livrée` ;
- afficher téléphone, SMS, adresse, zone, articles et total.


---

# J5D — Dashboard livreur livré et clôturé

## Statut

**Réalisé, testé localement, poussé, déployé en préproduction et validé fonctionnellement.**

## Ce qui a été livré

J5D a créé le portail livreur mobile-first :

```text
/djama
```

Fonctions validées :

- accès réservé à `ROLE_COURIER` ;
- affichage des commandes prêtes `READY_FOR_PICKUP` ;
- affichage des commandes en cours `OUT_FOR_DELIVERY` assignées au livreur connecté ;
- bouton `Prendre en charge` ;
- bouton `Marquer livrée` ;
- lien téléphone client ;
- lien SMS client ;
- affichage client, adresse, zone, articles et total ;
- lien `Livreur` dans le header uniquement pour les livreurs.

## Correction admin complémentaire

Après J5D, le champ de sélection des rôles dans EasyAdmin a été amélioré.

Avant, les rôles étaient trop libres et peu pédagogiques.

Après correction, l'admin choisit les rôles via des libellés compréhensibles :

- Client ;
- Livreur ;
- Administrateur.

Le futur rôle `ROLE_SELLER` devra être ajouté lors du portail vendeur.

## Point pédagogique pour un développeur débutant

Le bug de test rencontré n'était pas un bug code : la commande n'apparaissait pas dans le dashboard parce qu'elle n'était pas au bon statut.

Règle à retenir :

```text
Un écran peut être correct même s'il n'affiche rien.
Il faut vérifier les données et les conditions métier avant de modifier le code.
```

Dans ce cas :

```text
condition dashboard = status READY_FOR_PICKUP + assignedCourier NULL
```

---

# Nouvelle séquence après J5D

La suite validée devient :

```text
1. Clôturer J5D dans les docs.
2. J5E : marge produit Hodina.
3. J5F : zones tarifaires + communes + communes voisines.
4. J5G : aperçu logistique dans le panier.
5. J6 : portail vendeur.
```

La stratégie globale est de construire d'abord les règles économiques et logistiques communes, puis seulement ensuite les interfaces avancées.

Raison : le futur portail vendeur devra réutiliser ces règles.

---

# J5E — Marge produit Hodina

## Objectif

Mettre en place le modèle économique produit de Hodina.

Le vendeur indique son prix producteur. Hodina calcule le prix client à partir d'une marge effective.

```text
prix client = prix producteur × (1 + taux marge)
```

## Stratégie validée

La marge est hiérarchique :

```text
Produit > Vendeur > Global
```

Cela donne de la souplesse :

- une marge globale permet de démarrer vite ;
- une marge vendeur permet de gérer des accords spécifiques ;
- une marge produit permet d'ajuster certains produits sensibles.

## Important pour le futur portail vendeur

Le vendeur pourra saisir son prix producteur, mais pas la marge Hodina ni le prix client final.

Le calcul doit être dans un service réutilisable :

```text
ProductPricingService
```

Ce service devra être utilisable par :

- catalogue ;
- panier ;
- checkout ;
- EasyAdmin ;
- futur portail vendeur.

## Actions pilote J5E

1. Lire le code existant autour de `Product`, `Seller`, `OrderItem`, panier et checkout.
2. Identifier le rôle actuel du champ prix produit existant.
3. Ajouter un prix producteur si nécessaire.
4. Ajouter les marges nullable produit et vendeur.
5. Ajouter le réglage global `global_margin_rate`.
6. Créer `ProductPricingService`.
7. Brancher le calcul dans catalogue / panier.
8. Figer les données de prix dans `OrderItem` au checkout.
9. Tester les trois niveaux de marge.

## À reporter après pilote

- portail vendeur complet ;
- validation avancée des produits ;
- promotions ;
- règles de marge par catégorie ;
- facturation automatique vendeur ;
- comptabilité avancée ;
- historique des changements de prix.

---

# J5F — Zones tarifaires, communes et communes voisines

## Objectif

Créer le socle de calcul livraison.

Règle pilote :

```text
une commune appartient à une zone tarifaire
une zone tarifaire définit le prix client et la rémunération livreur
```

## Stratégie validée

Hodina doit éviter les tarifs codés en dur.

L'admin doit pouvoir paramétrer :

- les zones tarifaires ;
- les frais client ;
- la rémunération livreur ;
- les communes ;
- le territoire PT / GT ;
- les communes voisines ;
- les zones locales et zones avec barge.

## Rémunération livreur

Pour éviter la frustration, le livreur n'est pas rémunéré en pourcentage du panier.

Il reçoit un forfait clair défini par la zone tarifaire.

Exemple :

```text
Zone : Petite-Terre proche
Frais client : 4 €
Rémunération livreur : 3 €
Marge livraison Hodina : 1 €
```

## Barge

La barge est calculée en comparant le territoire du client et celui des vendeurs.

```text
client PT + vendeur GT → barge
client GT + vendeur PT → barge
```

En multi-vendeurs, un seul vendeur sur l'autre territoire suffit à rendre la barge nécessaire.

## Communes voisines

Pendant le pilote, l'admin définit les communes voisines.

Le système ne calcule pas encore la proximité par GPS.

## Actions pilote J5F

1. Créer `DeliveryPricingZone`.
2. Créer `DeliveryCommune`.
3. Ajouter le territoire `PT` / `GT` à chaque commune.
4. Ajouter la relation communes voisines.
5. Ajouter la zone tarifaire locale et la zone tarifaire barge.
6. Ajouter `Seller.deliveryCommune`.
7. Créer les CRUD EasyAdmin.
8. Tester la création de zones, communes, voisinages et vendeurs.

## À reporter après pilote

- GPS ;
- distance automatique ;
- temps réel trafic ;
- estimation barge dynamique ;
- optimisation de tournée ;
- tarification selon météo / heure / charge ;
- calcul carburant automatisé.

---

# J5G — Aperçu logistique dans le panier

## Objectif

Informer le client dès le panier si sa commande implique une contrainte logistique.

Le client doit comprendre avant validation pourquoi la livraison peut coûter plus cher.

## Stratégie validée

Le panier affiche une estimation. Le checkout recalcule et fige.

```text
Panier = aperçu informatif
Checkout = calcul définitif
```

## Relations logistiques

Le système classe chaque vendeur par rapport à la commune du client :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
```

Priorité globale :

```text
OTHER_TERRITORY > REMOTE_COMMUNE > NEIGHBOR_COMMUNE > SAME_COMMUNE
```

## Messages panier

Le panier doit afficher un message adapté :

- même commune : pas d'alerte forte ;
- commune voisine : message doux ;
- commune éloignée : message distance ;
- autre territoire : message barge ;
- éloigné ou barge : message global.

Message global validé :

```text
Certains produits de ton panier viennent de vendeurs éloignés ou situés sur une autre île. La livraison peut nécessiter une traversée en barge et des frais adaptés seront appliqués.
```

## Actions pilote J5G

1. Créer `DeliveryLogisticsService`.
2. Créer un DTO ou tableau `CartLogisticsPreview`.
3. Calculer la relation client / vendeurs.
4. Choisir la zone tarifaire locale ou barge.
5. Afficher le message panier.
6. Afficher les frais estimés si l'adresse est connue.
7. Recalculer au checkout.
8. Figer les données dans `CustomerOrder`.

## À reporter après pilote

- carte ;
- détail logistique par vendeur côté client ;
- suivi temps réel ;
- délai estimé dynamique ;
- groupement automatique des livraisons ;
- affichage itinéraire.

---

# J6 — Portail vendeur prévu

## Objectif futur

Créer une interface dédiée aux vendeurs.

Le vendeur pourra :

- compléter son profil ;
- renseigner sa commune de retrait / production ;
- ajouter ses produits ;
- ajouter ses photos ;
- saisir ses prix producteur ;
- gérer disponibilité simple ;
- soumettre ses produits à validation.

## Règle d'architecture dès maintenant

J5E, J5F et J5G doivent être codés comme des services réutilisables.

```text
Ne pas coder les règles uniquement pour EasyAdmin.
Ne pas coder les règles uniquement pour le panier.
```

Elles doivent pouvoir être réutilisées par le futur portail vendeur.


---

# J5E — Marge produit Hodina livré et clôturé

## Statut

**Terminé, corrigé, testé localement, déployé en préproduction et validé fonctionnellement le 07/06/2026.**

## Objectif atteint

J5E met en place :

```text
prix client = prix producteur × (1 + marge effective)
```

Priorité :

```text
Produit > Vendeur > Global
```

## Livré

- `Product.producerPrice` ;
- `Product.marginRate` ;
- `Seller.marginRate` ;
- `HodinaSetting.KEY_GLOBAL_MARGIN_RATE` ;
- réglage `global_margin_rate = 20.00` ;
- `ProductPricingService` ;
- prix calculés dans catalogue / fiche produit / panier ;
- prix recalculés et figés au checkout ;
- snapshot économique dans `OrderItem`.

## Validation

Exemple validé :

```text
Prix producteur : 10,00 €
Marge globale : 20,00 %
Prix client affiché : 12,00 €
```

Anciennes commandes inchangées. Nouvelle commande avec valeurs économiques figées. Préproduction validée.

## Incidents corrigés

- migration absente au premier passage ;
- `ProductPricingService.php` tronqué ;
- `Version20260607120000.php` tronquée.

## Suite

```text
J5F — zones tarifaires, communes et communes voisines
```

J5F devra mettre en place `DeliveryPricingZone`, `DeliveryCommune`, les territoires PT / GT, les communes voisines et l'association vendeur → commune.

---

# Ajustement Roadmap J5F — Clarification barge avant application

## Contexte

Avant d'appliquer le premier patch J5F-A, la règle de barge a été clarifiée pour éviter un mauvais modèle métier.

## Règle ajoutée à J5F

J5F doit gérer les territoires PT / GT ainsi :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

## Impact roadmap

Le premier patch J5F-A reste pertinent :

```text
DeliveryPricingZone
DeliveryCommune
Seller.deliveryCommune
```

Mais le service logistique futur J5G devra impérativement calculer la barge uniquement par comparaison de territoire.

## Jalons maintenus

```text
J5F-A
→ entités + migration + CRUD admin

J5F-B
→ tests admin de zones, communes, territoires et vendeur.commune

J5G
→ DeliveryLogisticsService, aperçu panier, checkout et snapshots livraison
```

## Critère de validation futur

Une livraison Dzaoudzi → Pamandzi doit être sans barge.

Une livraison Dzaoudzi → Mamoudzou doit être avec barge.


---

# J5F-A — Socle communes et zones tarifaires livré

## Statut

**Réalisé, testé localement, déployé en préproduction et validé.**

## Objectif atteint

Le socle de paramétrage logistique est en place.

Livré :

```text
DeliveryPricingZone
DeliveryCommune
Seller.deliveryCommune
CRUD EasyAdmin zones tarifaires
CRUD EasyAdmin communes livrées
menu admin Logistique
migrations Version20260607170000 et Version20260607173000
```

## Tests validés

Local :

```text
php -l nouveaux fichiers OK
cache:clear OK
migrations OK
schema:validate OK
lint:container OK
EasyAdmin OK
```

Recette :

```text
git pull OK
migrations OK
cache clear / warmup OK
schema validate OK
jeu de test ajouté OK
working tree serveur propre OK
```

## Jeu de test recette

```text
PT_LOCAL / GT_LOCAL
Dzaoudzi PT
Labattoir PT
Mamoudzou GT
Dzaoudzi ↔ Labattoir
ferme houmadi → Mamoudzou GT
```

## Incident corrigé

Après la migration principale, Doctrine a demandé un alignement du schéma.

Correction : migration postérieure `Version20260607173000`.

Règle confirmée : ne pas utiliser `schema:update --force` en préproduction.


---

# J5F-B — DeliveryLogisticsService livré

## Statut

**Réalisé, testé localement, poussé, déployé en préproduction et validé techniquement.**

## Objectif atteint

Le service métier logistique est prêt.

Livré :

```text
DeliveryLogisticsService
CartLogisticsPreview
```

## Ce que le service sait faire

```text
calculer la relation client / vendeur
détecter la barge
choisir la zone tarifaire
préparer les données à figer dans CustomerOrder
```

## Ce qui reste à faire

Le service n'est pas encore branché dans le panier.

Prochaine étape :

```text
J5G-A — Aperçu logistique panier
```

Objectif J5G-A :

```text
CartController / CartService
→ DeliveryLogisticsService
→ template panier
→ message + frais estimés
```

Toujours sans figer dans `CustomerOrder` à cette étape. Le gel définitif viendra ensuite au checkout.


---

# Ajustement navigation avant J5G

## Statut

**Réalisé.**

Avant J5G, le header a été ajusté pour afficher un lien admin uniquement aux admins connectés.

Règle :

```text
ROLE_ADMIN → Admin
ROLE_COURIER seul → Livreur
sinon → Devenir vendeur
```

Le footer public reste sans lien admin.


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

# Mise à jour roadmap — J5G avancé

## État après J5F-B

Les bases sont en place :

```text
J5F-A → communes, zones tarifaires, commune vendeur
J5F-B → DeliveryLogisticsService + CartLogisticsPreview
```

## J5G-A — Aperçu panier par périmètre vendeur

Statut :

```text
réalisé côté code local et poussé sur la branche pilot/j5-order-delivery-pricing
```

Rôle :

```text
afficher au panier un aperçu logistique estimatif
```

Limite volontaire :

```text
pas de snapshot dans CustomerOrder
pas de migration
pas encore de calcul avancé par chemin de communes
```

## J5G-B — Calcul chemin communes

Objectif :

```text
utiliser les communes voisines comme graphe
calculer le plus court chemin
déduire le nombre de communes traversées
```

## J5G-C — Settings livraison avancés

Objectif :

```text
paramétrer en backoffice les montants par commune traversée et par barge aller-retour
```

## J5G-D — Panier détaillé

Objectif :

```text
afficher frais local + communes traversées + barge
```

## J5G-E — Checkout snapshot livraison

Objectif :

```text
recalculer au checkout et figer les montants dans CustomerOrder
```

## J6 — Portail vendeur

J6 doit attendre que les services soient stables.

Le futur portail vendeur devra réutiliser :

```text
ProductPricingService
DeliveryLogisticsService
```


---

# Mise à jour Roadmap — J5G-B source de données validée

## Changement important

Avant de continuer le calcul de livraison avancée, une étape supplémentaire est ajoutée.

La source :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

est validée comme source initiale, mais elle doit être transformée en base modifiable.

## Nouveau découpage J5G-B

```text
J5G-B1 — documenter la source validée
J5G-B2 — créer / faire évoluer le modèle Doctrine
J5G-B3 — seed initial des communes et liaisons
J5G-B4 — EasyAdmin pour gérer les liaisons
J5G-B5 — BFS dans DeliveryLogisticsService
```

## Pourquoi ajouter ces sous-étapes ?

Parce que le calcul avancé dépend de la qualité de la donnée.

Si les voisinages sont codés en dur, chaque correction terrain nécessitera un patch.

Si les voisinages sont en base et administrables, Hodina pourra s'adapter rapidement.

## Ordre de développement recommandé

```text
1. Docs
2. Modèle Doctrine
3. Seed local
4. EasyAdmin
5. Service BFS
6. Panier détaillé
7. Checkout snapshot
```

---

# Mise à jour Roadmap — J5G-B2 / J5G-B3 validés et J5G-B4 à démarrer

## Statut au 11/06/2026

Les sous-jalons J5G-B2 et J5G-B3 ont été réalisés, testés localement et déployés sur la recette.

```text
J5G-B1 — source communes / voisinage validée        → terminé
J5G-B2 — modèle Doctrine modifiable                 → terminé local + recette
J5G-B3 — seed initial communes / liaisons           → terminé local + recette
J5G-B4 — branchement service sur la carte Doctrine  → à démarrer
```

## Ajustement de découpage

Dans les notes préparatoires, J5G-B4 avait d'abord été nommé "EasyAdmin" et J5G-B5 "BFS".

Décision de clarification après réalisation :

```text
Le CRUD EasyAdmin des communes et des liaisons a été absorbé par J5G-B2.
```

Raison : il était plus cohérent de rendre le modèle modifiable dès sa création, au lieu de créer d'abord les tables puis d'attendre un jalon séparé pour les exposer dans le backoffice.

Le nouveau découpage opérationnel devient donc :

```text
J5G-B4 — lire DeliveryCommuneConnection dans DeliveryLogisticsService
J5G-B4 — construire une carte en mémoire depuis Doctrine
J5G-B4 — trouver le plus court chemin entre commune vendeur et commune client
J5G-B4 — compter les hops LAND et BARGE
J5G-B4 — préparer le détail logistique pour le panier avancé
```

## J5G-B2 — résultat livré

J5G-B2 a créé le modèle Doctrine modifiable pour représenter la carte logistique de Mayotte.

Livré :

- enrichissement de `DeliveryCommune` ;
- création de `DeliveryCommuneConnection` ;
- création du CRUD EasyAdmin des liaisons ;
- ajout des champs utiles à la source validée ;
- migrations dédiées ;
- correction de schéma dédiée ;
- validation locale ;
- validation recette.

Migrations :

```text
Version20260607213000 — modèle Doctrine modifiable
Version20260607214500 — alignement schéma Doctrine / MariaDB
```

Incident conservé : la migration corrective créée via PowerShell a d'abord été écrite avec un BOM UTF-8 invisible, ce qui a provoqué l'erreur PHP `strict_types declaration must be the very first statement`. Le fichier a été réécrit en UTF-8 sans BOM avec `System.Text.UTF8Encoding($false)`.

## J5G-B3 — résultat livré

J5G-B3 a inséré les données initiales issues de la source validée.

Livré :

- 18 points logistiques ;
- 17 communes administratives de Mayotte ;
- Labattoir comme point logistique rattaché à Dzaoudzi ;
- 23 liaisons logistiques ;
- 22 liaisons `LAND` ;
- 1 liaison `BARGE` : Dzaoudzi ↔ Mamoudzou ;
- seed idempotent ;
- validation locale ;
- validation recette.

Migration :

```text
Version20260607220000 — seed communes et liaisons logistiques
```

Point critique conservé : en recette, Doctrine a affiché `0 sql queries`, mais les requêtes de vérification ont confirmé que les données étaient bien présentes. La validation d'un seed ne doit donc pas se limiter au message Doctrine ; il faut interroger les tables métier.

## Prochaine étape — J5G-B4

J5G-B4 ne doit pas encore modifier le checkout ni figer des valeurs dans `CustomerOrder`.

Objectif strict :

```text
DeliveryLogisticsService
→ lit les communes actives
→ lit les liaisons actives
→ construit un graphe en mémoire
→ calcule un plus court chemin
→ retourne un aperçu logistique enrichi
```

Ce qui reste hors J5G-B4 :

```text
pas de snapshot CustomerOrder
pas de nouveaux champs commande
pas de paiement
pas de portail vendeur
pas de refonte checkout complète
```

## J5G-SUPPORT-ADRESSES — jalon intercalé avant clôture J5G-B4

Ce jalon n'était pas prévu comme étape principale, mais il est devenu nécessaire pendant les tests J5G-B4.

### Pourquoi ce jalon est nécessaire

J5G-B4 calcule un trajet entre la commune vendeur et la commune client. Si l'adresse client est incohérente, l'algorithme calcule sur une base fausse. Le support adresses est donc un prérequis de qualité.

### Contenu du jalon

```text
- validation des communes livrables pour les adresses de livraison
- distinction adresse de livraison / adresse de facturation
- zone AUTRE — Autre pour la facturation hors zone Hodina
- validation des adresses imbriquées dans Customer EasyAdmin
- correction des erreurs 500 sur adresse incomplète
```

### Statut actuel

```text
EasyAdmin livraison valide : OK
EasyAdmin livraison fausse : KO propre
Facturation AUTRE : test encore à terminer
Inscription : test encore à terminer
Checkout : test encore à terminer
Déploiement recette : pas encore à faire tant que les tests locaux ne sont pas terminés
```

### Reprise de J5G-B4 après ce support

Une fois le support adresses validé :

```text
1. commiter le support adresses séparément
2. reprendre les tests du panier J5G-B4
3. valider les trajets réels LAND/BARGE
4. déployer recette seulement après validation locale complète
```

---

# Mise à jour roadmap — 12/06/2026 — support adresses validé localement

## J5G-SUPPORT-ADRESSES — statut actualisé

Le jalon intercalé support adresses est désormais validé localement sur les cas critiques.

### Ce qui est fait

```text
Address.type DELIVERY/BILLING
DeliveryZone AUTRE — Autre
validation des livraisons contre delivery_commune
validation des facturations AUTRE/PT/GT selon le cas
validation EasyAdmin
validation checkout
validation inscription
erreurs front améliorées
e-mail existant bloqué
doublon e-mail inscription supprimé
```

### Ce qui reste avant déploiement recette

```text
nettoyer les patchs et zips temporaires
séparer les commits si possible
commiter le support adresses
déployer en recette
exécuter les migrations si non présentes en recette
valider recette sur EasyAdmin + checkout + inscription
```

## Reprise J5G-B4

J5G-B4 peut reprendre après le commit support adresses.

Priorité suivante :

```text
valider que DeliveryLogisticsService utilise bien DeliveryCommuneConnection
valider les trajets LAND/BARGE
valider l'aperçu panier
ne pas encore figer dans CustomerOrder avant J5G-E
```

## Découpage de commits recommandé

```text
1. feat(address): validate delivery and billing addresses
2. feat(cart): compute logistics routes from commune connections
3. docs(logistics): update J5G support address and B4 history
```

Si les fichiers sont déjà mélangés dans le diff, utiliser `git add` sélectif.

---

# J5H — E-mails transactionnels Hodina

## Statut

**Décidé, à développer après ou en parallèle de la préouverture selon priorité.**

## Pourquoi ce jalon existe

Le pilote Hodina fonctionne déjà avec des traces SMS et une validation admin. Mais pour professionnaliser l'expérience client, il faut envoyer des e-mails transactionnels.

Le premier besoin prioritaire est l'e-mail automatique de création de commande : dès qu'une commande est créée, le client reçoit un descriptif clair de sa commande.

## J5H-A — Socle e-mail + e-mail automatique de création de commande

Objectif :

```text
Configurer Symfony Mailer avec SMTP o2switch.
Créer une traçabilité EmailLog.
Créer un service d'envoi e-mail.
Créer un template HTML de récapitulatif commande.
Envoyer automatiquement l'e-mail après création de commande.
Ne jamais bloquer la commande si le SMTP échoue.
```

Contenu de l'e-mail de création :

```text
Bonjour {prénom},
Nous avons bien reçu votre commande {numéro métier}.
Elle est en attente de validation par l'équipe Hodina.
Paiement à la livraison pendant le pilote.
Tableau produits / quantités / prix.
Sous-total / frais de livraison / total.
Adresse de livraison.
Adresse de facturation.
```

## J5H-B — Actions EasyAdmin manuelles

Objectif futur : renvoyer le récapitulatif depuis une commande, envoyer un message manuel au client, consulter EmailLog depuis EasyAdmin.

## J5H-C — E-mails automatiques de changement d'état

Objectif futur : validation admin, annulation, commande prête, commande confiée au livreur, livraison en cours, commande livrée.

Ces e-mails ne doivent être branchés qu'après stabilisation du socle `EmailLog`.

---

# J5I — Préouverture commerciale et compte à rebours

## Statut

**Décidé comme dernier jalon avant validation recette et mise en production.**

## Objectif

Avant les premières ventes, Hodina doit afficher un bloc de préouverture proche de l'exemple fourni :

```text
Notre site web est presque prêt
Jours / Heures / Minutes / Secondes
M'avertir quand c'est prêt
Champ e-mail
Bouton soumettre
```

Mais avec la charte Hodina : logo, couleurs actuelles, ton local, message rassurant.

## Règle commerciale

Pendant la préouverture :

```text
catalogue visible
produits visibles
prix visibles
bouton Ajouter au panier désactivé
aucun panier créé
aucune commande traitée
capture d'e-mail possible pour être prévenu
```

Après l'ouverture :

```text
le compte à rebours disparaît ou devient message d'ouverture
les boutons panier redeviennent actifs
le checkout redevient utilisable
```

## Découpage J5I

```text
J5I-A — Configuration EasyAdmin
J5I-B — Bannière globale dans base.html.twig
J5I-C — Capture e-mail préouverture
J5I-D — Blocage panier / commande
J5I-E — Notification des inscrits à l'ouverture, après socle SMTP
```

## Jalon production

La mise en production est prévue après validation de J5I en recette.

Ordre :

```text
local OK
commit OK
recette OK mobile + PC
production
```


---

# Mise à jour roadmap — 13/06/2026 — J5I livré local + recette

## J5I — Préouverture commerciale

Statut :

```text
Développé : OK
Test local : OK
Commit : OK
Push GitHub : OK
Déploiement recette : OK
Correction migration recette : OK
Paramètres dev injectés en recette : OK
Tests bannière : OK
```

Branche :

```text
pilot/j5i-preouverture-countdown
```

Commit :

```text
5bf3e0e feat: add J5I sales opening countdown and launch email capture
```

## Point bloquant avant production

Avant production, traiter proprement l'ordre des migrations :

```text
Version20260613094055 ne doit pas passer avant la création de launch_subscriber.
```

## Prochaine séquence recommandée

```text
1. Corriger / sécuriser l'ordre de migration J5I pour production.
2. Revalider migration sur une base fraîche ou copie recette.
3. Mettre à jour les docs si correction migration.
4. Valider recette finale mobile + PC.
5. Déployer production avec Basic Auth ou protection équivalente si besoin.
6. Configurer la vraie date d'ouverture.
7. Préparer J5H e-mails transactionnels après stabilisation préouverture.
```

---

# J5J — Mode commerce contrôlé et testeurs production

## Statut

**Développé, testé localement, commité, poussé et déployé en recette.**

## Objectif

Fusionner préouverture, maintenance commerciale et tests production dans un seul système durable.

## Livré

```text
- nouveaux paramètres commerce_* ;
- suppression des anciens paramètres J5I ;
- rôle ROLE_COMMERCE_TESTER ;
- switchs EasyAdmin pour les booléens ;
- liste de choix pour commerce_mode ;
- panier/checkout bloqués pour le public ;
- contournement autorisé pour testeurs et admins ;
- disparition de la bannière en mode open ;
- migration Version20260613130000.
```

## Prochaine étape après J5J

Mettre à jour la documentation finale, nettoyer la table backup recette si elle n'est plus utile, puis décider du merge/déploiement production.
---

# Production — remise à plat terminée

## Statut au 15 juin 2026

```text
La production a été remise à plat et validée.
Le dossier production est désormais un vrai clone Git.
Le DocumentRoot o2switch pointe vers /public.
La base production a été remplacée par un dump recette validé puis nettoyée.
Le mode commerce J5J est actif en preopening.
HTTPS est forcé via public/.htaccess.
Les secrets et mots de passe exposés pendant l'intervention ont été remplacés.
```

## Prochaine étape produit

Après validation navigateur finale, la suite peut reprendre sur les prochains lots fonctionnels ou sur la préparation d'ouverture commerciale.

---

# J5H-A — e-mail récapitulatif commande validé

## Statut

J5H-A est terminé et validé en recette.

## Résultat livré

- Envoi automatique d'un e-mail récapitulatif au client après création de commande.
- Expéditeur métier : `contact@hodina.fr`.
- Journalisation dans `EmailLog`.
- Consultation via EasyAdmin > Journaux e-mails.
- Envoi manuel de secours via bouton `Envoyer manuellement`.
- Symfony Mailer configuré avec SMTP o2switch.
- Messenger async consommé par cron toutes les minutes en recette.
- Template e-mail complet : articles, quantités, prix unitaires, sous-total, frais de livraison, total, adresse et contact client.

## Impact roadmap

J5H-A n'est plus dans les tâches à faire. La suite recommandée redevient :

```text
1. Correction courte logistique panier : adresse de livraison réelle.
2. Validation / stabilisation J5G-B4 existant.
3. Suppléments logistiques J5G-C.
4. Affichage panier détaillé J5G-D.
5. Snapshot livraison checkout J5G-E.
6. J5H-B : e-mails de statut.
7. J6 : portail vendeur MVP.
```

## Point de vigilance

Ne pas commencer J5H-B avant de décider si les statuts e-mail doivent distinguer :

```text
QUEUED
SENT_TO_MESSENGER
SENT_SMTP
FAILED
```

Pour le pilote, le statut `SENT` actuel est accepté car le cron Messenger est validé.

---

# Roadmap — J5G-E0 snapshot adresse validé

J5G-E0 a été ajouté comme jalon intermédiaire entre le support adresses et le snapshot logistique complet.

## Pourquoi ce jalon a été créé

Pendant les tests de suppression d'adresses, il est apparu que le modèle historique `customer_order.delivery_address_id` empêchait une règle produit simple : l'utilisateur doit pouvoir supprimer ses adresses quand il veut.

La correction complète ne consistait donc pas à bloquer la suppression. Elle consistait à séparer :

```text
Carnet d'adresses client = vivant
Adresse de commande = figée
```

## Statut

- J5G-E0 snapshot adresse commande : terminé et validé en recette.
- J5G-E snapshot logistique financier : reste à faire.

## Impact sur la roadmap

L'ancien jalon `J5G-E — snapshot livraison au checkout` est désormais séparé :

1. `J5G-E0` — snapshot adresse commande : fait ;
2. `J5G-E` — snapshot logistique / financier : à faire.

Cette séparation évite de mélanger correction de modèle historique et calcul avancé des frais de livraison.

---

# Roadmap — Insertion J5G-E1 avant J5G-B4

Date de décision : **16/06/2026**

## Pourquoi la roadmap change

Après J5G-E0, le modèle historique des adresses de commande est sain. Mais les tests ont montré une friction en amont : le client doit encore saisir trop d'informations logistiques.

Avant de stabiliser le trajet réel J5G-B4, il faut donc fiabiliser la donnée d'entrée.

## Nouvel ordre recommandé

```text
1. J5G-E1 — Simplifier la saisie adresse par commune livrée
2. J5G-B4 — Stabiliser le trajet logistique réel
3. J5G-C  — Frais / suppléments logistiques
4. J5G-E2 — Snapshot logistique financier
```

## Impact

J5G-B4 reste important, mais il est décalé après J5G-E1 pour éviter de tester des trajets avec des communes / zones saisies manuellement et potentiellement incohérentes.

---

# Roadmap — J5G-E1 à J5G-E2-bis-A validés localement

Date : **17/06/2026**
Branche : `pilot/j5g-e1-commune-livree`

## Statut

```text
Développement local : OK
Tests locaux : OK
Commit : OK
Push GitHub : OK
Recette : à faire
Production : à faire après recette
```

## Ce qui change dans la roadmap

J5G-E1 n'est plus un simple cadrage : il est développé et validé localement avec ses correctifs E1B, E1C, E1D, E1E et E2-bis-A.

La suite J5G doit partir de ce nouveau socle :

```text
Panier = écran de livraison + validation pendant le pilote.
Checkout = étape future de paiement / facturation uniquement.
```

## Ordre recommandé actualisé

```text
1. Déployer J5G-E1 → E2-bis-A en recette.
2. Valider mobile + PC : changement commune, barge, prix, confirmation.
3. Stabiliser J5G-B4 : plus court chemin BFS.
4. Ajouter les coûts de traversées terrestres.
5. Préparer J5G-E2 : snapshot logistique financier complet.
6. Reporter le vrai checkout au paiement en ligne.
```

## Impact sur J5G-B4

J5G-B4 ne doit plus corriger la saisie adresse. Cette partie est réglée.

J5G-B4 doit se concentrer sur :

```text
DeliveryCommuneConnection comme graphe
BFS pour plus court chemin
coûts par traversée terrestre
conservation de la règle barge par territoire PT / GT
```

---

# Roadmap — J5G-E1 à J5G-E2-bis-A validés en recette et production

Date : **17/06/2026**
Branche consolidée : `pilot/j5j-commerce-mode-role-tester`
Tag production : `j5g-e1-e2bis-prod`
Commit final docs : `36cc357 docs(j5g): document commune delivery and cart validation flow`

## Statut final

```text
Recette : déployée et validée
Production : déployée et validée
Migrations production : exécutées jusqu'à Version20260615225836
Schema production : synchronisé
Tests production : OK
```

## Historique de merge / déploiement

La recette était sur `pilot/j5j-commerce-mode-role-tester`. La branche `pilot/j5g-e1-commune-livree` a été vérifiée comme contenant bien l'historique J5J, puis la branche consolidée `pilot/j5j-commerce-mode-role-tester` a été fast-forward jusqu'à `36cc357`.

En production, le dossier `/home/vopu3712/hodina.fr` a été mis à jour par fast-forward :

```text
933d70b → 36cc357
```

Ce fast-forward a apporté notamment :

- les snapshots d'adresse commande J5G-E0 ;
- `EmailLog` et l'e-mail récapitulatif J5H ;
- la commune livrée J5G-E1 ;
- le recalcul AJAX livraison ;
- le verrouillage du total ;
- le panier contractuel ;
- les docs consolidées.

## Conséquence roadmap

J5G-E1 à E2-bis-A est maintenant clôturé et ne doit plus être rouvert pour modifier le principe UX. La suite doit partir de ce socle :

```text
commune livrée = source de vérité
panier = écran contractuel de validation pendant le paiement manuel
checkout = futur paiement / facturation
```

La prochaine étape logique reste J5G-B4, mais uniquement pour enrichir le graphe logistique et le calcul du plus court chemin, pas pour recréer la saisie adresse.

# Roadmap — J5G-B4 fusionné dans la branche pilote principale

Date : **17/06/2026**
Branche source : `pilot/j5g-b4-bfs-link-costs`
Branche cible : `pilot/j5j-commerce-mode-role-tester`
Merge final : `10ff512 merge(j5g): integrate BFS delivery logistics rules`

## Statut

```text
Développement local : OK
Tests locaux : OK
Branche GitHub J5G-B4 : poussée
Merge dans branche pilote principale : OK
Push branche pilote principale : OK
Recette : à déployer / valider
Production : à faire après recette
```

## Ce qui change dans la roadmap

J5G-B4 n'est plus une étape préparatoire. Le calcul logistique réel est maintenant intégré dans la branche pilote.

La suite ne doit plus parler de “créer le BFS” mais de :

```text
valider J5G-B4 en recette
valider J5G-B4 en production
surveiller les vrais paniers
ajuster les settings globaux selon la rentabilité observée
```

## Règle logistique désormais en place

```text
commune livrée = source de vérité
DeliveryCommuneConnection = carte des liaisons
BFS = plus court chemin
LAND = coût de traversée terrestre
BARGE = coût de traversée maritime PT/GT
tarif local = base de la commune livrée
trajet retenu = trajet de collecte le plus contraignant
supplément multicommunes = communes de collecte distinctes - 1
plafond global = protection client après calcul complet
snapshot = historique figé sur commande
```

## Roadmap immédiate après merge

```text
1. Déployer J5G-B4 en recette.
2. Appliquer les migrations jusqu'à Version20260617162000.
3. Vérifier / renseigner les settings globaux.
4. Supprimer les anciennes commandes de test si nécessaire.
5. Créer des commandes test snapshotées.
6. Vérifier panier : sans barge, avec barge, multicommunes, plafond.
7. Vérifier admin : bouton Logistique et snapshot JSON.
8. Déployer en production après validation recette.
9. Créer un tag production J5G-B4 si la prod est validée.
```

## Roadmap future après J5G-B4

J5G-B4 ne fait pas encore de vraie optimisation de tournée. Les futurs jalons possibles sont :

```text
J5G-C : ajustements tarifaires après observation terrain
J5G-D : affichage / pédagogie client si besoin après retours utilisateurs
J5G-E2 : déjà partiellement absorbé par le snapshot logistique J5G-B4
J6 : portail vendeur MVP
J7 : stabilisation ouverture publique
J8 : suivi financier manuel / rentabilité livraison
J10 : industrialisation logistique
```

## Mise à jour 18/06/2026 — Socle MEP par tag validé

Le jalon J5G-B4 n'est pas seulement un jalon logistique. Il marque aussi une montée en maturité du processus de livraison Hodina.

### Acquis

- Déploiement par tag depuis `main`.
- Script unique recette / production.
- Sauvegarde DB automatique.
- Protection des fichiers runtime.
- Cron Messenger prod installé.
- Production alignée sur `j5g-b4-20260618-v7`.

### Impact roadmap

La phase pilote peut continuer avec une base plus saine : les prochaines évolutions doivent repartir de `main` après la MEP v7, et non d'une ancienne branche pilote déjà dépassée en production.

### Prochaine priorité technique

Avant d'ajouter de gros modules, nettoyer la dette d'exploitation révélée par cette MEP : env suivis Git, uploads suivis Git, dépréciations Symfony/Doctrine/EasyAdmin.

# Roadmap — état validé J5G-B4 v11 et ordre de suite

Date : **19/06/2026**
Tag production validé : `j5g-b4-20260618-v11`
Commit final : `b998b63 fix(admin): avoid collapsing menu items matching section names`

## Statut production

```text
Recette : OK
Production : OK
Schema Doctrine : OK
Migrations : latest Version20260617162000
Assets AssetMapper : compilés automatiquement
Admin EasyAdmin : stable après test hors MEP
Miniatures produits : OK
Ajax ajout panier : OK
Menu admin mobile : OK
E-mail commande : reçu après configuration MAILER_DSN réelle
```

## J5G-B4 est maintenant clôturé comme socle pilote

Le périmètre J5G-B4 a absorbé plus que le calcul logistique : il a aussi stabilisé la mécanique de MEP et plusieurs irritants d'exploitation.

Acquis consolidés :

- graphe logistique `DeliveryCommuneConnection` ;
- BFS et coûts LAND / BARGE ;
- plafond client et supplément multicommunes ;
- snapshot logistique commande ;
- admin commande > logistique ;
- compilation AssetMapper en prod ;
- protection `public/uploads/products` ;
- `public/assets` reconnu comme dossier généré ;
- menu admin mobile repliable ;
- ajout panier Ajax ;
- e-mails commande réels après configuration SMTP.

## Ordre de suite validé

```text
1. Docs de suivi v11.
2. Petite dette technique : env/uploads/assets + MAILER_DSN documenté.
3. J5K GPS livraison.
4. J5L admin commande/logistique plus terrain.
5. J5M portail livreur exploitable.
6. Ensuite seulement paiement ou automatisation plus lourde.
```

## Pourquoi GPS avant paiement

Le problème le plus terrain à Mayotte n'est pas encore le paiement. C'est la capacité à trouver rapidement le client et à exploiter les informations de livraison.

J5K doit donc enrichir l'adresse sans remettre en cause la commune livrée :

```text
commune livrée = source de vérité logistique
latitude / longitude = aide terrain optionnelle
snapshot commande = historique figé
admin/livreur = liens Maps/Waze
```

## Décision roadmap

Le paiement en ligne reste volontairement repoussé. Le pilote doit d'abord prouver :

- la fiabilité des adresses ;
- la capacité à livrer ;
- la disponibilité vendeurs ;
- le coût réel logistique ;
- la conversion catalogue → panier → commande.


## Mise à jour roadmap — Dette pré-J5K

Avant J5K GPS livraison, une étape courte de stabilisation runtime est placée explicitement :

```text
J5G-B4 v11 validé
→ dette env/uploads/assets/MAILER_DSN
→ J5K GPS livraison
→ J5L admin terrain
→ J5M portail livreur
```

Critère de passage vers J5K :

```text
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

ne doit retourner que :

```text
public/uploads/products/.gitkeep
```

---

## Mise à jour 19/06/2026 soir — J5K local validé, recette à suivre

J5K a évolué au-delà du GPS initial : le panier gère maintenant les adresses de livraison et de facturation avec des défauts séparés.

Statut :

```text
Local : OK après correctifs J5K-v8 à J5K-v8-quater
Recette : à déployer / tester demain
Production : non concernée à ce stade
```

Règle de reprise :

```text
Ne pas déployer un tag intermédiaire.
Créer un tag propre après commit final.
Déployer recette.
Rejouer les tests complets.
```

Après validation recette, J5K pourra être clôturé et la roadmap pourra reprendre vers J5L / J5M.


---

# Mise à jour 20/06/2026 — Planning fin juin réaligné client / livreur

## Contexte

Après la validation locale J5K-v8-quater et la mise en production manuelle d’images catalogue WebP légères, la roadmap est réalignée sur la valeur terrain.

L’objectif n’est plus d’ajouter beaucoup de fonctionnalités avant ouverture, mais de rendre le pilote exploitable :

```text
commander
→ suivre
→ livrer
→ piloter
```

## Nouvel ordre de suite

```text
1. J5K final — panier, adresses, GPS, facturation
2. J5L — portail client MVP
3. J5M — portail livreur MVP
4. J5N — admin exploitation
5. J5O — optimisation automatique des images produits
6. J5P — suivi financier manuel
```

## Décision sur les images

Des miniatures catalogue ont été optimisées manuellement et mises en production le 20/06/2026 :

```text
ananas_600.webp          < 200 Ko
canne_a_sucre_600.webp   < 200 Ko
mangues_600.webp         < 200 Ko
manioc_600.webp          < 200 Ko
jackfruit_600.webp       < 200 Ko
```

Cette action règle le besoin immédiat de performance catalogue. Le futur pipeline automatique d’images reste prévu, mais il ne doit pas retarder le portail client et le portail livreur.

## Arbitrage roadmap

Le portail client passe avant l’optimisation admin, car il réduit directement l’incertitude client et les sollicitations manuelles.

Le portail livreur passe avant l’optimisation admin avancée, car il conditionne la qualité réelle de livraison.

L’admin reste indispensable, mais son optimisation peut être progressive tant que le flux minimum reste exploitable.


---

# Mise à jour roadmap — 20/06/2026 soir — J5K clôturé recette, J5L-A ajouté

## État J5K

J5K-v8-quater est clôturé en recette.

```text
Tag final recette : devops-deploy-composer-before-console-v2
Commit : 48dae1d
```

La roadmap ne doit plus considérer J5K comme un chantier à développer. Il reste seulement la mise en production contrôlée.

## Nouvel ordre de suite

```text
1. Production J5K-v8-quater
2. J5L-A — UX panier mobile PWA
3. J5L — portail client MVP
4. J5M — portail livreur MVP
5. Admin exploitation + finance manuelle
6. Gel fonctionnel
7. Ouverture contrôlée
```

## Pourquoi J5L-A est ajouté

Le panier est le dernier écran avant la commande. Même si la logique métier J5K est validée, l’écran doit être plus clair sur mobile : moins de boutons concurrents, résumé sticky, blocs mieux hiérarchisés, et détails logistiques techniques repliés.

Règle : J5L-A ne doit pas modifier le moteur de calcul, les snapshots, les entités ou les migrations.

## Planning quotidien validé

```text
20/06 — DevOps script + docs
21/06 — Prod J5K-v8-quater
22/06 — J5L-A UX panier mobile PWA
23/06 — Tests panier mobile
24/06 — J5L portail client MVP
25/06 — Tests portail client
26/06 — J5M portail livreur MVP
27/06 — Test parcours complet
28/06 — Admin exploitation + finance manuelle
29/06 — Gel fonctionnel
30/06 — Ouverture contrôlée
```

---

# Mise à jour roadmap — 21/06/2026 — J5L clôturé recette

## État

J5L est validé en recette.

Le panier mobile, le sélecteur compact d'adresses et l'affichage facturation admin sont considérés comme prêts pour la suite des tests et la préparation production.

## J5L livré

```text
J5L-A — UX panier mobile PWA
J5L-B — Sélecteur compact d'adresses panier
J5L-C — Facturation visible côté admin
```

## Roadmap mise à jour

Ordre recommandé :

```text
1. Clôture / tag production J5L si recette complète OK
2. J5M-A — Workflow livreur enrichi
3. Tests parcours complet client → admin → livreur
4. Admin exploitation / finance manuelle
5. Gel fonctionnel avant ouverture contrôlée
```

## J5M-A cible

Ajouter deux étapes opérationnelles :

```text
Prise en charge par le livreur
En cours de livraison
```

Nomenclature technique recommandée :

```text
picked_up
out_for_delivery
```

Affichage utilisateur :

```text
Prise en charge par Chahere
En cours de livraison
```

## Règle d'architecture

Le statut ne doit pas contenir le nom du livreur. Le livreur est une donnée associée à la commande.


---

# Roadmap — J5M-C2/C3 validé localement

## Acquis J5M

Le portail livreur dispose maintenant :

```text
- du workflow PICKED_UP / OUT_FOR_DELIVERY ;
- d’une vue terrain compacte ;
- du détail client / livraison ;
- du bloc Collecte vendeurs ;
- des points de retrait vendeur ;
- des produits et quantités à récupérer par vendeur.
```

Le backoffice vendeur dispose maintenant :

```text
- création automatique/rattachement du compte client vendeur ;
- rôle ROLE_SELLER ;
- adresse de retrait intégrée ;
- commune de retrait seedée ;
- nom de structure optionnel ;
- synchronisation commune logistique / zone.
```

## Prochaines étapes

Avant production :

```text
1. Commit local J5M-C2/C3.
2. Tag local de validation.
3. Déploiement recette.
4. Migration recette.
5. Tests recette vendeur + commande + livreur + catalogue.
6. Documentation de validation recette.
```

Plus tard, hors MVP immédiat :

```text
- checklist persistante de collecte ;
- validation produit par produit ;
- preuve photo de collecte ;
- portail vendeur complet ;
- édition autonome du point de retrait par le vendeur.
```

---

# Mise à jour roadmap — 24/06/2026 — J5O/J5P/J5Q clôturés recette

## État réel des jalons

Les numéros J5O et J5P ont été utilisés pour des lots différents des anciens libellés prévisionnels.

Lots réels validés :

```text
J5O-A — code de réception client chiffré
J5P-A — notifications client sur statuts
J5Q-A — paiements livreurs / historique Djama / suivi admin
```

Anciens libellés prévisionnels déplacés en backlog sans numéro définitif :

```text
optimisation automatique images produits
suivi financier global / reversement vendeur / export CSV
```

## Ce que J5Q-A change dans l'ordre stratégique

Le portail livreur n'est plus seulement opérationnel pour livrer. Il devient aussi un espace de confiance pour le livreur : il peut voir ce qu'il a gagné, ce qui a été payé, et quelles commandes composent son paiement.

Cette brique réduit un risque terrain important : la rémunération livreur ne doit pas dépendre de notes externes ou de mémoire humaine.

## Prochaine priorité recommandée

Après J5Q-A, la priorité stratégique redevient le **portail client MVP**, car le client reçoit désormais beaucoup d'informations par SMS/e-mail mais n'a pas encore son espace de suivi autonome.

Ordre recommandé :

```text
1. Portail client MVP
2. Tests bout en bout multi-commandes
3. Procédures support client / livreur
4. Ajustements notification anti-spam si besoin
5. Export financier / reversement vendeur
6. Optimisation images automatique
7. Gel fonctionnel
8. Ouverture contrôlée
```

---

# Roadmap — repositionnement après J5Q-A

Après validation recette de J5Q-A, le prochain jalon fonctionnel prioritaire reste la clôture de la boucle livreur avant d'ouvrir la boucle vendeur.

```text
J5Q-A — Paiements livreurs admin + historique Djama
✅ validé recette

J5Q-B — Mes paiements Djama
✅ intégré dans J5Q-A

J5Q-C — Automatisation cron + récap admin
➡️ prochain jalon

J5Q-D — Ajustements / export CSV / récap mensuel
➡️ ensuite

J5R-A — Paiements vendeurs
➡️ après stabilisation livreur
```

La remontée à Rennes rend J5Q-C prioritaire : Hodina doit préparer les rémunérations et prévenir les admins sans dépendre d'un rappel manuel.

# Roadmap — J5Q-C-1 / J5Q-C-2

Le besoin de branding e-mail a révélé un problème plus large : les paramètres globaux Hodina deviennent trop nombreux pour rester dans une liste unique.

Découpage acté :

```text
J5Q-C-1 — Structuration des réglages en groupes
J5Q-C-2 — Branding e-mail
```

J5Q-C-1 pose la fondation UX/admin : `HodinaSetting` est enrichi avec des groupes, une vue experte `Tous les paramètres` et des vues métier filtrées.

J5Q-C-2 s'appuiera ensuite sur cette structure pour ajouter le groupe ou la sous-section `Branding e-mail` et appliquer le préfixe d'objet, la formule d'ouverture, la formule de fin et la signature des e-mails.

# Roadmap — suite J5Q-C-2

Après J5Q-C-1, J5Q-C-2 finalise la mise en place de la sous-section `Branding e-mail` et raccorde tous les e-mails existants.

Ordre maintenu :

```text
J5Q-C-1 — Structuration des réglages en groupes
✅ validé recette

J5Q-C-2 — Branding e-mail
➡️ patch préparé

J5Q-D — Ajustements / export paiements livreurs
➡️ après validation branding
```

---

# Roadmap — après validation recette J5Q-C-2

## État acquis

```text
J5Q-A   → rémunérations livreurs historisées et visibles
J5Q-C   → génération de brouillons paiement livreur + cron recette
J5Q-C-1 → réglages Hodina structurés par groupe + paramètres paiements
J5Q-C-2 → branding e-mail centralisé + groupe Branding e-mail
```

Tous ces jalons sont validés en recette, pas encore en production pour J5Q-C, J5Q-C-1 et J5Q-C-2.

## Suite logique

Avant d'ajouter de gros modules financiers, il faut terminer la validation e-mail réelle en recette :

1. configurer `[Recette]` dans `Réglages > Branding e-mail` ;
2. déclencher les familles d'e-mails existantes ;
3. vérifier objet, corps et `EmailLog.subject` ;
4. surveiller `public/error_log` et les access logs pendant les tests.

Ensuite seulement, arbitrer entre :

- `J5Q-D` : export / ajustements financiers livreurs ;
- portail client MVP ;
- `J5R-A` : paiements vendeurs côté admin.

## Point de vigilance stratégique

Ne pas confondre observabilité recette et fonctionnalité produit. Le debug `ERR_CONNECTION_CLOSED` doit rester un sujet support/infra tant qu'aucune erreur applicative n'est prouvée.


---

# Roadmap — arbitrage après J5Q-C-2

Décision du 25/06/2026 : avant le Portail client MVP, faire un court lot de stabilisation Djama.

```text
J5Q-D0 — Stabilisation Djama
➡️ patch préparé localement, recette à faire

Portail client MVP
➡️ prochain chantier fonctionnel après validation J5Q-D0

J5Q-D — export / ajustements paiements livreurs
➡️ reporté après Portail client MVP, sauf besoin exploitation urgent
```

Raison : le portail livreur est déjà structurellement complet. Le risque immédiat est la friction terrain ou la notification redondante, pas l’absence d’un nouveau modèle métier.


---

# J5R — Portail client MVP

## J5R-A — Portail client commandes + annulation client encadrée

Statut : patch préparé le 25/06/2026.

Objectif : permettre au client connecté de suivre ses commandes, consulter le détail, voir l’adresse/GPS de livraison utilisée et annuler sans friction tant que la préparation n’est pas engagée.

## J5R-B — Avis client + notation vendeur/livreur

À construire après validation de J5R-A. Réutilisera `CustomerOrderFeedback`.

## J5R-C — Profil/adresses en lecture simple

À construire après stabilisation des commandes. L’édition complète hors panier reste sensible et ne doit pas casser le checkout.

## J5R-D — Historique et textes d’état

Améliorations UX et microcopies après retours recette.

## J5R-E — Documentation et recette complète

Clôture documentaire et tag recette.

---

# J5S — Points de remise imposés / relais pickup / barge / aéroport

## J5S-A — Socle DeliveryPoint admin

Statut : patch préparé localement.

Objectif : créer la capacité admin pour gérer des points de remise précis et les associer à des produits.

Amélioration J5S-A-bis/quater : rendre le formulaire Produit plus pratique en permettant d’associer plusieurs points existants, de créer un point de remise et ses plages, et de choisir si le point est obligatoire ou optionnel en complément de la livraison standard.

## J5S-B — Activation panier/checkout

Objectif futur : si un produit impose un point de remise, le client devra choisir un point autorisé et une plage horaire avant validation. Si un produit est en mode livraison standard + point de remise, le client pourra choisir entre adresse classique et point autorisé.

## J5S-C — Affichages opérationnels

Objectif futur : afficher le point et le créneau dans l’admin commande, Djama, le portail client et les notifications utiles.

## J5S-D — Modification client avant préparation

Objectif futur : permettre au client de modifier point/plage uniquement avant préparation.


## J5S-B — Panier/checkout avec choix point de remise

Objectif : activer côté client le socle DeliveryPoint validé en J5S-A.

Le panier/checkout doit respecter les trois modes produit : livraison standard uniquement, point de remise imposé uniquement, ou livraison standard + point de remise.

Le client choisit un point, une plage horaire et peut ajouter une instruction d’arrivée. Les informations sont snapshotées sur la commande et affichées dans confirmation, admin, Djama et portail client.

## J5T-A — Checkout première commande simplifié

- Ajout d’un parcours checkout invité simplifié.
- Le client non connecté ne saisit plus de mot de passe avant validation.
- Un compte est créé automatiquement et l’e-mail de commande contient un lien sécurisé pour définir le mot de passe.
- Le checkout connecté reste inchangé.
- Aucun changement de schéma.


# Mise à jour 27/06/2026 — Roadmap après J5U-A

## Validé recette récemment

- J5T-A / J5T-A-bis : checkout invité simplifié validé recette sur le formulaire simple nouveau client.
- J5U-A : expéditeur e-mails paramétrable EasyAdmin validé recette ; les e-mails partent bien avec `commande@hodina.fr`.

## État historique — validé recette avec correctif avant production

- J5V-A : `Product.minimumOrderLeadTimeHours` est présent dans le code, la migration `Version20260626194000` existe, et la validation serveur checkout est rebranchée par `3b508d0`. Recette validée sous `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Ordre stratégique recommandé

1. État supersédé : la promotion production a ensuite été réalisée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
2. J5W-A — Zones tarifaires locales par secteur, sans remplacer PT/GT : validé localement + recette + production.
3. J5W-A-ter — Production J5W-A : fait sous `prod-j5w-a-local-pricing-zones-20260629`.
4. J5W-B — DeliveryArea administrable et rattachement des 18 `DeliveryCommune` seedées, y compris Labattoir.
5. J5W-C — Planning par DeliveryArea avec cutoff 10h00 la veille.
6. J5W-D — Demande livraison express hors créneau standard.
7. J5W-E — Proposition d’heure livreur pour point de remise.
8. J5Y-A — Disponibilité produit par commune livrée, ancien J5W-A repoussé pour éviter la collision.

## Règle stratégique anti-régression

Le découpage opérationnel en sous-zones ne doit jamais remplacer les responsabilités existantes. Depuis J5W-A : `DeliveryPricingZone` porte le forfait local, `DeliveryCommuneConnection` porte les liaisons/barge, `DeliveryCommune.territory` garde PT/GT, et `DeliveryArea` servira au planning, à l’exploitation et aux affectations livreurs futures.

## Mise à jour 28/06/2026 — Stabilisation checkout point/standard avant J5W

Avant de lancer J5W, la priorité reste de stabiliser la frontière entre livraison standard et point de remise.

Ordre recommandé :

1. Clôturer localement J5S-B-ter/quater : point imposé, standard, optionnel standard + point, messages français, bouton sticky, référence commande robuste.
2. Taguer et déployer en recette J5S-B-ter/quater uniquement après validation locale complète.
3. Rejouer J5V-A en recette avec un produit à délai 48 h. État du 28/06/2026 : fait après correctif `3b508d0`, tag `recette-j5v-a-checkout-lead-time-fix-20260628`.
4. Ensuite seulement lancer J5W-A puis J5W-B.

Pourquoi : le futur planning `DeliveryArea` dépendra du mode réellement choisi par le client. Tant que le checkout peut mélanger adresse standard et point de remise, ajouter les sous-zones créerait une dette fonctionnelle.

## Mise à jour 28/06/2026 — Reprise avant J5W

Avant de poursuivre J5W et les `DeliveryArea`, la priorité opérationnelle est de clôturer J5T-C et la validation recette J5S-B-ter/quater.

Ordre recommandé :

1. Reprendre J5T-C localement : e-mail nouveau, e-mail existant avec popup, confirmation, ORDER_CREATED, EmailLog.body.
2. Committer J5T-C uniquement après validation locale complète.
3. Déployer J5T-C en recette avec un tag dédié.
4. Finaliser la recette J5S-B-ter/quater et J5T-C.
5. Reprendre ensuite J5W-A/J5W-B sans mélanger les règles de checkout client avec les futures sous-zones opérationnelles.

## Mise à jour 28/06/2026 — Après validation recette J5S/J5T-C/J5V-A, avant MEP

État de reprise : J5S-B-ter/quater, J5T-C et J5V-A corrigé sont validés en recette, mais ensuite validés production sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` dans cette documentation.

Ordre recommandé :

1. Clôturer la documentation J5S/J5T-C/J5V-A.
2. État supersédé le 29/06/2026 : les contrôles production minimum sont annoncés passés et le tag `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` est validé.
3. Reprendre J5W-A/J5W-B uniquement après documentation production et sans modifier les responsabilités `DeliveryZone`.

Ne pas lancer `DeliveryArea` tant que la frontière standard / point de remise et le délai minimum produit ne sont pas verrouillés côté production.


## Mise à jour 28/06/2026 — J5V-A corrigé avant reprise J5W

J5V-A n’est plus un chantier à réconcilier techniquement en recette : la validation serveur checkout est rebranchée par `3b508d0` et validée sous `recette-j5v-a-checkout-lead-time-fix-20260628`.

État supersédé le 29/06/2026 : le tag production `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` contient ce correctif et les tests minimum production sont annoncés passés.

## Mise à jour 29/06/2026 — Après MEP production checkout

Le bloc checkout stabilisé est désormais validé production sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` (`d5466fe`). Cette MEP fige J5S-B-ter/quater, J5T-C, J5U-A et J5V-A corrigé.

Ordre recommandé après documentation production :

1. Ne plus modifier le checkout sans test ciblé, car il combine maintenant standard, point de remise, e-mail existant et délai minimum produit.
2. État supersédé par J5W-A : reprendre J5W-B uniquement en gardant la séparation métier validée production : `DeliveryPricingZone` = forfait local, `DeliveryCommuneConnection` = liaisons/barge, `DeliveryCommune.territory` = PT/GT, `DeliveryArea` = planning, sous-zones, exploitation et affectation livreurs.
3. Éviter de mélanger livraison express, tarification spéciale et planning dans un seul lot MVP.
4. Avant J5W, garder un point de rollback clair : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.


## Mise à jour 29/06/2026 — J5W-A commencé sur `develop`

J5W-A est recentré : il ne porte plus les produits limités à certaines communes. Ce sujet est repoussé en J5Y-A pour éviter une collision documentaire.

J5W-A porte désormais les **zones tarifaires locales par secteur**, sans remplacer `PT` / `GT`.

Ordre immédiat :

1. Garde-fou `tools/assert-j5w-a-local-pricing-zones.php` corrigé : plus de faux positif sur la suppression défensive de `PETITE_TERRE_LOCAL`.
2. Contrôles locaux J5W-A rejoués et OK.
3. Documentation J5W-A alignée sur l’état local.
4. Commit sur `develop` fait.
5. Merge contrôlé `develop` → `main` fait.
6. Recette validée sous `recette-j5w-a-local-pricing-zones-20260629`.
7. Production J5W-A réalisée sous `prod-j5w-a-local-pricing-zones-20260629`; reprendre ensuite J5W-B sans mélanger secteurs tarifaires et futures sous-zones opérationnelles.

Point non négociable : la barge reste portée par les liaisons logistiques et les territoires PT/GT ; les zones tarifaires locales ne doivent pas devenir une nouvelle source de vérité pour la barge.


## Mise à jour 29/06/2026 — Séquence J5X après production J5W-A

Après J5W-A validé production, l’ordre retenu est :

1. **J5X-A — tarifs zones tarifaires** : mise à jour des frais client par secteur, sans toucher à la formule logistique.
2. **J5X-B — calendrier de livraison par secteur** : jours de passage et cutoff paramétrables dans EasyAdmin.
3. **J5X-C — promesse produit / produit sur créneau** : broche de jasmin, collier de fleurs, créneaux événementiels/aéroport.
4. **J5X-D — catalogue recherche/filtres/tri/priorité admin** : amélioration UX catalogue mobile-first.

Arbitrage : J5X-A reste volontairement court pour limiter le risque sur panier/checkout. Le calendrier et la promesse produit ne doivent pas être mélangés à la mise à jour tarifaire.

## J5X-B — Calendrier livraison par secteur

Après J5X-A, J5X-B structure la promesse de passage client. Ce lot doit être validé avant J5X-C, car les produits sur créneau ne doivent pas masquer une promesse secteur encore fausse ou statique.

Jalons suivants :

```text
J5X-C : promesse produit / produits sur créneau.
J5X-D : catalogue recherche, filtres, tri, priorité admin.
```

## J5X-C — Promesse produit avant catalogue avancé

J5X-C doit être validé avant J5X-D. Un catalogue filtrable avec des fiches produit peu claires créerait une mauvaise première impression à l’ouverture.

Priorité J5X-C : confiance client, clarté mobile, distinction produit standard / produit sur créneau.

J5X-D reste ensuite consacré à la recherche, filtres, tri et priorité admin.

## J5X-D — Catalogue exploitable avant ouverture

Après J5X-A/B/C, le catalogue devient le prochain levier d’ouverture : recherche, filtres, tri et mise en avant admin.

Priorité J5X-D : permettre à un client de trouver rapidement un produit local et d’ajouter au panier sans friction. Les règles de livraison restent volontairement hors de ce lot.

## Mise à jour 01/07/2026 — J5Y validé production et lot clos

La séquence J5Y a été stabilisée, validée en recette puis validée en production.

```text
Tag recette final : recette-j5y-carnet-livraison-footer-clean-20260701
Tag production validé : prod-j5y-carnet-livraison-footer-20260701
Commit production : 200d84b merge: document j5y recette validation
```

Ce lot regroupe :

```text
J5Y-A/B : point de remise et créneaux demi-heure
J5Y-C/D : catalogue en homepage, Découvrir Hodina, logo/favicons
J5Y-E : route canonique /decouvrir-hodina
J5Y-F : /carnet et /carnet/livraison
J5Y-G : header Infos livraison et footer réassurance compact
J5Y-H : illustrations WebP et simplification éditoriale livraison
```

Stratégie retenue : ne plus élargir J5Y. La valeur vient maintenant de la stabilité du socle public, pas d’un nouveau raffinement UI.

Ordre recommandé après validation production :

```text
1. Figer J5Y sauf bug bloquant.
2. Documenter toute observation production réelle séparément.
3. Choisir le prochain lot métier ou dette technique sans mélanger avec J5Y.
4. Prioriser les dettes non bloquantes : runtime uploads, trajectoire Symfony non-LTS, dépréciations Doctrine/EasyAdmin, wording du script de MEP.
5. Reporter les nouveaux contenus Carnet tant qu’ils n’apportent pas une valeur opérationnelle claire avant ouverture.
```

À ne pas relancer dans J5Y : nouveau contenu Carnet, page saisonnalité, portraits vendeurs, pagination avancée catalogue, disponibilité produit par commune, paiement en ligne, automatisation SMS.

Raisons : ces sujets sont utiles, mais ils ajoutent une dette de contenu et de validation. Le MVP a maintenant un socle public clair ; le prochain lot doit être choisi explicitement.

# Roadmap 02/07/2026 — Après J5Z : stabiliser le socle et étendre sans réécrire

## J5Z — Checkout/admin UX — terminé

J5Z est clos : validation locale, recette et production réalisées.

```text
Tag recette final : recette-j5z-delivery-fee-reason-refresh-20260702
Tag production final : prod-j5z-delivery-fee-reason-refresh-20260702
Statut : validé production le 02/07/2026
```

Le lot améliore la conversion et la confiance sans changer la formule tarifaire : téléphone avec indicatif explicite, frais expliqués uniquement si nécessaire, flash frais recalculés, formulaire produit EasyAdmin plus efficace, correctifs mobile panier.

## Orientation architecturale post-MVP

Le MVP fonctionne. La roadmap doit désormais éviter les réécritures risquées. La stratégie retenue : extension contrôlée du monolithe Symfony, avec garde-fous, documentation et recette courte avant production.

Ordre recommandé :

1. Clore documentairement J5Z.
2. Traiter les dettes techniques bloquantes ou proches : Symfony 8.0.5 non-LTS, dépréciations Doctrine/EasyAdmin, cron recette Messenger, uploads runtime suivis par Git.
3. Cadrer J5AA `AddressLocality` avant développement.
4. Développer J5AA par petits sous-lots : modèle + seed + EasyAdmin, puis formulaires, puis snapshots/affichages.

## J5AA — Localité d’adresse — prochain lot candidat

Objectif terrain : un client qui dit `je suis à Kawéni` doit pouvoir créer une adresse correcte sans comprendre l’organisation administrative de Mamoudzou.

Périmètre initial recommandé :

```text
J5AA-A — AddressLocality + seed initial + CRUD EasyAdmin
J5AA-B — champ Localité dans les formulaires d’adresse
J5AA-C — commune auto-remplie si localité reconnue
J5AA-D — snapshot commande + affichage admin/Djama/client
```

Règles non négociables :

```text
Localité = précision terrain.
Commune = source logistique et tarifaire.
Commande = snapshot.
```

À éviter dans le MVP : calcul tarifaire par localité, déduction automatique depuis texte libre, recherche floue agressive, GPS automatique par localité, obligation du champ partout dès le premier lot.

# Roadmap 03/07/2026 — Après J5AB / J5AC

## Jalons clôturés

### J5AB — Catalogue mobile orienté achat

Statut : validé production.

Le catalogue est désormais plus adapté à l’achat mobile : recherche visible immédiatement, filtres compacts, bloc institutionnel retiré du haut. Ce lot clôt l’irritant UX principal remonté sur le catalogue.

Référence production :

```text
prod-j5ab-catalogue-mobile-achat-20260703
```

### J5AC — Espace client finalisé avec AJAX discret

Statut : validé production.

L’espace client couvre désormais le socle attendu du MVP : hub compte, commandes, profil, sécurité, reset connecté et AJAX progressif discret.

Référence production :

```text
prod-j5ac-espace-client-ajax-20260703
```

## Orientation suivante

Priorité : ne pas élargir trop vite le produit. Hodina dispose maintenant d’un tunnel plus cohérent avant et après commande.

Ordre recommandé :

1. Documentation et stabilisation post-J5AB/J5AC.
2. Corrections uniquement si bug bloquant constaté sur catalogue ou espace client.
3. J5AA `AddressLocality` dans un lot séparé, sans toucher au calcul livraison.
4. Dette technique ciblée : uploads runtime, dépréciations Symfony/EasyAdmin/Doctrine, scripts de déploiement.
5. Améliorations métier futures seulement après observation terrain.

## Règles de pilotage

- Ne pas mélanger J5AA avec l’espace client.
- Ne pas refaire le moteur catalogue sous prétexte d’UX.
- Ne pas rendre l’espace client plus complexe avant retours réels.
- Garder les asserts J5AB/J5AC dans les contrôles recette/prod.

# Roadmap 04/07/2026 — correction priorités après J5AC

## Priorité corrigée

Le bloc ancien `Portail client MVP` n’est plus une prochaine priorité : J5AC a finalisé le hub compte, le profil, le mot de passe, le reset connecté et l’AJAX progressif discret.

Priorités candidates actuelles :

1. J5AA — `AddressLocality` + cohérence code postal / commune.
2. Page autonome `/mon-compte/adresses`, à cadrer séparément si elle devient prioritaire.
3. Dette technique : Symfony non-LTS, dépréciations Doctrine/EasyAdmin, uploads runtime suivis par Git, cron recette.

## J5AA — point de départ recommandé

Commencer par analyser et consolider le référentiel existant : `DeliveryCommune.postalCode`, `DeliverableAddressValidator`, formulaires checkout / inscription et snapshots d’adresse. Ne pas coder l’autocomplete localité avant d’avoir verrouillé le modèle de données et la validation serveur.

Découpage recommandé :

```text
J5AA-A — modèle AddressLocality + seed + EasyAdmin + décisions code postal/commune.
J5AA-B — intégration formulaires adresse avec sélection cohérente code postal / commune.
J5AA-C — snapshots commande + affichages admin/client/Djama/email si utile.
```
