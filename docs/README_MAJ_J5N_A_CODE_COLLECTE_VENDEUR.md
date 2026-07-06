# README MAJ — J5N-A — Collecte vendeur avec code de validation

Dernière mise à jour : **22/06/2026**

## Objectif

Transformer la collecte vendeur dans **Djama** en vraie preuve terrain : le livreur ne peut pas simplement cocher un vendeur collecté. Il doit obtenir un **code de validation vendeur**.

## Règle métier

Pour chaque vendeur d'une commande :

1. Le livreur prend la commande en charge dans `/djama`.
2. Djama affiche les vendeurs à collecter, leurs points de retrait, GPS, instructions et produits.
3. Pour valider une collecte vendeur, le livreur saisit le code donné par le vendeur.
4. Si un code fixe est configuré sur le vendeur, ce code est utilisé.
5. Si aucun code fixe n'est configuré, Hodina génère un code ponctuel et l'envoie au vendeur par SMS et e-mail.
6. Les envois de code sont logués dans les journaux SMS et e-mails du backoffice.
7. La livraison client ne peut démarrer que lorsque toutes les collectes vendeur sont validées.

## Données ajoutées

### `seller.collection_validation_code`

Code de collecte fixe optionnel, configuré dans EasyAdmin sur le vendeur.

- Si renseigné : le vendeur connaît son code et le communique au livreur.
- Si vide : Hodina génère un code ponctuel par commande/vendeur.

### `customer_order.seller_collection_snapshot`

Snapshot JSON de suivi des collectes vendeur par commande.

Contient notamment :

- statut de collecte vendeur ;
- date d'envoi du code ponctuel ;
- IDs des logs SMS/e-mail ;
- tentatives incorrectes ;
- date de collecte validée ;
- livreur ayant validé ;
- note de collecte.

### `email_log.body`

Corps d'e-mail optionnel, utile pour tracer et rejouer manuellement un e-mail de code de collecte.

## Fichiers principaux

- `src/Entity/Seller.php`
- `src/Entity/CustomerOrder.php`
- `src/Entity/EmailLog.php`
- `src/Service/SellerCollectionCodeService.php`
- `src/Service/CustomerOrderWorkflowService.php`
- `src/Controller/Courier/CourierDashboardController.php`
- `src/Controller/Admin/SellerCrudController.php`
- `src/Controller/Admin/EmailLogCrudController.php`
- `templates/courier/dashboard.html.twig`
- `templates/emails/seller_collection_code.html.twig`
- `migrations/Version20260622172000.php`

## Routes Djama

Nouvelle route de validation :

```text
POST /djama/commande/{orderId}/collecte-vendeur/{sellerId}
```

Nom Symfony :

```text
courier_order_seller_collection_validate
```

## Tests attendus

### Cas 1 — vendeur avec code fixe

1. Configurer un vendeur avec `collectionValidationCode`.
2. Créer une commande avec ce vendeur.
3. Admin : passer la commande à prête.
4. Livreur : prendre en charge.
5. Saisir un mauvais code : la collecte doit être refusée.
6. Saisir le bon code : la collecte est validée.
7. Démarrer la livraison client.

### Cas 2 — vendeur sans code fixe

1. Laisser le code vendeur vide.
2. Livreur : cliquer sur validation sans saisir de code.
3. Hodina génère un code ponctuel et l'envoie au vendeur.
4. Vérifier les logs SMS/e-mail dans EasyAdmin.
5. Saisir le code reçu par le vendeur.
6. La collecte est validée.

### Cas 3 — multi-vendeurs

1. Créer une commande avec deux vendeurs.
2. Valider seulement un vendeur.
3. Vérifier que le bouton démarrer livraison reste bloqué.
4. Valider le second vendeur.
5. Vérifier que le démarrage livraison devient disponible.

## Décision importante

La collecte vendeur reste un sous-processus de terrain et ne crée pas un nouveau statut global de commande.

Le statut global reste :

```text
READY_FOR_PICKUP -> PICKED_UP -> OUT_FOR_DELIVERY -> DELIVERED
```

La preuve de collecte est portée par `seller_collection_snapshot`.
