# HODINA - Vision

## Historique conservé

État initial du document de référence :

```text
Marketplace locale dédiée à Mayotte.

Mission :
Aider les producteurs, artisans et vendeurs locaux à vendre davantage tout en simplifiant l'accès aux produits locaux.
```

Cette vision reste inchangée. Les décisions J4 et J5 viennent la rendre plus opérationnelle.

---

# Vision générale

Hodina est une marketplace locale dédiée à Mayotte.

Sa vocation est de reconnecter les habitants avec les producteurs, artisans, vendeurs et revendeurs locaux, en rendant les produits plus visibles, plus accessibles et plus faciles à commander.

Hodina ne cherche pas seulement à créer une boutique en ligne. Le projet vise à structurer un circuit local :

- visibilité des vendeurs ;
- prise de commande ;
- validation humaine ;
- préparation ;
- livraison ;
- relation client ;
- traçabilité des étapes.

---

# Mission

Aider les producteurs, artisans et vendeurs locaux à vendre davantage tout en simplifiant l'accès aux produits locaux pour les habitants de Mayotte.

---

# Principes du pilote

Le pilote Hodina repose sur un fonctionnement volontairement simple et contrôlé :

- paiement manuel ;
- validation admin obligatoire ;
- disponibilité confirmée humainement ;
- commandes traitées par étapes ;
- notifications simulées via SmsLog ;
- possibilité d'envoyer manuellement les SMS depuis un iPhone ;
- couverture géographique administrable ;
- interface mobile-first pour les usages terrain.

Ce choix permet de tester le marché sans complexifier trop tôt le produit avec du paiement en ligne, des prestataires SMS ou de la logistique automatisée.

---

# Vision opérationnelle J4

J4 a rendu le traitement admin réellement utilisable.

La commande n'est plus seulement une ligne en base : elle devient un dossier opérationnel avec :

- numéro métier ;
- client ;
- adresse ;
- zone ;
- articles ;
- total ;
- statut ;
- dates métier ;
- SmsLog ;
- fiche terrain.

L'admin peut suivre une commande depuis la validation jusqu'à la livraison ou l'annulation.

---

# Vision opérationnelle J5

J5 doit connecter le backoffice commande à la réalité logistique.

La décision retenue est le modèle B :

```text
Le livreur voit les commandes prêtes et les prend lui-même.
```

Cela correspond bien au pilote Hodina, car :

- le système reste simple ;
- il n'y a pas besoin d'un dispatch complexe au départ ;
- les livreurs peuvent agir depuis leur téléphone ;
- l'admin garde le contrôle de la préparation ;
- le livreur prend le relais uniquement quand la commande est prête.

---

# Dashboard livreur

Le portail livreur sera un dashboard authentifié dédié, mobile-first, séparé du backoffice EasyAdmin admin.

Il ne s'agit pas de priver les livreurs d'un dashboard, mais au contraire de leur fournir une interface plus adaptée que le CRUD EasyAdmin.

Le livreur doit voir uniquement ce qui lui est utile :

- commandes prêtes ;
- commandes qu'il a prises ;
- adresse ;
- téléphone ;
- SMS ;
- articles ;
- total à encaisser ;
- actions simples.

---

# Intelligence de développement

La vision technique retenue pour J5 est de coder intelligemment :

```text
ne pas dupliquer la logique de changement de statut.
```

La logique commune doit être centralisée dans un service métier :

```text
CustomerOrderWorkflowService
```

Ainsi, l'admin et le livreur utilisent les mêmes règles métier :

- mêmes transitions ;
- mêmes dates ;
- mêmes SmsLog ;
- mêmes sécurités ;
- moins de duplication ;
- moins de risques d'incohérence.

---

# Vision long terme

À terme, Hodina pourra évoluer vers :

- paiement en ligne ;
- SMS réels ;
- suivi livreur ;
- preuve de livraison ;
- optimisation des tournées ;
- portail vendeur ;
- statistiques ;
- extension géographique.

Mais le pilote garde une priorité :

```text
un outil simple, fiable, mobile et utilisable sur le terrain à Mayotte.
```

---

# Vision préproduction et conformité pilote

Le 05/06/2026, la vision du pilote a été complétée par une étape de préproduction.

L'objectif n'est plus seulement de faire fonctionner Hodina localement, mais de permettre à un testeur externe, notamment l'admin terrain à Mayotte, de valider les parcours sur une URL réaliste :

```text
https://recette.hodina.fr
```

## Vision préprod

La préprod doit rester :

- protégée ;
- proche de la future production ;
- isolée de la base production ;
- utilisable sur mobile ;
- testable par l'équipe restreinte.

La Basic Auth est assumée comme protection temporaire de recette.

## Vision légale

Les CGU et CGV sont ajoutées avant de continuer, car le parcours client doit présenter un minimum de cadre légal avant les tests terrain.

Pour le pilote, les textes doivent rester cohérents avec la réalité actuelle :

- paiement manuel ;
- validation admin ;
- SMS manuel ;
- disponibilité confirmée humainement ;
- Hodina comme intermédiaire technique / logistique.

## Vision UX

Les pages légales doivent être consultables sur mobile sans gêner l'utilisateur.

Décision UX : un sommaire compact horizontal est préférable à un grand sommaire vertical sur téléphone.

## Vision sécurité publique

Le backoffice ne doit pas être mis en avant dans l'interface publique.

Le lien `Admin` a donc été retiré du footer public, même si `/ouegnewe` reste accessible aux administrateurs qui connaissent l'URL.


---

# Vision J5C — Préparer la livraison sans brûler les étapes

Le 06/06/2026, Hodina a franchi une deuxième étape structurante dans J5 : les données nécessaires à la prise en charge livreur sont prêtes.

Cette étape ne crée pas encore le dashboard livreur. Elle prépare le terrain de manière robuste.

## Vision produit

Le modèle logistique reste le modèle B :

```text
Le livreur voit les commandes prêtes et les prend lui-même.
```

Pour rendre ce modèle possible, chaque commande doit pouvoir garder la trace de :

- quel livreur l'a prise ;
- quand elle a été assignée ;
- quand elle est partie en livraison.

C'est exactement le rôle des champs ajoutés en J5C.

## Vision métier

Une commande prête n'est pas encore une commande en livraison.

La distinction est désormais claire :

```text
READY_FOR_PICKUP
→ commande prête, visible par les livreurs

OUT_FOR_DELIVERY
→ commande prise par un livreur, en cours de livraison
```

Cette distinction prépare un vrai suivi terrain, même si le pilote reste simple.

## Vision technique

Hodina confirme le principe :

```text
Le service métier reste le moteur du workflow.
```

Le futur dashboard livreur devra appeler les méthodes déjà préparées :

```text
takeForDelivery()
markDeliveredByCourier()
```

Il ne devra pas recréer une logique parallèle.

## Vision sécurité

La route `/djama` est réservée dès maintenant à :

```text
ROLE_COURIER
```

Même si le dashboard n'est pas encore développé, le cadre de sécurité est posé.

## Vision de développement

J5C confirme une méthode de travail saine :

1. préparer la donnée ;
2. préparer le service métier ;
3. déployer et tester ;
4. seulement ensuite créer l'interface.

Cette progression limite les risques et rend chaque étape testable.


---

# Vision économique et logistique après J5D

Après la validation du dashboard livreur, Hodina entre dans une phase plus stratégique.

Le sujet n'est plus seulement de traiter une commande, mais de construire un modèle économique clair pour :

- les vendeurs ;
- les livreurs ;
- Hodina ;
- les clients.

La vision retenue est de séparer les flux :

```text
prix producteur vendeur
+ marge produit Hodina
+ frais de livraison client
= total payé par le client
```

Puis :

```text
vendeur → reçoit son prix producteur
livreur → reçoit un forfait de livraison
Hodina → garde la marge produit + éventuellement une part de livraison
```

Cette séparation évite les frustrations.

## Vision vendeur

Le vendeur ne doit pas avoir l'impression que Hodina lui retire une partie de son prix.

Le modèle présenté au vendeur est :

```text
Tu fixes ton prix producteur.
Hodina ajoute sa marge au-dessus.
Tu sais combien tu touches.
```

À terme, le portail vendeur permettra au vendeur de gérer lui-même ses produits, mais Hodina gardera le contrôle du calcul du prix client.

## Vision livreur

Le livreur ne doit pas dépendre d'un pourcentage variable du panier.

Le modèle retenu pour le pilote est :

```text
forfait par livraison selon la zone tarifaire
```

Ainsi, le livreur sait avant de prendre une commande combien il peut gagner.

## Vision client

Le client doit voir un prix simple et compréhensible.

Il ne voit pas :

- la marge Hodina ;
- le détail rémunération livreur ;
- les calculs internes.

Mais il doit être informé si la livraison est particulière :

- vendeur éloigné ;
- vendeur sur une autre île ;
- barge requise ;
- frais adaptés.

## Vision pilote

Pendant le pilote, Hodina privilégie des règles simples, administrables et testables :

```text
marge produit hiérarchique
zones tarifaires administrables
communes administrables
communes voisines définies par l'admin
barge calculée par comparaison PT / GT
aperçu logistique dans le panier
```

## Vision après pilote

Après le pilote, Hodina pourra améliorer :

- calcul automatique de distances ;
- cartes ;
- optimisation de tournée ;
- suivi live ;
- facturation vendeur ;
- portail vendeur complet ;
- statistiques de marge ;
- automatisation des reversements.


---

# Vision J5E — Modèle économique produit validé

J5E transforme Hodina d'un catalogue avec prix saisi en une marketplace avec un début de modèle économique contrôlé.

La vision devient :

```text
un vendeur fixe son prix producteur
Hodina ajoute sa marge
le client voit un prix final simple
la commande conserve les valeurs au moment de l'achat
```

## Vision vendeur

Discours à conserver :

```text
Vous indiquez votre prix producteur.
Hodina ajoute sa marge pour financer la plateforme, la relation client et l'organisation.
Vous savez à l'avance combien vous touchez.
```

## Vision client

Le client voit un prix final clair. Il n'a pas besoin de voir le détail interne de marge pour acheter.

## Vision Hodina

Hodina sépare progressivement ses sources de marge :

```text
marge produit Hodina
+ future marge livraison éventuelle
```

## Vision technique

Comme `CustomerOrderWorkflowService`, J5E confirme qu'une règle métier utilisée à plusieurs endroits doit vivre dans un service :

```text
ProductPricingService
```

## Vision de robustesse

Les anciennes commandes ne doivent pas changer quand les règles changent. Les valeurs économiques sont donc figées dans `OrderItem`.

## Suite

Après J5E, la priorité est J5F : communes, zones tarifaires, barge, rémunération livreur et marge livraison Hodina.

---

# Vision J5F clarifiée — La barge est une traversée, pas une distance

## Clarification produit

Hodina doit refléter la réalité logistique mahoraise.

Pour aller d'une commune de Petite-Terre à une autre commune de Petite-Terre, il n'y a pas de barge.

Pour aller d'une commune de Grande-Terre à une autre commune de Grande-Terre, il n'y a pas de barge.

La barge est liée à la traversée entre Petite-Terre et Grande-Terre.

## Vision client

Le client ne doit pas payer ou voir un message barge si sa commande reste sur le même territoire.

Exemples :

```text
Client à Dzaoudzi
Produit à Pamandzi
→ livraison interne Petite-Terre
→ pas de message barge
```

```text
Client à Mamoudzou
Produit à Koungou
→ livraison interne Grande-Terre
→ pas de message barge
```

En revanche :

```text
Client à Dzaoudzi
Produit à Mamoudzou
→ traversée PT/GT
→ message barge possible
```

## Vision business

Cette clarification protège la confiance client.

Si Hodina applique des frais barge à une livraison PT → PT ou GT → GT, le client ou le vendeur pourra trouver le tarif incohérent.

La règle simple et explicable est donc :

```text
même territoire = pas de barge
territoire différent = barge
```

## Vision technique

La distance, le voisinage et la barge sont trois notions différentes :

```text
distance
→ sert au ressenti logistique

voisinage
→ sert au message client

barge
→ sert au changement de territoire PT / GT
```

Cette séparation doit guider J5F et J5G.


---

# Vision J5F-A / J5F-B — De la donnée logistique au service métier

## Ce qui change

Avec J5F-A et J5F-B, Hodina passe d'une logique de livraison encore essentiellement administrative à une logique de calcul métier préparée.

Avant :

```text
commune texte
zone de livraison simple
interprétation humaine
```

Après J5F-A / J5F-B :

```text
commune métier
territoire PT / GT
zones tarifaires
voisinage
service de calcul
aperçu logistique prêt pour le panier
```

## Vision terrain

À Mayotte, la logistique n'est pas seulement une distance. Il y a une réalité particulière : Petite-Terre et Grande-Terre.

Hodina doit donc être capable de dire simplement :

```text
Même territoire → pas de barge
Territoire différent → barge
```

Cela protège la confiance client et évite de facturer ou d'annoncer une contrainte barge quand elle n'existe pas.

## Vision économique

Les frais de livraison sont désormais séparés en deux montants :

```text
frais payés par le client
rémunération prévue du livreur
```

La différence est la marge livraison Hodina.

Cette marge livraison reste secondaire par rapport à la marge produit, mais elle doit être visible dans le modèle interne.

## Vision développeur

J5F confirme une règle importante :

```text
La donnée d'abord, le service ensuite, l'interface après.
```

J5F-A crée les données administrables.

J5F-B crée le service métier.

J5G branchera le service dans le panier.

Cette progression évite de mélanger trop vite base de données, interface client et règles métier.

## Vision portail vendeur

Le futur vendeur devra choisir sa commune de retrait / production via `DeliveryCommune`, pas via un texte libre.

Cela permettra au portail vendeur de réutiliser directement les règles logistiques sans nouveau modèle.


---

# Vision navigation — Admin visible seulement pour les admins connectés

## Objectif

Garder une interface publique simple tout en facilitant l'accès admin pour les comptes autorisés.

## Décision UX

Le footer public ne montre toujours pas `Admin`.

Le header affiche `Admin` uniquement si l'utilisateur connecté a `ROLE_ADMIN`.

Un admin n'a pas besoin que l'interface lui tienne trop la main : s'il possède aussi le rôle livreur, on affiche seulement `Admin` pour garder le header léger.

---

# Vision J5G avancée — Une livraison fidèle au terrain mahorais

## Vision métier

Hodina ne doit pas seulement dire “barge ou pas barge”.

La livraison à Mayotte dépend aussi :

```text
des communes traversées
du temps de route
des contraintes Petite-Terre / Grande-Terre
du coût réel pour le livreur
```

Le modèle décidé devient plus juste :

```text
plus le livreur traverse de communes,
plus sa rémunération doit augmenter.
```

## Vision client

Le client doit comprendre que les frais de livraison ne sont pas arbitraires.

Ils tiennent compte :

```text
de la commune de livraison
du trajet nécessaire
de la barge si la commande traverse PT / GT
```

Mais l'interface doit rester simple. Le client n'a pas besoin de voir tout le détail technique du graphe.

## Vision livreur

Le livreur ne doit pas être payé pareil pour une livraison courte et une livraison plus longue.

Le modèle cible protège la motivation livreur :

```text
forfait local
+ supplément par commune traversée
+ compensation barge si nécessaire
```

## Vision Hodina

Hodina garde une marge livraison calculable, mais la priorité du pilote reste l'adoption.

La marge livraison peut rester faible au départ. Le plus important est :

```text
livreur correctement rémunéré
client informé
modèle compréhensible
paramètres modifiables
```

## Vision technique

Les communes voisines deviennent une base de calcul métier.

Ce n'est pas encore un GPS. C'est un graphe simple, administré par l'équipe Hodina.

Cette approche est adaptée au pilote :

```text
simple
compréhensible
testable
modifiable depuis EasyAdmin
```


---

# Vision J5G-B — La donnée logistique doit être vivante

## Vision produit

Hodina ne doit pas dépendre d'une donnée logistique figée dans un fichier ou dans le code.

La réalité terrain à Mayotte peut évoluer :

- une route peut devenir moins pratique ;
- un point de collecte peut changer ;
- une liaison peut être préférable selon l'usage ;
- une commune peut nécessiter une note terrain ;
- la barge doit être traitée comme une liaison spécifique.

## Vision retenue

```text
Source Excel validée
→ import initial
→ base de données modifiable
→ EasyAdmin pour correction terrain
→ DeliveryLogisticsService pour calcul
```

## Vision critique

L'idée de table de hashage est utile, mais seulement comme optimisation en mémoire.

Le bon modèle pour Hodina est :

```text
donnée relationnelle propre en base
+ calcul rapide en PHP
```

## Vision terrain

Labattoir illustre bien le besoin Hodina : ce n'est pas seulement l'administration officielle qui compte, c'est aussi la réalité de livraison.

Hodina doit pouvoir gérer :

```text
commune officielle
localité terrain
point logistique
liaison terrestre
liaison barge
```

Cette souplesse est une force pour le pilote.

---

# Mise à jour vision — livraison avancée Hodina

J5G-B2 / J5G-B3 renforcent une idée stratégique importante : Hodina ne doit pas seulement vendre des produits, Hodina doit comprendre la réalité logistique de Mayotte.

La carte de communes et de liaisons permet de préparer :

- une tarification plus juste ;
- une rémunération livreur plus cohérente ;
- une meilleure explication client ;
- une meilleure décision admin ;
- une exploitation future par le portail vendeur et le portail livreur.

Le choix de rendre les données modifiables en EasyAdmin protège le pilote : si une liaison est mal modélisée ou si le terrain montre une autre réalité, on corrige la donnée sans redéployer le code.

La vision reste progressive :

```text
données propres
→ service de calcul
→ affichage panier
→ snapshot commande
→ optimisation terrain
```

## Vision produit — qualité des adresses comme socle de confiance

La distinction livraison / facturation renforce la crédibilité de Hodina.

Pour le client, cela signifie :

```text
je peux me faire livrer à Mayotte sur une commune réellement desservie
je peux avoir une adresse de facturation différente, par exemple en métropole
l'application ne mélange pas mes besoins administratifs et logistiques
```

Pour Hodina, cela signifie :

```text
moins d'erreurs de livraison
moins de corrections manuelles
calcul logistique plus fiable
meilleure préparation au futur portail vendeur/livreur
```

Ce choix évite de traîner une dette fonctionnelle dès le pilote.

---

# Vision — mise à jour 12/06/2026 — ne pas traîner de boulets fonctionnels

La session support adresses confirme une règle importante pour Hodina :

```text
Quand une dette fonctionnelle bloque la fiabilité du pilote, on la corrige immédiatement.
```

La séparation livraison / facturation n'est pas un détail technique. Elle protège :

```text
le client
l'admin
le livreur
le futur calcul de frais
le futur portail vendeur
```

## Qualité attendue pour un pilote terrain

Même en pilote, Hodina doit éviter les ambiguïtés suivantes :

```text
adresse de livraison hors zone acceptée
facturation forcée hors zone alors qu'elle peut être à Mayotte
e-mail existant utilisé sans connexion
erreurs incompréhensibles pour le client
formulaire vidé après erreur
```

## Impact confiance

Un client qui saisit une mauvaise adresse doit comprendre précisément le problème.

Exemple :

```text
Le code postal 97619 ne correspond pas à la commune Labattoir. Le code postal attendu est 97615.
```

C'est plus rassurant qu'un rejet générique.

## Impact logistique

Une adresse fiable permet ensuite de calculer correctement :

```text
trajet
barge
distance communale
frais de livraison
rémunération livreur
marge livraison
```

La qualité des adresses est donc un socle de la marketplace, pas un détail de formulaire.

---

# Vision — mise à jour 13/06/2026 — lancement maîtrisé et confiance client

La bannière de préouverture permet de montrer que Hodina arrive, de laisser le catalogue visible et de bloquer les commandes tant que l'organisation terrain n'est pas officiellement prête.

Formule stratégique retenue :

```text
Voir l'offre maintenant.
Commander à l'ouverture officielle.
```

Dès que les commandes seront ouvertes, l'e-mail automatique de création de commande rassurera le client avec un récapitulatif complet : produits, prix, total, adresse de livraison, adresse de facturation et rappel du paiement à la livraison.

L'e-mail de création ne doit pas dire que la commande est validée : il doit indiquer qu'elle est reçue et en attente de validation par l'équipe Hodina.


---

# Vision — validation terrain 13/06/2026 — préouverture comme sas de confiance

La préouverture devient un sas entre le développement et les premières commandes réelles.

Elle permet de :

```text
montrer que Hodina avance ;
laisser les clients découvrir le catalogue ;
récupérer des e-mails de personnes intéressées ;
éviter les commandes trop tôt ;
protéger l'équipe terrain avant l'ouverture officielle.
```

Ce choix est cohérent avec la stratégie pilote : aller vite, mais ne pas créer une mauvaise première expérience client.

Le message à retenir :

```text
Hodina est visible avant d'être ouvert aux commandes.
```

---

# Vision J5J — Portail visible, commandes contrôlées

J5J permet à Hodina d'être visible publiquement sans ouvrir immédiatement les commandes. C'est important pour communiquer, rassurer les vendeurs, collecter des e-mails et tester la production réelle.

La vision retenue :

```text
Le public peut voir Hodina.
Hodina garde le contrôle sur l'ouverture des commandes.
Les testeurs internes peuvent valider la production sans ouvrir au public.
```

Ce fonctionnement est plus professionnel qu'une page de maintenance classique.

---

# Complément vision pilote — e-mails de confiance

J5H-A renforce la confiance client pendant la phase pilote.

Le client reçoit maintenant un e-mail clair après commande :

- numéro de commande ;
- statut en attente de validation ;
- articles commandés ;
- quantités ;
- prix ;
- frais de livraison ;
- total à régler ;
- rappel du paiement à la livraison.

Ce choix soutient la stratégie Hodina : rester simple, humain et transparent pendant le pilote, sans ajouter de paiement en ligne trop tôt.

---

# Vision J5G-E0 — Carnet d'adresses vivant, commande figée

Une marketplace fiable doit distinguer les données vivantes des données historiques.

Le client doit pouvoir gérer son carnet d'adresses librement : ajouter, corriger, supprimer. Mais une commande déjà passée doit rester compréhensible même plusieurs mois plus tard.

J5G-E0 pose donc une règle simple et durable :

```text
Une adresse client appartient au présent.
Une adresse de commande appartient à l'historique.
```

Cette règle protège :

- l'administration qui doit relire une commande ;
- le livreur qui doit comprendre où livrer ;
- le client qui reçoit un récapitulatif fiable ;
- Hodina qui doit garder une trace opérationnelle même si le carnet client change.

Cette logique prépare aussi les futurs exports, litiges, remboursements, relances et bilans opérationnels.

---

# Vision J5G-E1 — Le client choisit une commune, Hodina gère la logistique

Hodina doit rester simple pour le client. Le client ne doit pas connaître les subtilités internes : Petite-Terre, Grande-Terre, zone tarifaire, barge, communes voisines, route logistique.

La bonne expérience est :

```text
Je choisis où je veux être livré.
Hodina me dit si c'est livrable.
Hodina calcule le reste.
```

Cette vision est cohérente avec Mayotte, où les adresses et le GPS peuvent être imparfaits. La commune / point logistique devient une aide terrain, pas une contrainte technique visible.

---

# Vision — précision J5G-E1 / panier contractuel

Le pilote Hodina privilégie un parcours très court : le client doit comprendre le total avant de valider, sans être obligé de traverser plusieurs écrans.

Décision confirmée :

```text
Pendant le paiement manuel, le panier porte la livraison et la validation.
Le checkout reviendra plus tard pour le paiement en ligne et la facturation.
```

Cette décision protège la simplicité du MVP : moins d'étapes, moins d'erreurs d'adresse, moins de confusion entre livraison et facturation.

---

# Vision production — J5G-E1 à J5G-E2-bis-A validé

Le passage en production de J5G-E1 à E2-bis-A confirme une orientation produit importante : Hodina doit simplifier au maximum le parcours client tout en gardant une logique serveur stricte.

La vision confirmée en production est :

```text
Le client choisit une commune livrée.
Hodina déduit le code postal, la zone, la barge et les frais.
Le panier devient l'écran contractuel du total.
La commande n'est créée que si le total vu est encore le total réel.
```

Ce choix est cohérent avec le terrain mahorais : l'adresse précise peut être imparfaite, mais la commune / point logistique permet déjà une organisation fiable pour le pilote.

Le checkout n'est pas supprimé comme concept futur. Il est simplement replacé au bon endroit : plus tard, il servira au paiement en ligne et à la facturation, pas au choix principal de livraison.

Tag de référence :

```text
j5g-e1-e2bis-prod
```

## Mise à jour 18/06/2026 — robustesse exploitation pilote

La vision pilote Hodina reste centrée sur la preuve terrain : commande, validation admin, collecte, livraison.

La MEP J5G-B4 ajoute une brique importante : la capacité à livrer plus sereinement en production grâce à un déploiement par tag, un backup automatique, une protection des fichiers runtime et une traçabilité des snapshots logistiques.

Cette robustesse d'exploitation est indispensable avant d'élargir le pilote à davantage de vendeurs / clients.

# Vision 19/06/2026 — après v11

La production dispose maintenant d'un socle plus solide : logistique avancée, Ajax panier, admin mobile plus utilisable, miniatures admin, mails de commande réels.

## Nouvelle priorité

Le prochain enjeu n'est pas d'ajouter du paiement en ligne, mais de rendre l'exploitation terrain plus fiable.

À Mayotte, l'adresse textuelle est souvent insuffisante. Hodina doit donc ajouter progressivement une aide GPS sans casser la simplicité du pilote.

## Vision J5K

```text
Le client choisit sa commune livrée comme aujourd'hui.
Il peut ajouter sa position GPS si disponible.
L'admin voit un lien carte.
Le livreur pourra ouvrir l'itinéraire.
La commande reste validée humainement.
```

## Vision J5L

L'admin doit avoir une vraie fiche terrain : qui appeler, où collecter, où livrer, quel trajet, quel statut, quelle prochaine action.

## Vision J5M

Le livreur doit pouvoir agir depuis son téléphone sans complexité : prendre une commande prête, voir le client, ouvrir l'itinéraire, marquer livré.

## Vision paiement

Le paiement reste après la preuve terrain. Il ne faut pas automatiser une logistique qui n'est pas encore observée en conditions réelles.

---

# Vision — après J5Q-A : confiance opérationnelle livreur

Hodina ne doit pas seulement organiser la livraison ; il doit aussi créer de la confiance avec les personnes qui livrent.

Le paiement des livreurs est un sujet de fatigue et de confiance. Si le montant dû est calculé à la main ou mémorisé hors système, Hodina risque de déplacer la charge mentale au lieu de la réduire.

J5Q-A ajoute donc une brique cohérente avec la vision du projet :

```text
commande livrée
→ gain livreur calculé
→ période de paiement claire
→ validation admin
→ paiement marqué payé
→ historique visible dans Djama
```

Ce choix reste compatible avec le pilote : il n'y a pas de virement automatique ni de paiement en ligne. Le système trace et sécurise l'exploitation, l'humain garde l'action réelle de paiement.

La confiance recherchée concerne désormais les trois acteurs :

```text
client   → notifications, code réception, suivi futur
vendeur  → collecte sécurisée et traçable
livreur  → commandes, GPS, code client, historique de rémunération
```

La prochaine étape logique côté expérience reste le portail client MVP, pour donner au client la même visibilité que celle donnée progressivement aux livreurs.

---

# Vision 25/06/2026 — traçabilité des environnements et observabilité recette

Après J5Q-A, J5Q-C, J5Q-C-1 et J5Q-C-2, Hodina avance vers une exploitation plus fiable : les paiements livreurs sont historisés, leur génération de brouillons est encadrée par des réglages, et les e-mails portent un branding centralisé.

La logique produit reste la même : ne pas automatiser les actions financières sensibles, mais réduire la charge mentale et clarifier ce qui se passe.

## Branding e-mail

Le branding e-mail répond à un besoin simple : ne pas confondre un e-mail de recette avec un e-mail de production.

```text
dev / recette / production
→ objet identifiable
→ formule homogène
→ signature cohérente
→ EmailLog aligné avec le sujet réellement envoyé
```

Le préfixe `[Recette]` n'est pas imposé par défaut dans le code. Il est configuré dans la base recette via EasyAdmin. La production garde un défaut sobre pour éviter toute pollution de marque.

## Observabilité recette

L'incident intermittent `ERR_CONNECTION_CLOSED` observé sur mobile rappelle qu'un pilote terrain ne se valide pas uniquement avec Doctrine et Twig. Il faut aussi savoir capturer ce qui se passe au niveau navigateur, PHP web, logs d'accès et Symfony.

La documentation `DEBUG_RECETTE_HODINA.md` devient une pièce de support : elle sert à diagnostiquer sans rollback réflexe quand les contrôles applicatifs sont bons mais que le navigateur coupe la connexion.


# Vision 27/06/2026 — Logistique extensible sans casser la barge

Hodina entre dans une phase où la promesse client doit devenir plus fine : certains produits peuvent demander un délai de préparation, certains produits peuvent être remis à des points précis, et les jours de livraison doivent refléter les tournées réelles de Mayotte.

La vision retenue reste prudente : ajouter de la précision opérationnelle sans rigidifier le système.

Principes :

```text
le client voit une promesse simple ;
Hodina garde une validation humaine ;
la donnée historique de commande est snapshotée ;
la logistique est structurée sans casser la barge ;
les sous-zones futures servent au planning, pas aux coûts.
```

Le découpage hérité de Hodidagoni sert de base terrain, mais il doit être modélisé comme une couche extensible. Demain, une sous-zone pourra servir à affecter des livreurs ou organiser des tournées, sans modifier la grande séparation Petite-Terre / Grande-Terre.

Règle de vision :

```text
DeliveryPricingZone porte le forfait local.
DeliveryCommuneConnection et PT/GT protègent la barge.
DeliveryArea organisera le terrain.
```

La livraison express doit rester une demande pendant le pilote, non une promesse automatique. Cela protège Hodina tant que les disponibilités vendeurs, livreurs, barge et trafic ne sont pas automatisées.

## Principe checkout mobile — point de remise et simplicité client

Le checkout mobile doit toujours refléter le choix réel du client :

- une livraison à l’adresse du client ;
- ou une remise dans un point Hodina.

Ces deux modes ne doivent pas être mélangés à l’écran. En mode point de remise, le client ne doit pas croire qu’il choisit une adresse libre. En mode livraison standard, il ne doit pas voir les points de remise si ceux-ci ne sont pas utiles au choix en cours.

La simplicité ne signifie pas absence de règles : les contraintes métier restent côté serveur. L’interface doit seulement guider sans afficher des erreurs trop tôt et sans bloquer silencieusement.

# Vision 01/07/2026 — Catalogue comme porte d’entrée et Découvrir Hodina

Après les lots J5X et J5Y, l’entrée publique de Hodina évolue : le catalogue devient la homepage (`/`) et l’ancienne landing devient la page institutionnelle `/decouvrir-hodina`.

Ce choix est cohérent avec l’ouverture progressive : un visiteur doit d’abord voir les produits disponibles, comprendre les prix et pouvoir ajouter au panier. La page éditoriale reste utile, mais elle devient un espace d’explication et de confiance plutôt qu’un sas obligatoire avant le catalogue.

La vision produit se précise ainsi :

```text
/                         → acheter / explorer les produits locaux
/decouvrir-hodina         → comprendre le projet, rejoindre comme client, vendeur ou livreur
```

La page Découvrir Hodina reste la page institutionnelle. La ligne éditoriale est désormais amorcée par `/carnet`, mais uniquement comme espace pédagogique utile au MVP. Au 01/07/2026, seule la page `/carnet/livraison` est active ; les contenus `Fruits, légumes et saisons` et `Nos vendeurs et producteurs partenaires` restent à venir, sans lien actif. Le Carnet n’est donc pas un blog généraliste : il sert d’abord à réduire les incompréhensions client et à rassurer sur les contraintes terrain.

Principes validés pour ne pas disperser le MVP :

- le catalogue reste simple, mobile-first et orienté conversion ;
- l’espace Carnet Hodina est ouvert de manière limitée et utile : `/carnet/livraison` seulement ; `/blog/...` reste seulement une compatibilité de redirection legacy ;
- les vendeurs, clients et livreurs doivent comprendre leur place dans l’écosystème ;
- les promesses restent prudentes : Hodina montre des créneaux et prochains passages possibles, mais la validation humaine reste active pendant le pilote ;
- l’identité visuelle du header doit être lisible sans transformer l’en-tête en bannière.

Point de vigilance historique : plus la homepage ressemble à un catalogue, plus la qualité des fiches produits, des images, des prix, du panier et des messages de livraison devient critique. J5Y a ensuite été validé recette puis production le 01/07/2026.

# Vision 01/07/2026 — Carnet Hodina et réassurance livraison validés production

J5Y-E/F/G/H confirme une évolution importante : Hodina ne doit pas seulement afficher des produits, il doit aussi expliquer simplement comment le service fonctionne sur le terrain mahorais.

Décision produit validée :

```text
/decouvrir-hodina = page institutionnelle publique.
/carnet = espace pédagogique Hodina.
/carnet/livraison = première page utile du Carnet, centrée sur la réassurance livraison.
Blog = terme évité côté UX publique.
Djama = portail privé, non exposé publiquement.
```

La livraison est un levier de confiance aussi important que le prix ou la photo produit. À Mayotte, un client veut comprendre rapidement si Hodina livre sa commune, quels jours sont envisagés, et pourquoi les délais peuvent varier. La page `/carnet/livraison` répond à ce besoin sans promettre une livraison garantie.

Le header est volontairement orienté conversion et réassurance : le lien visible devient `Infos livraison`. La page `Découvrir Hodina` reste accessible dans le footer, car elle sert davantage à comprendre le projet qu’à finaliser une commande immédiate.

Règle stratégique : continuer à expliquer le fonctionnement de Hodina, mais ne pas transformer le MVP en média trop tôt. Le Carnet doit rester utile, court et lié aux problèmes réels des clients : livraison, saisons, vendeurs, produits et usages.

Statut : J5Y-E/F/G/H validé en recette sous le tag `recette-j5y-carnet-livraison-footer-clean-20260701`, puis validé production sous le tag `prod-j5y-carnet-livraison-footer-20260701`.

# Vision 02/07/2026 — MVP stable, application future par extensions

Après J5Y et J5Z, Hodina dispose d’un MVP public et opérationnel validé production : catalogue, panier, checkout, points de remise, frais expliqués, téléphone client, admin produit, emails/SMS et portail terrain.

Le risque principal n’est plus le manque de fonctionnalités. Le risque principal est de casser ce qui fonctionne en voulant préparer trop tôt l’application future.

Principe stratégique retenu :

```text
Ne pas réécrire le MVP.
Stabiliser le noyau.
Ajouter des extensions autour.
```

Le noyau à protéger :

- panier ;
- commune de livraison ;
- frais / barge / communes traversées ;
- points de remise et créneaux ;
- snapshots commande ;
- Djama ;
- emails/SMS ;
- EasyAdmin exploitation.

## J5Z — Confiance client au checkout

J5Z renforce la confiance sans changer les règles métier : le client choisit son indicatif, comprend pourquoi des frais augmentent, reçoit un flash lisible après changement d’adresse, et voit les annotations uniquement quand elles sont justifiées.

Règle de communication validée :

```text
Frais standard simple → pas d’annotation.
Frais avec barge / communes traversées → annotation visible.
```

Cela évite de surcharger le panier tout en expliquant les suppléments réels.

## J5AA — Localité d’adresse comme extension terrain

La réflexion J5AA prépare une extension adaptée à Mayotte : ajouter une `Localité` d’adresse, expliquée en petit comme `Village / quartier / lieu-dit`.

Cette évolution répond à un besoin terrain : un client pense souvent `Kawéni`, `Mtsapéré`, `Kavani` ou `Tsoundzou II` avant de penser `Mamoudzou`.

Vision retenue :

```text
La localité aide à localiser.
La commune calcule.
La commande snapshotte.
Le livreur comprend.
```

Le choix technique `AddressLocality` évite d’enfermer Hodina dans le seul cas `village de livraison`. Il permet demain de gérer aussi facturation, retrait vendeur, lieux-dits hors Mayotte et extensions géographiques.

À éviter : calculer les frais par localité, deviner une commune depuis un texte libre, rendre le champ obligatoire partout trop tôt, ou refaire le checkout pour cette seule évolution.

# Vision 03/07/2026 — Catalogue achat-first et espace client de confiance

Après J5AB et J5AC, Hodina renforce deux moments critiques du parcours client :

```text
Avant achat  → voir rapidement les produits et chercher sans friction.
Après achat  → suivre ses commandes et gérer son compte sans dépendre de l’admin.
```

J5AB confirme que la page catalogue est une page d’achat, pas une page institutionnelle. Le discours pédagogique reste disponible dans `/decouvrir-hodina`, le footer et le Carnet, mais il ne doit plus repousser les produits vers le bas sur mobile.

J5AC confirme que l’espace client devient une brique de confiance du MVP : le client peut consulter ses commandes en cours/passées, accéder au détail protégé de ses commandes, modifier son profil, changer son mot de passe avec l’ancien mot de passe et demander un lien de réinitialisation.

Principe produit acté :

```text
Catalogue = acheter vite.
Espace client = rassurer après achat.
Découvrir Hodina / Carnet = expliquer sans bloquer l’achat.
```

Cette évolution ne transforme pas Hodina en application complète. Elle stabilise le MVP autour de la confiance : achat plus rapide, suivi plus clair, compte client plus autonome.

À protéger :

- ne pas réintroduire un gros hero institutionnel au-dessus des produits ;
- ne pas refaire le moteur catalogue dans un lot UX ;
- ne pas transformer l’espace client en back-office client complexe ;
- ne pas modifier les règles panier, checkout, Djama ou livraison dans les lots d’interface ;
- conserver les parcours sans JavaScript comme fallback.

Statut : J5AB validé production sous `prod-j5ab-catalogue-mobile-achat-20260703`. J5AC validé production sous `prod-j5ac-espace-client-ajax-20260703`.

# Vision 04/07/2026 — adresse plus guidée sans refaire le socle

Après J5AC, Hodina doit continuer à évoluer par extension contrôlée. Le prochain besoin terrain autour de l’adresse ne doit pas refaire le panier ou le checkout : il doit améliorer la qualité des données saisies.

Principe :

```text
Code postal = guide.
Localité = précision terrain.
Commune = calcul.
Commande = snapshot.
```

Le client ne doit pas avoir à comprendre toute l’organisation administrative de Mayotte pour se localiser, mais Hodina doit rester strict côté calcul : les frais, jours, créneaux et barge restent liés à `DeliveryCommune`.

Le choix `AddressLocality` reste le bon vocabulaire long terme, car il couvre village, quartier, lieu-dit, facturation, retrait vendeur et futurs territoires.
