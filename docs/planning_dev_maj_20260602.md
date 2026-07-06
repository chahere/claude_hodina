Voici le **planning pilote Hodina remis au propre**, avec **ce qui est fait** et **ce que je te suggère maintenant**.

## J1 — Socle technique + backoffice

**Objectif :** avoir un projet Symfony exploitable avec une base propre et un admin.

**Fait :**

* Symfony 8 opérationnel.
* MariaDB locale configurée.
* Doctrine + migrations fonctionnels.
* EasyAdmin opérationnel.
* Sécurité admin sur `/ouegnewe`.
* Création admin via `Customer`.
* Checkpoint Git : `checkpoint-j3-stable`.

**À sécuriser :**

* Nettoyer Git : retirer `var/cache` et `var/log` du repository.
* Vérifier `.gitignore`.
* Garder MariaDB comme base dev principale.

---

## J2 — Catalogue public + fiche produit

**Objectif :** permettre au client de voir les produits.

**Fait :**

* Page catalogue.
* Fiche produit.
* Images produits.
* Ajout au panier depuis catalogue.
* Ajout au panier depuis fiche produit.
* UX mobile améliorée.
* Header mobile avec icônes connexion + panier.

**À améliorer plus tard :**

* Filtres par catégorie.
* Recherche produit.
* Badge “précommande / disponible”.
* Affichage vendeur plus clair.

---

## J3 — Panier + checkout socle

**Objectif :** passer d’un catalogue à une vraie commande.

**Fait :**

* `CartService`.
* `CartController`.
* Page `/panier`.
* Quantités `+ / -`.
* Mise à jour visuelle immédiate du total.
* Boutons retirer / vider panier.
* Checkout `/checkout`.
* Création client automatique.
* Création SMS log.
* Création `CustomerOrder`.
* Création `OrderItem`.
* Statut commande `PENDING_VALIDATION`.
* Panier vidé après validation.
* Page confirmation commande.
* Multi-adresses client.
* Adresse de facturation unique.

**À finaliser :**

* Si client connecté : afficher directement ses adresses au checkout.
* Proposer une adresse de livraison existante.
* Permettre “nouvelle adresse”.
* Ne pas redemander les infos client si connecté, sauf modification.

---

## J4 — Backoffice commandes opérationnel

**Objectif :** permettre à ton frère/admin de gérer réellement les commandes.

**Fait partiellement :**

* Commandes visibles dans EasyAdmin.
* Statut, paiement, total, zone visibles.
* Formulaire commande corrigé.

**À faire maintenant — priorité forte :**

1. Afficher les lignes de commande dans le détail admin :

   * produit
   * quantité
   * vendeur
   * prix
   * total ligne

2. Ajouter des actions admin :

   * Valider commande
   * Annuler commande
   * Passer en préparation
   * Marquer prête
   * Marquer livrée

3. À chaque action importante :

   * changer le statut
   * créer un `SmsLog`
   * garder une trace métier.

---

## J5 — Logistique PT / GT

**Objectif :** transformer la commande en tournée exploitable.

**Déjà amorcé :**

* `DeliveryZone`.
* Zone liée à l’adresse.
* Zone liée à la commande.

**À faire :**

* Définir règles :

  * Petite-Terre
  * Grande-Terre
  * jour de livraison
  * cutoff de commande
* Calculer une date de livraison estimée.
* Afficher dans admin.
* Avertir si panier multi-zone / frais supplémentaires.

---

## J6 — Notifications pilote

**Objectif :** simuler les notifications sans coût externe.

**Fait :**

* `SmsLog`.
* SMS générés en base.
* Table visible admin.

**À faire :**

* SMS “commande reçue”.
* SMS “commande validée”.
* SMS “commande prête”.
* SMS “livraison prévue”.
* Plus tard : vrai envoi SMS via provider.

---

## J7 — Portail client

**Objectif :** permettre au client connecté de gérer son compte.

**Fait partiellement :**

* Login client.
* Customer comme utilisateur de sécurité.
* Adresses liées au client.

**À faire :**

* Page “Mon compte”.
* Voir mes commandes.
* Voir/modifier mes adresses.
* Définir adresse de facturation.
* Définir adresse de livraison par défaut.

---

## J8 — Portail vendeur / onboarding vendeur

**Objectif :** préparer la croissance vendeurs.

**Fait :**

* Entité `Seller`.
* Produits liés aux vendeurs.
* Admin vendeur.

**À faire :**

* Formulaire “Devenir vendeur”.
* Backoffice “leads vendeurs”.
* Statut vendeur :

  * prospect
  * contacté
  * onboardé
  * actif
* Préparer la future IA onboarding vendeur.

---

## J9 — Bascule IA onboarding vendeur

**Objectif :** utiliser l’IA là où elle apporte vite de la valeur.

**À faire après stabilisation J4/J5 :**

* Assistant IA qui aide à créer une fiche vendeur.
* Génération description vendeur.
* Génération description produit.
* Suggestions catégories.
* Suggestions prix / unités.
* Checklist photos produits.

**Pourquoi commencer par vendeur :**

* Gain opérationnel immédiat.
* Tu accélères l’acquisition.
* Tu réduis le temps de mise en catalogue.

---

## J10 — Agent IA grocery client

**Objectif :** le client donne une intention, l’agent propose un panier.

**À faire plus tard :**

* “Je veux faire mes courses pour la semaine.”
* L’agent cherche produits disponibles.
* Il propose un panier.
* Il respecte zone PT/GT.
* Il suggère alternatives si produit indisponible.

**À ne pas faire maintenant :**

* Ne pas brancher l’agent grocery avant d’avoir :

  * commandes fiables
  * catalogue stable
  * zones fiables
  * stocks à peu près propres.

---

## Ma recommandation immédiate

Tu es actuellement entre **J3 stabilisé** et **J4 à construire**.

Je te recommande cet ordre :

1. Nettoyer Git (`var/cache`, `var/log`).
2. Ajouter affichage des `OrderItem` dans l’admin commande.
3. Ajouter boutons de statut commande.
4. Générer `SmsLog` sur changement de statut.
5. Améliorer checkout connecté avec choix d’adresse.

Le prochain vrai chantier doit être :

**J4 — Backoffice commandes opérationnel.**
