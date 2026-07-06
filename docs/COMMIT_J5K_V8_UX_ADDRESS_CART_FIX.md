# COMMIT — J5K-v8 UX panier adresses livraison/facturation

## Contexte

Après les premiers tests locaux de J5K-v8, le panier distinguait bien l'adresse de livraison et l'adresse de facturation, mais plusieurs points UX devaient être clarifiés avant déploiement recette :

- le bouton `Sélectionner cette adresse` était redondant sur mobile car la carte est déjà cliquable ;
- le bouton `Utiliser cette adresse ... par défaut` était visuellement placé dans la carte alors qu'il doit être sous la carte ;
- l'adresse de facturation affichait encore une zone métier alors que cette information ne doit pas être exposée au client ;
- en l'absence d'adresse de livraison par défaut, le panier doit sélectionner automatiquement une adresse de livraison disponible ;
- en l'absence d'adresse de facturation par défaut, le panier doit sélectionner automatiquement une adresse de facturation disponible ;
- la modification d'une adresse de livraison doit conserver l'accès aux instructions livreur et à la position GPS.

## Décision

Le panier conserve deux blocs distincts :

1. **Adresse de livraison**
   - adresse utilisée ;
   - commune livrée ;
   - instructions livreur si présentes ;
   - GPS si présent ;
   - stylo pour modifier ;
   - cartes d'adresses cliquables ;
   - bouton sous chaque carte pour définir l'adresse de livraison par défaut ;
   - bouton d'ajout si aucune adresse n'existe ou si le client veut saisir une nouvelle adresse.

2. **Adresse de facturation**
   - adresse utilisée ;
   - code postal / commune ;
   - stylo pour modifier ;
   - cartes d'adresses cliquables ;
   - bouton sous chaque carte pour définir l'adresse de facturation par défaut ;
   - bouton d'ajout si aucune adresse n'existe ou si le client veut saisir une nouvelle adresse.

La zone reste un champ métier nécessaire au serveur mais n'est plus visible dans le formulaire de facturation côté client.

## Changements réalisés

- Suppression des boutons `Sélectionner cette adresse` dans les listes d'adresses.
- Conservation du clic sur la carte comme action de sélection de l'adresse pour la commande en cours.
- Déplacement des boutons `Utiliser cette adresse ... par défaut` sous les cartes.
- Masquage du champ `billingZone` dans le panier client.
- Repli automatique vers une adresse de livraison existante si aucune adresse de livraison par défaut n'est définie.
- Repli automatique vers une adresse de facturation existante si aucune adresse de facturation par défaut n'est définie.
- Maintien du bloc GPS et des instructions dans la modification d'adresse de livraison.

## Points non modifiés

- Le snapshot commande reste inchangé.
- Les coordonnées GPS restent uniquement utiles à la livraison.
- L'adresse de facturation ne porte pas d'instructions livreur.
- L'admin, la fiche terrain et le portail livreur ne sont pas refondus dans cette correction.

## Tests attendus

- Ouvrir le panier avec un client connecté sans adresse de livraison par défaut mais avec au moins une adresse de livraison : la première adresse utile doit être reprise automatiquement.
- Ouvrir le panier avec un client connecté sans adresse de facturation par défaut mais avec au moins une adresse de facturation : la première adresse disponible doit être reprise automatiquement.
- Cliquer sur une carte de livraison : l'adresse utilisée doit changer.
- Cliquer sur une carte de facturation : l'adresse utilisée doit changer.
- Vérifier qu'aucun bouton `Sélectionner cette adresse` n'est visible.
- Vérifier que le bouton `Utiliser cette adresse ... par défaut` est sous la carte.
- Vérifier que le formulaire de facturation n'affiche pas la zone.
- Modifier une adresse de livraison : le bloc GPS et les instructions doivent rester disponibles.
- Modifier une adresse de facturation : aucun GPS ne doit apparaître.
