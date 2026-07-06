# J5T-A — Checkout première commande simplifié

## Objectif

Simplifier le checkout pour un client non connecté / première commande, sans modifier le parcours des clients connectés.

Le formulaire invité doit rester mobile-first et limité aux informations nécessaires :

- prénom ;
- nom ;
- téléphone ;
- e-mail ;
- adresse ou point de remise selon le panier ;
- consigne utile ;
- GPS optionnel ;
- récapitulatif et validation.

## Décisions

- Aucun mot de passe n’est demandé avant validation de la commande.
- Hodina crée automatiquement un compte client avec un mot de passe temporaire interne.
- Un lien sécurisé de création/réinitialisation du mot de passe est généré et ajouté à l’e-mail récapitulatif de commande.
- Le parcours connecté reste inchangé : adresses sauvegardées, sélection avancée, facturation, GPS, points de remise.
- Le parcours invité masque les panneaux avancés d’adresses et de facturation.
- L’adresse de facturation du client invité est automatiquement alignée sur l’adresse ou le point de remise choisi.

## Périmètre technique

Fichiers modifiés :

- `src/Controller/CheckoutController.php`
- `src/Service/OrderEmailService.php`
- `templates/cart/index.html.twig`
- `templates/emails/order_created.html.twig`
- `public/css/style_mobile.css`

Pas de migration.

## Règles anti-régression

- Ne pas modifier le checkout connecté.
- Ne pas modifier le panier standard.
- Ne pas modifier Djama.
- Ne pas modifier les statuts commande.
- Ne pas modifier les règles d’annulation client J5R-A.
- Ne pas modifier les entités DeliveryPoint J5S-A/J5S-B.

## Tests attendus

### Client invité — livraison standard

- Le formulaire affiche uniquement les champs utiles.
- La commune déduit le code postal.
- Le GPS optionnel fonctionne.
- La commande est créée.
- Le compte client est créé.
- L’e-mail récapitulatif contient un lien pour créer le mot de passe.

### Client invité — point de remise

- Le formulaire affiche les informations client, le point de remise, la plage et l’instruction.
- L’adresse libre n’est pas mise en avant quand le point est imposé.
- La commande est créée avec le snapshot du point de remise.
- L’e-mail récapitulatif contient le lien de création du mot de passe.

### Client connecté

- Parcours inchangé.
- Adresses existantes toujours disponibles.
- Facturation inchangée.
