# COMMIT J5S-B-quater — Feedback global checkout point de remise

## Objectif

Améliorer l’expérience mobile du checkout lorsque le client valide une commande avec point de remise ou livraison standard.

Le problème constaté : les erreurs apparaissaient trop bas dans la page, les boutons restaient visuellement verts alors que des informations obligatoires manquaient, puis certains correctifs affichaient une erreur rouge trop tôt, avant toute tentative de validation.

## Périmètre

- Message global sous le header après retour serveur invalide.
- Message client dynamique sous le header après tentative de validation.
- État visuel grisé des boutons `Valider` tant que les informations simples sont incomplètes.
- Prénom, nom, téléphone, e-mail, CGV, point/date/heure, adresse/commune selon le mode.
- Masquage des points de remise en mode `Livraison à mon adresse` pour les produits optionnels.
- Validation conditionnelle `address` / `commune` uniquement en mode standard.
- `deliveryPointTimeWindowId` non obligatoire : la plage est déduite de l’heure réelle du client.
- Aucun changement de calcul de frais.
- Aucune migration.
- Aucun changement e-mail, SMS, Djama ou statut commande.

## Décisions techniques

- Le JavaScript améliore l’UX mais ne devient pas source de vérité métier.
- Les contraintes métier complexes restent côté Symfony : point autorisé, heure dans une plage active, délai minimum produit.
- Le message global rouge ne s’affiche pas à l’arrivée sur la page, seulement après tentative de validation ou retour serveur invalide.
- En mode point de remise, l’adresse standard ne doit jamais bloquer le formulaire.
- En mode standard, adresse et commune restent obligatoires si aucune adresse existante n’est sélectionnée.

## Fichiers concernés

- `templates/cart/index.html.twig`
- `public/css/style_mobile.css`
- `src/Form/CheckoutType.php`
- documentation de suivi

## Tests recommandés

```powershell
php -l src/Form/CheckoutType.php
php bin/console lint:twig templates/cart/index.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Tests navigateur :

- arrivée sur panier avec champs vides : pas de message rouge avant clic ;
- clic `Valider` avec prénom vide : message global français ;
- mode point avec heure hors plage : message français `Choisis une heure dans les horaires proposés pour ce point de remise.` ;
- mode standard sans adresse : message français adresse/commune ;
- produit standard + point : les points sont masqués en mode standard et réaffichés en mode point ;
- bouton sticky et bouton principal : aucun blocage silencieux.

## Statut

Tests locaux améliorés selon retour du 28/06/2026. Recette complète à jouer avant clôture.
