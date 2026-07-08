# TODO Hodina — état opérationnel après J5AA (localité d’adresse livrée)

Dernière mise à jour : **05/07/2026**
But : donner à un développeur ou architecte débutant un état clair, exploitable et non contradictoire du projet Hodina.

---

## Règles de lecture

1. Les éléments validés sont au-dessus, avec cases cochées.
2. Les prochains chantiers sont ensuite classés par priorité.
3. Les anciens noms prévisionnels qui créent une collision sont corrigés.
4. Ne jamais recréer une fonctionnalité existante : analyser, corriger, étendre et tester.
5. Ne jamais faire `git add .` dans ce projet : les archives, patchs et `.bak` sont fréquents.

---

# 1. État validé / socle acquis

## Socle général

- [x] Symfony / Twig / EasyAdmin comme socle applicatif.
- [x] MariaDB compatible o2switch.
- [x] Backoffice admin sur `/ouegnewe`.
- [x] Portail livreur sur `/djama`.
- [x] Paiement manuel conservé pour le pilote.
- [x] Validation admin obligatoire.
- [x] Commandes avec numéro métier.
- [x] Catalogue public fonctionnel.
- [x] Panier fonctionnel.
- [x] Carnet d’adresses technique utilisé par panier/checkout (`Address`).
- [x] Adresses de livraison et facturation distinctes.
- [x] Snapshots de commande pour conserver l’historique.
- [x] E-mails réels disponibles en recette/serveur.
- [x] Logs SMS/e-mail disponibles en EasyAdmin.
- [x] J5U-A — Expéditeur e-mails paramétrable EasyAdmin : validé recette, expéditeur `commande@hodina.fr` confirmé.

## J5G-B4 — Logistique avancée

Statut : **validé recette / production comme socle**.

- [x] `DeliveryCommune` comme référentiel communes.
- [x] `DeliveryCommuneConnection` pour les connexions logistiques.
- [x] BFS pour trajets multi-communes.
- [x] Frais LAND / BARGE.
- [x] Suppléments multi-vendeurs.
- [x] Plafond frais client.
- [x] Snapshot logistique dans `CustomerOrder.deliveryLogisticsSnapshot`.
- [x] Affichage logistique côté admin.

## J5K — GPS livraison / adresses / facturation

Statut : **validé recette + production**.

- [x] GPS sur `Address`.
- [x] Snapshot GPS sur `CustomerOrder`.
- [x] Bouton position GPS côté panier.
- [x] Correction affichage GPS visible quand le client change d’adresse.
- [x] Adresse livraison par défaut.
- [x] Adresse facturation par défaut.
- [ ] Page autonome `/mon-compte/adresses` non codée : ne pas confondre avec le carnet `Address` utilisé par le panier/checkout.
- [x] Adresse facturation visible côté admin.
- [x] Commune livrée reste la source de vérité tarifaire.

## J5L — UX panier mobile

Statut : **validé recette**.

- [x] Panier mobile simplifié.
- [x] Sélecteur compact d’adresses.
- [x] Panneau adresse qui reste ouvert jusqu’à confirmation.
- [x] Libellés `Changer l’adresse`.
- [x] Total / frais mieux placés.
- [x] Facturation visible admin.

## J5M — Portail livreur Djama

Statut : **socle terrain opérationnel**.

- [x] Route `/djama`.
- [x] Statut `PICKED_UP`.
- [x] Statut `OUT_FOR_DELIVERY`.
- [x] Assignation du livreur connecté.
- [x] Carte commande compacte.
- [x] Cartes repliables/dépliables.
- [x] Blocs : à prendre, prises/en cours, livrées.
- [x] Commune client affichée.
- [x] Gain livreur affiché.
- [x] Total commande affiché.
- [x] Adresse client, GPS, instructions, note terrain.
- [x] Collecte vendeurs visible par vendeur.
- [x] Points de retrait vendeur.
- [x] Produits et quantités à collecter par vendeur.

## J5N — Consolidation Djama / collecte / timezone / AJAX

Statut : **validé dans le flux recette**.

- [x] `Seller.collectionValidationCode`.
- [x] Code ponctuel vendeur si aucun code permanent.
- [x] `CustomerOrder.sellerCollectionSnapshot`.
- [x] `SellerCollectionCodeService`.
- [x] E-mail vendeur `SELLER_COLLECTION_CODE`.
- [x] SMS vendeur `seller_collection_code`.
- [x] Interdiction d’envoyer à `contact@hodina.fr` comme destinataire de secours.
- [x] Plafond rémunération livreur global `global_delivery_courier_payout_cap`.
- [x] Plafond spécifique livreur `Customer.courierPayoutCap`.
- [x] Timezone commande `CustomerOrder.customerTimezone`.
- [x] Réglage `default_timezone = Indian/Mayotte`.
- [x] Actions Djama en AJAX avec fallback POST.
- [x] Conservation de la carte ouverte après action AJAX.
- [x] Hotfix GPS panier validé recette : `j5n-hotfix-cart-gps-reset-recette`.

## J5O-A — Code de réception client chiffré

Statut : **validé recette**.

Référence :

```text
Tag : j5o-code-reception-client-recette-v2
Commit : 9a7ac76
```

- [x] Code généré au démarrage livraison.
- [x] Code à 6 chiffres.
- [x] Code stocké chiffré, pas en clair.
- [x] Chiffrement AES-256-GCM via clé dérivée de `kernel.secret` / `APP_SECRET`.
- [x] Envoi SMS client `customer_delivery_code`.
- [x] Envoi e-mail client `CUSTOMER_DELIVERY_CODE`.
- [x] Renvoi du même code si le livreur valide sans code saisi.
- [x] Mauvais code refusé.
- [x] Bon code nécessaire pour passer `DELIVERED` côté Djama.
- [x] Code chiffré supprimé après livraison validée.
- [x] Logs visibles dans EasyAdmin.

## J5P-A — Notifications client sur statuts

Statut : **validé recette**.

Référence :

```text
Tag : j5p-notifications-statuts-client-recette
Commit : 8ec44f2
```

- [x] `CustomerOrderNotificationService`.
- [x] Template `templates/emails/order_status_update.html.twig`.
- [x] E-mail `ORDER_STATUS_CONFIRMED`.
- [x] E-mail `ORDER_STATUS_PREPARING`.
- [x] E-mail `ORDER_STATUS_READY_FOR_PICKUP`.
- [x] E-mail `ORDER_STATUS_PICKED_UP`.
- [x] E-mail `ORDER_SELLER_COLLECTIONS_COMPLETED`.
- [x] E-mail `ORDER_STATUS_DELIVERED`.
- [x] E-mail `ORDER_STATUS_CANCELED`.
- [x] Idempotence : ne pas renvoyer un event déjà `PENDING` ou `SENT`.
- [x] Pas d’e-mail générique `OUT_FOR_DELIVERY`, car le code J5O couvre déjà cette étape.
- [x] Bouton `Voir` EasyAdmin pour lire les logs e-mails.


## J5Q-A — Paiements livreurs / historique Djama / suivi admin

Statut : **validé recette**.

Référence :

```text
Tag : j5q-paiements-livreurs-recette
Commit : 12bb402
Migration : Version20260624140000
```

- [x] Entité `CourierPayout`.
- [x] Entité `CourierPayoutLine`.
- [x] Service `CourierPayoutService`.
- [x] Périodes de paiement : 1→15 et 16→fin de mois.
- [x] Paiement cible : 15 ou 30 / dernier jour réel du mois.
- [x] Source de rattachement : `CustomerOrder.deliveredAt`.
- [x] Seules les commandes `DELIVERED` sont rémunérables.
- [x] Une commande ne peut être rattachée qu’à une seule ligne de rémunération.
- [x] Paiements `DRAFT`, `VALIDATED`, `PAID`, `CANCELED`.
- [x] Actions admin : générer période en cours, générer période précédente, valider, marquer payé, annuler.
- [x] Menu EasyAdmin réorganisé : `Clients`, `Vendeurs`, `Livreurs`, `Logs`.
- [x] `Livreurs > Livreurs` avec `CourierCrudController` filtré sur `ROLE_COURIER`.
- [x] `Livreurs > Rémunérations livreurs`.
- [x] `Livreurs > Lignes rémunération`.
- [x] Portail Djama : section `Mes paiements` avec cartes repliées.
- [x] Historique payé visible côté livreur.
- [x] Test recette validé : paiement `PAID` de 30,00 € sur 2 commandes.

---

## J5Q-D0 — Stabilisation Djama avant Portail client MVP

Statut : **validé recette**.

Objectif : verrouiller le portail livreur existant sans créer de nouvelle entité ni de workflow parallèle.

- [x] Correction injection `EmailBrandingService` dans `SellerCollectionCodeService`.
- [x] Suppression du SMS générique `customer_order_out_for_delivery` au démarrage livraison.
- [x] Conservation du SMS/e-mail indispensable `customer_delivery_code`.
- [x] Ajout alertes terrain client dans Djama : téléphone, e-mail, GPS.
- [x] Ajout alertes terrain vendeur dans Djama : contact, code collecte, adresse retrait, GPS retrait, commune retrait/logistique.
- [x] Ajout accès rapide appel/e-mail vendeur depuis le bloc collecte.
- [x] Aucun ajout d’entité : pas de `Courier`, pas de `SellerCollection`, pas de `DeliveryCode`.
- [x] Lints PHP locaux sur les fichiers modifiés.
- [x] `lint:twig` rejoué dans l’environnement projet complet.
- [x] `doctrine:schema:validate` rejoué dans l’environnement projet complet.
- [x] Validation recette parcours complet multi-vendeurs.

Règle anti-régression : le passage `OUT_FOR_DELIVERY` ne doit plus envoyer deux SMS au client ; l’information départ livraison est portée par le code réception client.

---

# 2. Points de vigilance immédiats

## Doublon SMS départ livraison

Statut : **résolu par J5Q-D0 et validé recette**.

Au démarrage livraison, le client ne doit plus recevoir le SMS générique :

```text
customer_order_out_for_delivery
```

Le client reçoit uniquement le flux indispensable de code réception :

```text
customer_delivery_code
```

- [x] J5P-bis repris dans J5Q-D0 — supprimer le SMS générique `customer_order_out_for_delivery` lorsque `customer_delivery_code` est envoyé.
- [x] Vérifié en recette dans J5Q-D0 : le flux utile au démarrage livraison passe par `customer_delivery_code`, pas par un doublon SMS générique.

---

# 3. Portail client — état réel après J5AC

## Statut

Ce bloc remplace l’ancien plan `Prochaine priorité P1 — Portail client MVP`. Le portail client MVP n’est plus une prochaine priorité : il a été livré progressivement via J5R-A puis finalisé en J5AC.

État réel d’après le code actuel :

- [x] `/mon-compte` : hub compte client, pas une simple redirection MVP.
- [x] `/mon-compte/commandes` : liste commandes du client connecté.
- [x] `/mon-compte/commandes/{id}` : détail commande propriétaire uniquement.
- [x] `/mon-compte/commandes/{id}/annuler` : annulation client encadrée avant préparation.
- [x] `/mon-compte/profil` : informations personnelles modifiables : prénom, nom, email, indicatif, téléphone.
- [x] `/mon-compte/mot-de-passe` : modification du mot de passe avec ancien mot de passe.
- [x] `/mon-compte/mot-de-passe/lien-reinitialisation` : génération d’un lien reset connecté via `SmsLog`.
- [ ] `/mon-compte/adresses` : page autonome carnet d’adresses non codée.

## Contenu commandes réellement couvert

Le portail client affiche les commandes en cours et l’historique, le statut lisible, le message de prochaine étape, le détail propriétaire, les produits, quantités, prix, frais, total, adresse snapshotée, instructions et timeline simple. Les commandes `DRAFT` restent exclues.

## Décision

Les adresses restent gérées dans le panier/checkout tant que la page autonome `/mon-compte/adresses` n’est pas cadrée dans un lot séparé.

Ne pas confondre :

```text
Address = carnet d’adresses technique vivant, déjà utilisé par panier/checkout.
/mon-compte/adresses = future page client autonome, non codée.
J5AA = localité d’adresse et cohérence code postal / commune, pas une refonte du portail client.
```

## Mapping statut client validé

```text
PENDING_VALIDATION  → Commande reçue
CONFIRMED           → Commande validée
PREPARING           → En préparation
READY_FOR_PICKUP    → Prête pour le livreur
PICKED_UP           → Prise en charge
OUT_FOR_DELIVERY    → En cours de livraison
DELIVERED           → Livrée
CANCELED            → Annulée
```

## Hors périmètre restant côté compte client

- [ ] `/mon-compte/adresses` : carnet d’adresses autonome client.
- [ ] Paiement en ligne.
- [ ] Remboursement.
- [ ] Messagerie client/admin.
- [ ] Notation vendeur/livreur exploitée côté client, même si `CustomerOrderFeedback` prépare déjà le terrain.
- [ ] Programme fidélité.

---

# 4. Backlog tests bout en bout multi-commandes

- [ ] Commande avec GPS.
- [ ] Commande sans GPS.
- [ ] Client avec e-mail + téléphone.
- [ ] Client sans e-mail.
- [ ] Client sans téléphone.
- [ ] Vendeur avec code permanent.
- [ ] Vendeur sans code permanent.
- [ ] Vendeur sans e-mail.
- [ ] Vendeur sans téléphone.
- [ ] Plusieurs vendeurs sur une commande.
- [ ] Plusieurs commandes prises par le même livreur.
- [ ] Renvoi code client.
- [ ] Mauvais code client.
- [ ] Annulation commande avant préparation.
- [ ] Logs EasyAdmin visibles.

---

# 5. Backlog post-MVP sans numéro définitif

## Optimisation automatique images produits

Important avant ouverture large aux vendeurs, mais non bloquant immédiat.

- [ ] Upload image originale.
- [ ] Conversion WebP.
- [ ] Redimensionnement automatique.
- [ ] Miniature cible < 200 Ko.
- [ ] Génération miniature / fiche produit / large optionnel.
- [ ] Rotation EXIF mobile.
- [ ] Formats autorisés JPG/PNG/WebP.
- [ ] Protection fichier malveillant.
- [ ] Aperçu EasyAdmin.
- [ ] Nettoyage images orphelines plus tard.

## Suivi financier restant hors rémunération livreur

Important avant montée en volume, mais à ne pas confondre avec J5Q-A.

Déjà fait avec J5Q-A :

- [x] Historique rémunération livreur.
- [x] Paiement livreur validable et marquable payé.
- [x] Détail commande par commande pour le livreur.

Reste à faire plus tard :

- [ ] Total produits par période.
- [ ] Frais livraison client par période.
- [ ] Total client encaissé / à encaisser.
- [ ] Montant vendeur à reverser.
- [ ] Marge produit Hodina.
- [ ] Marge livraison Hodina.
- [ ] Filtre par date.
- [ ] Filtre par statut livré/annulé.
- [ ] Export CSV simple.

## Admin exploitation

- [ ] Tableau jour de livraison.
- [ ] Filtres par statut.
- [ ] Filtres par commune.
- [ ] Filtres par livreur.
- [ ] Vue problèmes terrain.
- [ ] Synthèse SMS/e-mails échoués.
- [ ] Synthèse commandes prêtes non prises.

---

# 6. Dette technique à surveiller

- [ ] Corriger les warnings Doctrine/EasyAdmin avant Symfony/EasyAdmin majeur.
- [ ] Ne pas versionner les `.bak`, `.zip`, `.patch`, fichiers temporaires.
- [ ] Nettoyer les artefacts locaux avant chaque archive.
- [ ] Garder `public/uploads/products` comme runtime, sauf `.gitkeep`.
- [ ] Ne pas faire `schema:update --force` comme solution de fond.
- [ ] Corriger les migrations plutôt que patcher seulement la base locale.
- [ ] Toujours créer un tag recette propre après commit final.

---

# 7. Checklist avant ouverture contrôlée

- [ ] Portail client MVP disponible.
- [ ] Parcours client complet testé mobile.
- [ ] Parcours admin complet testé mobile/desktop.
- [ ] Parcours livreur Djama testé mobile.
- [ ] Notifications e-mail/SMS validées avec destinataires contrôlés.
- [ ] Logs EasyAdmin lisibles.
- [ ] Procédure support client écrite.
- [ ] Procédure support livreur écrite.
- [ ] Procédure backup / rollback connue.
- [ ] Gel fonctionnel décidé.

---

# Mise à jour J5Q-C / J5Q-C-1 / J5Q-C-2 — état au 25/06/2026

## J5Q-C — Automatisation paiements livreurs

- [x] Patch J5Q-C v2 appliqué localement.
- [x] Lint PHP validé.
- [x] `lint:twig` du template récap admin validé.
- [x] `doctrine:schema:validate` validé.
- [x] Commande `hodina:courier-payouts:generate` disponible.
- [x] Dry-run période courante validé.
- [x] Mode `--auto-due` validé sur le 15 et le dernier jour du mois.
- [x] Déploiement recette par tag `j5q-c-cron-recap-admin-recette` validé.
- [x] Cron recette installé à `05:10 UTC` / `08:10 Mayotte`.
- [x] Cron confirmé no-op hors 15 et dernier jour du mois.
- [ ] Vérifier un récap admin réel sur SMTP recette quand une génération réelle éligible existe.

Règle anti-régression : la commande et le cron ne valident pas un paiement et ne marquent jamais `PAID` automatiquement.

## J5Q-C-1 — Structuration des réglages en groupes

- [x] Patch J5Q-C-1 appliqué localement.
- [x] Lints PHP validés.
- [x] Migration `Version20260624233000` jouée localement et recette.
- [x] Migration `Version20260624234500` jouée localement et recette pour les paramètres paiements livreurs.
- [x] `doctrine:schema:validate` validé.
- [x] EasyAdmin > Réglages > Tous les paramètres validé.
- [x] EasyAdmin > Réglages > Paiements validé.
- [x] Les quatre paramètres paiements livreurs sont présents en recette.
- [x] Le réglage `courier_payout_cron_enabled` bloque bien `--auto-due` quand désactivé.
- [x] Déploiement recette par tag `j5q-c1-settings-groups-recette` validé.
- [ ] Déploiement production non fait.

Règle anti-régression : les paramètres paiements sont des garde-fous d'exploitation, pas des déclencheurs de paiement réel.

## J5Q-C-2 — Branding e-mail

- [x] Patch J5Q-C-2 appliqué localement.
- [x] Lints PHP validés.
- [x] Migration `Version20260625090000` jouée localement et recette.
- [x] `doctrine:schema:validate` validé.
- [x] `lint:twig` validé sur les 6 templates d'e-mail.
- [x] EasyAdmin > Réglages > Branding e-mail visible.
- [x] Les quatre paramètres `email_branding_*` sont présents en recette.
- [x] Déploiement recette par tag `j5q-c2-branding-email-recette` validé.
- [ ] Configurer durablement `[Recette]` en préfixe objet e-mail sur recette.
- [ ] Déclencher et vérifier au moins un e-mail réel par famille si possible : commande, statut client, code réception, code collecte vendeur, récap admin paiements.
- [ ] Vérifier `EmailLog.subject` avec préfixe après envoi réel.
- [ ] Déploiement production non fait.

Règles anti-régression : les SMS ne sont pas modifiés, le sujet logué est le sujet réellement envoyé, et le préfixe ne doit pas être doublé.

## Debug recette — incident `ERR_CONNECTION_CLOSED`

- [x] Document `DEBUG_RECETTE_HODINA.md` ajouté.
- [x] PHP web vérifié : 8.4.21, `memory_limit=512M`, `max_execution_time=600`.
- [x] Access logs live identifiés : `~/access-logs/recette.hodina.fr-ssl_log`.
- [x] PHP web écrit dans `public/error_log`.
- [x] Configuration Monolog prod constatée : handler prod vers `php://stderr`, pas directement vers `var/log/prod.log`.
- [ ] Capturer le prochain incident avec heure exacte + navigateur + réseau + logs.
- [ ] Décider plus tard si un lot `J5Q-C-2-bis — Observabilité recette` est nécessaire.

## Prochaines priorités après documentation

- [ ] Finaliser les tests e-mails J5Q-C-2 en recette SMTP.
- [ ] Mettre à jour la documentation après réception de mails réels.
- [x] Arbitrage fait le 25/06/2026 : stabiliser Djama avec `J5Q-D0`, puis démarrer le Portail client MVP.
- [ ] Valider `J5Q-D0 — Stabilisation Djama` en recette : injection `EmailBrandingService`, suppression du SMS générique au départ livraison, badges d’alerte terrain.
- [x] Démarrer le Portail client MVP après validation recette J5Q-D0 : patch `J5R-A — Portail client commandes + annulation client encadrée` préparé.
- [ ] Reporter `J5Q-D — export / ajustements paiements livreurs` sauf urgence exploitation.
- [x] Renommer la suite paiements vendeurs hors `J5R-A` pour éviter collision : `J5R-A` est désormais Portail client commandes + annulation.


---

# J5R-A — Portail client commandes + annulation client encadrée

## Statut

- [x] Patch préparé.
- [x] Validation locale effectuée.
- [x] Validation recette effectuée.

## Inclus

- [x] Routes `/mon-compte`, `/mon-compte/commandes`, `/mon-compte/commandes/{id}`.
- [x] Liste commandes en cours / historique.
- [x] Détail commande propriétaire uniquement.
- [x] Statuts client lisibles et messages de prochaine étape.
- [x] Adresse de livraison snapshotée, consignes client, GPS et lien carte.
- [x] Information code de réception sans afficher le code.
- [x] Annulation client directe uniquement avant préparation.
- [x] Motif/commentaire d’annulation persistés dans `CustomerOrderFeedback`.
- [x] Entrée EasyAdmin `Retours clients` en lecture seule.

## Exclus du lot

- [ ] Notation vendeur/livreur : lot J5R-B.
- [ ] Modification profil/adresses hors panier : lot J5R-C.
- [ ] Paiement en ligne, remboursement, litige, messagerie, suivi GPS live.

## Tests à rejouer

- [ ] Client non connecté sur `/mon-compte/commandes` : redirection connexion.
- [ ] Client connecté sans commande : état vide clair.
- [ ] Client connecté avec commande en validation : carte visible.
- [ ] Détail commande propriétaire : visible.
- [ ] Détail commande d’un autre client : 404.
- [ ] Annulation `PENDING_VALIDATION` : commande `CANCELED`, SMS/e-mail existants, feedback créé.
- [ ] Annulation `CONFIRMED` : commande `CANCELED`, feedback créé.
- [ ] Annulation à partir de `PREPARING` : refus avec message.
- [ ] Djama : aucun changement de route ni workflow livreur.

---

# J5S-A — Socle DeliveryPoint / points de remise admin

Statut : **validé recette**.

Objectif : créer une brique logistique générique pour imposer certains produits à des points de remise précis, sans toucher encore au panier ni au checkout.

- [x] Modélisation cible retenue : vraie table `DeliveryPoint`, pas champ codé en dur.
- [x] `DeliveryPoint` : barge, aéroport, relais pickup, point vendeur, point événementiel.
- [x] `DeliveryPointTimeWindow` : plages horaires par point.
- [x] `ProductDeliveryPoint` : association produit ↔ points autorisés.
- [x] `Product.deliveryMode` : `STANDARD`, `DELIVERY_POINT_REQUIRED` ou `DELIVERY_POINT_OPTIONAL`.
- [x] CRUD EasyAdmin points de remise.
- [x] CRUD EasyAdmin plages horaires.
- [x] CRUD EasyAdmin association produit ↔ points.
- [x] Seed initial : accueil barge Petite-Terre et accueil passager aéroport Pamandzi.
- [x] Seed initial : plages matin 08h-12h et après-midi 14h-18h.
- [x] Formulaire produit pratique : associer plusieurs points existants depuis le produit.
- [x] Formulaire produit pratique : créer un point et ses plages depuis le produit.
- [x] Lints PHP locaux rejoués.
- [x] Migration locale/recette réalisée.
- [x] `doctrine:schema:validate` validé.
- [x] Validation navigateur EasyAdmin réalisée.
- [x] Validation recette réalisée.

Hors périmètre J5S-A : activation panier/checkout, snapshot commande, affichage Djama, affichage portail client, modification client du point/plage.

Note J5S-A-bis/quater : pour rendre l’admin utilisable, le formulaire produit permet désormais d’associer plusieurs points existants ou de créer rapidement un point de remise et ses plages. Un produit peut être livré uniquement en adresse classique, uniquement en point de remise, ou en adresse classique + point de remise. Cette amélioration reste strictement admin/socle et ne change pas encore le comportement client.


---

# J5S-B — Panier/checkout avec choix point de remise

Statut : **validé recette + production**, avec prolongements J5S-B-bis puis J5S-B-ter/quater. La recette `recette-j5s-b-ter-quater-checkout-point-standard-20260628`, puis la production `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`, ont confirmé la séparation livraison standard / point de remise, le masquage des points en mode standard et la validation conditionnelle adresse/commune.

- [x] Détection panier des produits à point de remise.
- [x] Choix livraison standard ou point de remise selon `Product.deliveryMode`.
- [x] Point de remise obligatoire pour `DELIVERY_POINT_REQUIRED`.
- [x] Sélection point + date/heure client ; la plage horaire reste une aide/validation serveur depuis J5S-B-bis.
- [x] Instruction client libre : heure d’arrivée, vol, barge, repère.
- [x] Validation serveur checkout.
- [x] Snapshot point/plage/instruction dans `CustomerOrder`.
- [x] Affichage confirmation commande.
- [x] Affichage admin commande, fiche opérationnelle, Djama, portail client.
- [x] Lints PHP/Twig initiaux réalisés lors des patches.
- [x] Migrations présentes dans les sources.
- [ ] Rejouer `doctrine:schema:validate` avant prochaine recette si l’environnement a changé.
- [x] Rejouer tests navigateur point de remise après J5V-A.
- [x] Déploiement recette complet tracé : `recette-j5s-b-ter-quater-checkout-point-standard-20260628`.
- [x] Tests recette point de remise / standard confirmés.
- [x] Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Hors périmètre J5S-B : modification client après commande, capacité par créneau, calendrier avancé, tarification spéciale par point.

## J5T-A — Checkout première commande simplifié

- Ajout d’un parcours checkout invité simplifié.
- Le client non connecté ne saisit plus de mot de passe avant validation.
- Un compte est créé automatiquement et l’e-mail de commande contient un lien sécurisé pour définir le mot de passe.
- Le checkout connecté reste inchangé.
- Aucun changement de schéma.


### J5T-A-bis — Nettoyage checkout invité + corps e-mail première commande

Statut : **validé recette sur le formulaire simple nouveau client**.

- [x] Supprimer les deux cases parasites du checkout invité.
- [x] Journaliser le corps de l’e-mail `ORDER_CREATED`.
- [x] Inclure le lien de création du mot de passe dans le corps journalisé quand il existe.
- [x] Valider en recette après création d’une commande invité.


## J5S-B-bis — Date/heure client pour point de remise

- [x] Remplacer le choix obligatoire de plage par une date et une heure client.
- [x] Garder les plages comme aide et validation serveur.
- [x] Snapshotter le rendez-vous client dans la commande.
- [x] Afficher le rendez-vous dans confirmation, admin, Djama et portail client.

## J5V-A — Délai minimum de commande par produit

Statut : **corrigé, revalidé recette puis validé production**. Une régression avait débranché la validation serveur du checkout : `DeliveryPointCartService::validateMinimumOrderLeadTime()` existait mais n’était plus appelée. Le correctif `3b508d0` rebranche l’appel dans `CheckoutController`. Tag recette : `recette-j5v-a-checkout-lead-time-fix-20260628`. Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

- [x] Ajouter un champ `minimumOrderLeadTimeHours` sur `Product`.
- [x] Afficher ce délai dans EasyAdmin Produit, section précommande/délais.
- [x] Appliquer le délai le plus strict du panier.
- [x] Bloquer le checkout point de remise si le rendez-vous est trop proche.
- [x] Informer le client dans le panier lorsqu’un délai minimum existe.
- [x] Tests navigateur locaux annoncés OK.
- [x] Tests recette annoncés OK.
- [x] Migration `Version20260626194000` présente dans les sources et nécessaire au champ produit.
- [ ] Rejouer lints PHP/Twig avant production.
- [x] `doctrine:schema:validate` validé en production avant/après MEP.
- [x] Contrôler et rebrancher explicitement l’appel serveur à `validateMinimumOrderLeadTime()` dans `CheckoutController` (`3b508d0`).
- [x] Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Hors périmètre J5V-A : calendrier avancé, règle par point de remise, blocage livraison standard sans date/heure client explicite.

## J5W — Logistique zones tarifaires, secteurs et express

Statut J5W-A : **validé localement sur `develop` + validé recette + validé production sous `prod-j5w-a-local-pricing-zones-20260629`**.

Décision structurante : ne pas confondre `DeliveryZone`, `DeliveryPricingZone` et les futures sous-zones opérationnelles.

- [x] J5W-A — Zones tarifaires locales par secteur, sans remplacer PT/GT :
  - créer `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL` ;
  - réutiliser `PT_LOCAL` pour Dzaoudzi, Labattoir et Pamandzi ;
  - ne pas créer `PETITE_TERRE_LOCAL` ;
  - conserver `DeliveryCommune.territory = PT/GT` ;
  - conserver la barge via `DeliveryCommuneConnection` + garde-fou territoire ;
  - corriger le champ `deliveryPointCustomerInstructions` perdu en bas du panier standard.
- [x] J5W-A-bis — Recette J5W-A validée sous `recette-j5w-a-local-pricing-zones-20260629` après merge contrôlé `develop` → `main`.
- [x] J5W-A-ter — Production J5W-A validée sous `prod-j5w-a-local-pricing-zones-20260629`.
- [ ] J5W-B — `DeliveryArea` administrable : créer une couche de sous-zones opérationnelles pour planning, exploitation et affectation future des livreurs.
- [ ] J5W-C — Planning par `DeliveryArea` : jours de livraison et cutoff à 10h00 la veille du créneau.
- [ ] J5W-D — Demande de livraison express : demande client hors créneau standard avec supplément paramétrable, à confirmer humainement pendant le pilote.
- [ ] J5W-E — Proposition d’heure livreur pour point de remise : le livreur peut proposer une heure différente sans écraser l’heure demandée par le client.
- [ ] J5Y-A — Disponibilité produit par commune livrée : ancien intitulé J5W-A repoussé pour éviter la collision de jalon.

Répartition tarifaire locale J5W-A :

| Commune Hodina | Territoire technique conservé | Zone tarifaire locale |
|---|---|---|
| Mamoudzou | `GT` | `MAMOUDZOU_LOCAL` |
| Acoua, Bandraboua, Koungou, M'Tsangamouji, Mtsamboro | `GT` | `NORD_LOCAL` |
| Chiconi, Ouangani, Sada, Tsingoni | `GT` | `CENTRE_LOCAL` |
| Bandrélé, Bouéni, Chirongui, Dembéni, Kani-Kéli | `GT` | `SUD_LOCAL` |
| Dzaoudzi, Labattoir, Pamandzi | `PT` | `PT_LOCAL` |

Répartition initiale proposée des futures `DeliveryArea`, encore non codées :

| Code | Nom | Rôle |
|---|---|---|
| `MAMOUDZOU` | Mamoudzou | secteur opérationnel / tournée |
| `NORD` | Nord | secteur opérationnel / tournée |
| `CENTRE` | Centre | secteur opérationnel / tournée |
| `SUD` | Sud | secteur opérationnel / tournée |
| `PETITE_TERRE` | Petite-Terre | secteur opérationnel / tournée |

Règle anti-régression : `DeliveryArea` ne doit jamais remplacer `DeliveryPricingZone` pour les forfaits locaux, ni `DeliveryCommuneConnection` / `DeliveryCommune.territory` pour la barge et les garde-fous PT/GT. J5W-A ne modifie pas le checkout validé production.

## J5S-B-ter — Séparation stricte point de remise / adresse standard

Statut : **validé localement + recette + production**. Tags : `recette-j5s-b-ter-quater-checkout-point-standard-20260628`, puis `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

- [x] Cacher le bloc adresse de livraison quand le mode point de remise est actif.
- [x] Afficher un bloc `Point de remise choisi` côté panier connecté.
- [x] Empêcher visuellement le changement d’adresse client en mode point.
- [x] Envoyer `deliveryMethod` et `deliveryPointId` au recalcul des frais.
- [x] Calculer le preview des frais avec la commune du point en mode point.
- [x] Conserver le calcul standard basé sur l’adresse client en mode standard.
- [x] Préserver le cas produit standard + point selon le mode choisi.
- [x] Rejouer lints PHP/Twig utiles.
- [x] Rejouer tests navigateur point imposé en local.
- [x] Rejouer tests navigateur produit standard en local.
- [x] Rejouer tests navigateur produit standard + point en local.
- [x] Déploiement recette.
- [x] Validation recette.
- [x] Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Hors périmètre : e-mails, SMS, statuts, Djama, planning DeliveryArea, livraison express.

## J5S-B-quater — Feedback global checkout point de remise

Statut : **validé localement + recette + production** avec J5S-B-ter/quater. Tags : `recette-j5s-b-ter-quater-checkout-point-standard-20260628`, puis `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

- [x] Ajouter un message global sous le header quand le formulaire revient invalide.
- [x] Ajouter un message client dynamique sous le header après tentative de validation, sans afficher d’erreur dès l’arrivée sur la page.
- [x] Griser visuellement les boutons `Valider` tant que les champs obligatoires ne sont pas complets.
- [x] Garder la validation serveur existante pour les contraintes métier : point autorisé, date/heure obligatoires, plage active, délai minimum produit.
- [x] Ne pas modifier les calculs de frais, les e-mails, Djama, les statuts ni Doctrine.
- [x] Masquer les points de remise quand un produit standard + point est en mode `Livraison à mon adresse`.
- [x] Afficher l’unité de vente côté client dans le catalogue, la fiche produit et le panier.
- [x] Rejouer lint Twig du panier.
- [x] Rejouer cache clear/warmup.
- [x] Corriger le timing du message global : aucun message rouge avant tentative de validation.
- [x] Corriger les messages français identité / adresse standard / commune.
- [x] Corriger le champ hérité `deliveryPointTimeWindowId` : non obligatoire, plage déduite de l’heure client.
- [x] Rejouer tests locaux point de remise et livraison standard non connecté.
- [x] Rejouer test point de remise imposé complet : date vide, heure vide, heure hors plage, validation OK.
- [x] Rejouer test produit standard + point selon le mode choisi après dernier correctif `CheckoutType`.
- [x] Déploiement recette.
- [x] Validation recette.
- [x] Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Règle UX : le bouton peut être grisé pour les informations manquantes simples, mais les contraintes métier complexes restent validées côté serveur et remontées dans le message global.


## J5T-C — Checkout invité avec compte existant

Statut : **validé localement + recette + production**. Commit `38f9e23`, tag recette `recette-j5t-c-checkout-existing-account-20260628`, tag production `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

- [x] Ajouter des champs techniques non mappés pour confirmer le rattachement : `confirmExistingAccount` et `confirmedExistingAccountEmail`.
- [x] Ajouter le popup de confirmation dans `templates/cart/index.html.twig`.
- [x] Ajouter la mention de rattachement dans `ORDER_CREATED` et dans `EmailLog.body`.
- [x] Ne pas créer de migration.
- [x] Premier test local avec e-mail nouveau : annoncé comme passé.
- [x] Identifier l’ancien garde-fou qui bloquait encore l’e-mail existant dans `CheckoutController`.
- [x] Corriger le contrôleur pour ne plus ajouter l’erreur `Un compte existe déjà...` dans le checkout invité.
- [x] Corriger le flux sans `setData()` après `handleRequest()` pour éviter `AlreadySubmittedException`.
- [x] Rejouer les lints PHP/Twig après le dernier fichier `CheckoutController.php` corrigé.
- [x] Rejouer le test local e-mail existant : premier clic `Valider` → popup, aucune commande, aucun doublon.
- [x] Rejouer le test local confirmation popup : commande créée et rattachée au `Customer` existant.
- [x] Vérifier `ORDER_CREATED` et `EmailLog.body` avec la mention : `Cette commande a été rattachée à ton espace client Hodina.`
- [x] Rejouer les variantes point de remise et livraison standard avec e-mail existant.
- [x] Déployer en recette.
- [x] Valider recette.
- [x] Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Contrôle anti-régression à conserver : `Select-String -Path src\Controller\CheckoutController.php -Pattern "Connecte-toi avant de valider ta commande|utilise une autre adresse e-mail"` ne doit rien retourner dans le checkout. Le message peut rester dans `RegistrationController`, car l’inscription classique doit encore bloquer un e-mail déjà existant.

# Mise à jour 29/06/2026 — Production checkout stabilisation validée

Statut global : **production validée** pour le bloc checkout stabilisé.

Tag production : `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.
Commit : `d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix`.

Lots validés production :

- [x] J5S-B-ter/quater — séparation livraison standard / point de remise, frais selon la bonne source de vérité, masquage des points en standard.
- [x] J5T-C — checkout invité avec e-mail existant, popup avant création, rattachement au `Customer` existant après confirmation.
- [x] J5U-A — expéditeur e-mails paramétrable EasyAdmin, `commande@hodina.fr`.
- [x] J5V-A — délai minimum produit corrigé et appliqué côté serveur au checkout point de remise.

Tests minimum production annoncés passés : site/panier, point de remise avec délai trop proche refusé, délai valide accepté, e-mail existant invité avec popup/rattachement, `ORDER_CREATED`.

État supersédé par J5W-A : la production checkout est documentée, puis J5W-A a été livré. La prochaine reprise doit partir de l’état J5W-A validé production : `DeliveryArea` reste prévu/non codé et ne doit pas remplacer `DeliveryPricingZone` pour les forfaits locaux ni les liaisons/territoires techniques pour la barge.

## J5X — Livraison, promesse produit et catalogue avant ouverture

Statut global : **J5X est implémenté et validé localement par garde-fous techniques ; J5X a été déployé en recette sous `recette-j5x-livraison-catalogue-20260630-1440`, mais la validation navigateur complète reste à terminer avant production.**

### J5X-A — Tarifs zones tarifaires

- [x] Migration de données `Version20260629141000`.
- [x] Frais client : PT 12 €, Mamoudzou 12 €, Centre 17 €, Sud 21 €, Nord 21 €, GT fallback 21 €.
- [x] `courierPayout` non modifié.
- [x] Garde-fou `tools/assert-j5x-a-delivery-pricing-zones.php` OK local.
- [x] Déployé recette dans le lot groupé J5X.
- [ ] Production non validée.

### J5X-B — Calendrier livraison par secteur

- [x] Champs calendrier sur `DeliveryPricingZone`.
- [x] Migration `Version20260629152000` présente dans le cycle J5X.
- [x] `DeliveryScheduleService` ajouté.
- [x] Panier AJAX enrichi avec `deliverySchedule`.
- [x] Garde-fou `tools/assert-j5x-b-delivery-schedules.php` OK local.
- [x] Déployé recette dans le lot groupé J5X.
- [ ] Validation navigateur recette complète à terminer.
- [ ] Production non validée.

### J5X-C — Promesse produit / produits sur créneau

- [x] Champs promesse publique sur `Product`.
- [x] `ProductDeliveryPromiseService` et DTO dédiés.
- [x] Fiche produit standard / commune connue / commune inconnue.
- [x] Produit sur créneau pour broche, collier, accueil aéroport ou événement.
- [x] Clarification EasyAdmin J5X-C-bis.
- [x] Garde-fou `tools/assert-j5x-c-product-delivery-promises.php` OK local.
- [x] Déployé recette dans le lot groupé J5X.
- [x] Tests EasyAdmin produit annoncés OK : champs promesse et produit sur créneau.
- [ ] Tests navigateur recette fiche produit/panier à compléter.
- [ ] Production non validée.

### J5X-D — Catalogue recherche, filtres, tri, priorité admin

- [x] Champs merchandising catégorie et produit.
- [x] Repositories catalogue dédiés.
- [x] Recherche, filtre catégorie, tri GET, rendu AJAX progressif.
- [x] Ordre Hodina par défaut.
- [x] Ajout panier AJAX conservé.
- [x] Garde-fou `tools/assert-j5x-d-catalogue-search-filters.php` OK local.
- [x] Déployé recette dans le lot groupé J5X.
- [ ] Validation navigateur complète catalogue après passage de `/catalogue` vers `/`.
- [ ] Production non validée.

Repoussé : filtre commune, disponibilité produit par commune, tri livraison la plus proche et pagination avancée.

## J5Y — Points de remise UX, homepage catalogue, identité, Carnet et navigation publique

Statut global : **J5Y-A/B/C/D/E/F/G/H validé localement, validé recette, déployé production et validé production**.

Tag recette final : `recette-j5y-carnet-livraison-footer-clean-20260701`.
Tag production validé : `prod-j5y-carnet-livraison-footer-20260701`.
Commit production : `200d84b merge: document j5y recette validation`.

### J5Y-A — Interface guidée plages horaires point de remise

- [x] Remplacer l’expérience textarea brute par une interface guidée EasyAdmin.
- [x] Conserver le textarea Symfony comme source soumise au backend.
- [x] Ajouter `Jours ouvrés` et `Jours ouvrables` comme raccourcis UI.
- [x] Garde-fou `tools/assert-j5y-a-delivery-point-window-ui.php` OK.
- [x] Validé localement.
- [x] Mergé `main` dans le lot J5Y.
- [x] Déployé et validé recette dans le lot J5Y final.
- [x] Déployé et validé production dans le lot J5Y final.

### J5Y-B — Créneaux panier point de remise par demi-heure

- [x] Remplacer l’heure libre par un select de créneaux de 30 minutes.
- [x] Ne jamais proposer l’heure de fin comme début de créneau.
- [x] Validation serveur maintenue dans `DeliveryPointCartService`.
- [x] Correctifs UI J5Y-B-bis et J5Y-B-ter validés.
- [x] Déployé et validé recette dans le lot J5Y final.
- [x] Déployé et validé production dans le lot J5Y final.

### J5Y-C — Catalogue en homepage et page Découvrir Hodina

- [x] `/` affiche le catalogue.
- [x] `/catalogue` redirige vers `/`.
- [x] `/decouvrir-hodina` contient la page institutionnelle Découvrir Hodina.
- [x] `/blog/decouvrir-hodina` et `/blog` redirigent vers `/decouvrir-hodina`.
- [x] `Blog` n’est plus exposé comme libellé UX public.
- [x] Tests techniques locaux OK.
- [x] Tests recette navigateur OK.
- [x] Tests production navigateur OK.

### J5Y-D — Logo header et favicon

- [x] Logo header horizontal branché : `public/images/logo_hodina_header.png`.
- [x] Logo header optimisé à environ 4,5 Ko avant recette.
- [x] Favicons `ico`, `16x16`, `32x32`, `apple-touch-icon` présents et validés par assert.
- [x] Version transparente J5Y-D-ter retenue pour le MVP.
- [x] Déployé et validé recette.
- [x] Déployé et validé production.

### J5Y-E — Clarification URL publique Découvrir Hodina

- [x] `/decouvrir-hodina` est la route canonique.
- [x] `templates/pages/decouvrir_hodina.html.twig` remplace l’ancien emplacement `templates/blog`.
- [x] `/blog` et `/blog/decouvrir-hodina` restent en redirections legacy.
- [x] Décision documentée : Découvrir Hodina = page institutionnelle, Blog = terme évité côté UX publique.
- [x] Validé recette.
- [x] Validé production.

### J5Y-F — Page Carnet et page pédagogique Livraison Hodina

- [x] Créer `/carnet` comme entrée pédagogique publique.
- [x] Créer `/carnet/livraison` comme page de réassurance livraison.
- [x] Garder `Fruits, légumes et saisons` sans lien actif tant que le contenu n’existe pas.
- [x] Garder `Nos vendeurs et producteurs partenaires` sans lien actif tant que le contenu n’existe pas.
- [x] Vérifier que la page livraison ne promet pas une livraison garantie.
- [x] Vérifier que Djama reste privé et absent du contenu public.
- [x] Garde-fou `tools/assert-j5y-f-carnet-livraison.php` OK.
- [x] Tests navigateur recette OK : `/carnet`, `/carnet/livraison`, `/decouvrir-hodina`, `/`.
- [x] Tests navigateur production OK.

### J5Y-G — Footer UX et navigation publique

- [x] Sortir `Découvrir Hodina` du header.
- [x] Ajouter `Infos livraison` dans le header vers `/carnet/livraison`.
- [x] Créer un footer compact de réassurance : Hodina, Explorer, Livraison, Pratique.
- [x] Conserver CGV, CGU et contact `contact@hodina.fr`.
- [x] Rappeler le paiement manuel pilote et les dates/frais confirmés au panier.
- [x] Validé recette.
- [x] Validé production.

### J5Y-H — Illustrations et simplification de la page livraison

- [x] Ajouter 4 images WebP de zones : Petite-Terre, Mamoudzou, Nord/Centre, Sud.
- [x] Garder les images sous 100 Ko chacune.
- [x] Simplifier le texte pour éviter les répétitions.
- [x] Corriger la numérotation finale de la page livraison.
- [x] Réaligner l’assert J5Y-F avec la version éditoriale retenue.
- [x] Validé recette.
- [x] Validé production.

### Tags et commits à retenir

- [x] Tag recette final validé : `recette-j5y-carnet-livraison-footer-clean-20260701`.
- [x] Commit déployé : `b1bbab6 chore(j5y): remove delivery guide backup template`.
- [x] Tag `recette-j5y-carnet-livraison-footer-20260701` supersédé car il contenait un backup `.bk`.
- [x] Tag `recette-j5y-public-catalogue-discover-branding-perf-20260701` supersédé fonctionnellement par le tag Carnet/Footer.
- [x] Tag production validé : `prod-j5y-carnet-livraison-footer-20260701`.

## Prochaine priorité recommandée

1. Ne plus modifier J5Y sauf bug bloquant.
2. Reprendre un nouveau lot séparé pour la suite produit ou la dette technique.
3. Traiter séparément les dettes non bloquantes : `public/uploads/products/.gitkeep`, `PUBLIC_URL` manquant dans les MEP automatisées, dépréciations Doctrine/EasyAdmin, trajectoire Symfony non-LTS.
4. Repousser les nouvelles fonctionnalités éditoriales du Carnet tant qu’un nouveau lot n’est pas explicitement cadré.

# J5Z — Checkout/admin UX — clos production 02/07/2026

- [x] J5Z-A — Réordonner le formulaire Produit EasyAdmin autour des champs opérationnels.
- [x] J5Z-B — Clarifier la phrase catalogue sur frais, jours possibles et créneaux au panier.
- [x] J5Z-C — Ajouter un champ indicatif explicite pour inscription / checkout invité.
- [x] J5Z-C — Garder Mayotte / La Réunion `+262` en premier, puis France `+33`, Comores `+269`, Madagascar `+261`.
- [x] J5Z-C — Ajouter la commande de rattrapage `hodina:customers:normalize-phones` en simulation par défaut.
- [x] J5Z-C — Appliquer le rattrapage recette après simulation : 84 numéros modifiés, 0 non normalisable.
- [x] J5Z-D — Factoriser l’annotation frais via `DeliveryFeeReasonFormatter`.
- [x] J5Z-D — Afficher `Inclus : X commune(s) traversée(s) + barge.` uniquement quand justifié.
- [x] J5Z-D — Ne pas afficher d’annotation sur frais standard simple.
- [x] J5Z-D — Ajouter le flash frais recalculés en haut du panier, fond opaque marron clair, supprimable.
- [x] J5Z-D — Reprendre l’annotation dans panier, checkout, confirmation, détail client, email et récapitulatif SMS.
- [x] J5Z-E — Corriger le débordement mobile du champ `Date de rendez-vous` sur invité et connecté.
- [x] J5Z-E — Cacher le champ `Indicatif` technique pour client connecté.
- [x] J5Z-F — Versionner le cache de preview logistique pour forcer le recalcul après déploiement.
- [x] J5Z-F — Corriger la réponse AJAX pour garder l’annotation après changement d’adresse sans refresh.
- [x] Déployé recette sous `recette-j5z-delivery-fee-reason-refresh-20260702`.
- [x] Validé recette.
- [x] Déployé production sous `prod-j5z-delivery-fee-reason-refresh-20260702`.
- [x] Validé production.

## Tags J5Z supersédés

- [x] `recette-j5z-checkout-admin-ux-20260702` supersédé.
- [x] `recette-j5z-checkout-admin-ux-fix-mobile-20260702` supersédé.

## Points de vigilance J5Z restants

- [ ] Tracer dans la documentation de déploiement si le rattrapage téléphone production a bien été exécuté ; ne jamais le relancer sans simulation.
- [ ] Corriger plus tard le cron recette affiché avec `--time-limit=50--memory-limit=128M`.
- [ ] Traiter hors J5Z `public/uploads/products/.gitkeep` suivi par Git.

# J5AA — Localité d’adresse — LIVRÉ (validé recette + production 2026-07-04)

> Ce bloc était la planification initiale. Il est réalisé et déployé via les sous-lots J5AA-0, J5AA-A et J5AA-B (voir sections détaillées plus bas). Tags : `recette-j5aa-address-locality-20260704`, `prod-j5aa-address-locality-20260704`.

- [x] Créer l’entité `AddressLocality`.
- [x] Afficher le champ comme `Localité` avec aide `Village / quartier / lieu-dit`.
- [x] Préparer un seed initial des localités connues par commune, notamment Mamoudzou : les villages/localités de Mayotte connus au démarrage.
- [x] Créer un CRUD EasyAdmin `Localités d’adresse`.
- [x] Ajouter la localité aux formulaires d’adresse livraison, facturation et potentiellement retrait vendeur.
- [x] Autoriser la saisie libre d’une localité non reconnue.
- [x] Préremplir la commune uniquement quand l’utilisateur sélectionne une localité connue et validée.
- [x] Ne jamais utiliser la localité libre pour calculer les frais, la barge, les jours ou les créneaux.
- [x] Snapshotter la localité au moment de la commande si elle est utilisée.
- [x] Afficher la localité dans admin commande, détail client, Djama et récapitulatifs utiles.
- [x] Ajouter les tests anti-régression : commune source tarifaire, village/localité précision terrain, anciennes commandes lisibles.

## Complément J5AA — code postal / commune seedés (livré via J5AA-B)

- [x] Vérifier le référentiel `DeliveryCommune.postalCode` existant avant de créer une nouvelle entité de code postal.
- [x] Ne pas autoriser un code postal libre non seedé dans les formulaires de livraison.
- [x] Ne pas autoriser une commune libre hors `DeliveryCommune` active pour les adresses de livraison.
- [x] Garantir côté serveur la cohérence `code postal + DeliveryCommune`.
- [x] Si un code postal correspond à une seule commune active, préremplir la commune.
- [x] Si un code postal correspond à plusieurs communes actives, limiter la liste aux communes compatibles.
- [x] Si une commune est choisie d’abord, limiter ou préremplir le code postal compatible.
- [x] Corriger l’écart actuel : le checkout utilise déjà une commune livrée en sélection avec code postal déduit, alors que l’inscription utilise encore `postalCode` / `commune` en texte avec validation serveur.
- [x] Ne jamais calculer les frais directement depuis le code postal.


# Mise à jour opérationnelle 03/07/2026 — J5AB / J5AC

## Terminé et validé production

- [x] J5AB — Catalogue mobile orienté achat.
  - [x] Recherche + loupe + `Filtres` sur une ligne.
  - [x] Bloc institutionnel retiré du haut catalogue.
  - [x] Produits rapprochés du header mobile.
  - [x] AJAX catalogue existant conservé.
  - [x] Pas de pagination ajoutée.
  - [x] Validé production : `prod-j5ab-catalogue-mobile-achat-20260703`.

- [x] J5AC — Espace client finalisé.
  - [x] Hub `/mon-compte`.
  - [x] Liste/détail commandes conservés et protégés.
  - [x] Profil client modifiable.
  - [x] Mot de passe modifiable avec ancien mot de passe.
  - [x] Lien de réinitialisation connecté via `SmsLog`.
  - [x] Email client unique nullable.
  - [x] Migration non transactionnelle.
  - [x] AJAX progressif discret du portail client.
  - [x] Validé production : `prod-j5ac-espace-client-ajax-20260703`.

## Nettoyage de donnée effectué

- [x] Production : correction `customer.id = 13`, email `chahere.kdu` remplacé par `chahere.kdu@outlook.fr`.
- [x] Audit J5AC-DB production après correction : aucun email invalide simple, aucun doublon normalisé.

## Prochaines priorités recommandées

- [x] Documentation J5AB/J5AC mise à jour dans les fichiers centraux et READMEs de lots.
- [ ] Ne pas rouvrir J5AB/J5AC sauf bug bloquant.
- [x] J5AA — `AddressLocality` livré (sous-lots J5AA-0/A/B), validé recette + production 2026-07-04.
- [ ] Dette technique : `public/uploads/products/.gitkeep` suivi par Git alors que les uploads sont runtime.
- [ ] Dette technique : Symfony 8.0.5 non-LTS, trajectoire framework à planifier.
- [ ] Dette technique : dépréciations Doctrine `controller_resolver.auto_mapping` et EasyAdmin `#[AdminDashboard]`.
- [ ] Dette script : `PUBLIC_URL` absent sur certains déploiements, donc URL publique non testée automatiquement.
# J5AA-0 — Audit strict des adresses DELIVERY

- [x] Cadrer `Address.commune` comme champ métier central de l'adresse, non supprimé dans J5AA.
- [x] Confirmer que la contrainte stricte concerne les adresses `DELIVERY`, pas les facturations `AUTRE`.
- [x] Ajouter un assert read-only : `tools/assert-j5aa-delivery-address-commune-audit.php`.
- [x] Vérifier commune, code postal, zone et correspondance exacte avec `DeliveryCommune` active/logistique.
- [x] Refuser comme anomalie une commune `DELIVERY` seulement résoluble en fuzzy ou ambiguë.
- [x] Ne créer aucune migration, aucune entité `AddressLocality`, aucun champ `Address.deliveryCommune`.
- [x] Exécuter l'audit localement sur la base dev.
- [x] Corriger ou documenter explicitement les éventuelles adresses `DELIVERY` ambiguës avant J5AA-A.
- [x] Passer à J5AA-A uniquement après décision sur les anomalies remontées par l'audit.

# J5AA-B — Checkout code postal + commune

Statut : livré, validé recette + production 2026-07-04 (`prod-j5aa-address-locality-20260704`).

## Inclus

- Code postal livraison en choix issu des `DeliveryCommune` actives/logistiques.
- Commune livraison en choix issu des `DeliveryCommune` actives/logistiques.
- Filtrage UX des communes compatibles selon le code postal.
- Préremplissage possible de la commune si le code postal est non ambigu.
- Contrôle serveur du couple `postalCode + commune` dans le checkout.
- Contrôle du même couple dans l'aperçu AJAX des frais.
- Persistance inchangée : le serveur écrit toujours dans `Address.postalCode` et `Address.commune`.
- Aucun `Address.deliveryCommune`.
- Aucune migration.

## À valider localement

- Choisir `97600` puis `Mamoudzou` : OK.
- Choisir `97600` puis `Koungou` : OK.
- Manipuler le POST avec `97600 + Dzaoudzi` : refus serveur.
- Changer de code postal après avoir choisi une commune : commune incompatible réinitialisée.
- Adresse de facturation `AUTRE` : non impactée.
- J5AA-0 audit : toujours OK.
- J5Z : frais recalculés, flash et annotation frais non régressés.

# J5AA-A — AddressLocality

Statut : livré, validé recette + production 2026-07-04 (`prod-j5aa-address-locality-20260704`). Migration `Version20260704210000`.

- [x] Ajouter l’entité `AddressLocality`.
- [x] Ajouter `Address.addressLocality` et `Address.localityText`.
- [x] Ajouter le snapshot `CustomerOrder.deliveryAddressLocalityName`.
- [x] Ajouter le CRUD EasyAdmin des localités.
- [x] Ajouter la commande idempotente `hodina:address-localities:seed`.
- [x] Seed initial Mamoudzou : les villages/localités de Mayotte connus au démarrage.
- [x] Ajouter le champ optionnel `Localité` au checkout livraison.
- [x] Valider migration localement.
- [x] Exécuter le seed localement.
- [x] Vérifier qu’aucun frais n’est calculé par localité.
- [x] Vérifier les affichages client/admin/Djama avant recette.

Hors périmètre J5AA-A : `Address.deliveryCommune`, tarification par localité, tarification par code postal, Google Maps/API externe.

# État après J5AF / correctif AdminContext / J5AG (08/07/2026)

## J5AD — Chatbot IA client + support

Statut : validé recette (tag `recette-j5ad-j5ae-assistant-hodina-20260708c`). Statut production non confirmé.

- [x] Chatbot IA connecté `/mon-compte/assistant`, escalade vers ticket support.
- [x] Formulaire de contact public `/contact`.
- [x] Réglages LLM administrables (`AiChatbotSetting`), clé API chiffrée.
- [ ] Confirmer le statut production.

## J5AE — Widget Assistant Hodina

Statut : validé recette (même tag que J5AD). Statut production non confirmé.

- [x] Widget flottant sur tout le site public, moteur à règles.
- [x] Escalade vers ticket support (origine `CHAT_WIDGET`).
- [ ] Confirmer le statut production.

## J5AF — Suppression pilote corrigée + anonymisation RGPD

Statut : validé recette (tag `recette-j5af-suppression-anonymisation-client-20260708`, sortie de script confirmée). **Non déployé production.**

- [x] Suppression pilote : gère désormais les conversations IA (`ChatbotConversation`).
- [x] Anonymisation RGPD : nouvelle action admin, scrub données + blocage connexion + conservation historique.
- [x] Migrations `Version20260708120000` / `Version20260708130000`.
- [ ] Déploiement production.

## Correctif transverse — `AdminContext::getEntity()` (piège n°11)

Statut : testé localement sur hodina.fr. Tag `recette-j5ag-fix-admincontext-20260708` créé et poussé — **déploiement recette à confirmer** (pas de sortie de script vue).

- [x] `CustomerOrderCrudController` (6 méthodes), `SupportTicketCrudController`, `CourierPayoutCrudController` corrigés.
- [x] Grep de vérification sur tout `src/Controller/Admin/` : zéro occurrence restante.
- [ ] Confirmer le déploiement recette réel (sortie de script).
- [ ] Déploiement production.

## J5AG — Gestion des logs SMS / e-mails + checklist minimale

Statut : testé localement sur hodina.fr. Tag `recette-j5ag-gestion-logs-sms-email-20260708` créé et poussé — **déploiement recette à confirmer**.

- [x] Bouton « Vider les SMS logs ».
- [x] Suppression unitaire/par lot EmailLog + bouton « Vider les journaux e-mails ».
- [x] Checklist minimale documentée (`docs/DEPLOIEMENT_PREPROD.md`), référencée dans `CLAUDE.md`.
- [ ] Confirmer le déploiement recette réel (sortie de script).
- [ ] Déploiement production.

## Documentation

- [x] `CLAUDE.md` fusionné (claude_hodina + hodina.fr) — architecture/domaine + process/pièges dans un seul fichier, synchronisé sur les deux dépôts.

## Prochaines priorités recommandées

- [ ] Confirmer en recette (sortie de script + checklist minimale) le correctif AdminContext et J5AG avant de les tagger production.
- [ ] Confirmer le statut production réel de J5AD/J5AE.
- [ ] Après validation recette complète : tag production, déploiement production.
- [ ] Poursuivre le développement directement sur `D:\hodina\hodina.fr` / `chahere/hodina` (fin du travail combiné avec le dépôt sandbox `chahere/claude_hodina`).
