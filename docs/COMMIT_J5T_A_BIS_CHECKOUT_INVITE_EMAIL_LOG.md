# J5T-A-bis — Nettoyage checkout invité + corps e-mail première commande

## Objectif

Stabiliser le checkout invité simplifié après les premiers tests navigateur mobile.

## Correctifs validés dans ce lot

- Suppression des deux cases à cocher parasites affichées sans libellé avant le bloc `Qui recevra cette commande ?`.
- Les champs `makeDeliveryDefault` et `makeBillingDefault` restent soumis côté formulaire, mais sont rendus dans le bloc caché du checkout invité.
- Le parcours client connecté reste inchangé.
- Le journal `EmailLog` de l'événement `ORDER_CREATED` conserve désormais un corps texte lisible.
- Le corps journalisé inclut le récapitulatif de commande et, si disponible, le lien sécurisé de création du mot de passe de l'espace client.

## Ce qui n'a pas été modifié

- Pas de migration.
- Pas de changement sur Djama.
- Pas de changement sur les statuts commande.
- Pas de changement sur l'annulation client.
- Pas de changement sur les SMS.
- Pas de changement sur les e-mails de changement de statut déjà existants.

## Tests à rejouer

```bash
php -l src/Service/OrderEmailService.php
php bin/console lint:twig templates/cart/index.html.twig templates/emails/order_created.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tests navigateur

- Nouveau client non connecté : le checkout ne doit plus afficher de cases parasites.
- Validation commande invité : commande créée.
- EmailLog `ORDER_CREATED` : statut `SENT` et corps non nul.
- Le corps doit contenir le récapitulatif et le lien de création du mot de passe quand un token est disponible.
- Client connecté : checkout inchangé.
