# COMMIT — J5K-v8-bis création automatique adresse de facturation

## Contexte

Les tests locaux J5K-v8 ont montré que l’adresse de livraison était correctement sélectionnée, mais que l’adresse de facturation pouvait rester vide quand le client n’avait pas encore d’adresse de type `BILLING`.

La règle métier validée est la suivante :

1. si `customer.billing_address_id` est renseigné, utiliser cette adresse ;
2. sinon, chercher une adresse client de type `BILLING` ;
3. sinon, s’il existe une adresse client restante, créer une vraie adresse `BILLING` en copiant les données d’adresse de la première adresse disponible ;
4. si aucune adresse n’existe, afficher un état vide et proposer l’ajout d’une adresse de facturation.

## Décision

La facturation ne doit pas réutiliser directement une adresse de livraison comme simple affichage. Hodina crée une adresse de facturation séparée afin d’éviter qu’une modification de facturation modifie ensuite une adresse de livraison.

Lors de la copie depuis une adresse existante, Hodina copie uniquement les données utiles à la facturation :

- ligne 1 ;
- ligne 2 ;
- code postal ;
- commune ;
- zone métier requise par le modèle `Address`.

Hodina ne copie pas :

- GPS ;
- précision GPS ;
- instructions livreur ;
- notes terrain livreur.

## Fichiers concernés

- `src/Controller/CartController.php`
- `src/Controller/CheckoutController.php`

## Validation attendue

- client avec `billing_address_id` : adresse de facturation reprise ;
- client avec adresse `BILLING` mais sans `billing_address_id` : adresse reprise et définie comme défaut ;
- client avec uniquement une adresse `DELIVERY` : création automatique d’une adresse `BILLING` propre ;
- client sans aucune adresse : affichage “Adresse de facturation à compléter” et bouton “Ajouter une adresse de facturation”.
