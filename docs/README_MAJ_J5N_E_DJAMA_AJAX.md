# J5N-E — Actions AJAX du portail livreur Djama

Dernière mise à jour : **22/06/2026**

## Objectif

Fluidifier le portail livreur `/djama` pour que les actions terrain ne rechargent plus toute la page lorsque JavaScript est disponible.

Le fonctionnement reste volontairement progressif :

- sans JavaScript, les formulaires POST continuent de fonctionner avec redirection classique ;
- avec JavaScript, les formulaires marqués `data-djama-ajax="true"` sont envoyés en `fetch` ;
- le serveur retourne du JSON pour les requêtes AJAX ;
- le tableau Djama est rafraîchi après action en récupérant la page `/djama` mise à jour.

## Actions concernées

- Prendre en charge une commande.
- Valider / demander un code de collecte vendeur.
- Enregistrer une note terrain.
- Démarrer la livraison client.
- Marquer la commande livrée.

## Choix technique

Les routes existantes ne changent pas :

- `POST /djama/commande/{id}/prendre`
- `POST /djama/commande/{orderId}/collecte-vendeur/{sellerId}`
- `POST /djama/commande/{id}/note-adresse`
- `POST /djama/commande/{id}/demarrer-livraison`
- `POST /djama/commande/{id}/livree`

Les contrôleurs détectent une requête AJAX via `X-Requested-With: XMLHttpRequest` ou `Accept: application/json`.

Pour les requêtes classiques, le comportement historique est conservé : message flash + redirection vers `courier_dashboard`.

Pour les requêtes AJAX, le serveur renvoie :

```json
{
  "ok": true,
  "message": "...",
  "flashType": "success",
  "refreshUrl": "/djama"
}
```

Le JavaScript recharge ensuite uniquement la section Djama et restaure les cartes ouvertes si possible.

## Points de vigilance

- Ne pas supprimer le comportement POST classique : il est nécessaire en cas de réseau mobile instable ou de JavaScript indisponible.
- Les flashes restent globaux et affichés une seule fois en haut de page.
- Les routes métier ne changent pas, donc aucune migration n’est nécessaire.
- Ce lot ne modifie pas le workflow métier de collecte par code vendeur.

## Validation locale recommandée

```powershell
php -l src/Controller/Courier/CourierDashboardController.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console debug:router | findstr djama
php bin/console cache:clear
php bin/console cache:warmup
```

## Tests navigateur

1. Ouvrir `/djama` avec un compte livreur.
2. Prendre en charge une commande sans rechargement visuellement lourd.
3. Sur un vendeur sans code, cliquer sur `Valider la collecte` sans code : un code doit être généré/envoyé et la carte doit se rafraîchir.
4. Saisir un mauvais code : message d’erreur visible, page toujours utilisable.
5. Saisir le bon code : vendeur marqué collecté.
6. Lorsque toutes les collectes sont validées, démarrer la livraison sans rechargement complet.
7. Enregistrer une note terrain.
8. Marquer la commande livrée.
9. Désactiver JavaScript ou simuler un échec : les POST classiques doivent encore fonctionner.
