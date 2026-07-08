# Décisions Hodina

## Historique conservé

État initial du document de référence :

```text
- MariaDB retenue
- Paiement manuel
- Validation admin obligatoire
- Couverture géographique administrable depuis EasyAdmin
```

Ces décisions restent actives.

---

# Décisions fondatrices

## Base de données

- MariaDB retenue.

Raison : compatibilité avec l'hébergement o2switch et simplicité de déploiement pour le pilote.

## Paiement

- Paiement manuel pendant le pilote.

Raison : réduire la complexité au démarrage, tester la demande et garder une validation humaine.

## Validation admin

- Validation admin obligatoire.

Raison : vérifier la disponibilité réelle des produits auprès des vendeurs avant d'engager la préparation et la livraison.

## Couverture géographique

- Couverture géographique administrable depuis EasyAdmin.

Évolution : cette décision est désormais matérialisée dans `Réglages Hodina` via le paramètre `delivered_communes`.

---

# Décisions J4

## Backoffice commandes

- J4 a pour statut : terminé, testé, validé et commité.
- EasyAdmin reste le backoffice admin.
- Les commandes doivent être traitables de bout en bout par l'admin.
- Les lignes de commande doivent être visibles et consultables.
- Une fiche terrain commande est ajoutée pour l'usage mobile.

## Workflow commande admin

Workflow retenu J4 :

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

## Dates métier

Noms retenus :

- `confirmedAt`
- `preparingAt`
- `readyAt`
- `deliveredAt`
- `canceledAt`

Décision : utiliser les noms cohérents avec les statuts `CONFIRMED` et `CANCELED`, plutôt que `validatedAt` et `cancelledAt`.

## SmsLog

- Les SmsLog sont des traces système.
- Les SmsLog sont en lecture seule dans EasyAdmin.
- Ils sont créés automatiquement lors des changements de statut importants.
- Ils ne nécessitent pas de fournisseur SMS externe pendant le pilote.

## Format des messages SMS

- Les messages doivent commencer par `Gégé {prénom client}`.
- Les messages doivent inclure le numéro métier de commande.

## Envoi SMS manuel

Nouvelle décision : ajouter un bouton `Envoyer le SMS` dans chaque SmsLog.

Plan A :

```text
Ouvrir l'application SMS de l'iPhone avec numéro et message préremplis.
```

Plan B si le préremplissage du message n'est pas fiable :

```text
Bouton Copier le message + bouton Envoyer le SMS avec le numéro uniquement.
```

## Numéro métier de commande

Format retenu :

```text
préfixe + AAAAMMJJ + numéro du jour
```

Exemple :

```text
hodina202606041
```

Décisions :

- le préfixe est paramétrable ;
- le numéro apparaît côté client ;
- le numéro apparaît côté admin ;
- le numéro est présent dans les SmsLog ;
- une ancienne commande sans numéro peut en recevoir un lors d'une action métier.

## Réglages Hodina

Décision : utiliser un système générique.

```text
1 ligne = 1 paramètre
```

Paramètres actuels :

- `order_reference_prefix`
- `delivered_communes`

## Communes livrées

Décisions :

- ne pas utiliser une saisie à virgules comme interface principale ;
- afficher une commune par champ ;
- ajouter un bouton `Ajouter une commune` ;
- permettre la suppression commune par commune ;
- conserver la compatibilité avec les anciennes valeurs à virgules.

---

# Décisions J5

## Modèle livraison

Modèle B retenu :

```text
Le livreur voit les commandes prêtes et les prend lui-même.
```

Raison : simplicité, rapidité de développement, cohérence avec le pilote.

## Point d'entrée livraison

Le statut `READY_FOR_PICKUP` devient le point d'entrée du dashboard livreur.

```text
Commande prête → visible pour les livreurs
```

## Statut de livraison

Le statut `OUT_FOR_DELIVERY` sera exploité en J5.

Il représente :

```text
Commande prise en charge par un livreur et en cours de livraison.
```

## Dashboard livreur

Décision clarifiée :

```text
Le portail livreur sera un dashboard authentifié dédié, mobile-first, séparé du backoffice EasyAdmin admin.
```

Cela signifie :

- le livreur aura bien un dashboard ;
- il ne s'agira pas d'une interface EasyAdmin ;
- le dashboard sera limité aux besoins livreur ;
- les droits seront maîtrisés ;
- l'interface sera pensée mobile.

## Refactoring obligatoire avant portail livreur

Décision technique majeure :

```text
Ne pas dupliquer la logique de changement de statut entre admin et livreur.
```

On créera un service commun :

```text
CustomerOrderWorkflowService
```

Ce service sera utilisé par :

- `CustomerOrderCrudController` côté admin ;
- `CourierDashboardController` côté livreur.

## Responsabilités du service workflow

- vérifier les transitions ;
- changer les statuts ;
- renseigner les dates métier ;
- créer les SmsLog ;
- associer le livreur ;
- générer le numéro de commande si absent ;
- sauvegarder les modifications ;
- éviter les incohérences entre interfaces.

## Rôles

À prévoir en J5 :

- `ROLE_ADMIN`
- `ROLE_COURIER`
- `ROLE_CUSTOMER`

## Sécurité livraison

Décisions :

- seul un livreur authentifié peut accéder au dashboard livreur ;
- un livreur ne peut prendre qu'une commande prête ;
- une commande prise passe en `OUT_FOR_DELIVERY` ;
- une commande en livraison est associée au livreur ;
- seul le livreur associé ou un admin peut finaliser selon les règles retenues ;
- une commande annulée ou livrée ne peut plus être prise.

---

# Décisions reportées

À ne pas intégrer dans J5 MVP :

- géolocalisation temps réel ;
- optimisation de tournée ;
- signature client ;
- photo de preuve ;
- paiement en ligne ;
- prestataire SMS réel ;
- application mobile native ;
- statistiques avancées livreur.

---

# Décisions J5A — Préproduction, sécurité, légal et finalisation du 05/06/2026

## Validation réinitialisation mot de passe par SMS

Décision validée : ajouter un parcours de réinitialisation de mot de passe client avec lien envoyable par SMS.

Fonctionnement retenu :

```text
Client clique “Mot de passe oublié”
→ saisit son email
→ Hodina génère un token temporaire
→ Hodina crée un SmsLog contenant le lien de réinitialisation
→ l'admin envoie le SMS manuellement depuis SmsLog
→ le client ouvre le lien
→ le client définit un nouveau mot de passe
→ le token est supprimé / invalidé
```

Décisions associées :

- le lien de réinitialisation est logué dans `SmsLog` ;
- l'envoi réel reste manuel via iPhone pendant le pilote ;
- le SMS de reset suit la même logique que les autres SMS pilote ;
- le reset password est validé fonctionnellement avant déploiement préprod.

## Préproduction recette.hodina.fr

Décision : créer un environnement de préproduction dédié.

URL :

```text
https://recette.hodina.fr
```

Rôle :

- tester les parcours avant production ;
- permettre au frère / admin terrain de tester ;
- valider les correctifs hors environnement local ;
- préparer les futures mises en production.

Document root retenu sur o2switch :

```text
/home/vopu3712/recette.hodina.fr/public
```

Dossier projet côté serveur :

```text
/home/vopu3712/recette.hodina.fr
```

Dossier de préparation local :

```text
E:\hodina\recette.hodina.fr
```

## Protection Basic Auth préprod

Décision : protéger la préproduction par authentification Basic Apache.

Raison : la préprod ne doit pas être accessible publiquement pendant les tests.

Comptes prévus :

- `djanfar` ;
- `Chahere`.

Le fichier `.htpasswd` doit rester hors du dossier `public` :

```text
/home/vopu3712/recette.hodina.fr/.htpasswd
```

Le `.htaccess` doit se trouver dans :

```text
/home/vopu3712/recette.hodina.fr/public/.htaccess
```

Décision importante : ne pas commiter le `.htpasswd` et éviter de commiter un `.htaccess` contenant une configuration d'environnement spécifique sans version exemple.

## HTTPS préprod

Constat : le certificat SSL AutoSSL est bien généré pour :

```text
recette.hodina.fr
www.recette.hodina.fr
```

Décision : forcer HTTPS avant l'authentification Basic pour éviter l'alerte navigateur `connexion non sécurisée`.

Règle retenue en haut du `.htaccess` :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{HTTPS} !=on
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</IfModule>
```

Objectif attendu :

```text
http://recette.hodina.fr
→ 301 vers https://recette.hodina.fr
→ Basic Auth sur HTTPS uniquement
```

## Base de données préprod

Décision : créer une base dédiée à la recette.

Base utilisée :

```text
vopu3712_hodina_recette
```

Utilisateur :

```text
vopu3712_hodina_recette_user
```

Données importées depuis la base de développement via dump SQL.

Incident rencontré et décision de correction :

- le dump généré sous PowerShell était initialement encodé en UTF-16 ;
- phpMyAdmin o2switch échouait avec des erreurs SQL dès la ligne 1 ;
- le dump a été nettoyé et converti en UTF-8 ;
- l'import phpMyAdmin a ensuite réussi.

Décision pour les futurs dumps : utiliser `cmd.exe` ou forcer un encodage UTF-8 afin d'éviter les fichiers SQL UTF-16.

## Configuration DATABASE_URL

Décision : encoder les caractères spéciaux des mots de passe dans `DATABASE_URL`.

Exemple :

```text
@ → %40
```

Raison : dans une URL MySQL, `@` sépare le mot de passe du host. S'il est présent dans le mot de passe, il doit être encodé.

## CGU / CGV

Décision : ajouter les pages légales avant de continuer vers le portail livreur.

Routes publiques retenues :

```text
/cgu
/cgv
```

Décisions fonctionnelles :

- les CGU et CGV sont accessibles depuis le footer public ;
- le checkout exige l'acceptation des CGU/CGV avant validation de commande ;
- les textes sont adaptés à la phase pilote Hodina ;
- le paiement manuel et la validation admin sont explicitement pris en compte ;
- les CGU/CGV gardent une mise en forme mobile-first.

## UX sommaire CGU/CGV

Constat : le sommaire vertical initial créait trop d'espace sur mobile.

Décision : remplacer le sommaire vertical par un sommaire compact horizontal avec chips scrollables.

Objectif UX :

```text
Titre légal
→ encart pilote
→ sommaire compact
→ contenu des articles rapidement visible
```

## Footer public

Décision : retirer le lien `Admin` du footer public.

Avant :

```text
Catalogue • CGU • CGV • Admin
```

Après :

```text
Catalogue • CGU • CGV
```

Raison : le lien backoffice `/ouegnewe` ne doit pas être exposé publiquement dans le socle client.

## Traduction EasyAdmin JS

Décision : rendre la traduction JavaScript EasyAdmin plus générique.

Ancien comportement problématique :

```text
Add a new item → Ajouter une commune
```

Problème : ce libellé EasyAdmin peut apparaître dans plusieurs contextes, pas seulement les communes.

Comportement retenu :

```text
Add a new item → Ajouter un nouvel item
Delete → Supprimer
```

Décision future possible : ajouter une traduction contextuelle uniquement dans le formulaire des communes livrées si nécessaire.

## Fin de session du 05/06/2026

État validé avant arrêt :

- reset password SMS logué : validé ;
- dump DB dev → recette : import réussi ;
- Basic Auth : opérationnel mais à sécuriser avec redirection HTTPS avant auth ;
- SSL AutoSSL : certificat présent ;
- CGU/CGV ajoutées ;
- sommaire légal compact ajouté ;
- lien Admin retiré du footer public ;
- traduction EasyAdmin corrigée ;
- commit Git à faire pour figer ces changements.


---

# Décisions J5C — Données livraison et migration sûre

## Statut J5C

Décision : considérer J5C comme validé après tests local + préproduction.

État validé :

- champs livraison ajoutés ;
- relation livreur ajoutée ;
- workflow service étendu ;
- route `/djama` réservée à `ROLE_COURIER` ;
- migrations corrigées et appliquées ;
- champs visibles dans EasyAdmin ;
- préprod synchronisée.

## Entité livreur

Décision MVP : utiliser `Customer` pour représenter aussi les livreurs.

```text
Customer + ROLE_COURIER = livreur
```

Relation retenue :

```text
CustomerOrder.assignedCourier -> Customer
```

Raison :

- le projet utilise déjà `Customer` comme entité authentifiable ;
- le pilote doit rester simple ;
- créer une entité `User` séparée maintenant serait prématuré ;
- le modèle pourra être refactorisé plus tard si besoin.

## Champs livraison

Décision : ajouter dans `CustomerOrder` :

```text
assignedCourier
courierAssignedAt
outForDeliveryAt
```

Ces champs deviennent la base du suivi livraison.

## Statut OUT_FOR_DELIVERY

Décision : exploiter `STATUS_OUT_FOR_DELIVERY` comme statut intermédiaire entre :

```text
READY_FOR_PICKUP
DELIVERED
```

Signification :

```text
La commande a été prise en charge par un livreur et est en cours de livraison.
```

## Sécurité livreur

Décision : réserver la future route :

```text
/djama
```

au rôle :

```text
ROLE_COURIER
```

## Migration / déploiement

Décision : documenter l'incident de migration J5C.

Erreur rencontrée en préprod :

```text
Key 'idx_3cf0a31e4b1e148f' doesn't exist in table 'customer_order'
```

Cause : migration corrective exécutée avant la migration principale à cause d'un timestamp antérieur.

Décision corrective :

- migration ancienne transformée en no-op ;
- migration corrective postérieure ajoutée ;
- renommage d'index rendu conditionnel.

Règle retenue :

```text
Ne pas générer de migration corrective dont le timestamp est antérieur à la migration corrigée.
```

## Décision sur la suite

J5D peut maintenant démarrer.

Prochaine étape :

```text
Dashboard livreur /djama
```

Le dashboard devra utiliser les méthodes existantes de `CustomerOrderWorkflowService` et ne pas réimplémenter les règles métier.


---

# Décisions J5D — Dashboard livreur livré, testé et déployé

## Statut J5D

Décision : considérer J5D comme terminé après validation locale et préproduction.

État validé :

- le dashboard livreur `/djama` existe ;
- l'accès est réservé aux comptes ayant `ROLE_COURIER` ;
- un utilisateur non connecté ne peut pas accéder au dashboard ;
- un utilisateur connecté sans rôle livreur ne peut pas accéder au dashboard ;
- une commande `READY_FOR_PICKUP` non assignée apparaît dans les commandes prêtes ;
- un livreur peut prendre en charge une commande prête ;
- la commande passe en `OUT_FOR_DELIVERY` ;
- la commande est associée au livreur connecté ;
- la commande apparaît dans les livraisons en cours du livreur ;
- le livreur assigné peut marquer la commande comme livrée ;
- la commande passe en `DELIVERED` ;
- les liens téléphone et SMS client sont disponibles depuis le dashboard ;
- le lien `Livreur` apparaît dans la navigation uniquement pour les utilisateurs ayant `ROLE_COURIER`.

## Point de test important retenu

Pendant les tests, une commande n'apparaissait pas dans le dashboard livreur.

Cause identifiée : le statut de la commande n'avait pas été changé côté admin jusqu'à :

```text
READY_FOR_PICKUP
```

Décision de documentation : rappeler explicitement qu'une commande doit être marquée prête par l'admin avant d'être visible par un livreur.

```text
Commande non prête → invisible côté livreur
Commande READY_FOR_PICKUP + sans livreur assigné → visible côté livreur
```

## Sélection des rôles dans EasyAdmin

Décision : ne plus laisser la saisie des rôles utilisateur comme tableau libre difficile à comprendre.

Le formulaire d'édition client dans EasyAdmin doit proposer des rôles avec description :

- Client — accès catalogue, panier et commandes ;
- Livreur — accès au dashboard `/djama` ;
- Administrateur — accès complet au backoffice `/ouegnewe`.

Décision complémentaire : préparer l'ajout futur de :

```text
ROLE_SELLER
```

pour le portail vendeur.

---

# Décisions J5E — Marge produit Hodina

## Objectif métier

Décision validée : Hodina doit calculer automatiquement le prix client à partir du prix producteur et d'un taux de marge Hodina.

Formule retenue :

```text
prix client = prix producteur + (prix producteur × taux de marge)
```

Équivalent :

```text
prix client = prix producteur × (1 + taux de marge)
```

Exemple :

```text
prix producteur = 10 €
taux marge = 20 %
prix client = 12 €
marge Hodina = 2 €
```

## Clarification vocabulaire

Décision : ne pas appeler le prix vendeur `marge producteur`.

Les bons termes sont :

```text
prix producteur / prix vendeur
→ montant demandé par le vendeur pour son produit

marge Hodina
→ montant ajouté par Hodina au prix producteur

prix client
→ prix final affiché et payé par le client pour le produit
```

Cette distinction évite de mélanger le revenu du vendeur et la marge de la plateforme.

## Hiérarchie de marge validée

Décision : la marge peut être définie sur trois niveaux.

Priorité :

```text
marge produit
> marge vendeur
> marge globale
```

Règle :

```text
Si le produit a une marge spécifique
→ utiliser la marge produit.

Sinon, si le vendeur a une marge spécifique
→ utiliser la marge vendeur.

Sinon
→ utiliser la marge globale Hodina.
```

Exemple :

```text
marge globale = 20 %
marge vendeur = 15 %
marge produit = vide

Résultat : marge appliquée = 15 %
```

Autre exemple :

```text
marge globale = 20 %
marge vendeur = 15 %
marge produit = 25 %

Résultat : marge appliquée = 25 %
```

## Stratégie vendeur

Décision importante pour éviter la frustration des vendeurs : le vendeur ne doit pas entendre que Hodina prend une commission sur son prix.

Le discours retenu :

```text
Vous indiquez votre prix producteur.
Hodina ajoute sa marge pour financer la plateforme, la relation client et l'organisation.
Vous savez à l'avance combien vous touchez.
```

Le vendeur doit voir clairement :

```text
prix producteur saisi
prix client calculé
revenu vendeur
```

Mais la marge Hodina reste une règle de gestion contrôlée par Hodina.

## Portail vendeur futur

Décision structurante : un portail vendeur sera créé plus tard.

Les vendeurs pourront :

- compléter leur profil ;
- renseigner leur commune / point de retrait ;
- ajouter leurs produits ;
- ajouter leurs photos ;
- saisir leur prix producteur ;
- gérer disponibilité / stock simple ;
- soumettre leurs produits à validation.

Décision : le vendeur ne saisira pas le prix client final.

Le prix client sera calculé par :

```text
ProductPricingService
```

Cette règle doit être respectée dès J5E pour éviter un futur refactoring lourd.

## Ce qui est dans le pilote J5E

À faire pendant le pilote :

- ajouter ou clarifier le prix producteur sur `Product` ;
- ajouter une marge spécifique nullable sur `Product` ;
- ajouter une marge spécifique nullable sur `Seller` ;
- ajouter un réglage global `global_margin_rate` ;
- créer un service `ProductPricingService` ;
- afficher le prix client calculé côté catalogue / panier ;
- figer les prix au moment de la commande.

## Ce qui est reporté après le pilote

À reporter :

- gestion comptable complète de reversement vendeur ;
- facturation automatique vendeur ;
- portail vendeur complet ;
- validation avancée de produits ;
- historique détaillé des changements de marge ;
- règles de marge par catégorie ;
- promotions ;
- prix barrés ;
- commissions dynamiques selon volume.

---

# Décisions J5F — Zones tarifaires, communes et communes voisines

## Objectif métier

Décision validée pour le pilote :

```text
une commune appartient à une zone tarifaire
une zone tarifaire définit le prix payé par le client et la rémunération du livreur
```

Cette règle permet de calculer automatiquement :

- les frais de livraison client ;
- la rémunération prévue du livreur ;
- la marge livraison Hodina ;
- le message logistique à afficher au client.

## Rémunération livreur

Décision : ne pas payer les livreurs en pourcentage du panier pendant le pilote.

Raison :

- petite commande = livreur frustré ;
- grosse commande = marge Hodina moins maîtrisée ;
- risque de confusion avec la marge vendeur ;
- modèle difficile à expliquer.

Modèle retenu :

```text
rémunération livreur = forfait défini par la zone tarifaire
```

Exemple :

```text
Petite-Terre proche
client paie 4 €
livreur reçoit 3 €
Hodina garde 1 € sur la livraison
```

## Frais livraison et marge Hodina

Règle :

```text
marge livraison Hodina = frais livraison client - rémunération livreur
```

Exemple :

```text
frais client = 10 €
rémunération livreur = 8 €
marge livraison Hodina = 2 €
```

Décision : la marge produit reste le cœur du modèle économique Hodina. La marge livraison peut être faible ou nulle au démarrage si cela facilite l'adoption.

## Barge

Décision corrigée : la barge ne dépend pas seulement de la commune client.

La barge est requise si au moins un vendeur de la commande se situe sur un territoire différent de celui du client.

Territoires logistiques :

```text
PT = Petite-Terre
GT = Grande-Terre
```

Règles :

```text
client PT + vendeur GT → barge requise
client GT + vendeur PT → barge requise
client PT + vendeur PT → pas de barge
client GT + vendeur GT → pas de barge
```

Cas multi-vendeurs :

```text
Si un seul vendeur du panier est sur l'autre territoire,
la commande est considérée comme nécessitant la barge.
```

## Communes voisines

Décision validée pour le pilote :

```text
L'admin définit les communes voisines.
Le service calcule automatiquement la relation.
Le panier affiche le bon message.
```

La notion de commune voisine ne sera pas calculée automatiquement par carte ou GPS pendant le pilote.

Raison : à Mayotte, la proximité réelle dépend aussi :

- des routes ;
- des temps de trajet ;
- des habitudes terrain ;
- des embouteillages ;
- de la barge ;
- des points de retrait vendeurs.

## Relation logistique entre client et vendeur

Décision : créer une fonction métier capable de classer la relation entre la commune client et la commune vendeur.

Relations possibles :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
```

Signification :

```text
SAME_COMMUNE
→ vendeur dans la même commune que le client

NEIGHBOR_COMMUNE
→ vendeur dans une commune voisine définie par l'admin

REMOTE_COMMUNE
→ vendeur éloigné mais sur le même territoire PT ou GT

OTHER_TERRITORY
→ vendeur sur l'autre territoire, barge requise
```

Priorité globale panier :

```text
OTHER_TERRITORY
> REMOTE_COMMUNE
> NEIGHBOR_COMMUNE
> SAME_COMMUNE
```

## Ce qui est dans le pilote J5F

À faire pendant le pilote :

- créer une entité de zone tarifaire ;
- créer une entité de commune livrée ;
- ajouter le territoire PT / GT à chaque commune ;
- permettre à l'admin d'associer les communes voisines ;
- associer chaque vendeur à une commune logistique ;
- définir une zone tarifaire locale et une zone tarifaire barge pour chaque commune ;
- calculer automatiquement les frais livraison et la rémunération livreur ;
- figer les montants dans la commande.

## Ce qui est reporté après le pilote

À reporter :

- calcul GPS réel ;
- distance kilométrique automatique ;
- intégration Google Maps / OpenStreetMap ;
- optimisation de tournée ;
- tarification dynamique selon heure / météo / trafic ;
- prise en compte automatique du prix carburant en temps réel ;
- gestion avancée des tournées multi-commandes ;
- compensation automatique en cas d'attente barge longue.

---

# Décisions J5G — Aperçu logistique dans le panier

## Objectif UX

Décision : dès l'ouverture du panier, le client doit être informé si les produits sélectionnés impliquent une contrainte logistique particulière.

Exemples :

- vendeur dans une commune voisine ;
- vendeur éloigné ;
- vendeur sur l'autre île ;
- barge requise ;
- frais de livraison adaptés.

Objectif : éviter que le client découvre trop tard pourquoi les frais de livraison sont plus élevés.

## Panier = estimation, checkout = calcul définitif

Décision : le panier affiche une estimation logistique.

Le checkout recalcule et fige les valeurs définitives.

Raison : entre l'ouverture du panier et la validation, le client peut :

- changer d'adresse ;
- ajouter un produit ;
- retirer un produit ;
- changer la composition des vendeurs ;
- modifier son panier.

Donc :

```text
Panier → information / estimation
Checkout → calcul définitif / valeurs figées
```

## Messages client validés

Même commune uniquement :

```text
Livraison calculée automatiquement selon ton adresse.
```

Commune voisine uniquement :

```text
Certains produits viennent d'une commune voisine. Les frais de livraison sont calculés automatiquement selon ton adresse.
```

Commune éloignée :

```text
Certains produits de ton panier viennent de vendeurs éloignés de ton adresse. Les frais de livraison tiennent compte de cette distance.
```

Autre territoire / barge :

```text
Certains produits de ton panier viennent de vendeurs situés sur une autre île. La livraison nécessitera une traversée en barge, ce qui peut influencer les frais et le délai de livraison.
```

Éloigné ou barge :

```text
Certains produits de ton panier viennent de vendeurs éloignés ou situés sur une autre île. La livraison peut nécessiter une traversée en barge et des frais adaptés seront appliqués.
```

## Ce qui est dans le pilote J5G

À faire pendant le pilote :

- créer `DeliveryLogisticsService` ;
- calculer la relation client / vendeurs ;
- afficher un message clair dans le panier ;
- afficher les frais de livraison estimés si l'adresse est connue ;
- indiquer si une barge est requise ;
- recalculer au checkout ;
- figer les données dans `CustomerOrder`.

## Ce qui est reporté après le pilote

À reporter :

- carte interactive ;
- estimation temps réel de délai ;
- détail complet par vendeur côté client ;
- suivi live du livreur ;
- affichage d'un itinéraire ;
- prédiction d'attente barge ;
- optimisation automatique du groupement des commandes.


---

# Décisions J5E — Marge produit livrée et validée

## Statut J5E

Décision : considérer J5E comme terminé après validation locale et préproduction.

État validé :

- `ProductPricingService` créé ;
- prix producteur ajouté sur `Product` ;
- marge produit nullable ajoutée sur `Product` ;
- marge vendeur nullable ajoutée sur `Seller` ;
- réglage global `global_margin_rate` ajouté dans `HodinaSetting` ;
- prix client calculé côté catalogue, fiche produit et panier ;
- checkout recalculé et figé dans `OrderItem` ;
- anciennes commandes inchangées ;
- nouvelle commande avec valeurs économiques figées ;
- préproduction `recette.hodina.fr` validée.

## Décision sur `Product.price`

`Product.price` est conservé temporairement pour compatibilité.

À partir de J5E, la référence métier est :

```text
Product.producerPrice
```

Règle de secours :

```text
Si Product.producerPrice est vide ou nul,
ProductPricingService réutilise Product.price comme prix producteur de secours.
```

## Décision sur les données figées dans `OrderItem`

Champs figés :

```text
producerUnitPrice
appliedMarginRate
hodinaMarginAmount
unitPrice
lineTotal
```

Raison : une commande ancienne ne doit pas être modifiée si l'admin change demain la marge globale, la marge vendeur, la marge produit ou le prix producteur.

## Décision sur l'affichage client

Le client voit le prix client calculé. Il ne voit pas forcément la marge Hodina, le prix producteur ou le détail de la formule.

## Décision sur le futur portail vendeur

Le vendeur saisira son prix producteur, mais il ne saisira pas le prix client final et ne contrôlera pas la marge Hodina. Le portail vendeur devra appeler `ProductPricingService`.


## Historique des incidents J5E corrigés

J5E a été validé, mais l'historique des incidents doit rester dans les documents pour aider le prochain développeur à comprendre la chronologie et les bons réflexes de diagnostic.

### Incident 1 — Migration J5E absente du premier patch

Après application du premier patch J5E, le code contenait déjà les nouveaux champs Doctrine, mais la base n'avait pas encore les colonnes correspondantes. La commande suivante a donc échoué :

```powershell
php bin/console doctrine:schema:validate
```

Erreur observée :

```text
[ERROR] The database schema is not in sync with the current mapping file.
```

Doctrine indiquait être seulement monté jusqu'à :

```text
DoctrineMigrations\Version20260606103000
```

Correction : ajout de la migration J5E :

```text
migrations/Version20260607120000.php
```

### Incident 2 — `ProductPricingService.php` tronqué

Après ajout du correctif migration/service, Symfony ne pouvait plus démarrer.

Erreur observée :

```text
ParseError: Unclosed '{' on line 78
File: src/Service/ProductPricingService.php
```

Cause : le fichier `ProductPricingService.php` était incomplet. La méthode `getPriceBreakdown()` avait été commencée, mais la fin du service manquait.

Correction : compléter le service avec :

```text
getPriceBreakdown()
money()
percent()
fermeture de la classe ProductPricingService
```

Vérification :

```powershell
php -l src\Service\ProductPricingService.php
```

Résultat :

```text
No syntax errors detected in src\Service\ProductPricingService.php
```

### Incident 3 — Migration J5E tronquée

Après réparation du service, la migration a échoué.

Erreur observée :

```text
In Version20260607120000.php line 94:
Unclosed '{' on line 11
```

Cause : la migration `Version20260607120000.php` était tronquée. Il manquait la méthode utilitaire :

```php
private function columnExists(string $tableName, string $columnName): bool
```

et la fermeture finale de la classe.

Correction : réparation complète de la migration.

Vérification :

```powershell
php -l migrations\Version20260607120000.php
```

Résultat :

```text
No syntax errors detected in migrations\Version20260607120000.php
```

### Validation finale locale

Commandes validées :

```powershell
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:container
```

Résultat :

```text
Migration Version20260607120000 exécutée
Mapping Doctrine OK
Database schema in sync OK
Container Symfony OK
```

### Validation finale préproduction

Commandes validées sur o2switch :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultat :

```text
Migration DoctrineMigrations\Version20260607120000 exécutée
Mapping Doctrine OK
Database schema in sync OK
Cache prod clear OK
Cache prod warmup OK
Tests recette bons
```

### Point pédagogique

Si `doctrine:schema:validate` échoue après un patch Doctrine, vérifier dans cet ordre :

```text
1. La migration existe-t-elle ?
2. La migration est-elle syntaxiquement valide ?
3. La migration a-t-elle été exécutée ?
4. doctrine:schema:update --dump-sql propose-t-il quelque chose ?
5. Après cache clear / warmup, schema:validate repasse-t-il au vert ?
```

Ne jamais utiliser `doctrine:schema:update --force` en préproduction sans comprendre l'écart.

---

# Décision J5F clarifiée — Barge uniquement entre Petite-Terre et Grande-Terre

## Date / contexte

Clarification ajoutée avant application du patch J5F-A.

Pendant la préparation de J5F, une ambiguïté a été levée : il ne faut pas confondre commune éloignée, commune non voisine et barge.

## Décision ferme

La barge ne dépend pas de la distance entre deux communes.

La barge dépend uniquement du changement de territoire logistique :

```text
PT = Petite-Terre
GT = Grande-Terre
```

Règles définitives :

```text
commune PT → commune PT = pas de barge
commune GT → commune GT = pas de barge
commune PT → commune GT = barge
commune GT → commune PT = barge
```

Exemples Petite-Terre :

```text
Dzaoudzi → Pamandzi = PT → PT = pas de barge
Pamandzi → Dzaoudzi = PT → PT = pas de barge
Dzaoudzi → Labattoir = PT → PT = pas de barge
Labattoir → Pamandzi = PT → PT = pas de barge
```

Exemples Grande-Terre :

```text
Mamoudzou → Koungou = GT → GT = pas de barge
Mamoudzou → Sada = GT → GT = pas de barge
Sada → Ouangani = GT → GT = pas de barge
```

Exemples avec barge :

```text
Dzaoudzi → Mamoudzou = PT → GT = barge
Mamoudzou → Dzaoudzi = GT → PT = barge
Pamandzi → Koungou = PT → GT = barge
Sada → Labattoir = GT → PT = barge
```

## Cas multi-vendeurs

Pour une commande avec plusieurs vendeurs, on compare le territoire de la commune client avec le territoire de chaque vendeur.

Règle :

```text
Si au moins un vendeur du panier est sur l'autre territoire,
alors la commande nécessite la barge.
```

Exemple :

```text
Client Dzaoudzi = PT
Vendeur A Pamandzi = PT
Vendeur B Labattoir = PT
→ pas de barge
```

Autre exemple :

```text
Client Dzaoudzi = PT
Vendeur A Pamandzi = PT
Vendeur B Mamoudzou = GT
→ barge requise
```

## Ce que les communes voisines ne doivent pas faire

Les communes voisines ne doivent jamais déclencher la barge.

Elles servent uniquement à affiner le message logistique.

```text
commune voisine
→ message doux

commune éloignée sur le même territoire
→ message distance

autre territoire
→ message barge
```

Donc :

```text
non voisine ≠ barge
commune éloignée ≠ barge
barge = changement PT / GT uniquement
```

## Interprétation des zones tarifaires

`DeliveryCommune.localPricingZone` signifie :

```text
Zone tarifaire utilisée quand le client et tous les vendeurs concernés sont sur le même territoire PT ou GT.
```

`DeliveryCommune.bargePricingZone` signifie :

```text
Zone tarifaire utilisée uniquement si au moins un vendeur est sur l'autre territoire.
```

Important : le mot `bargePricingZone` ne veut pas dire “zone éloignée”. Il veut dire “zone tarifaire à appliquer quand la traversée PT/GT est nécessaire”.

## Conséquence pour J5F-A

Le patch J5F-A peut conserver le modèle de données prévu :

```text
DeliveryPricingZone
DeliveryCommune
DeliveryCommune.territory = PT / GT
DeliveryCommune.localPricingZone
DeliveryCommune.bargePricingZone
DeliveryCommune.neighboringCommunes
Seller.deliveryCommune
```

Mais la documentation et les textes d'aide doivent être explicites pour éviter une mauvaise interprétation.

## Conséquence pour J5G

Le futur `DeliveryLogisticsService` devra appliquer cette règle :

```text
requiresBarge = true uniquement si clientTerritory !== sellerTerritory pour au moins un vendeur
```

Il ne devra pas calculer `requiresBarge` à partir :

- de la distance ;
- du fait qu'une commune soit voisine ou non ;
- du niveau `REMOTE_COMMUNE`.

## Point pédagogique pour développeur débutant

Pour coder J5F/J5G, toujours séparer deux questions :

```text
Question 1 : faut-il une barge ?
→ comparer uniquement PT / GT.

Question 2 : quel message afficher au client ?
→ comparer même commune / commune voisine / commune éloignée / autre territoire.
```

Cette séparation évite d'appliquer des frais barge à tort à une livraison interne à Petite-Terre ou interne à Grande-Terre.


---

# Décisions J5F-A — Socle communes et zones tarifaires livré

## Statut

Décision : considérer **J5F-A** comme livré après validation locale, validation EasyAdmin et déploiement recette.

État validé :

- création de `DeliveryPricingZone` ;
- création de `DeliveryCommune` ;
- association `Seller.deliveryCommune` ;
- ajout des CRUD EasyAdmin ;
- ajout des entrées de menu admin ;
- migration principale `Version20260607170000` ;
- migration corrective `Version20260607173000` ;
- validation locale ;
- validation préproduction ;
- jeu de test recette ajouté.

## Décision sur les noms réels de colonnes

Les colonnes booléennes Doctrine sont nommées en base :

```text
delivery_pricing_zone.is_active
delivery_commune.is_active
seller.is_active
```

Une tentative d'insertion SQL avec `active` a échoué en recette :

```text
Unknown column 'active' in 'INSERT INTO'
```

Décision : pour les scripts SQL directs, utiliser les noms SQL réels, pas les noms approximatifs :

```sql
is_active
```

En PHP, le champ reste accessible via les méthodes de l'entité :

```php
isActive()
setIsActive()
```

## Décision sur les zones tarifaires

`DeliveryPricingZone` définit les montants économiques d'une livraison :

```text
customerDeliveryFee → frais payés par le client
courierPayout       → rémunération prévue du livreur
deliveryMargin      → différence calculée côté PHP
```

Le champ `deliveryMargin` n'est pas stocké en base. Il est calculé par l'entité :

```text
deliveryMargin = customerDeliveryFee - courierPayout
```

Cette décision évite une colonne redondante qui pourrait devenir incohérente.

## Décision sur les communes

`DeliveryCommune` devient la donnée métier de référence pour la logistique.

Elle porte :

```text
name
territory PT / GT
localPricingZone
bargePricingZone
neighboringCommunes
isActive
internalNote
```

La commune texte historique du vendeur reste conservée, mais elle ne doit plus servir aux futurs calculs logistiques.

Règle :

```text
Seller.commune              → champ texte historique / compatibilité
Seller.deliveryCommune      → commune métier à utiliser pour J5F / J5G / J6
```

## Décision sur la zone locale et la zone barge

Pour une commune client donnée :

```text
localPricingZone
→ utilisée quand tous les vendeurs du panier sont sur le même territoire que le client

bargePricingZone
→ utilisée si au moins un vendeur est sur l'autre territoire
```

Exemple de test validé :

```text
Dzaoudzi  PT → local PT_LOCAL / barge GT_LOCAL
Labattoir PT → local PT_LOCAL / barge GT_LOCAL
Mamoudzou GT → local GT_LOCAL / barge PT_LOCAL
```

Attention : `bargePricingZone` ne veut pas dire “commune éloignée”. Cela veut dire “zone à utiliser quand il y a traversée PT/GT”.

## Décision sur les communes voisines

Les communes voisines sont paramétrées manuellement par l'admin.

Elles servent à produire un message logistique plus juste :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
```

Elles ne déclenchent jamais la barge.

## Décision sur le jeu de test recette

Le jeu de test validé en recette est :

```text
GT_LOCAL → 6 € client / 5 € livreur / 1 € marge livraison
PT_LOCAL → 6 € client / 5 € livreur / 1 € marge livraison

Dzaoudzi  → PT → PT_LOCAL / GT_LOCAL
Labattoir → PT → PT_LOCAL / GT_LOCAL
Mamoudzou → GT → GT_LOCAL / PT_LOCAL

Dzaoudzi ↔ Labattoir voisines
ferme houmadi → Mamoudzou → GT
```

Le vendeur `ferme Abdallah` peut rester sans commune logistique tant qu'il n'est pas utilisé dans les tests logistiques. Pour éviter des cas vides, on peut l'associer plus tard à une commune de test.

## Point pédagogique

Un développeur débutant doit retenir :

```text
Une commune texte ne suffit pas pour calculer la logistique.
Il faut une vraie entité DeliveryCommune avec territoire, zones tarifaires et voisinage.
```

C'est ce qui permettra au futur `DeliveryLogisticsService` de calculer la barge et les frais sans logique fragile.


---

# Décisions J5F-B — DeliveryLogisticsService livré

## Statut

Décision : considérer **J5F-B** comme livré et validé techniquement après validation locale et préproduction.

J5F-B ajoute :

```text
src/Service/DeliveryLogisticsService.php
src/Dto/CartLogisticsPreview.php
```

Il ne modifie pas encore :

```text
panier
checkout
CustomerOrder
templates
base de données
```

Donc il n'y a pas de migration pour J5F-B.

## Décision sur le rôle du service

`DeliveryLogisticsService` devient la source de vérité pour les règles logistiques.

Responsabilités :

- retrouver la commune client depuis `Address.commune` ;
- comparer la commune client avec les communes logistiques des vendeurs ;
- calculer la relation logistique ;
- détecter si la barge est requise ;
- choisir la zone tarifaire applicable ;
- produire un aperçu logistique prêt à afficher ;
- préparer les valeurs qui seront figées plus tard dans `CustomerOrder`.

## Règle barge verrouillée dans le service

Le service applique la règle validée :

```text
requiresBarge = true uniquement si clientTerritory !== sellerTerritory
```

Donc :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

La méthode dédiée est :

```php
requiresBarge(DeliveryCommune $clientCommune, DeliveryCommune $sellerCommune): bool
```

## Relations logistiques calculées

Le service utilise les niveaux suivants :

```text
SAME_COMMUNE
NEIGHBOR_COMMUNE
REMOTE_COMMUNE
OTHER_TERRITORY
UNKNOWN
```

Signification :

```text
SAME_COMMUNE      → vendeur dans la même commune que le client
NEIGHBOR_COMMUNE  → vendeur dans une commune voisine paramétrée par l'admin
REMOTE_COMMUNE    → vendeur sur le même territoire, mais pas voisin
OTHER_TERRITORY   → vendeur sur l'autre territoire PT/GT, barge requise
UNKNOWN           → commune client ou commune vendeur non paramétrée
```

Priorité globale panier :

```text
OTHER_TERRITORY > REMOTE_COMMUNE > NEIGHBOR_COMMUNE > SAME_COMMUNE > UNKNOWN
```

Dans le code, cette priorité permet de produire un seul niveau global pour un panier multi-vendeurs.

## Choix de zone tarifaire

Le service choisit la zone tarifaire depuis la commune client :

```php
return $requiresBarge
    ? $clientCommune->getBargePricingZone()
    : $clientCommune->getLocalPricingZone();
```

C'est volontaire : les frais de livraison sont calculés selon l'adresse client et la contrainte logistique globale de la commande.

## Décision sur le DTO

Le terme DTO signifie **Data Transfer Object**, c'est-à-dire objet de transfert de données.

Dans Hodina :

```text
src/Entity → objets stockés en base
src/Service → règles métier / calculs
src/Dto → objets simples qui transportent un résultat calculé
```

`CartLogisticsPreview` n'est pas une entité Doctrine. Il ne crée pas de table.

Il transporte le résultat du service :

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

Pourquoi un DTO plutôt qu'un tableau PHP ?

```text
Un DTO documente mieux les données.
Il évite les clés de tableau mal orthographiées.
Il rend le code plus lisible pour un développeur débutant.
```

## Ce que J5F-B prépare sans encore le faire

J5F-B prépare les données à figer dans `CustomerOrder`, mais ne les écrit pas encore.

Le gel aura lieu plus tard dans le checkout :

```text
deliveryFee
courierPayout
deliveryMargin
pricingZoneCode
pricingZoneName
clientCommuneName
clientTerritory
requiresBarge
logisticsLevel
```

## Note Symfony sur debug:container

Pendant le test local, Symfony a affiché :

```text
The service or alias has been removed or inlined when the container was compiled.
```

Ce n'est pas une erreur.

Cause : le service n'est pas encore utilisé par un contrôleur ou un autre service. Symfony optimise donc le container compilé.

Ce qui compte :

```text
Service ID : App\Service\DeliveryLogisticsService
Autowired : yes
Arguments : doctrine.orm.default_entity_manager
lint:container OK
```

## Décision de clôture

J5F-B est une étape de service métier pur. L'étape suivante sera l'intégration dans le panier :

```text
J5G-A — Aperçu logistique panier
```


---

# Décision navigation header — lien Admin prioritaire

## Contexte

Le lien admin avait été retiré du footer public pour ne pas exposer le backoffice.

Ensuite, une amélioration a été ajoutée dans `templates/base.html.twig` : afficher un lien `Admin` dans le header uniquement pour les utilisateurs connectés ayant `ROLE_ADMIN`.

## Décision retenue

Règle finale :

```text
ROLE_ADMIN → lien Admin
ROLE_COURIER seul → lien Livreur
ROLE_ADMIN + ROLE_COURIER → lien Admin seulement
visiteur / client simple → Devenir vendeur
```

Raison :

- un administrateur n'a pas besoin qu'on lui affiche aussi le lien livreur ;
- un admin connaît ou peut garder le lien `/ouegnewe` en favori ;
- le header doit rester simple sur mobile ;
- le lien backoffice reste invisible au public.

Bloc Twig retenu :

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a class="nav-link" href="/ouegnewe">
        Admin
    </a>
{% elseif is_granted('ROLE_COURIER') %}
    <a class="nav-link" href="{{ path('courier_dashboard') }}">
        Livreur
    </a>
{% else %}
    <a class="nav-link" href="{{ path('app_home') }}#devenir-vendeur">
        Devenir vendeur
    </a>
{% endif %}
```

## Incident syntaxe Twig évité

Twig n'accepte pas :

```twig
{% else if condition %}
```

La bonne syntaxe est :

```twig
{% elseif condition %}
```

Dans notre cas, on a choisi `elseif` avec priorité admin, car un admin + livreur doit voir seulement `Admin`.

---

# Décisions J5G avancées — Frais local, chemin de communes et barge aller-retour

## Contexte

Après J5G-A, le panier sait afficher un aperçu logistique et détecter la barge.

Pendant les tests, un point a été clarifié : détecter la barge ne suffit pas. Il faut aussi calculer un prix plus réaliste.

L'ancien modèle :

```text
si barge → prendre la zone barge de la commune client
sinon → prendre la zone locale de la commune client
```

est trop pauvre pour le modèle terrain de Hodina.

## Décision métier finale

Les frais de livraison doivent être composés.

Règle client :

```text
frais livraison client =
prix local de la commune de livraison
+ montant par commune traversée
+ prix barge aller-retour si la livraison traverse PT / GT
```

Règle livreur :

```text
rémunération livreur =
payout local de la commune de livraison
+ payout par commune traversée
+ payout barge si la livraison traverse PT / GT
```

## Pourquoi cette décision

Le livreur doit gagner plus lorsqu'il traverse plus de communes.

Raison métier :

```text
plus de route
plus de temps
plus de carburant
plus de contrainte terrain
```

Le client doit payer des frais cohérents avec la complexité de livraison, mais sans rendre le calcul incompréhensible.

## Exemple validé

Produit à Mamoudzou, livraison à Labattoir.

```text
Mamoudzou = GT
Labattoir = PT
```

La livraison nécessite la barge.

Le calcul cible est :

```text
frais livraison =
frais local Labattoir
+ prix barge aller-retour
+ supplément de traversée pour aller de Dzaoudzi à Labattoir
```

Pourquoi Dzaoudzi intervient ?

Dans le modèle pilote, Dzaoudzi peut représenter le point d'arrivée côté Petite-Terre après la barge.

Chemin simplifié :

```text
Mamoudzou
→ barge
→ Dzaoudzi
→ Labattoir
```

## Cas sans barge

S'il n'y a pas de barge, on ne prend pas le supplément barge.

Mais les frais peuvent quand même augmenter si plusieurs communes doivent être traversées.

Exemple :

```text
vendeur = Koungou
client = Sada
territoire = GT pour les deux
```

Alors :

```text
barge = non
frais = frais local client + supplément par communes traversées
```

## Barge : règle conservée

La barge reste strictement liée au territoire.

```text
barge = clientTerritory !== sellerTerritory
```

Elle ne dépend pas :

```text
du nombre de communes
de la distance supposée
du fait que les communes soient voisines ou non
du montant des frais
```

## Communes voisines : nouvelle importance

Avant, les communes voisines servaient surtout à qualifier le message :

```text
même commune
commune voisine
commune éloignée
autre territoire
```

Désormais, elles servent aussi au calcul du plus court chemin.

Décision :

```text
DeliveryCommune.neighboringCommunes devient le graphe logistique du pilote.
```

## Algorithme retenu

Pour le pilote :

```text
BFS = parcours en largeur
```

Rôle :

```text
trouver le chemin avec le plus petit nombre de communes traversées
```

Pourquoi pas GPS ?

```text
plus long à intégrer
données terrain Mayotte à vérifier
complexité non nécessaire pour le pilote
```

Pourquoi pas Dijkstra tout de suite ?

```text
Dijkstra sert surtout quand les chemins ont des poids différents.
Pour l'instant, chaque saut de commune a le même coût.
```

## Réglages Hodina décidés

À ajouter dans `HodinaSetting` :

```text
delivery_commune_hop_customer_fee
delivery_commune_hop_courier_payout
delivery_barge_round_trip_customer_fee
delivery_barge_round_trip_courier_payout
```

Ces réglages permettent à Hodina de changer les montants sans modifier le code.

## Panier versus checkout

Décision maintenue :

```text
panier = estimation
checkout = calcul définitif et snapshot
```

Le panier peut afficher :

```text
frais local
supplément communes traversées
supplément barge
total livraison estimé
```

Le checkout devra recalculer puis figer.

## Découpage validé

```text
J5G-A — aperçu panier par périmètre vendeur
J5G-B — plus court chemin / communes traversées
J5G-C — réglages Hodina des suppléments
J5G-D — affichage détaillé panier
J5G-E — gel checkout dans CustomerOrder
```

## Décision de prudence

Ne pas intégrer le calcul avancé brutalement dans J5G-A.

Raison :

```text
J5G-A est déjà validé comme branchement panier.
Le calcul avancé mérite un patch séparé et testable.
```


---

# Décision J5G-B1 — Source de données communes / voisinage validée

## Statut

**Décision validée avant codage du calcul de plus court chemin.**

Le fichier :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

est validé comme **source initiale de données logistiques** pour les communes, points logistiques, codes postaux, territoires et voisinages.

Cette décision prolonge le document source précédent :

```text
Voisinage commune.pdf
```

Le PDF donnait une première vision terrain du voisinage entre communes. Le fichier Excel corrigé devient la version structurée à traduire en base.

## Décision critique

Le fichier Excel ne doit pas rester une source morte dans le code.

Décision retenue :

```text
Excel validé
→ seed initial de base de données
→ données modifiables dans EasyAdmin
→ EasyAdmin devient la source opérationnelle après import
```

Cela signifie qu'une correction terrain future ne doit pas nécessiter de modifier le code Symfony.

Exemples de corrections possibles en backoffice :

- modifier un code postal ;
- désactiver une commune ;
- corriger un voisinage ;
- ajouter une liaison terrain ;
- désactiver temporairement une liaison ;
- corriger une note logistique.

## Correction de vocabulaire : pas une table de hashage en base

L'idée initiale était de créer une table de hashage avec commune, code postal et communes voisines.

Correction technique :

```text
Base de données Doctrine
→ tables relationnelles propres

PHP / DeliveryLogisticsService
→ hash map construite en mémoire pour calculer rapidement
```

Une table de hashage est utile côté PHP pour le BFS, mais ce n'est pas le bon modèle principal en base de données.

## Modèle relationnel cible

La base doit contenir deux notions distinctes.

### 1. Les points logistiques

Table / entité principale :

```text
DeliveryCommune
```

Un point logistique peut être :

- une commune administrative officielle ;
- un point logistique utile terrain ;
- une localité rattachée à une commune administrative.

Exemple important :

```text
Labattoir
```

Labattoir est utile pour Hodina comme point logistique, mais ne doit pas forcément être traité comme une commune administrative autonome. Il doit être rattaché à Dzaoudzi via un champ de rattachement.

### 2. Les liaisons entre points logistiques

Entité recommandée :

```text
DeliveryCommuneConnection
```

ou nom équivalent :

```text
DeliveryCommuneNeighborLink
```

Cette entité doit permettre de distinguer :

```text
LAND  = liaison terrestre
BARGE = liaison maritime / barge
```

Cette distinction est essentielle parce qu'une liaison terrestre et une liaison barge n'ont pas le même coût.

## Pourquoi ne pas se limiter au ManyToMany actuel ?

J5F-A a déjà introduit une relation simple de communes voisines.

Cette relation suffit pour dire :

```text
Dzaoudzi est voisine de Labattoir
```

Mais elle ne suffit pas à porter proprement :

```text
Dzaoudzi ↔ Mamoudzou = BARGE
Dzaoudzi ↔ Labattoir = LAND
liaison active / inactive
note terrain
coût spécifique éventuel
ordre d'affichage
```

Décision : pour J5G-B, créer une entité de liaison riche est plus propre que de surcharger la relation ManyToMany simple.

## Champs recommandés pour DeliveryCommune

```text
id
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

### Explication des champs

```text
name
→ nom affiché : Dzaoudzi, Labattoir, Mamoudzou...

slug
→ clé technique stable : dzaoudzi, labattoir, mamoudzou...

territory
→ PT ou GT, utilisé pour détecter la barge

postalCode
→ code postal de référence, utile pour rattacher une adresse

inseeCode
→ code commune officiel quand il existe

parentInseeCode
→ code de la commune administrative parente si le point n'est pas autonome

isLogisticsPoint
→ indique que le point est utilisé dans les calculs Hodina

isActive
→ permet de désactiver une commune sans supprimer l'historique

localPricingZone
→ zone tarifaire locale de base

internalNote
→ note terrain pour l'admin
```

## Champs recommandés pour DeliveryCommuneConnection

```text
id
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

### Explication des champs

```text
fromCommune / toCommune
→ les deux extrémités de la liaison

linkType
→ LAND ou BARGE

isBidirectional
→ indique si la liaison fonctionne dans les deux sens

hopCount
→ poids simple du lien pour le BFS pilote, souvent 1

isActive
→ permet de désactiver un lien temporairement

internalNote
→ explication terrain : barge, route principale, lien à vérifier...
```

## Exemple de données issues de la source validée

```text
Dzaoudzi → Labattoir
linkType = LAND
hopCount = 1

Dzaoudzi → Pamandzi
linkType = LAND
hopCount = 1

Dzaoudzi → Mamoudzou
linkType = BARGE
hopCount = 1

Mamoudzou → Koungou
linkType = LAND
hopCount = 1

Mamoudzou → Dembeni
linkType = LAND
hopCount = 1

Mamoudzou → Ouangani
linkType = LAND
hopCount = 1
```

## Hash map PHP construite depuis Doctrine

Une fois les données stockées en base, `DeliveryLogisticsService` pourra construire une structure rapide :

```php
[
    'dzaoudzi' => [
        ['to' => 'labattoir', 'type' => 'LAND'],
        ['to' => 'pamandzi', 'type' => 'LAND'],
        ['to' => 'mamoudzou', 'type' => 'BARGE'],
    ],
    'mamoudzou' => [
        ['to' => 'dzaoudzi', 'type' => 'BARGE'],
        ['to' => 'koungou', 'type' => 'LAND'],
        ['to' => 'dembeni', 'type' => 'LAND'],
        ['to' => 'ouangani', 'type' => 'LAND'],
    ],
]
```

Cette hash map est un outil de calcul, pas la source de vérité.

## Règle barge maintenue

La règle barge reste :

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Mais avec les liaisons typées, le chemin peut aussi montrer explicitement le passage par une liaison `BARGE`.

Exemple :

```text
Mamoudzou → Dzaoudzi → Labattoir
```

Analyse :

```text
Mamoudzou → Dzaoudzi = BARGE
Dzaoudzi → Labattoir = LAND
```

Résultat :

```text
requiresBarge = true
landHopCount = 1
bargeHopCount = 1
```

## Découpage validé après cette décision

```text
J5G-B1 — documentation source validée
J5G-B2 — modèle base DeliveryCommune / DeliveryCommuneConnection
J5G-B3 — seed initial depuis la source Excel
J5G-B4 — EasyAdmin pour corriger les communes et liaisons
J5G-B5 — BFS dans DeliveryLogisticsService
J5G-C  — réglages financiers des suppléments
J5G-D  — affichage panier détaillé
J5G-E  — snapshot checkout dans CustomerOrder
```

## Point pédagogique pour développeur débutant

Il faut retenir cette image :

```text
Commune = point
Liaison = route entre deux points
LAND = route terrestre
BARGE = route maritime
BFS = méthode pour trouver le plus court chemin
EasyAdmin = outil pour corriger les données sans redéployer
```

---

# Décisions J5G-B2 / J5G-B3 validées

## 1. La carte logistique doit être en base, pas codée en dur

Décision confirmée :

```text
DeliveryCommune + DeliveryCommuneConnection = source de vérité logistique modifiable
```

Raison : Mayotte a une réalité terrain qui peut changer. Le voisinage, les routes réellement utilisables, les points de passage et la barge doivent pouvoir être corrigés via EasyAdmin.

## 2. Labattoir est un point logistique, pas une commune INSEE autonome

Décision :

```text
Labattoir est conservé comme point logistique Hodina.
```

Mais :

```text
inseeCode = null
parentInseeCode = 97608
```

Raison : Hodina a besoin de Labattoir comme point terrain, mais la documentation ne doit pas faire croire qu'il s'agit d'une commune administrative autonome.

## 3. Les liaisons sont bidirectionnelles par défaut pour le pilote

Décision :

```text
isBidirectional = true
```

Raison : pour le pilote, une liaison terrestre entre deux communes est généralement utilisable dans les deux sens. Cela évite de dupliquer toutes les lignes.

Conséquence technique : J5G-B4 devra ajouter le lien inverse dans la hash map en mémoire.

## 4. La barge reste liée au changement PT / GT

Décision maintenue :

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Mais désormais, le chemin pourra aussi montrer explicitement une liaison `BARGE`, par exemple :

```text
Mamoudzou → Dzaoudzi → Labattoir
```

## 5. Les champs financiers par liaison restent nullable

`customerExtraFee` et `courierExtraPayout` sont créés sur `DeliveryCommuneConnection`, mais ils restent optionnels.

Décision :

```text
J5G-B4 calcule le chemin.
J5G-C décidera comment valoriser financièrement les hops et la barge.
```

Raison : séparer l'algorithme de trajet de la politique tarifaire.

## 6. Ne jamais valider un seed uniquement avec le message Doctrine

Incident : en recette, la migration J5G-B3 a indiqué 0 SQL queries.

Décision de méthode :

```text
Après un seed, on vérifie toujours les tables métier par SQL.
```

Requêtes de vérification utilisées :

```text
SELECT ... FROM delivery_commune
SELECT ... FROM delivery_commune_connection
```

## 7. Ne pas utiliser schema:update --force

Décision maintenue :

```text
schema:update --dump-sql sert au diagnostic.
schema:update --force est interdit dans ce projet pilote.
```

Les écarts Doctrine doivent être corrigés par migration dédiée.

## J5G-SUPPORT-ADRESSES — décisions prises pendant les tests J5G-B4

### 1. Ne pas mélanger adresse de livraison et adresse de facturation

Décision : une adresse porte désormais un usage métier explicite.

```text
DELIVERY = adresse de livraison
BILLING  = adresse de facturation
```

Raison : une adresse de facturation peut être hors zone Hodina, alors qu'une adresse de livraison doit être strictement livrable.

### 2. Nom français de la zone hors livraison

Décision : la zone hors livraison s'appelle :

```text
AUTRE — Autre
```

La proposition `OTHER` a été rejetée car l'application est destinée à un usage français.

### 3. Livraison stricte, facturation souple

Décision :

```text
Livraison
→ commune livrable obligatoire
→ zone PT ou GT obligatoire
→ cohérence commune / code postal / zone obligatoire

Facturation
→ commune libre
→ code postal français à 5 chiffres
→ zone AUTRE
```

### 4. Le support adresses passe avant la clôture J5G-B4

Décision : ne pas clôturer J5G-B4 tant que les adresses ne sont pas propres.

Raison : le calcul de trajet réel dépend directement de la commune client. Laisser passer une adresse fausse reviendrait à construire l'algorithme sur une donnée instable.

### 5. Ne pas commiter les patchs tant que les tests complets ne sont pas terminés

Décision de méthode :

```text
EasyAdmin livraison OK/KO ne suffit pas.
Il faut aussi tester facturation AUTRE, inscription et checkout.
```

---

# Décisions du 12/06/2026 — validation finale adresses front / EasyAdmin

## 1. La facturation peut être livrable

La décision initiale "facturation = AUTRE" a été affinée.

Décision finale :

```text
Une adresse de facturation peut être :
- hors zone Hodina → AUTRE
- à Mayotte livrable → PT ou GT
```

Conséquence :

```text
si facturation = AUTRE
→ code postal français 5 chiffres uniquement

si facturation = PT ou GT
→ commune livrable + code postal + zone cohérents
```

## 2. Livraison et facturation restent séparées

La livraison sert à la logistique.

La facturation sert à l'administratif.

Même si une adresse de facturation est livrable, elle ne doit pas influencer le calcul du trajet de livraison.

## 3. Le front doit afficher la zone de facturation

Décision : le formulaire client doit être cohérent avec EasyAdmin.

Donc :

```text
Checkout : champ Zone de facturation visible
Inscription : champ Zone de facturation visible quand facturation séparée
EasyAdmin : zone visible sur chaque adresse
```

## 4. Les erreurs doivent être utiles et contextualisées

Décision : ne pas afficher des messages génériques si la base logistique permet un diagnostic précis.

Exemple préféré :

```text
La commune Labattoir appartient à Petite-Terre (PT), pas à la zone Autre.
```

plutôt que :

```text
Une adresse de livraison ne peut pas utiliser AUTRE.
```

## 5. L'e-mail existant doit bloquer le checkout invité

Décision de sécurité :

```text
Un checkout invité ne peut pas créer une commande avec l'e-mail d'un compte existant.
```

Raison : éviter de rattacher ou créer une commande ambiguë sur un compte déjà existant sans authentification.

## 6. Un seul message d'erreur e-mail à l'inscription

Décision : supprimer le doublon `UniqueEntity` + message contrôleur.

Choix retenu :

```text
contrôle manuel dans RegistrationController
message métier unique
```

## 7. Les patchs code doivent être testés avant livraison

Décision renforcée après incidents :

```text
un patch code doit être testé avec git apply --check
un patch PHP doit être testé avec php -l
un patch Doctrine doit être suivi d'un schema:validate après application projet
```

Le développeur doit pouvoir appliquer le patch directement depuis la racine du dépôt.

---

# Décisions du 13/06/2026 — e-mails transactionnels et préouverture commerciale

## Contexte

Après la validation locale du support adresses, Hodina a clarifié les derniers éléments nécessaires avant la mise en recette puis la mise en production :

```text
- informer automatiquement le client par e-mail à la création d'une commande ;
- préparer les futurs e-mails de changement d'état de commande ;
- utiliser le SMTP o2switch pour l'envoi ;
- afficher une bannière de préouverture avec compte à rebours ;
- récupérer les e-mails des visiteurs souhaitant être prévenus ;
- bloquer toute création de panier et toute commande avant l'ouverture officielle.
```

Ces décisions ajoutent une couche e-mail et une mécanique de lancement commercial. Elles ne remplacent pas le workflow SMS existant.

## 1. Utiliser le SMTP o2switch pour les e-mails Hodina

Décision : utiliser le serveur SMTP mis à disposition par o2switch pour les e-mails transactionnels Hodina.

Principe prévu :

```text
Symfony Mailer
→ SMTP o2switch
→ adresse d'envoi dédiée, par exemple commandes@hodina.fr ou contact@hodina.fr
```

Les identifiants SMTP ne doivent pas être commités dans Git. Ils doivent être placés dans `.env.local` en local et dans l'environnement recette / production côté o2switch.

Exemple de principe, à adapter avec le vrai serveur o2switch :

```env
MAILER_DSN=smtps://commandes%40hodina.fr:MOT_DE_PASSE_SMTP@serveur-o2switch.net:465
MAILER_FROM=commandes@hodina.fr
MAILER_FROM_NAME="Hodina"
```

Le caractère `@` de l'adresse e-mail est encodé en `%40` dans le DSN.

## 2. Créer un journal des e-mails envoyés

Décision : ne pas envoyer d'e-mails sans traçabilité.

Une future entité `EmailLog` est prévue, sur le modèle de `SmsLog`, pour garder une trace des envois automatiques et manuels.

Champs recommandés :

```text
id
customerOrder nullable
customer nullable
recipientEmail
subject
templateKey
eventKey
status: PENDING / SENT / FAILED
errorMessage nullable
sentAt nullable
createdAt
```

Objectifs : savoir si un e-mail est parti, diagnostiquer les erreurs SMTP, éviter les doublons, permettre un renvoi manuel depuis EasyAdmin et conserver une preuve en cas de litige client.

## 3. Envoyer automatiquement le descriptif de commande dès la création

Décision : à la création d'une commande, le client doit recevoir automatiquement un e-mail HTML de récapitulatif.

Cet e-mail doit partir dès que possible, juste après la création et l'enregistrement de la commande.

Important : l'e-mail doit dire que la commande est **reçue / en attente de validation**, et non pas déjà validée admin.

Contenu attendu :

```text
- salutation client ;
- numéro métier de commande ;
- date de commande ;
- statut : en attente de validation admin ;
- rappel : paiement à la livraison pendant le pilote ;
- tableau des produits ;
- quantité ;
- prix ;
- sous-total ;
- frais logistiques / livraison si disponibles ;
- total ;
- adresse de livraison ;
- adresse de facturation ;
- téléphone ;
- e-mail client ;
- message de confiance Hodina.
```

Template prévu :

```text
templates/emails/order_created.html.twig
```

Le format devra rester compatible avec les clients e-mail : tableaux HTML simples, styles inline ou CSS très simple, pas de dépendance JavaScript.

## 4. Ne pas bloquer la commande si l'e-mail échoue

Décision importante : l'échec SMTP ne doit jamais empêcher la création de commande.

Règle métier :

```text
Commande créée en base = prioritaire.
E-mail envoyé = information client.
Si l'e-mail échoue, la commande reste créée et l'erreur est logguée dans EmailLog.
```

## 5. Préparer les e-mails de changement d'état, mais ne pas tout automatiser d'un coup

Décision : ne pas automatiser tous les e-mails de statut immédiatement.

Ordre retenu :

```text
J5H-A : socle e-mail + e-mail automatique de création de commande
J5H-B : boutons EasyAdmin pour renvoi / message manuel
J5H-C : e-mails automatiques sur validation admin, annulation, livraison, etc.
```

Événements futurs envisagés :

```text
ORDER_CREATED_CUSTOMER
ORDER_VALIDATED_CUSTOMER
ORDER_CANCELED_CUSTOMER
ORDER_READY_CUSTOMER
ORDER_OUT_FOR_DELIVERY_CUSTOMER
ORDER_DELIVERED_CUSTOMER
MANUAL_ADMIN_MESSAGE
PASSWORD_RESET_CUSTOMER
```

## 6. Ajouter une préouverture commerciale avec compte à rebours

Décision : avant les premières ventes, Hodina affichera une bannière / section globale de préouverture avec compte à rebours.

Objectif : créer de l'attente, annoncer l'ouverture des commandes, garder le catalogue visible, empêcher les paniers prématurés et récupérer les e-mails des visiteurs intéressés.

Règle retenue :

```text
Voir les produits : OUI
Créer un panier : NON avant ouverture
Créer une commande : NON avant ouverture
```

## 7. La préouverture doit être paramétrable depuis EasyAdmin

Décision : l'affichage et la date de fin du compte à rebours doivent être administrables depuis EasyAdmin.

Configuration prévue :

```text
Compte à rebours actif : oui/non
Date et heure d'ouverture des commandes
Titre affiché
Message affiché
Texte du bouton e-mail
Capture e-mail active : oui/non
Blocage panier avant ouverture : oui/non
Message après inscription e-mail
```

Entité possible : `SalesOpeningSetting` ou configuration globale équivalente si le projet conserve la logique `HodinaSetting`.

## 8. Bloquer côté serveur, pas seulement côté template

Décision : désactiver le bouton `Ajouter au panier` en front ne suffit pas.

La règle de préouverture doit être protégée côté serveur dans :

```text
CartController / ajout panier
CheckoutController / validation panier
service de création de commande si existant
```

Le front informe, le back protège.

## 9. Récupérer les e-mails de préouverture dans une entité dédiée

Décision : créer une table dédiée aux visiteurs qui veulent être prévenus de l'ouverture.

Entité proposée : `LaunchSubscriber`.

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

Règle : un même e-mail ne doit pas être inscrit plusieurs fois.

Message conseillé :

```text
En laissant votre e-mail, vous acceptez de recevoir un message lorsque les commandes Hodina ouvriront. Aucun spam.
```

## 10. La mise en production attendra la bannière de préouverture

Décision de jalon : la production sera mise à jour après validation de la bannière de préouverture en recette.

Ordre retenu :

```text
1. finir J5I préouverture ;
2. valider local ;
3. mettre à jour docs ;
4. commit propre ;
5. déployer recette ;
6. tester recette mobile + PC ;
7. valider ;
8. mettre à jour production.
```


---

# Décisions validées — 13/06/2026 — J5I déployé en recette

## 1. La préouverture est livrée avec `HodinaSetting`, pas avec une nouvelle table de configuration

La préparation J5I envisageait deux options : une entité dédiée `SalesOpeningSetting` ou la réutilisation du système générique `HodinaSetting`.

Décision finale : conserver `HodinaSetting`.

Raison : le projet possède déjà un mécanisme simple et administrable depuis EasyAdmin :

```text
1 ligne hodina_setting = 1 paramètre fonctionnel
```

Cette approche évite d'ajouter une deuxième logique de configuration globale.

## 2. Les réglages J5I retenus

Les paramètres actifs pour la préouverture sont :

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

Valeurs dev injectées ensuite en recette :

```text
is_countdown_enabled = 1
sales_opening_at = 2026-06-30 18:00
countdown_title = Votre marché en ligne de produits locaux arrive bientôt
countdown_message = Le catalogue est accessible, mais la prise de commande sera possible à la date officielle. Laisse nous ton e-mail pour être informé de l'ouverture.
countdown_button_label = Me faire signe à l’ouverture
is_email_capture_enabled = 1
is_cart_locked_before_opening = 1
countdown_success_message = Merci, ton e-mail est bien enregistré. On te préviendra pour l’ouverture des commandes.
```

## 3. Décision : le catalogue reste visible pendant la préouverture

Pendant la préouverture :

```text
Accueil visible : OUI
Catalogue visible : OUI
Fiche produit visible : OUI
Prix visibles : OUI
Capture e-mail : OUI si paramètre actif
Ajout panier : NON si panier bloqué
Checkout : NON si panier bloqué
Commande créée : NON avant ouverture
```

Raison métier : Hodina doit montrer l'offre et rassurer les futurs clients, sans accepter de commandes avant que l'organisation terrain soit prête.

## 4. Décision : le blocage doit être serveur

Le bouton désactivé en Twig n'est pas suffisant. La règle finale est :

```text
Le front informe.
Le serveur protège.
```

Le blocage a donc été ajouté dans :

```text
CartController
CheckoutController
SalesOpeningService
```

## 5. Décision : capture e-mail dédiée avec `LaunchSubscriber`

Les visiteurs intéressés par l'ouverture sont stockés dans une table dédiée :

```text
launch_subscriber
```

Cette table ne doit pas être mélangée avec les clients. Un visiteur peut vouloir être prévenu sans avoir encore créé de compte.

## 6. Décision recette : admin sur `/ouegnewe`

L'URL admin de recette reste :

```text
https://recette.hodina.fr/ouegnewe
```

Ne pas documenter `/admin` comme URL opérationnelle pour cette instance.

## 7. Décision `.htaccess` recette : corriger Basic Auth / HTTPS / 401.shtml

Problème constaté : après Basic Auth, certains navigateurs renvoyaient vers :

```text
https://recette.hodina.fr/401.shtml
```

Décision : conserver le Basic Auth, forcer HTTPS proprement et empêcher la redirection parasite o2switch vers `/401.shtml`.

Règle retenue :

```apache
ErrorDocument 401 "Authentification requise"
```

Puis règles Symfony standard.

## 8. Décision importante : corriger l'ordre des migrations avant production

En recette, la migration `Version20260613094055` a tenté de modifier `launch_subscriber.created_at` avant que la table ne soit créée par `Version20260613110000`.

Problème exact :

```text
Version20260613094055
→ ALTER TABLE launch_subscriber ...

Version20260613110000
→ CREATE TABLE launch_subscriber ...
```

Résultat recette : erreur `Table launch_subscriber doesn't exist`.

Contournement recette appliqué : marquer `Version20260613094055` comme exécutée, lancer `Version20260613110000`, puis appliquer manuellement la correction `created_at`.

Décision : ne pas reproduire ce contournement en production sans correction Git. Avant production, il faudra rendre l'ordre des migrations propre, soit en renommant la migration corrective après la création de table, soit en intégrant la correction directement dans la migration J5I.

---

# Décisions J5J — Mode commerce durable et rôle testeur

## Décision principale

J5I ne reste pas un mécanisme isolé de préouverture. Il est remplacé par J5J : un **mode commerce durable** capable de couvrir la préouverture, les maintenances de production et les fermetures temporaires.

## Décisions prises

```text
- utiliser des paramètres génériques commerce_* ;
- supprimer les anciens paramètres J5I après migration ;
- utiliser ROLE_COMMERCE_TESTER plutôt qu'une liste d'e-mails ;
- afficher les booléens comme des switchs EasyAdmin ;
- afficher commerce_mode comme une liste de choix ;
- masquer le champ technique field_type lors de l'édition d'un réglage système ;
- ne jamais afficher la bannière si commerce_mode = open.
```

## Raison

Le besoin produit n'est pas seulement la préouverture. Hodina doit pouvoir ouvrir le catalogue, bloquer les commandes publiques et permettre à des testeurs d'utiliser le portail réel en production. Un seul système évite les doublons et les nettoyages futurs.

## Règle validée

```text
commerce_mode = open
→ aucun chrono
→ aucune bannière de préouverture ou maintenance
→ affichage normal du portail
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

# Décisions J5H-A validées — e-mail récapitulatif commande

## Statut

J5H-A est validé en recette le 15/06/2026.

## Décisions métier

- L'e-mail de création de commande est un **récapitulatif informatif**.
- Il ne valide pas la commande.
- La commande reste en `PENDING_VALIDATION`.
- Le message client rappelle que Hodina vérifie la disponibilité des produits avant validation.
- Le paiement reste manuel / à la livraison pendant le pilote.
- L'expéditeur visible est `contact@hodina.fr`.
- `no-reply@hodina.fr` est abandonné pour ce flux.
- Aucun e-mail vendeur, livreur, PDF, paiement en ligne ou newsletter n'est ajouté dans J5H-A.

## Décisions techniques

- Les e-mails de commande sont centralisés dans `OrderEmailService`.
- Le checkout ne doit plus envoyer directement un e-mail.
- L'appel au service e-mail se fait après le `flush()` de création de commande.
- Un échec e-mail ne doit jamais bloquer la commande.
- `EmailLog` devient la source de consultation admin pour les e-mails transactionnels.
- L'écriture de `EmailLog` utilise DBAL pour éviter les erreurs d'EntityManager fermé.
- Le template `emails/order_created.html.twig` reçoit un snapshot scalaire des articles.
- Les articles sont relus en base via DBAL après le flush, car la collection inverse `CustomerOrder::items` peut être vide en mémoire au moment de l'envoi asynchrone.
- Symfony Mailer passe par Messenger.
- Le vrai envoi SMTP dépend du worker `messenger:consume async`.

## Décisions opérationnelles

- En recette, le worker Messenger est lancé par cron toutes les minutes.
- Le cron crée `var/log` si nécessaire.
- Le cron écrit dans `var/log/messenger_cron.log`.
- Le cron utilise `flock` pour éviter deux workers concurrents.
- `EmailLog = SENT` signifie pour le pilote : e-mail accepté par Symfony Mailer / Messenger.
- Une granularité future `QUEUED` / `SENT_SMTP` pourra être étudiée plus tard, mais n'est pas bloquante pour le pilote.
- Pour inscrire un e-mail dans les `Messages envoyés`, Hodina ne force pas une copie IMAP.
- L'admin dispose à la place d'un bouton `Envoyer manuellement` dans EasyAdmin.
- Ce bouton ouvre le client mail du téléphone ou du PC avec destinataire, sujet et corps préremplis.
- L'admin clique lui-même sur envoyer depuis `contact@hodina.fr`, ce qui inscrit naturellement le message dans les envoyés du client mail.

## Décision critique

Ne pas complexifier J5H-A avec IMAP. Pour le pilote, la combinaison retenue est :

```text
EmailLog EasyAdmin
+ SMTP o2switch
+ Messenger cron
+ bouton manuel mailto
```

---

# Décisions J5G-E0 — Snapshot adresse commande

Date de validation recette : **15/06/2026**
Commit principal : `279f49c feat(j5g): snapshot order addresses`
Commit préparatoire : `d4c5ab9 fix(j5g): use delivery address for cart logistics preview`

## Décision métier

Les adresses du compte client sont un **carnet d'adresses vivant**. Elles peuvent être ajoutées, modifiées ou supprimées par le client ou par l'administration.

Une commande est un **document historique figé**. Elle ne doit pas dépendre d'une adresse client supprimable ou modifiable après la commande.

La règle retenue est donc :

```text
Au checkout, l'adresse sélectionnée est copiée dans CustomerOrder.
Après création de la commande, les affichages métier lisent le snapshot de commande.
La relation vers Address reste facultative et ne doit plus être la source unique d'historique.
```

## Pourquoi cette décision est importante

L'ancien modèle `customer_order.delivery_address_id -> address.id` posait trois problèmes :

1. l'utilisateur ne pouvait pas supprimer librement une adresse utilisée par une commande ;
2. une suppression d'adresse pouvait provoquer une erreur de contrainte SQL ;
3. l'historique d'une commande dépendait d'une donnée vivante.

Ce modèle était incohérent avec les lignes de commande : `OrderItem` fige déjà les prix et marges produit. L'adresse doit suivre la même logique.

## Décisions techniques

- Ajouter des champs snapshot livraison et facturation dans `CustomerOrder`.
- Remplir ces champs au checkout.
- Reprendre les anciennes commandes par migration.
- Passer `customer_order.delivery_address_id` en relation tolérante à la suppression (`ON DELETE SET NULL`).
- Lire les snapshots dans l'admin commande, la fiche terrain, le dashboard livreur et les e-mails.
- Réutiliser une adresse identique existante au checkout pour limiter les doublons futurs.

## Ce qui n'est pas décidé ici

J5G-E0 ne fige pas encore toute la logistique avancée : frais détaillés, rémunération livreur, marge livraison, route, hops, barge. Ces éléments restent dans le périmètre J5G-E logistique / financier.

## Tests recette validés

- Anciennes commandes reprises avec snapshot livraison.
- Anciennes commandes reprises avec snapshot facturation lorsque l'information était disponible.
- Nouvelle commande créée avec snapshot livraison et facturation.
- Pas de nouvelle adresse identique créée lors du test checkout.
- Suppression EasyAdmin d'une adresse liée à une commande : aucune erreur 500.
- Commande liée à une adresse supprimée : `delivery_address_id = NULL`, mais snapshot conservé.
- `doctrine:schema:validate --env=prod` OK après clear/warmup cache.

---

# Décisions J5G-E1 — Simplifier la saisie adresse par commune livrée

Date de cadrage : **16/06/2026**
Statut : **décision validée, développement à faire dans une nouvelle discussion**
Archive code de référence transmise : `code_j5g_e1_commune_livree_2026-06-16_17-48-30.zip`

## Décision produit

La saisie actuelle d'adresse de livraison demande trop d'effort au client : commune, code postal et zone. Cette friction est mauvaise pour le pilote et augmente le risque de données incohérentes.

La décision retenue est :

```text
La commune livrée devient la source de vérité.
Le code postal devient une aide de saisie / une valeur préremplie.
La zone devient une donnée interne Hodina, déduite automatiquement.
```

Le client ne doit pas avoir à comprendre ou choisir `PT`, `GT` ou `AUTRE` pour une adresse de livraison.

## Décision critique

La logique ne doit pas être basée uniquement sur le code postal. Un code postal peut couvrir plusieurs zones ou points logistiques. Pour Hodina, la donnée métier réellement importante est la **commune livrée / point logistique**.

Règle cible :

```text
Commune livrée choisie
→ DeliveryCommune retrouvée
→ code postal prérempli
→ zone déduite depuis DeliveryCommune.territory
→ DeliveryZone retrouvée côté serveur
```

## Sécurité backend

Le frontend peut aider l'utilisateur, mais ne doit jamais être la source de vérité.

Le backend doit recalculer la zone et valider la commune via l'existant :

- `DeliveryCommune` ;
- `DeliveryCommuneMatcherService` ;
- `DeliveryZone` ;
- `DeliveryLogisticsService` pour les calculs aval.

## Décision sur la facturation

L'adresse de facturation différente n'a pas les mêmes contraintes que l'adresse de livraison. Elle peut rester hors zone livrable (`AUTRE`) si le client ne coche pas “facturation identique à livraison”.

Donc J5G-E1 doit surtout simplifier **la livraison**. Il ne faut pas imposer aux adresses de facturation d'être des communes livrées.

## Décision d'ordre projet

J5G-E1 doit être fait avant J5G-B4 :

```text
J5G-E1 — Simplifier la saisie adresse par commune livrée
J5G-B4 — Stabiliser le trajet logistique réel
J5G-C  — Frais / suppléments logistiques
J5G-E2 — Snapshot logistique financier
```

La raison est simple : stabiliser le trajet logistique réel avec une saisie adresse encore trop libre créerait du bruit et des faux bugs.

## Ce qui n'est pas décidé ici

- Pas de nouveau modèle logistique.
- Pas de duplication de `DeliveryCommune`.
- Pas de duplication du calcul de trajet.
- Pas de suppression de J5G-E0.
- Pas de snapshot financier dans cette étape.

---

# Décisions J5G-E1 → J5G-E2-bis-A — Commune livrée et panier contractuel

Date : **17/06/2026**
Branche : `pilot/j5g-e1-commune-livree`

## Décision UX

Pendant le pilote avec paiement manuel, le client ne doit pas passer par un checkout séparé pour choisir son adresse de livraison.

Le parcours cible devient :

```text
Catalogue
→ Panier
→ adresse de livraison dans le panier
→ récapitulatif livraison + total
→ validation commande
→ confirmation
```

Le checkout reviendra plus tard seulement pour :

```text
paiement en ligne
adresse de facturation si différente
validation paiement
```

## Décision commune livrée

`DeliveryCommune` devient la source de vérité de l'adresse de livraison côté UX.

```text
Client choisit une commune livrée
→ code postal prérempli
→ zone affichée en lecture seule
→ serveur recalcule commune / code postal / zone
```

La zone envoyée par le navigateur ne doit jamais être considérée comme fiable.

## Décision total panier

Le panier devient l'écran contractuel du total avant validation.

```text
Si le total affiché n'est plus le total réel au moment de valider,
la commande n'est pas créée.
```

Le client doit revenir au panier avec une raison claire, voir le nouveau total, puis revalider.

## Décision tarifaire temporaire avant J5G-B4

Avant le calcul BFS et les coûts de traversées terrestres, la règle pilote est :

```text
frais livraison = forfait local de la commune livrée + coût fixe barge si barge détectée
```

La barge reste détectée uniquement par différence de territoire :

```text
PT → GT = barge
GT → PT = barge
PT → PT = pas de barge
GT → GT = pas de barge
```

Le coût de barge est fixe et identique dans les deux sens. Il doit être configuré sur la liaison logistique `BARGE`.

## Décision affichage panier

L'ordre retenu dans le panier est :

```text
1. Produits
2. Livraison et validation
3. Récapitulatif
4. Validation commande
```

L'adresse utilisée est visible. Le formulaire de modification est replié par défaut pour ne pas alourdir l'écran mobile.

## Décision confirmation

La page de confirmation ne doit pas seulement rassurer. Elle doit aussi récapituler ce qui vient d'être enregistré :

```text
produits
vendeurs
quantités
frais livraison
total
adresse
zone
statut
paiement manuel
```

## Anti-doublon technique

Ne pas recréer :

```text
DeliveryCommune
DeliveryCommuneMatcherService
DeliveryLogisticsService
```

Les prochaines étapes doivent étendre ces composants au lieu de produire une logique parallèle.

---

# Décisions production J5G-E1 → J5G-E2-bis-A

Date : **17/06/2026**
Branche production : `pilot/j5j-commerce-mode-role-tester`
Tag production : `j5g-e1-e2bis-prod`

## Décision de mise en production

Après validation recette, J5G-E1 à E2-bis-A est déployé en production.

La règle métier est donc confirmée en production :

```text
Le panier est l'écran de livraison et de validation pendant le pilote.
Le checkout ne sert plus au choix principal de livraison.
Le checkout reviendra plus tard pour le paiement en ligne et la facturation.
```

## Décision de sécurité total

Le total affiché dans le panier est le total contractuel. Si le serveur recalcule un total différent au moment de valider, la commande doit être refusée et le client doit revenir au panier.

Cette règle reste prioritaire sur l'UX : il vaut mieux demander une revalidation que créer une commande avec un prix différent de celui vu par le client.

## Décision déploiement base

En production, deux migrations étaient en attente :

```text
Version20260615140801
Version20260615225836
```

Elles ont été exécutées avec `doctrine:migrations:migrate --env=prod`. La commande `doctrine:schema:update --dump-sql --env=prod` a été utilisée uniquement pour diagnostiquer, jamais avec `--force`.

## Décision incidents / warnings

Les warnings de dépréciation suivants ne bloquent pas la production :

```text
doctrine.orm.controller_resolver.auto_mapping deprecated
DashboardController sans #[AdminDashboard] deprecated EasyAdmin 5
Doctrine migrations implicit commit deprecation
```

Ils sont à traiter plus tard dans un jalon technique de nettoyage, sans bloquer J5G.

# Décisions J5G-B4 — calcul logistique BFS et frais de livraison

Date : **17/06/2026**
Merge : `10ff512 merge(j5g): integrate BFS delivery logistics rules`

## Décision 1 — Ne pas recréer la logistique

J5G-B4 étend l'existant :

```text
DeliveryCommune
DeliveryCommuneConnection
DeliveryLogisticsService
CartLogisticsPreview
CustomerOrder
```

Aucune nouvelle table de communes, aucune logique parallèle et aucun calcul dans Twig ne doivent être ajoutés.

## Décision 2 — La commune livrée reste la source de vérité

Le tarif local vient de la commune livrée choisie par le client dans la liste Hodina.

```text
commune livrée PT → forfait local PT
commune livrée GT → forfait local GT
```

La zone envoyée par le navigateur ne fait pas foi.

## Décision 3 — La barge reste territoriale

La barge ne dépend pas d'une distance, ni d'un nombre de communes, ni d'un “éloignement”.

```text
PT ↔ GT = barge requise
PT ↔ PT = pas de barge
GT ↔ GT = pas de barge
```

Les liaisons `BARGE` servent à chiffrer et afficher la traversée, mais la règle métier de nécessité de barge reste protégée par la différence de territoire.

## Décision 4 — Le coût global de traversée s'applique aux LAND

Le coût global `global_commune_crossing_customer_fee` est un fallback pour les liaisons terrestres `LAND`.

Il s'applique avant et après la barge lorsque le trajet contient des segments terrestres.

Il ne remplace pas le coût spécifique de barge.

## Décision 5 — Paniers multivendeurs : ne pas sommer toutes les livraisons

Pour préserver la conversion, Hodina ne facture pas une livraison complète par vendeur.

Règle pilote :

```text
frais client = tarif local commune livrée
             + trajet de collecte le plus contraignant
             + supplément multicommunes plafonné
             puis plafond global
```

## Décision 6 — Le supplément est basé sur les communes de collecte distinctes

Le supplément multicommunes ne compte pas les articles.
Il ne compte pas directement les vendeurs.
Il compte les communes de collecte distinctes.

```text
1 vendeur avec 3 articles = 1 commune de collecte
2 vendeurs dans la même commune = 1 commune de collecte
2 communes de collecte = 1 supplément
3 communes de collecte = 2 suppléments, plafonnés si nécessaire
```

## Décision 7 — Le plafond client ne plafonne pas le livreur

`global_delivery_customer_fee_cap` protège le prix payé par le client.

Il ne doit pas écraser automatiquement l'estimation livreur.

Cela permet à Hodina de mesurer une éventuelle perte ou marge faible sur certains trajets longs.

## Décision 8 — Snapshot obligatoire pour analyser plus tard

Les nouvelles commandes doivent conserver `deliveryLogisticsSnapshot`.

Objectif : pouvoir expliquer plus tard pourquoi une commande a été facturée à un certain montant, même si les settings ont changé.

## Décision 9 — Les anciennes commandes peuvent être nettoyées avant bascule

En recette ou production, si l'objectif est de repartir sur une base propre avec uniquement des commandes snapshotées, les anciennes commandes et leurs logs associés peuvent être supprimés après backup.

Ne pas supprimer les clients, produits, vendeurs, communes, zones ni settings.

# Décisions DevOps — déploiement par tag et scripts versionnés

## Déploiement

Décision du 18/06/2026 : Hodina doit déployer recette et production à partir de tags Git issus de `main`.

```text
main = branche de référence stable
tag = version livrable figée
serveur = checkout du tag
```

Raison : limiter le risque de déployer une branche mouvante et préparer le futur CI/CD.

## Remote Git

Décision : les serveurs doivent utiliser une clé SSH GitHub en lecture seule via Deploy Key.

Le script de déploiement impose par défaut un remote SSH :

```text
git@github.com:chahere/hodina.git
```

## Fichiers d'environnement

Décision : les fichiers `.env.local`, `.env.prod.local` et `prod.env.local` sont propres à chaque environnement et doivent être protégés avant checkout.

Le script de déploiement les sauvegarde dans :

```text
var/deploy_env_backup/<timestamp>/
```

## Nettoyage commandes

Décision : pour repartir sur des commandes avec snapshot logistique propre, les anciennes commandes de test peuvent être supprimées avec `dbal:run-sql`.

Cette suppression doit conserver clients, vendeurs, produits, communes, zones et réglages.

## Décisions du 18/06/2026 — MEP J5G-B4 et outillage v7

### Déploiement

- Décision : recette et production déploient un tag Git issu de `main`.
- Décision : le tag `j5g-b4-20260618-v7` est la référence J5G-B4 production.
- Décision : les dépôts serveur doivent utiliser un remote SSH, pas HTTPS.
- Décision : le script extrait depuis un tag doit être testé avec `test -s` puis `bash -n` avant exécution.

### Runtime

- Décision : `public/uploads/products` est une donnée runtime à protéger avant checkout.
- Décision : les fichiers uploadés ne doivent pas bloquer un déploiement.
- Décision : les anciennes images suivies par Git seront sorties plus tard proprement.

### Backup

- Décision : si `doctrine:database:export` est indisponible, utiliser `mariadb-dump` en priorité.
- Décision : vérifier que la base dumpée correspond à la base Doctrine.
- Décision : ne pas afficher le mot de passe DB en ligne de commande ; utiliser un `my.cnf` temporaire en `chmod 600`.

### Cache

- Décision : en recette/prod, ne pas supprimer brutalement `var/cache/prod`.
- Décision : utiliser `cache:clear --no-warmup` puis `cache:warmup`.

### Admin produit

- Décision : l'aperçu image produit EasyAdmin doit rester simple et stable.
- Décision : éviter les `MutationObserver` lourds et le scan large du DOM dans les collections EasyAdmin.

# Décisions du 19/06/2026 — v11, mails réels et ordre de suite

## Décision 1 — `j5g-b4-20260618-v11` devient la référence production

Le tag final validé pour la séquence J5G-B4 étendue est :

```text
j5g-b4-20260618-v11
```

Il remplace les tags intermédiaires pour les nouvelles MEP de référence.

Le tag `v10` ne doit pas être utilisé comme tag final, car il ne contient pas le correctif `Utilisateurs` du menu admin.

## Décision 2 — AssetMapper est une étape obligatoire de MEP

Les miniatures EasyAdmin ne s'affichaient pas en recette/prod tant que les assets n'étaient pas compilés.

Décision : `asset-map:compile --env=prod` fait partie du script MEP avant le cache prod.

`public/assets` est un dossier généré, non versionné, accepté par le précontrôle Git.

## Décision 3 — `public/uploads/products` reste runtime

Les uploads produits ne doivent pas être supprimés par un checkout Git.
Ils doivent être sauvegardés / parkés / restaurés pendant la MEP.

Dette : les fichiers historiques encore suivis par Git doivent être sortis du suivi proprement plus tard.

## Décision 4 — Menu admin mobile repliable

Le menu EasyAdmin gauche doit rester utilisable sur mobile. Les sections longues peuvent être repliées.

Sections concernées :

```text
Logistique
Catalogue
Commandes
Utilisateurs
Pilote
Réglages
```

Le cas `Utilisateurs` a été corrigé : le titre de section peut être replié, mais l'entrée menu `Utilisateurs` doit rester cliquable.

## Décision 5 — Ajout panier Ajax avec fallback

L'ajout produit depuis le catalogue ou la fiche produit doit être Ajax quand c'est possible :

- pas de rechargement page ;
- pastille panier mise à jour ;
- message court de confirmation ;
- gestion JSON du panier verrouillé ;
- fallback POST conservé.

Le panier reste la vérité côté serveur. L'Ajax est une amélioration UX, pas une source de vérité.

## Décision 6 — Un EmailLog `SENT` ne suffit pas si `MAILER_DSN=null://null`

En production, le transport `null://null` a permis à Symfony de considérer les e-mails comme envoyés sans livraison réelle.

Décision : pour valider les e-mails, il faut :

```text
EmailLog SENT
+ MAILER_DSN réel en .env.local
+ e-mail reçu dans une boîte réelle
```

Les secrets SMTP ne doivent jamais être commitées.

## Décision 7 — Incident admin pendant MEP classé transitoire

Un plantage admin a été observé pendant une MEP. Le test à froid ensuite n'a pas reproduit l'incident, et les logs PHP ne montrent pas de nouvelle erreur.

Conclusion : incident transitoire lié à une requête pendant le changement code/cache/assets.

Décision : éviter de tester l'admin pendant que le script tourne. À moyen terme, envisager maintenance courte ou déploiement atomique.

## Décision 8 — Ordre de développement après v11

L'ordre validé est :

```text
1. Docs v11.
2. Dette env/uploads/assets/MAILER_DSN.
3. J5K GPS livraison.
4. J5L admin commande/logistique terrain.
5. J5M portail livreur exploitable.
6. Paiement et automatisations plus tard.
```


# Décisions du 19/06/2026 — Dette technique pré-J5K env/uploads/assets/MAILER_DSN

## Décision 1 — Secrets environnement hors Git

`.env.local`, `.env.prod.local` et `prod.env.local` ne doivent pas être suivis par Git.

Ils peuvent exister physiquement sur recette / production, mais doivent rester locaux au serveur.

## Décision 2 — `prod.env.local` reste legacy

`prod.env.local` peut être protégé par les scripts pour éviter toute perte historique, mais Symfony charge naturellement `.env.local` et `.env.prod.local`.

Décision : les secrets réellement utilisés par l'application doivent être placés dans `.env.local` ou `.env.prod.local`, pas uniquement dans `prod.env.local`.

## Décision 3 — Uploads produits runtime

`public/uploads/products` contient des données métier runtime. Le dossier doit être conservé, mais les images réelles ne doivent pas être versionnées.

Seul le fichier suivant peut être suivi par Git :

```text
public/uploads/products/.gitkeep
```

## Décision 4 — Assets générés

`public/assets` est une sortie de compilation Symfony AssetMapper. Il doit être ignoré par Git et régénéré pendant la MEP avec :

```bash
php bin/console asset-map:compile --env=prod
```

## Décision 5 — Logs runtime o2switch

`public/error_log` est un fichier runtime serveur. Il ne doit pas être suivi par Git.

## Décision 6 — Validation mail réelle

Un e-mail Hodina n'est pas validé uniquement parce que `EmailLog` vaut `SENT`.

Validation complète :

```text
EmailLog = SENT
+ MAILER_DSN réel côté serveur
+ e-mail reçu dans une boîte réelle
```

`MAILER_DSN=null://null` reste acceptable comme valeur de sécurité dans `.env`, mais ne valide aucun envoi réel.

## 2026-06-19 — J5K-v8 : adresses de livraison et facturation par défaut

Décision : le panier client doit distinguer explicitement l'adresse de livraison et l'adresse de facturation.

- L'adresse de livraison par défaut est portée par `customer.delivery_address_id`.
- L'adresse de facturation par défaut reste portée par `customer.billing_address_id`.
- La zone de livraison n'est plus affichée dans le bloc client du panier ; elle reste calculée automatiquement côté serveur.
- Les cartes d'adresses gardent le clic de sélection ; la définition de l'adresse par défaut se fait uniquement dans le formulaire d'ajout/modification via une case à cocher.
- Le stylo sert à modifier l'adresse existante sélectionnée.
- Le snapshot commande reste la source de vérité historique pour les commandes validées.

---

## J5K-v8 — Correction UX panier adresses livraison/facturation

Décision validée après test local : le client ne doit pas voir les champs métiers inutiles dans le panier.

- La zone de livraison / facturation reste une donnée serveur, mais elle ne doit pas être exposée dans le bloc client de facturation.
- Le GPS est strictement réservé à l'adresse de livraison.
- Les instructions livreur sont strictement réservées à l'adresse de livraison.
- Le clic sur une carte d'adresse suffit à sélectionner l'adresse pour la commande en cours.
- Le bouton `Sélectionner cette adresse` est supprimé car redondant sur mobile.
- La préférence durable n'est plus portée par un bouton sous la carte : elle est portée par la case `Utiliser cette adresse par défaut` dans le formulaire d'ajout/modification.
- En l'absence d'adresse par défaut explicite, Hodina sélectionne automatiquement une adresse disponible pour éviter un état vide inutile.


## Décision — J5K-v8-bis adresse de facturation automatique

Une adresse de facturation doit être une vraie adresse `BILLING`, distincte de l’adresse de livraison. Si un client possède uniquement une adresse de livraison, Hodina crée une adresse de facturation en copiant les champs postaux utiles, sans GPS ni instructions livreur.


## J5K-v8-ter / quater — Décision finale locale sur les adresses par défaut

- [x] Les boutons séparés de mise par défaut sont remplacés par une case à cocher prise en compte à la validation du panier.
- [x] La case porte le libellé unique `Utiliser cette adresse par défaut`.
- [x] La case est visible uniquement dans les formulaires d'ajout ou de modification d'adresse.
- [x] La case n'est pas affichée sur les cartes d'adresses.
- [x] La case n'est pas affichée dans les blocs `Adresse utilisée`.
- [x] Correction : les champs techniques `mapped => false` du formulaire panier sont réhydratés pour afficher correctement l'adresse de facturation par défaut.
- [x] Validation locale : ouverture panier, sélection livraison/facturation, validation commande, persistance `billing_address_id` et `delivery_address_id` selon la case cochée.

Décision de prudence : ne pas déployer en recette un tag intermédiaire. La recette devra repartir d'un tag propre créé après les correctifs J5K-v8-quater.


---

# Décisions 20/06/2026 — Priorisation fin juin et images catalogue

## Priorisation produit / business

Décision : après la clôture du panier J5K, Hodina doit prioriser le portail client, puis le portail livreur, avant l’optimisation admin avancée.

Ordre retenu :

```text
J5K final — panier / adresses / GPS / facturation
J5L — portail client MVP
J5M — portail livreur MVP
J5N — optimisation admin exploitation
J5O — optimisation automatique des images produits
J5P — suivi financier manuel
```

Raison : le client et le livreur sont les deux expériences visibles qui conditionnent la confiance et la promesse de livraison. L’admin peut rester moins optimisé au début s’il reste exploitable par l’équipe interne.

## Décision portail client

Le portail client MVP doit rester simple.

Il doit permettre :

- de voir ses commandes ;
- de suivre un statut compréhensible ;
- de consulter le détail d’une commande ;
- de mettre à jour ses informations utiles ;
- de gérer ses adresses.

Inclure dans le MVP : annulation client encadrée avant préparation, motif d’annulation persisté, base technique de feedback client. Ne pas inclure dans J5R-A : paiement en ligne, remboursement, messagerie, favoris, notation publique, fidélité. La notation vendeur/livreur est prévue en J5R-B sur la même entité `CustomerOrderFeedback`.

## Décision portail livreur

Le portail livreur doit permettre de livrer depuis mobile.

Il doit afficher :

- commandes prêtes ;
- commandes prises ;
- adresse complète ;
- téléphone client ;
- GPS si disponible ;
- lien Maps / Waze ;
- instructions ;
- rémunération ;
- étapes de livraison ;
- problème de livraison.

Ne pas inclure dans le MVP : optimisation de tournée, géolocalisation temps réel, application native, preuve photo obligatoire, signature client obligatoire.

## Décision images catalogue

Des images catalogue légères ont été créées et mises en production manuellement le 20/06/2026.

Décision : cette optimisation manuelle est suffisante pour le démarrage et la présentation du catalogue. Elle ne remplace pas le futur développement d’un pipeline automatique d’optimisation d’images.

Le futur pipeline automatique devra traiter :

- conversion WebP ;
- redimensionnement ;
- poids cible inférieur à 200 Ko ;
- multi-formats catalogue / fiche produit ;
- limitation dimensions ;
- vérification format ;
- sécurité upload ;
- rotation EXIF ;
- aperçu poids / dimensions dans EasyAdmin.

Décision de périmètre : upload AJAX, barre de progression, régénération massive et nettoyage images orphelines sont utiles mais repoussés après le MVP.


---

# Décisions 20/06/2026 soir — Clôture J5K-v8-quater recette et planning corrigé

## Décision — J5K-v8-quater est terminé en recette

J5K-v8-quater est clôturé fonctionnellement et techniquement en recette.

Référence finale recette :

```text
devops-deploy-composer-before-console-v2
Commit : 48dae1d
```

Historique des tags :

```text
j5k-gps-livraison-recette-v9
→ correctif fonctionnel J5K-v8-quater.

devops-vendor-untracked-recette-v1
→ retrait de vendor/ du suivi Git.

devops-deploy-composer-before-console-v2
→ script corrigé et validé de bout en bout en recette.
```

## Décision — `vendor/` ne doit plus être suivi par Git

Le dossier `vendor/` est une dépendance générée par Composer. Il doit être reconstruit par :

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

Règle :

```text
composer.json + composer.lock = suivis par Git
vendor/ = ignoré par Git
```

## Décision — Composer avant `bin/console` si vendor absent

Après retrait de `vendor/` du suivi Git, un checkout de tag peut laisser `vendor/autoload.php` absent.

Le script de déploiement doit donc exécuter Composer avant le premier appel Symfony si nécessaire :

```text
checkout tag
→ restauration env/uploads
→ composer install si RUN_COMPOSER=1 ou vendor/autoload.php absent
→ bin/console --version
→ migrations / assets / cache / validate
```

## Décision — Cron Messenger recette corrigé

La ligne cron recette doit contenir un espace entre les deux options :

```text
--time-limit=50 --memory-limit=128M
```

L’ancienne forme est invalide et ne doit pas revenir :

```text
--time-limit=50--memory-limit=128M
```

## Décision — J5L-A avant J5L

Avant le portail client MVP, Hodina doit traiter une étape dédiée :

```text
J5L-A — UX panier mobile PWA avant portail client
```

Raison : le portail client intervient après la commande, alors que le panier décide si la commande existe. L’écran panier actuel est fonctionnel mais trop `desktop/admin` pour une PWA mobile.

Ordre validé jusqu’au 30/06 :

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

# Décisions 21/06/2026 — Clôture J5L et préparation J5M

## Décision — Le panier client doit rester court

Les listes d'adresses ne doivent plus rallonger le flux principal du panier.

Décision : le panier affiche uniquement l'adresse actuellement utilisée et ouvre un sélecteur compact lorsque le client veut changer d'adresse.

## Décision — Les détails logistiques techniques ne sont pas affichés au client

Les chemins BFS, détails de traversées et informations opérationnelles logistiques restent utiles pour l'admin, mais ils ne doivent pas être visibles dans le panier client.

Le client doit comprendre :

```text
ce qu'il achète
combien il paie
où il est livré
quelle adresse est utilisée pour la facturation
comment valider
```

## Décision — Sélection et confirmation d'adresse sont séparées

Cliquer sur une adresse dans le panneau ne ferme pas le panneau.

Le panneau se ferme uniquement via :

```text
Utiliser cette adresse de livraison
Utiliser cette adresse de facturation
```

Justification : l'utilisateur doit pouvoir ajouter le GPS, corriger des champs et cocher `Utiliser cette adresse par défaut` avant confirmation.

## Décision — L'adresse de facturation doit être visible côté admin

Même si le paiement reste manuel pendant le pilote, l'adresse de facturation est une donnée administrative importante.

Décision : la fiche terrain admin et le détail EasyAdmin affichent l'adresse de facturation snapshotée.

## Décision — J5L est clôturé avant J5M

J5L est validé en recette. Le prochain chantier est `J5M-A — Workflow livreur enrichi`.

## Décision préparatoire J5M — Ne pas stocker le nom du livreur dans le statut

À ne pas faire :

```text
status = "pris en charge par Chahere"
```

À faire :

```text
status = picked_up
courier = utilisateur livreur connecté
```

Affichage :

```text
Prise en charge par Chahere
```

Deuxième statut à créer :

```text
status = out_for_delivery
Affichage : En cours de livraison
```


---

# Décisions J5M-C2 à J5M-C3-ter — Vendeur, retrait et calcul logistique

## Vendeur = client Hodina

Décision : un vendeur Hodina est aussi représenté par un `Customer`.

Conséquences :

```text
Seller.customerAccount → Customer
Customer.roles contient ROLE_SELLER
Prénom vendeur obligatoire
Nom vendeur obligatoire
```

## Nom de structure optionnel

Décision : le nom de structure est distinct de l’identité du vendeur.

```text
Seller.businessName renseigné
→ affichage livreur = structure
→ affichage catalogue = structure

Seller.businessName vide
→ affichage livreur = prénom + nom
→ affichage catalogue = nom de famille
```

## Point de retrait vendeur

Décision : ne pas dupliquer adresse/GPS dans `Seller`. Utiliser `Address`.

```text
Seller.pickupAddress → Address
```

## Commune de retrait

Décision : ne jamais saisir la commune de retrait en texte libre.

```text
Formulaire vendeur → choix DeliveryCommune
Address.commune → DeliveryCommune.name
Address.postalCode → DeliveryCommune.postalCode
Seller.deliveryCommune → DeliveryCommune sélectionnée
Seller.deliveryZone → DeliveryCommune.deliveryZone
```

## Source de vérité logistique

Décision critique : `DeliveryLogisticsService` continue d’utiliser `Seller.deliveryCommune`.

Le point de retrait vendeur sert au terrain livreur mais ne sert pas au calcul du coût.

## Garde-fou technique

Décision : ajouter un script PHP portable :

```powershell
php tools/assert-delivery-logistics-commune-source.php
```

Il doit échouer si `DeliveryLogisticsService` commence à dépendre de `pickupAddress`, `getEffectivePickupAddress()` ou `customerAccount`.

---

# Décisions 24/06/2026 — J5O/J5P/J5Q

## Décision — Le code client est chiffré et réutilisable jusqu'à livraison

Le code de réception client n'est pas stocké en clair. Il est chiffré dans `CustomerOrder.deliveryValidationCodeEncrypted` et déchiffré uniquement pour permettre le renvoi du même code si le client ne le retrouve pas.

Règle validée :

```text
Démarrer livraison
→ générer le code si absent
→ envoyer SMS + e-mail
→ renvoyer le même code si validation sans code
→ valider uniquement si code correct
→ supprimer le code chiffré après livraison
```

## Décision — Ne pas utiliser `contact@hodina.fr` comme destinataire de secours

`contact@hodina.fr` peut être une adresse d'expédition ou de contact Hodina, mais ne doit jamais devenir destinataire de secours d'un code vendeur ou client.

Si le vendeur ou le client n'a pas de destinataire valide, l'envoi doit échouer proprement et être journalisé.

## Décision — Notifications client sans spam inutile

Les notifications client sont utiles, mais doivent rester compréhensibles.

Décision J5P-A : envoyer les e-mails correspondant aux statuts clés, mais ne pas ajouter d'e-mail générique `OUT_FOR_DELIVERY`, car le code de réception client J5O-A couvre déjà cette étape.

Point accepté : le SMS générique `customer_order_out_for_delivery` peut encore coexister avec `customer_delivery_code`. Il est noté comme vigilance et pourra être retiré dans un J5P-bis si l'expérience terrain paraît trop bavarde.

## Décision — Le paiement livreur est tracé, pas automatisé

J5Q-A ne déclenche aucun virement. Hodina calcule, l'admin valide, puis l'admin marque payé après action réelle hors plateforme.

Règle :

```text
DRAFT → VALIDATED → PAID
```

Le passage à `PAID` reste volontairement humain.

## Décision — `CustomerOrder.deliveredAt` est la source de période

Les paiements livreurs ne doivent pas utiliser `updatedAt`, car ce champ peut changer pour des raisons administratives.

La période de rattachement d'une commande est déterminée par :

```text
CustomerOrder.deliveredAt
```

## Décision — Une commande ne peut être payée qu'une fois

La contrainte `uniq_courier_payout_line_order` empêche une commande d'être rattachée à plusieurs lignes de rémunération.

Cette règle protège contre un double paiement livreur.

## Décision — Menu EasyAdmin par métiers

L'ancien menu mélangeait des catégories techniques. L'ordre validé est :

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

La section `Livreurs` est autonome et ne doit pas être absorbée par `Commandes` ou `Utilisateurs/Clients` dans le menu mobile. `assets/admin.js` doit donc connaître explicitement `Livreurs`, mais aussi `Clients`, `Vendeurs` et `Logs`.

## Décision — Collision des anciens jalons J5O/J5P

Les anciens libellés prévisionnels :

```text
J5O — optimisation automatique des images produits
J5P — suivi financier manuel
```

ne sont plus des jalons actifs sous ces numéros.

Les lots réels validés sont :

```text
J5O-A — code de réception client chiffré
J5P-A — notifications client sur statuts
J5Q-A — paiements livreurs
```

Les images automatiques et le suivi financier global restent dans le backlog post-MVP sans numéro définitif.

---

# Décision J5Q-C — Automatiser la préparation, pas le paiement

Date : 24/06/2026

## Contexte

Après J5Q-A, Hodina sait calculer, valider et marquer payé les rémunérations livreurs. Le risque restant est opérationnel : si l'admin oublie de générer une période, le suivi financier livreur prend du retard.

Comme l'exploitation doit rester fiable même à distance, J5Q-C introduit un cron avec récap admin.

## Décision

```text
Le cron prépare les rémunérations DRAFT.
Le cron envoie un récap aux admins.
Le cron ne valide jamais.
Le cron ne marque jamais payé.
```

## Justification

Cette décision automatise la fatigue et les oublis sans automatiser la responsabilité financière.

L'admin conserve les actions sensibles :

```text
DRAFT → VALIDATED
VALIDATED → PAID
```

## Règles anti-régression

- ne jamais utiliser `updatedAt` pour déterminer la période ; utiliser `deliveredAt` ;
- ne jamais rattacher une même commande à deux lignes de rémunération ;
- ne jamais recalculer un paiement `PAID` ;
- ne jamais créer un paiement `DRAFT` vide ;
- ne jamais envoyer de récap en `--dry-run` ;
- garder le cron idempotent.

# Décision J5Q-C-1 — Structurer les réglages sans multiplier les tables

Décision : `HodinaSetting` reste le registre central des paramètres globaux Hodina, mais il est structuré par groupe métier.

Justification :

- les réglages commencent à couvrir beaucoup de sujets différents ;
- une liste EasyAdmin unique devient risquée et difficile à lire ;
- créer une table par famille de réglages serait trop lourd et créerait de la dette technique ;
- un stockage central avec des écrans EasyAdmin filtrés offre un bon compromis entre simplicité technique et clarté UX.

Groupes initiaux :

- Général ;
- Commerce & commandes ;
- Livraison & logistique ;
- Notifications ;
- Paiements ;
- Technique / maintenance.

Règle : un paramètre doit avoir un groupe principal. Si un paramètre semble appartenir à deux sujets, il faut choisir le groupe où l'admin ira naturellement le chercher.

Hors périmètre : le branding e-mail n'est pas inclus dans J5Q-C-1. Il sera traité dans J5Q-C-2 pour éviter de mélanger fondation UX et fonctionnalité e-mail.


## Complément J5Q-C-1 — Paramètres paiements livreurs

Le groupe `Paiements` contient désormais les réglages opérationnels du module de rémunération livreur :

- `courier_payouts_enabled` : active ou suspend les générations de paiements livreurs ;
- `courier_payout_cron_enabled` : autorise la génération cron `--auto-due` ;
- `courier_payout_admin_recap_enabled` : autorise l’envoi du récapitulatif admin après génération réelle ;
- `courier_payout_frequency` : fréquence métier, valeur pilote `semi_monthly` pour la quinzaine.

Ces réglages restent des garde-fous : ils ne valident pas et ne marquent jamais un paiement comme payé automatiquement.

# Décision J5Q-C-2 — Centraliser le branding e-mail

Date : 25/06/2026

## Contexte

Hodina envoie désormais plusieurs e-mails : client, vendeur, livreur/admin et bientôt d'autres notifications. Avec dev, recette et production, il devient nécessaire d'identifier rapidement l'origine d'un e-mail à la réception.

## Décision

Le branding e-mail devient paramétrable depuis EasyAdmin > Réglages > Branding e-mail.

```text
Préfixe objet e-mail
Formule début e-mail
Formule fin e-mail
Signature e-mail
```

Tous les e-mails existants doivent utiliser `EmailBrandingService`.

## Justification

Cette décision évite de dupliquer des formules dans chaque service et sécurise les tests recette : un sujet `[Recette] ...` se distingue immédiatement d'un e-mail de production.

## Règles anti-régression

- aucun SMS n'est modifié par ce lot ;
- le sujet stocké dans `EmailLog` doit être le sujet envoyé ;
- un sujet déjà préfixé ne doit pas recevoir deux fois le même préfixe ;
- les valeurs par défaut restent sobres pour ne pas imposer `[Recette]` en production ;
- le branding ne doit pas modifier les règles métier d'envoi ou d'anti-spam.

---

# Décision 25/06/2026 — Ne pas rollback J5Q-C-2 sans preuve applicative

## Contexte

Après le déploiement recette de `J5Q-C-2 — Branding e-mail`, un comportement intermittent `ERR_CONNECTION_CLOSED` a été observé côté navigateur mobile, parfois lors d'authentifications ou de modifications EasyAdmin.

Les contrôles serveur ont montré :

- accueil et admin répondent en `200` / `302` depuis `curl` ;
- migrations Doctrine OK ;
- schema Doctrine OK ;
- Twig e-mails OK ;
- cache prod OK ;
- PHP web réel en 8.4.21 avec `memory_limit=512M` et `max_execution_time=600` ;
- access logs récents majoritairement normaux ;
- aucun `500/502/503/504` récent confirmé pour l'incident observé.

## Décision

Ne pas rollback J5Q-C-2 sans preuve applicative.

Le diagnostic doit d'abord capturer :

- `public/error_log` ;
- `var/log/prod.log` si Monolog est redirigé ou si le fichier reçoit des logs ;
- `~/access-logs/recette.hodina.fr-ssl_log` ;
- heure exacte, navigateur, réseau et action effectuée.

## Justification

Un rollback réflexe risquerait de masquer un problème de réseau mobile, HTTP/2, LSAPI, proxy ou session, alors que le déploiement applicatif est propre.

## Règles anti-régression

- ne pas confondre `200 500` dans l'access log avec un code HTTP 500 : le premier nombre est le statut HTTP, le second est souvent la taille de réponse ;
- ne pas laisser de fichiers publics de test : `_health_php_tmp.php`, `_ini_check.php`, `_log_test.php` ;
- ne pas committer `public/error_log`, `public/.user.ini`, `var/log/*.log` ;
- documenter tout changement de logging s'il devient durable.


---

# Décision J5Q-D0 — Stabiliser Djama sans créer de nouvelles entités

Date : 25/06/2026

## Contexte

Le portail livreur contient déjà le workflow opérationnel : prise en charge, collectes vendeurs, code réception client, livraison et rémunération livreur. Avant de démarrer le Portail client MVP, il faut éviter de créer des doublons techniques.

## Décision

J5Q-D0 stabilise l’existant au lieu d’ajouter une nouvelle couche métier.

Sont explicitement exclus :

- nouvelle entité `Courier` ;
- nouvelle entité `SellerCollection` ;
- nouvelle entité `DeliveryCode` ;
- nouveau workflow livreur parallèle ;
- nouvelle table de paiement livreur.

Le SMS générique `customer_order_out_for_delivery` est supprimé du démarrage livraison, car le code réception client `customer_delivery_code` porte déjà l’information utile et opérationnelle.

## Règles anti-régression

- Le livreur ne peut démarrer la livraison que si toutes les collectes vendeurs sont validées.
- Le client doit recevoir le code réception au démarrage livraison.
- Le client ne doit pas recevoir deux SMS redondants au même moment.
- Les alertes terrain Djama sont calculées depuis les données existantes, sans nouvelle table.
- Les coordonnées placeholder comme `0000000000` ou `contact@hodina.fr` ne doivent pas être traitées comme des contacts opérationnels.


---

# Décision J5R-A — Portail client commandes + annulation encadrée

Date : 25/06/2026

Décision : le Portail client MVP doit inclure l’annulation client encadrée et la collecte du motif d’annulation dès le départ.

Règles retenues :

- le client peut annuler directement uniquement si la commande est `PENDING_VALIDATION` ou `CONFIRMED` ;
- à partir de `PREPARING`, l’annulation directe disparaît et le client doit contacter Hodina ;
- l’annulation client réutilise `CustomerOrderWorkflowService` et le statut existant `CANCELED` ;
- le message SMS d’annulation client est distinct du message d’annulation admin ;
- le motif/commentaire d’annulation est stocké dans `CustomerOrderFeedback` ;
- le code de réception n’est pas affiché en clair dans le portail client ;
- le détail commande doit être filtré par propriétaire, jamais par ID seul.

Motif produit : le client ne doit pas se sentir piégé, et Hodina doit apprendre dès les premières commandes pourquoi un client annule.

Limites volontaires : pas de remboursement, pas de litige, pas de messagerie, pas de notation publique, pas de suivi GPS live.

---

# Décision J5S-A — Points de remise comme brique logistique dédiée

Date : 25/06/2026

Décision : certains produits Hodina peuvent être paramétrés pour imposer une remise dans un ou plusieurs points fixes. La modélisation retenue est une vraie entité `DeliveryPoint`, et non une adresse client ou une liste codée en dur.

Motifs :

- Mayotte nécessite des lieux repères fiables : barge, aéroport, relais pickup, points vendeurs, points événementiels.
- Les colliers de fleurs doivent pouvoir être remis uniquement à la barge Petite-Terre ou à l’accueil passager de l’aéroport de Pamandzi.
- Un point de remise est un lieu Hodina/logistique, pas une adresse personnelle du client.
- La relation produit ↔ points autorisés doit être administrable.
- Les plages horaires doivent être administrables pour permettre au client de choisir un créneau dans un prochain lot.

Règle : `DeliveryPoint.deliveryCommune` rattache le point à la commune logistique Hodina existante. `DeliveryCommune` reste la source de vérité logistique PT/GT.

Anti-régression : J5S-A ne modifie pas le panier, le checkout, Djama, le portail client, les SMS/e-mails ni les calculs de livraison.


## Décision J5S-B — Point de remise client

Un point de remise n’est pas une adresse client. C’est une adresse logistique Hodina.

Si un produit impose un point de remise, toute la commande suit le point choisi dans le MVP. Cette règle évite de créer plusieurs livraisons dans une seule commande.

Le client peut ajouter une instruction libre liée à la remise : heure d’arrivée, vol, barge ou repère.

## J5T-A — Checkout première commande simplifié

- Ajout d’un parcours checkout invité simplifié.
- Le client non connecté ne saisit plus de mot de passe avant validation.
- Un compte est créé automatiquement et l’e-mail de commande contient un lien sécurisé pour définir le mot de passe.
- Le checkout connecté reste inchangé.
- Aucun changement de schéma.


## J5T-A-bis — Décision checkout invité

Le checkout invité ne doit afficher aucun champ technique ni case à cocher sans libellé. Les champs internes nécessaires à la compatibilité du formulaire restent soumis de manière cachée.

Le contenu de l’e-mail `ORDER_CREATED` doit être vérifiable dans `EmailLog`, notamment le lien sécurisé de création du mot de passe pour les nouveaux clients créés au checkout.


# Mise à jour 27/06/2026 — J5T à J5W

## J5T-A / J5T-A-bis — Checkout invité simplifié validé recette

Le checkout invité doit rester mobile-first et ne pas demander de mot de passe avant la validation de commande. À la validation, Hodina crée le compte client si nécessaire et inclut dans `ORDER_CREATED` un lien sécurisé de création de mot de passe.

Décision confirmée en recette : le formulaire simple nouveau client est validé. Les champs techniques ne doivent jamais apparaître comme cases à cocher sans libellé. Le corps de `ORDER_CREATED` doit rester journalisé dans `EmailLog` pour pouvoir contrôler le lien envoyé.

## J5U-A — Expéditeur e-mails paramétrable EasyAdmin

Décision validée en recette : l’expéditeur des e-mails de commande/statut/collecte/code réception est paramétrable dans EasyAdmin, groupe `Branding e-mail`, et vaut `commande@hodina.fr` pour le pilote.

Les e-mails concernés sont :

```text
ORDER_CREATED
ORDER_STATUS_CONFIRMED
ORDER_STATUS_PREPARING
ORDER_STATUS_READY_FOR_PICKUP
ORDER_STATUS_PICKED_UP
SELLER_COLLECTION_CODE
ORDER_SELLER_COLLECTIONS_COMPLETED
CUSTOMER_DELIVERY_CODE
ORDER_STATUS_DELIVERED
ORDER_STATUS_CANCELED
```

Règles actées :

- `ORDER_CREATED` est envoyé au client et en copie cachée à l’adresse interne configurée, par défaut `commande@hodina.fr`.
- Les templates doivent indiquer au destinataire de ne pas répondre directement à l’e-mail.
- `EmailLog` historise l’expéditeur et le `Reply-To` réellement utilisés.
- Les réglages EasyAdmin doivent rester dans `Réglages → Branding e-mail` pour éviter de disperser la configuration.

## J5V-A — Délai minimum de commande par produit

Décision métier : le délai minimum avant remise/livraison est une propriété du produit, pas du point de remise.

Champ retenu :

```text
Product.minimumOrderLeadTimeHours
```

Règles :

- `null` ou `0` : aucune contrainte.
- valeur positive : nombre d’heures minimum entre la commande et le rendez-vous client.
- panier multi-produits : appliquer le délai le plus strict.
- application bloquante immédiate uniquement pour les points de remise, car le client y saisit une date et une heure précises.
- livraison standard : pas encore bloquée par cette règle tant que le client ne choisit pas de date/heure standard explicite.

## J5W — Décisions préparatoires zones, communes et express

Les besoins J5W sont actés comme backlog, non implémentés dans les sources du 27/06/2026.

Décision majeure : ajouter une couche de sous-zone opérationnelle sans remplacer la zone logistique existante.

```text
DeliveryZone / DeliveryCommune.territory = grande géographie technique PT/GT et compatibilité historique.
DeliveryPricingZone / DeliveryCommune.localPricingZone = forfait local de base.
DeliveryCommuneConnection = liaisons LAND/BARGE et garde-fou trajet.
DeliveryArea = planning, jours de livraison, exploitation, affectation future des livreurs.
```

Anti-régression obligatoire : le découpage en `DeliveryArea` ne doit jamais casser les calculs de barge. Mamoudzou, Grande-Terre Sud et Grande-Terre Centre/Nord restent rattachés à `DeliveryZone` Grande-Terre. Petite-Terre reste la seule grande zone opposée à Grande-Terre pour la barge.

Répartition initiale issue de l’expérience Hodidagoni :

```text
PT : Dzaoudzi, Labattoir, Pamandzi — lundi / jeudi
MAMOUDZOU_AGGLO : Mamoudzou, Koungou, Dembéni — mercredi / samedi
GT_SUD : Bandrélé, Chirongui, Bouéni, Kani-Kéli, Sada — mercredi / samedi
GT_NORD_CENTRE : Acoua, Bandraboua, Mtsamboro, M'Tsangamouji, Tsingoni, Ouangani, Chiconi — mardi / vendredi
```

Cutoff cible : une commande obtient le prochain créneau si elle est passée avant 10h00 la veille du créneau ; sinon elle bascule au créneau suivant.

La livraison express doit être une demande client hors créneau standard, pas une promesse automatique pendant le pilote. Le supplément en euros sera paramétrable et la faisabilité restera confirmée humainement.

Pour les points de remise, si le livreur propose une heure différente, l’heure demandée par le client doit rester historisée. La proposition livreur ne doit pas écraser le rendez-vous initial.
## J5S-B-ter — Point de remise et adresse standard sont deux sources de vérité différentes

Décision actée : en mode point de remise, la source de vérité logistique est le `DeliveryPoint` choisi, pas l’adresse de livraison du client.

Règles :

- Produit point imposé : le client choisit uniquement un point/date/heure parmi les points proposés.
- Produit standard : le client choisit une adresse de livraison.
- Produit standard + point : le mode choisi décide de la source de vérité.
- Frais en mode point : commune du point de remise.
- Frais en mode standard : commune de l’adresse client.
- Le bloc adresse standard doit être masqué en mode point pour éviter une confusion métier.
- Les cartes de points de remise doivent être masquées en mode standard pour éviter de faire croire au client qu’un point est encore nécessaire.

Cette décision ne modifie pas les règles de barge : la barge reste calculée par la logique logistique existante à partir des communes et territoires associés.

## J5S-B-quater — Feedback global sans remplacer la validation serveur

Décision actée : le checkout mobile affiche un feedback global sous le header quand une information manque ou qu’une contrainte serveur bloque la commande.

Le bouton `Valider` est grisé pour les champs obligatoires simples non saisis : prénom, nom, téléphone, e-mail, CGV, point de remise, date, heure, adresse standard, commune ou facturation selon le mode. Le message global rouge côté client n’est affiché qu’après une tentative de validation, afin de ne pas afficher une erreur avant que le client ait commencé à soumettre sa commande. En revanche, les contraintes métier complexes ne sont pas dupliquées comme source de vérité côté JavaScript. Elles restent validées côté Symfony, notamment : point autorisé, heure dans une plage active, délai minimum produit et cohérence du mode standard/point de remise.

Justification : améliorer l’expérience mobile sans créer de dette métier ni divergence entre front et serveur.

## Affichage de l’unité de vente produit côté client

Décision actée : l’unité de vente doit être visible dans le catalogue, la fiche produit et le panier. L’information vient du champ `Product.unit` déjà existant : `UNIT`, `KG`, `G`, `L`.

Aucune nouvelle entité ni migration n’est nécessaire. Le code expose un libellé métier via `Product::getUnitLabel()`. Si l’unité est absente ou inconnue, l’affichage retombe sur `À l’unité` pour préserver la lisibilité du catalogue.

Justification : le client doit comprendre s’il achète un produit à l’unité, au kilo, au gramme ou au litre avant d’ajouter au panier. Cela réduit les malentendus de prix sans complexifier le MVP.


## J5S-B-quater-ter — Timing du feedback de validation

Décision UX : le message global rouge ne doit pas apparaître dès l’arrivée sur la page panier. Il apparaît uniquement après une tentative de validation ou après un retour serveur invalide.

Avant tentative :

- le bouton peut être visuellement grisé si des informations simples manquent ;
- le texte d’aide reste neutre ;
- aucun message d’erreur global agressif n’est affiché.

Après tentative :

- le message global sous le header indique la première information bloquante ;
- les erreurs serveur restent affichées au niveau des champs ;
- la validation Symfony reste la source de vérité.

Justification : sur mobile, afficher une erreur avant action du client donne l’impression que le panier est déjà en échec. L’objectif est d’accompagner sans agresser.

## J5S-B-quater-quater — Validation conditionnelle standard / point de remise

Décision technique : les champs `address` et `commune` ne doivent plus être obligatoires globalement dans `CheckoutType`, car ils ne sont pas pertinents en mode point de remise.

Règle :

- mode `STANDARD` sans adresse existante : adresse et commune obligatoires ;
- mode `DELIVERY_POINT` : adresse client ignorée, point/date/heure validés par `CheckoutController` ;
- `deliveryPointTimeWindowId` reste non obligatoire, car la plage est déduite de l’heure client.

Cette décision corrige la fuite du message Symfony générique anglais `This value should not be blank.` et garantit une erreur métier française en cas d’heure hors plage : `Choisis une heure dans les horaires proposés pour ce point de remise.`

## J5T-C — Ne pas bloquer le checkout invité si l’e-mail existe déjà

Décision actée : un client invité peut valider une commande avec une adresse e-mail déjà connue de Hodina, à condition de confirmer explicitement le rattachement de la commande au compte existant.

Règles :

- aucun doublon `Customer` ne doit être créé pour le même e-mail ;
- le client n’est pas obligé de se connecter pour finir sa commande mobile ;
- le popup apparaît uniquement après tentative de validation complète, pas à la saisie de l’e-mail ;
- la confirmation est liée à l’e-mail soumis, pour éviter de confirmer un autre e-mail après modification du champ ;
- `ORDER_CREATED` mentionne : `Cette commande a été rattachée à ton espace client Hodina.` ;
- le lien de création de mot de passe n’est pas affiché pour un compte existant rattaché.

Justification : réduire la friction mobile tout en évitant les doublons clients et l’énumération d’adresses e-mail.

## J5T-C — Le checkout invité ne doit plus bloquer un e-mail déjà connu

Décision confirmée après test : l’ancien comportement `Un compte existe déjà avec cette adresse e-mail. Connecte-toi...` est conservable pour l’inscription classique, mais il est incorrect dans le checkout invité.

Règle retenue pour Hodina :

- si le client invité utilise un e-mail inconnu, Hodina crée le compte automatiquement comme en J5T-A ;
- si le client invité utilise un e-mail déjà connu, Hodina n’ajoute pas d’erreur au champ e-mail ;
- Hodina affiche un popup de confirmation avant de créer la commande ;
- la commande n’est créée qu’après confirmation explicite ;
- la confirmation est liée à l’e-mail soumis via `confirmedExistingAccountEmail` ;
- la commande est rattachée au `Customer` existant ;
- aucun doublon `Customer` ne doit être créé ;
- `ORDER_CREATED` contient la mention `Cette commande a été rattachée à ton espace client Hodina.` ;
- le lien de création de mot de passe n’est pas affiché pour un compte existant rattaché.

Justification : réduire la friction mobile sans forcer la connexion, tout en évitant les doublons clients et sans transformer le champ e-mail en outil de vérification d’existence d’un compte.

## Décision 28/06/2026 — J5T-C validé recette : rattachement compte existant sans blocage

Le checkout invité doit accepter un e-mail déjà connu de Hodina. La bonne règle métier n’est plus de bloquer l’e-mail dans le checkout, mais d’afficher une confirmation explicite avant création de commande. Après confirmation, la commande est rattachée au `Customer` existant et `ORDER_CREATED` mentionne : `Cette commande a été rattachée à ton espace client Hodina.`

Cette décision ne s’applique pas à l’inscription classique : `RegistrationController` peut continuer à bloquer les doublons d’e-mail.

## Décision 28/06/2026 — J5S validé recette avant DeliveryArea

La séparation standard / point de remise est verrouillée avant de poursuivre les futures `DeliveryArea`. Décision clarifiée après J5W-A : `DeliveryPricingZone` porte le forfait local, `DeliveryCommuneConnection` et `DeliveryCommune.territory` protègent la barge / PT-GT, et les futures `DeliveryArea` serviront au planning, aux sous-zones et à l’affectation livreur.

## Décision 28/06/2026 — J5V-A corrigé et revalidé recette après régression de branchement checkout

Le délai minimum produit reste une contrainte serveur. La régression détectée le 28/06/2026 venait du fait que `Product.minimumOrderLeadTimeHours` et `DeliveryPointCartService::validateMinimumOrderLeadTime()` existaient, mais que l’appel serveur n’était plus présent dans le flux checkout point de remise. Décision : ne pas déplacer cette règle en JavaScript et ne pas créer de nouveau moteur de planning ; rebrancher explicitement la validation dans `CheckoutController`. Le correctif `3b508d0` applique cette décision et la recette est validée sous `recette-j5v-a-checkout-lead-time-fix-20260628`. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Décision 28/06/2026 — Ne pas contourner J5V-A côté front

Après régression J5V-A, la décision est de garder le serveur comme source de vérité. Le bouton checkout et les messages client peuvent aider l’utilisateur, mais le blocage réel du délai minimum produit doit rester dans Symfony. Le correctif `3b508d0` rebranche donc `DeliveryPointCartService::validateMinimumOrderLeadTime()` dans `CheckoutController`, sans dupliquer la règle en JavaScript et sans créer de nouvelle entité de planning.

Cette décision protège le MVP contre trois risques : validation contournable côté navigateur, incohérence entre point de remise et panier multi-produits, et confusion avec les futures `DeliveryArea`. La règle est ensuite validée en production sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Décision 29/06/2026 — Promouvoir le checkout stabilisé avant J5W

Le bloc checkout J5S/J5T/J5U/J5V est promu en production sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` avant de lancer J5W / `DeliveryArea`.

Justification : le MVP doit d’abord stabiliser l’acte de commande. Ajouter les sous-zones opérationnelles avant de figer standard / point de remise / e-mail existant / délai minimum produit créerait de la dette métier et compliquerait les tests.

Décision anti-régression : J5W doit se brancher sur les choix déjà validés sans mélanger les responsabilités. Depuis J5W-A, la formulation cible est : `DeliveryPricingZone` pour le forfait local, `DeliveryCommuneConnection` pour les liaisons/barge, `DeliveryCommune.territory` pour les garde-fous PT/GT, et `DeliveryArea` pour le planning, l’exploitation et l’affectation livreur.


## Décision 29/06/2026 — J5W-A zones tarifaires locales sans remplacer PT/GT

Décision actée : J5W-A découpe les zones tarifaires locales par secteur, mais ne remplace pas les territoires techniques `PT` / `GT`.

Raisons :

1. `DeliveryZone` et `DeliveryCommune.territory` portent encore des compatibilités historiques et des garde-fous barge.
2. La barge reste déterminée par les liaisons logistiques `DeliveryCommuneConnection` de type `BARGE`, avec garde-fou territoire PT/GT.
3. Le forfait de base doit rester porté par `DeliveryCommune.localPricingZone`.
4. La création d’un doublon `PETITE_TERRE_LOCAL` créerait une confusion avec `PT_LOCAL`.

Découpage retenu :

| Secteur | Code tarifaire |
|---|---|
| Mamoudzou | `MAMOUDZOU_LOCAL` |
| Nord | `NORD_LOCAL` |
| Centre | `CENTRE_LOCAL` |
| Sud | `SUD_LOCAL` |
| Petite-Terre | `PT_LOCAL` existant |

Décision anti-dette : `GT_LOCAL` et `PT_LOCAL` restent en base. `PT_LOCAL` reste actif et utilisé. `GT_LOCAL` devient un fallback/historique pendant que les communes Grande-Terre pointent vers les zones plus fines.



### Validation recette et production J5W-A

La décision J5W-A est validée en recette le 29/06/2026 sous `recette-j5w-a-local-pricing-zones-20260629`, puis validée en production sous `prod-j5w-a-local-pricing-zones-20260629` sur le commit `cea4d19`. Les contrôles confirment que `PETITE_TERRE_LOCAL` est absent, que Petite-Terre reste sur `PT_LOCAL`, et que `DeliveryCommune.territory` conserve `PT` / `GT`.

Décision maintenue après production : `DeliveryPricingZone` porte le forfait local, mais la barge reste portée par `DeliveryCommuneConnection` et par les territoires techniques PT/GT.

## Décision 29/06/2026 — J5X-A ajustement tarifaire par secteur sans changer la formule logistique

Décision actée pour implémentation : J5X-A met à jour les frais de livraison client par zone tarifaire locale, sans changer la formule logistique validée par J5W-A.

Nouveaux frais client :

```text
Petite-Terre / PT_LOCAL : 12 €
Mamoudzou / MAMOUDZOU_LOCAL : 12 €
Centre / CENTRE_LOCAL : 17 €
Sud / SUD_LOCAL : 21 €
Nord / NORD_LOCAL : 21 €
Grande-Terre fallback / GT_LOCAL : 21 €
```

Justification métier : le prix doit être compréhensible pour le client dès l’ouverture du portail, tout en restant compatible avec les réalités terrain mahoraises : proximité Petite-Terre et Mamoudzou, coût plus élevé sur Nord/Sud, et Centre intermédiaire.

Décision technique : le changement est une migration de données `DeliveryPricingZone.customerDeliveryFee`. `courierPayout` n’est pas modifié faute de décision explicite sur la rémunération livreur.

La formule conservée reste : forfait local du secteur client + liaisons LAND/BARGE + supplément multi-vendeurs plafonné + plafond global client éventuel.

`GT_LOCAL` reste un fallback technique et ne doit pas être présenté comme un secteur commercial principal si toutes les communes Grande-Terre sont correctement rattachées à `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL` ou `SUD_LOCAL`.

Règle anti-régression : aucun tarif 12/17/21 ne doit être codé en dur dans Twig, JavaScript, contrôleur ou `DeliveryLogisticsService`. Les montants doivent rester administrables via `DeliveryPricingZone` / EasyAdmin.

## J5X-B — Calendrier standard porté par DeliveryPricingZone

Décision actée : les jours de livraison standard sont configurés sur `DeliveryPricingZone`.

Justification :

```text
Le client choisit une commune.
La commune pointe vers une zone tarifaire locale.
Cette zone porte le forfait et désormais le calendrier de passage standard.
Le produit standard suit le secteur client.
```

La promesse affichée est volontairement prudente : `prochain passage possible`, jamais `livraison garantie`. Pendant le pilote, Hodina confirme encore la date finale après vérification des vendeurs.

`GT_LOCAL` reste un fallback technique avec planning public inactif. `PETITE_TERRE_LOCAL` reste interdit.

## Décision 29/06/2026 — J5X-C promesse produit séparée du calcul logistique

Décision : la promesse affichée sur la fiche produit est portée par `Product` et construite par `ProductDeliveryPromiseService`, mais elle ne calcule jamais les frais et ne remplace pas le calendrier secteur J5X-B.

Choix actés :

- produit standard : `SECTOR_SCHEDULE`, suit les passages du secteur client ;
- produit sur créneau : `APPOINTMENT`, adapté aux broches de jasmin, colliers de fleurs, accueils aéroport, cérémonies et événements ;
- commune connue : afficher seulement la promesse pertinente ;
- commune inconnue : afficher un résumé et un tableau repliable ;
- `minimumOrderLeadTimeHours` J5V-A reste la contrainte de délai produit, surtout pour les flux à date/heure choisie ;
- aucune promesse ne doit dire “livraison garantie”. La confirmation finale reste assurée par Hodina pendant le pilote.

Règle anti-régression : ne pas dupliquer J5V-A avec un second délai produit concurrent. Le cutoff produit sur créneau sert à l’affichage de promesse ; la validation serveur reste à consolider dans les lots checkout si nécessaire.

## 2026-06-29 — J5X-C-bis — Séparation UX entre promesse produit et points de remise

Décision : dans le backoffice Produit, la promesse J5X-C est uniquement un message affiché sur la fiche produit. Elle ne crée pas de point de remise, ne calcule pas les frais, ne garantit pas un créneau et ne remplace pas le calendrier secteur J5X-B.

Les champs de plage des produits sur créneau sont renommés en plages indicatives. Les blocs de création/association de points de remise sont rangés dans une section avancée pour rappeler qu’ils relèvent de la logistique point de remise.

Raison : éviter que l’admin confonde “livraison sur créneau” pour broche/collier avec les `DeliveryPointTimeWindow` d’un vrai point de remise.

## J5X-D — Catalogue filtrable et merchandising simple

Décision : le catalogue public Hodina devient recherchable, filtrable et triable, avec un pilotage simple depuis EasyAdmin.

Choix actés :

- `Category.displayOrder`, `Category.isFeatured` et `Category.publicDescription` pilotent l’ordre et l’affichage des catégories.
- `Product.isFeatured` et `Product.displayPriority` pilotent l’ordre d’affichage des produits.
- Le tri par prix est fait après calcul du prix client par `ProductPricingService`, afin de ne pas trier sur l’ancien champ `Product.price`.
- Le rendu initial reste SSR Twig avec paramètres GET ; l’AJAX est une amélioration progressive, pas une dépendance.
- Le filtre commune, la disponibilité produit par commune et le tri livraison la plus proche sont repoussés pour éviter de mélanger catalogue et logistique.

# Décisions 01/07/2026 — J5X/J5Y avant recette groupée

## J5X — Ne pas mélanger catalogue, promesse et logistique

J5X a été découpé volontairement :

```text
J5X-A : tarifs par zone tarifaire, sans changer la formule logistique.
J5X-B : calendrier de passage par secteur, sans changer les frais.
J5X-C : promesse produit / produit sur créneau, sans modifier le checkout.
J5X-D : recherche, filtres, tri et priorité catalogue, sans calculer la livraison.
```

Décision maintenue : le catalogue ne calcule ni frais de livraison, ni disponibilité commune. La fiche produit peut rassurer, mais le panier reste la source de vérité opérationnelle avant validation de commande.

## J5Y-A — Interface guidée sans nouvelle complexité métier

Les raccourcis `Jours ouvrés` et `Jours ouvrables` servent uniquement à simplifier la création de plages depuis EasyAdmin. Ils ne deviennent pas des nouveaux jours persistés. Le backend doit continuer à raisonner avec les jours existants `1..7` ou une plage générique selon le modèle déjà codé.

## J5Y-B — Le client choisit un créneau, pas une heure libre

Décision UX et métier : en point de remise, le client ne saisit plus librement une heure. Il choisit un créneau de 30 minutes généré depuis les plages actives du point.

Justification : réduire les erreurs, éviter les heures impossibles et aligner l’interface avec l’exploitation réelle. Le serveur reste responsable de la validation.

## J5Y-C — Le catalogue devient l’accueil public

Décision : `/` devient le catalogue, `/catalogue` redirige vers `/`, et la page Découvrir Hodina devient la page institutionnelle publique `/decouvrir-hodina`. L’ancienne URL `/blog/decouvrir-hodina` reste une redirection permanente.

Justification : pour l’ouverture, le visiteur doit accéder directement aux produits. La page Découvrir Hodina reste importante, mais elle sert à expliquer le projet, recruter vendeurs/livreurs et préparer un futur contenu éditorial sans se présenter comme un blog. Le Carnet Hodina est réservé à une rubrique future, non activée dans le MVP.

Décision navigation : le header public ne doit pas afficher un lien `Catalogue` redondant quand le logo et la homepage mènent déjà au catalogue. Le lien principal restant est `Découvrir Hodina`.

## J5Y-D — Identité visuelle : lisibilité avant fidélité excessive

Le header utilise une version horizontale du logo afin que le mot Hodina soit lisible à côté des liens.

Pour le favicon, la décision finale n’est pas totalement stabilisée : le carré blanc a été rejeté visuellement ; les variantes transparentes ou recadrées doivent être validées en navigateur réel. À 16 px, il faut privilégier un symbole lisible plutôt qu’un logo complet illisible.

Règle avant recette : ne taguer J5Y que lorsque le favicon final est choisi ou explicitement sorti du périmètre de validation.

## J5Y-F — Le Carnet Hodina devient une rubrique pédagogique minimale

Décision : créer `/carnet` dès maintenant comme espace pédagogique public, sans rouvrir un blog généraliste. La première page active est `/carnet/livraison`.

Justification : la livraison est un sujet de confiance à Mayotte. Expliquer les communes concernées, les jours indicatifs, les points de remise et la source de vérité du panier réduit les incompréhensions avant commande.

Limite MVP : les contenus `Fruits, légumes et saisons` et `Nos vendeurs et producteurs partenaires` sont visibles comme intentions éditoriales, mais restent sans lien actif tant qu’ils ne sont pas produits. La page livraison reste indicative : elle ne doit jamais promettre une livraison garantie ou exposer l’outil privé Djama.

# Décisions J5Y-E/F/G/H — Navigation publique, Carnet et livraison

Date de validation recette : **01/07/2026**.

Date de validation production : **01/07/2026**.

Tag recette validé :

```text
recette-j5y-carnet-livraison-footer-clean-20260701
```

Commit déployé en recette :

```text
b1bbab6 chore(j5y): remove delivery guide backup template
```

## Décisions UX / marketing

- `Découvrir Hodina` n’est pas un article de blog : la route canonique est `/decouvrir-hodina`.
- `/blog` et `/blog/decouvrir-hodina` restent seulement des redirections legacy.
- `Le Carnet Hodina` devient un espace pédagogique public, mais pas un blog généraliste.
- Au MVP, seule la page `/carnet/livraison` est active.
- Les entrées `Fruits, légumes et saisons` et `Nos vendeurs et producteurs partenaires` restent visibles comme contenus à venir, sans lien actif.
- Le header public privilégie `Infos livraison`, car la livraison est un frein majeur à lever avant commande.
- `Découvrir Hodina` sort du header et reste accessible dans le footer.
- Le footer devient une zone compacte de réassurance et d’orientation, pas une simple zone administrative.

## Décisions métier

- La page livraison donne des repères indicatifs, mais ne promet pas une livraison garantie.
- Les frais, dates et créneaux exacts restent confirmés au panier.
- Les jours de livraison par secteur sont expliqués de manière pédagogique, avec des images WebP légères.
- Djama reste un portail livreur privé et ne doit pas être exposé dans la communication publique.
- Le paiement reste manuel pendant le pilote ; aucune promesse de paiement en ligne n’est introduite.

## Décisions techniques

- `HomeController` porte les routes publiques institutionnelles et pédagogiques : `/decouvrir-hodina`, `/carnet`, `/carnet/livraison`, et redirections legacy `/blog*`.
- Les pages publiques hors catalogue sont rangées sous `templates/pages`.
- Le template historique `templates/blog/decouvrir_hodina.html.twig` est supprimé/remplacé par `templates/pages/decouvrir_hodina.html.twig`.
- Les images de zones livraison sont versionnées sous `public/images/carnet/livraison/*.webp`, avec un poids inférieur à 100 Ko chacune.
- Ne jamais versionner de backup de template (`*.bk`, `*.bak`, `.old`) : le tag `recette-j5y-carnet-livraison-footer-20260701` est supersédé par le tag `clean` parce qu’un fichier `.bk` avait été embarqué.

## Anti-régression

- `/` reste le catalogue.
- `/catalogue` redirige vers `/`.
- `/decouvrir-hodina` reste la page institutionnelle.
- `/carnet/livraison` ne doit pas dupliquer la logique du panier.
- Les pages publiques ne doivent pas exposer `Djama`, `liaison logistique`, `marché digital`, `Blog`, ni une livraison garantie.
- Le header ne doit pas redevenir chargé : `Infos livraison` est le lien public prioritaire.


## Statut production J5Y

La production J5Y est validée le 01/07/2026. Cette validation ne change pas les décisions métier : le panier reste la source de vérité, Djama reste privé, le Carnet reste pédagogique et limité, et la livraison publique reste indicative.

Décision de clôture : ne plus modifier J5Y sauf bug bloquant. Les nouvelles idées liées au Carnet, au catalogue ou à la livraison publique doivent être cadrées dans un nouveau lot.

# Décisions 02/07/2026 — J5Z checkout/admin UX et socle post-MVP

## Ne pas réécrire les fonctionnalités validées production

Le MVP Hodina est fonctionnel. À partir de J5Z, la règle d’architecture devient :

```text
On ne réécrit pas une fonctionnalité validée.
On l’encadre, on la documente, on la teste, puis on ajoute autour.
```

Motif : éviter de casser le panier, le checkout, la commune de livraison, la barge, Djama ou les emails/SMS en voulant préparer trop tôt une application future.

## J5Z — Indicatif téléphone explicite

Décision : ne pas deviner le pays depuis le numéro saisi. Les numéros fixes, mobiles, Mayotte, métropole, Comores ou Madagascar rendent cette déduction fragile.

Règle validée :

```text
Le client choisit un indicatif explicite.
Hodina assemble indicatif + numéro local.
Le rattrapage legacy reste une commande séparée, contrôlée par simulation.
```

Choix d’indicatifs initiaux :

```text
Mayotte / La Réunion (+262)
France métropolitaine (+33)
Comores (+269)
Madagascar (+261)
```

Mayotte / La Réunion reste proposé en premier, car Hodina cible d’abord le terrain mahorais.

## J5Z — Annotation des frais livraison

Décision : l’annotation doit être concrète et client, pas logistique interne.

Libellés retenus :

```text
Inclus : barge.
Inclus : 1 commune traversée + barge.
Inclus : X communes traversées + barge.
```

Le terme `liaison terrestre` est rejeté : trop technique. `Commune traversée` est retenu parce qu’un client lambda comprend mieux pourquoi les frais augmentent.

Décision importante : si le trajet est simple et que les frais sont standards, aucune annotation n’est affichée. L’annotation sert à justifier un supplément, pas à décorer tous les paniers.

## J5Z — Source de vérité des frais

Décision : `DeliveryFeeReasonFormatter` formate l’explication, mais ne calcule pas les frais. Les frais restent calculés par les services logistiques existants.

Règle anti-régression :

```text
DeliveryFeeReasonFormatter explique un résultat.
DeliveryLogisticsService calcule le résultat.
Le panier reste la source de vérité avant validation.
```

Le cache de preview logistique est versionné pour éviter de réutiliser une session ancienne qui ne contient pas les nouvelles données d’annotation.

## J5Z — Flash frais recalculés

Message validé :

```text
Frais de livraison mis à jour
Selon ta nouvelle adresse, Hodina a recalculé les frais. Le détail apparaît sous “Frais de livraison”.
```

Décisions UX :

- affichage en haut du panier, pas caché dans le total ;
- fond opaque marron clair pour la lisibilité mobile ;
- croix de fermeture ;
- pas de persistance base pour le masquage ;
- affichage uniquement quand l’action client provoque un recalcul significatif des frais.

## J5Z — Formulaire produit EasyAdmin

Décision : les champs opérationnels de création produit doivent suivre immédiatement la marge Hodina. Cela réduit la charge mentale admin et accélère la saisie produit.

Ordre validé :

```text
Marge produit Hodina (%)
Stock illimité
Stock
Unité de vente
Description
Précommande
Jours fabrication
Mode de remise au client
Jours livraison
Délai minimum avant remise/livraison (h)
```

## J5AA — Choix `AddressLocality`

Décision : ne pas créer `DeliveryVillage`.

Motif : le concept n’est pas seulement un village de livraison à Mayotte. Il peut concerner une adresse de livraison, une adresse de facturation, une adresse de retrait vendeur ou une adresse hors Mayotte.

Choix retenu :

```text
Entité future : AddressLocality
Libellé UI principal : Localité
Aide UI : Village / quartier / lieu-dit
```

## J5AA — Auto-sélection de commune depuis localité

Décision prévue : si l’utilisateur sélectionne une localité connue et validée par Hodina, la commune associée peut être préremplie.

Règle de sécurité :

```text
Localité sélectionnée dans la liste Hodina → commune auto-remplie.
Texte libre non reconnu → texte conservé, commune à choisir manuellement.
```

Une saisie libre ne doit jamais déduire automatiquement la commune. Cela évite les erreurs tarifaires et les promesses de livraison incohérentes.

## J5AA — Commune reste source logistique

Règle anti-régression :

```text
AddressLocality précise l’adresse.
DeliveryCommune calcule les frais, la barge, les jours et les créneaux.
CustomerOrder snapshotte l’information utile au moment de la validation.
```

Aucune évolution future ne doit calculer les frais directement depuis une localité sans décision métier explicite et documentation dédiée.

# Décisions 03/07/2026 — J5AB / J5AC

## J5AB — Le catalogue est une page d’achat

Décision : la page catalogue publique n’est pas une page institutionnelle. Après le header Hodina, le client doit voir immédiatement la recherche, les filtres compacts et les produits.

Conséquences actées :

- suppression du bloc haut `Marketplace locale de Mayotte`, `Produits locaux de Mayotte`, texte explicatif long et CTA `Découvrir Hodina` ;
- maintien du contenu pédagogique sur `/decouvrir-hodina`, dans le footer et dans le Carnet ;
- recherche + loupe + bouton `Filtres` sur une ligne mobile ;
- pas de pagination ajoutée ;
- pas de modification du moteur catalogue ni de la logique livraison.

Justification : une marketplace mobile doit d’abord aider à acheter. L’explication du projet reste accessible, mais ne doit pas repousser les produits.

## J5AC — L’espace client devient une brique de confiance

Décision : `/mon-compte` devient un vrai hub compte et ne redirige plus directement vers les commandes.

Le client connecté peut :

- voir commandes en cours et historique ;
- ouvrir le détail de ses commandes ;
- modifier ses informations personnelles ;
- changer son mot de passe avec l’ancien mot de passe ;
- demander un lien de réinitialisation depuis son compte.

Le suivi commande J5R n’est pas refait. Il est conservé et encadré.

## J5AC — Email client unique nullable

Décision DB : `customer.email` devient unique nullable via `UNIQ_CUSTOMER_EMAIL`.

Justification :

- l’email sert d’identifiant de connexion ;
- un contrôle applicatif seul ne suffit pas ;
- la contrainte DB protège contre les doublons concurrents ;
- l’email reste nullable pour ne pas casser les comptes vendeurs ou comptes incomplets existants.

Décisions associées :

- ne pas rendre `customer.email` `NOT NULL` dans J5AC ;
- ne pas ajouter de contrainte unique sur `customer.phone` ;
- normaliser les emails existants en `LOWER(TRIM(email))` ;
- convertir les emails vides en `NULL` ;
- bloquer la migration si des doublons normalisés existent ;
- déclarer la migration non transactionnelle (`isTransactional(): false`) pour MariaDB/MySQL.

## J5AC — Reset connecté par SmsLog, sans nouveau canal

Décision : la demande de lien de réinitialisation depuis l’espace client utilise le même esprit que le pilote existant : génération de token et création d’un `SmsLog`.

Le lot n’ajoute pas d’envoi automatique externe. Il respecte le fonctionnement opérationnel existant et évite de créer un second tunnel de reset.

## J5AC-B — AJAX progressif HTML, pas contrat JSON

Décision : le portail client est optimisé en AJAX progressif en remplaçant le fragment HTML du portail, pas en créant une API JSON spécifique.

Justification :

- les routes Symfony/Twig existantes restent la source ;
- le fallback sans JavaScript est naturel ;
- la sécurité CSRF et les validations formulaire restent identiques ;
- moins de dette qu’un second contrat de rendu.

J5AC-B-bis supprime la barre de chargement globale et conserve seulement un feedback discret sur le déclencheur.

## Correction de donnée production

Après déploiement J5AC, l’audit production a révélé un email invalide :

```text
customer.id = 13
avant : chahere.kdu
après : chahere.kdu@outlook.fr
```

Décision : correction manuelle ciblée en production, car il s’agissait d’une donnée invalide isolée et non d’un problème de code. Après correction, l’audit J5AC-DB ne détecte plus aucun email invalide simple.

# Décisions 04/07/2026 — alignement docs / code avant J5AA

## Portail client : J5AC remplace l’ancien bloc “Portail client MVP”

Décision : ne plus présenter le portail client MVP comme prochaine priorité. Le code actuel montre que `/mon-compte` est un hub compte client, et non une simple redirection vers les commandes.

État acté :

```text
/mon-compte                         fait : hub compte client
/mon-compte/commandes               fait : liste commandes
/mon-compte/commandes/{id}          fait : détail propriétaire
/mon-compte/commandes/{id}/annuler  fait : annulation encadrée
/mon-compte/profil                  fait : profil modifiable
/mon-compte/mot-de-passe            fait : changement mot de passe
/mon-compte/mot-de-passe/lien-reinitialisation  fait : reset connecté via SmsLog
/mon-compte/adresses                non fait : page autonome à cadrer plus tard
```

Règle : ne pas confondre le carnet d’adresses technique `Address` déjà utilisé par le panier/checkout avec une future page `/mon-compte/adresses`.

## J5AA : code postal / commune / localité

Décision complémentaire : J5AA ne doit pas traiter seulement la localité. Il doit aussi tenir compte de la cohérence `code postal + DeliveryCommune`, car le code postal et la commune structurent l’adresse et peuvent réduire les erreurs terrain.

Règles retenues :

```text
Code postal = aide de sélection et contrôle de cohérence.
Localité = précision terrain.
DeliveryCommune = source logistique et tarifaire.
```

Le code postal ne calcule jamais les frais. La localité ne calcule jamais les frais. Les frais, la barge, les jours et les créneaux restent calculés depuis `DeliveryCommune` et les services logistiques existants.

## Pas de nouvelle entité PostalCode par défaut

Le code actuel possède déjà `DeliveryCommune.postalCode`. J5AA devra vérifier si ce champ suffit pour sélectionner / filtrer les codes postaux connus. Une entité dédiée `PostalCode` ne doit être créée que si le modèle existant ne permet pas de gérer proprement les cas multi-communes, les codes multiples ou la maintenance back-office.

## Saisie libre interdite pour la commune livrée, mais localité libre autorisée

Pour les adresses de livraison, la commune doit venir d’une `DeliveryCommune` active/seedée. En revanche, la localité peut rester libre si le client ne trouve pas son village/quartier/lieu-dit dans les suggestions Hodina.

Règle de sécurité :

```text
Localité connue sélectionnée → commune préremplie possible.
Localité libre non reconnue → texte conservé, commune choisie manuellement.
Texte libre seul → jamais de déduction automatique de commune.
```
# Décisions J5AA-0 — Audit strict des adresses de livraison

Date : 04/07/2026

## Address.commune reste le champ métier central de l'adresse

Décision : `Address.commune` n'est pas considéré comme un champ à supprimer dans J5AA. Il reste le champ métier central qui porte la commune d'une adresse Hodina.

Pour une adresse de type `DELIVERY`, ce champ doit contenir le nom canonique exact d'une `DeliveryCommune` active et utilisable comme point logistique. Il ne doit pas contenir une valeur composite ou ambiguë comme `Dzaoudzi-Labattoir` si le référentiel contient séparément `Dzaoudzi` et `Labattoir`.

J5AA-0 ne crée pas `Address.deliveryCommune`, ne renomme pas `Address.commune`, ne le supprime pas et ne modifie pas le calcul des frais.

## DeliveryCommune reste le référentiel de validation logistique

`DeliveryCommune` reste le référentiel qui permet de valider :

- la commune livrable ;
- le code postal associé ;
- le territoire `PT` / `GT` ;
- les règles de frais, barge, jours et créneaux via les services existants.

Règle : le code postal contrôle la cohérence, mais ne calcule jamais les frais. La future localité précisera le terrain, mais ne calculera jamais les frais.

## Livraison stricte, facturation souple

Une adresse `DELIVERY` doit être livrable par Hodina. Le couple `Address.postalCode + Address.commune` doit donc correspondre à une `DeliveryCommune` active.

Une adresse `BILLING` peut être hors zone Hodina. Si elle utilise la zone `AUTRE`, sa commune et son code postal restent des informations administratives et ne doivent pas être forcés à correspondre au référentiel `DeliveryCommune`.

Cette séparation protège les cas de facturation en métropole, en France hors Mayotte, en Italie ou ailleurs, sans affaiblir la rigueur des adresses de livraison.

## J5AA-0 est un garde-fou, pas une évolution fonctionnelle

Le sous-lot J5AA-0 ajoute uniquement un audit dans `tools/` et la documentation associée. Il ne doit pas modifier :

- les entités Doctrine ;
- les migrations ;
- le panier ;
- le checkout ;
- l'inscription ;
- Djama ;
- les emails/SMS ;
- le calcul logistique.

L'audit est volontairement plus strict que le matching runtime : une adresse `DELIVERY` doit stocker une commune canonique exacte, pas une valeur seulement résoluble par logique fuzzy.

# Décisions J5AA-B — Code postal + commune au checkout livraison

## Address.commune reste le champ métier central

J5AA-B ne crée pas `Address.deliveryCommune` et ne prépare pas la suppression de `Address.commune`.

Pour les adresses `DELIVERY`, `Address.commune` reste le champ métier porté par l'adresse. Il doit contenir le nom canonique exact d'une `DeliveryCommune` active/logistique. Le référentiel `DeliveryCommune` sert à valider et à alimenter ce champ, mais la persistance de l'adresse reste compatible avec le socle existant.

## Code postal guidant, commune décisive

Le checkout de livraison demande désormais un code postal sélectionné parmi les codes postaux connus via les `DeliveryCommune` actives. Ce code postal filtre les communes compatibles et aide l'utilisateur à choisir la bonne commune.

Le code postal ne calcule jamais les frais. La commune choisie, validée via `DeliveryCommune`, reste la base du calcul logistique existant : frais, barge, jours, créneaux et preview.

## Contrôle serveur obligatoire

J5AA-B ajoute un contrôle serveur du couple `postalCode + commune` dans le checkout et dans l'aperçu AJAX des frais. La résolution utilisée pour ce parcours est canonique et stricte : elle ne doit pas accepter une commune composite ou fuzzy. Une requête manipulée avec un code postal hors référentiel ou incompatible avec la commune doit être refusée clairement.

Le serveur continue d'écrire dans l'adresse finale :

- `Address.postalCode = DeliveryCommune.postalCode` ;
- `Address.commune = DeliveryCommune.name` ;
- `Address.deliveryZone = zone cohérente avec DeliveryCommune.territory`.

## Facturation hors périmètre strict J5AA-B

J5AA-B ne durcit pas les adresses `BILLING`. Les adresses de facturation peuvent rester hors référentiel Hodina lorsqu'elles sont en zone `AUTRE` ou équivalent. Cette règle protège les cas métropole, France hors Mayotte, Italie ou autre adresse administrative.

# Décisions J5AA-A — AddressLocality

## Address.commune reste le champ métier central

J5AA-A ajoute `AddressLocality` pour préciser l’adresse terrain, mais ne crée pas `Address.deliveryCommune` et ne prépare pas la suppression de `Address.commune`.

Pour une adresse de livraison, `Address.commune` reste la commune canonique utilisée par Hodina. Elle doit correspondre à une `DeliveryCommune` active et logistique. `AddressLocality` ne remplace jamais cette commune.

## Localité = précision terrain, pas source logistique

`AddressLocality` représente une localité d’adresse : village, quartier ou lieu-dit. Elle aide le client, l’admin et le livreur à mieux situer l’adresse.

La localité ne calcule jamais :

- les frais de livraison ;
- la barge ;
- les jours de passage ;
- les créneaux ;
- les liaisons inter-communes.

Ces règles restent portées par `Address.commune` contrôlé par `DeliveryCommune`.

## Localité connue et localité libre

Une adresse peut porter :

- une relation nullable `Address.addressLocality` vers une localité connue ;
- un texte nullable `Address.localityText` pour conserver une localité libre ou une précision non encore référencée.

Une localité inactive n’est plus proposée aux nouveaux clients, mais reste lisible sur les anciennes adresses et commandes.

## Snapshot commande

La commande snapshotte le nom de localité de livraison dans `CustomerOrder.deliveryAddressLocalityName`. Le snapshot permet de relire l’adresse de livraison historique même si une localité est renommée ou désactivée plus tard.

## Seed initial

J5AA-A ajoute une commande idempotente `hodina:address-localities:seed` pour initialiser les localités/villages de Mayotte connues au démarrage (72 entrées), rattachées à une commune livrable Hodina quand le référentiel `DeliveryCommune` le permet.


## J5AA-A bis — Seed complet localités Mayotte et suggestions visibles

Le seed `hodina:address-localities:seed` ne doit pas se limiter à Mamoudzou. Il initialise les villages/localités de Mayotte connus au démarrage afin de réduire les saisies libres et les erreurs terrain.

Le champ `Localité` reste facultatif. Il propose désormais des suggestions visibles sous le champ, plus fiables qu’un simple `datalist` navigateur sur mobile. Quand le client sélectionne une localité connue, Hodina préremplit le code postal et la commune associée, puis conserve la validation serveur stricte `postalCode + Address.commune`.

# Décisions J5AF — Suppression pilote et anonymisation

## Deux actions distinctes pour deux usages

« Supprimer pilote » reste une suppression physique en cascade, réservée au nettoyage des comptes de test de la phase pilote. « Anonymiser » est la voie recommandée pour un vrai client demandant la suppression de ses données : scrub des données personnelles, blocage de connexion, conservation intégrale de l'historique métier (commandes, tickets support, conversations IA, paiements livreur).

Raison : un vrai client a un historique métier qu'il n'est pas souhaitable de perdre (comptabilité, traçabilité support), alors qu'un compte de test pilote n'a pas cette contrainte.

## Correction de la suppression pilote par le code, pas par le schéma

Pas de nouvelle contrainte `ON DELETE CASCADE` sur `chatbot_conversation.customer_id` : la suppression explicite des conversations IA en PHP (`CustomerPilotCascadeDeleter`) suit le même principe déjà appliqué aux commandes et adresses dans ce service, sans toucher à une contrainte déjà déployée en recette.

## Blocage de connexion via le mécanisme standard Symfony

Le blocage des comptes anonymisés (`Customer.isActive = false`) passe par `UserCheckerInterface` (`CustomerUserChecker`), invoqué automatiquement par le firewall — pas de logique ad hoc dans `AppAuthenticator`.

# Décision transverse — Piège `AdminContext::getEntity()`

## Ne jamais dépendre du contexte CRUD EasyAdmin dans une action custom

Toute action CRUD custom EasyAdmin (`linkToCrudAction`) doit charger son entité directement via `entityId` (query string) + `EntityManagerInterface`, jamais via `$context->getEntity()`. Raison : ce mécanisme peut lever `LogicException: Cannot get entity outside of a CRUD context` selon la version d'EasyAdminBundle réellement installée, y compris avec une URL correctement formée — confirmé sur 4 contrôleurs (`Customer`, `CustomerOrder`, `SupportTicket`, `CourierPayout`) à ce jour. Une recherche `grep -rn "AdminContext \$context" src/Controller/Admin/` doit être relancée après toute correction de ce type pour vérifier qu'aucune occurrence ne subsiste.

# Décisions J5AG — Gestion des logs SMS / e-mails

## Suppression réelle en base, pas d'archivage

`SmsLog` et `EmailLog` sont des journaux techniques (traçabilité des envois), sans obligation de conservation identifiée pour ce projet à ce stade. Le bouton « vider » supprime réellement les lignes (`DELETE FROM` DQL), avec confirmation et compteur affichés avant toute suppression totale — pas d'archivage ni de purge automatique programmée pour cette itération.

# Décision process — Checklist minimale avant toute checklist de lot

Avant de dérouler la checklist spécifique à un lot déployé (recette ou production), dérouler d'abord la checklist minimale : catalogue, inscription, connexion d'un client existant, panier/checkout/commande, backoffice, portail livreur. Ne pas continuer sur les tests du lot si un point de cette checklist échoue.

Raison : un lot qui touche `Customer`, la sécurité ou EasyAdmin peut casser la connexion de tous les clients, pas seulement le cas spécifique visé par le lot — un incident réel (piège `AdminContext`) a montré qu'un test scopé au seul lot peut manquer une régression transverse.

# Décision — Fusion des CLAUDE.md et fin de la session à deux dépôts

Les deux `CLAUDE.md` (`claude_hodina` et `hodina.fr`), écrits indépendamment et jamais synchronisés, ont été fusionnés en un seul fichier après identification d'une confusion réelle entre les deux (l'un axé process/pièges, l'autre axé architecture/domaine — aucun des deux n'était complet seul). Décision de mettre fin au travail combiné sur deux dépôts (sandbox `claude_hodina` + portage manuel vers `hodina`) : le développement se poursuit directement sur `D:\hodina\hodina.fr` avec `chahere/hodina`, jugé plus simple et moins sujet à erreur qu'un portage par patch à chaque lot.

Cette évolution ne change pas la source de calcul logistique : `Address.commune` reste central, validé par `DeliveryCommune`. La localité aide à préciser l’adresse, mais ne calcule jamais les frais, la barge, les jours ou les créneaux.
