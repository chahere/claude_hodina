# README mise à jour — J5K-v8 adresses par défaut livraison / facturation

## Pourquoi cette mise à jour existe

Après la recette J5K-bis, le GPS, les instructions livreur et les snapshots commande fonctionnaient. Le point restant concernait l'expérience client dans le panier : il fallait distinguer clairement l'adresse de livraison par défaut et l'adresse de facturation par défaut.

## Règle finale panier

### Livraison

- Une adresse de livraison par défaut est stockée dans `customer.delivery_address_id`.
- La zone de livraison n'est pas affichée au client dans le bloc panier.
- La zone reste calculée automatiquement côté serveur.
- Les instructions et le GPS restent visibles si renseignés.

### Facturation

- Une adresse de facturation par défaut est stockée dans `customer.billing_address_id`.
- Le bloc facturation est séparé du bloc livraison.

## À vérifier avant déploiement

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console debug:router --env=prod | grep -E "cart_set_default_delivery_address|cart_set_default_billing_address"
```

## À vérifier après migration

```bash
php bin/console dbal:run-sql --force-fetch "SHOW COLUMNS FROM customer LIKE 'delivery_address_id';"
```

## Tests recette minimum

- Le panier affiche un bloc livraison et un bloc facturation.
- La zone de livraison n'est plus affichée au client dans le bloc adresse.
- Une adresse de livraison peut être sélectionnée pour la commande.
- Une adresse de livraison peut être définie comme adresse par défaut.
- Une adresse de facturation peut être sélectionnée.
- Une adresse de facturation peut être définie comme adresse par défaut.
- Le stylo ouvre la modification de l'adresse correspondante.
- Une commande sans GPS reste valide.
- Une commande sans instructions reste valide.
- Une commande avec GPS + instructions reste visible admin / fiche terrain / livreur.
