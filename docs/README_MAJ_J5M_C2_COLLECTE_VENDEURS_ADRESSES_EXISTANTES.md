# J5M-C2 — Collecte vendeurs avec adresses existantes et garde-fou logistique

## Statut

Patch consolidé préparé après analyse du modèle existant.

## Décision critique

Le premier cadrage ajoutait des champs `pickup_*` directement sur `Seller`. Cette approche est rejetée pour éviter un doublon fonctionnel.

Le projet possède déjà une entité `Address` avec :

- adresse structurée ;
- commune ;
- zone ;
- instructions ;
- commentaire terrain ;
- latitude GPS ;
- longitude GPS ;
- précision GPS ;
- URL Google Maps.

Comme un vendeur peut aussi être un utilisateur/client Hodina, J5M-C2 formalise le lien suivant :

```text
Seller
→ customerAccount : Customer optionnel
→ pickupAddress : Address optionnelle
```

## Règle fonctionnelle

```text
1. Si Seller.pickupAddress est renseignée, elle est utilisée pour guider la collecte terrain.
2. Sinon, si Seller.customerAccount est renseigné, son adresse de livraison par défaut est utilisée comme fallback terrain.
3. Sinon, le portail affiche la commune vendeur connue et indique que l'adresse de retrait est à préciser.
```

## Garde-fou logistique

La commune utilisée pour calculer les trajets, la barge, les frais client, la rémunération livreur et le snapshot logistique reste :

```text
Seller.deliveryCommune
```

Le point de retrait vendeur ne remplace jamais `Seller.deliveryCommune` dans `DeliveryLogisticsService`.

Décision verrouillée :

```text
Commune logistique = source de vérité coût / trajet / BFS.
Adresse de retrait = précision terrain livreur uniquement.
```

Le service `DeliveryLogisticsService` passe désormais par une méthode dédiée :

```text
resolveSellerLogisticsCommune(Product $product)
```

Cette méthode retourne explicitement :

```text
$product->getSeller()->getDeliveryCommune()
```

Un script de contrôle est ajouté :

```bash
php tools/assert-delivery-logistics-commune-source.php
```

Il échoue si `DeliveryLogisticsService` commence à utiliser le point de retrait vendeur comme source logistique.

## Cohérence affichée au livreur

Si le point de retrait a une commune différente de la commune logistique vendeur, le portail livreur affiche un avertissement non bloquant :

```text
Point de retrait : Labattoir · Commune logistique : Dzaoudzi
```

Cela permet de repérer une configuration potentiellement incohérente sans modifier le calcul des frais.

## Migration

La migration ajoute uniquement deux références nullable dans `seller` :

```text
customer_account_id
pickup_address_id
```

Elle ne duplique pas les champs adresse/GPS sur `seller`.

## Backoffice vendeur

EasyAdmin vendeur expose :

- Compte client vendeur ;
- Adresse / point de retrait ;
- Commune logistique.

Aide affichée :

```text
La commune logistique reste la source de vérité pour les trajets, la barge et les frais. L'adresse de retrait sert uniquement au livreur pour trouver le vendeur.
```

## Portail livreur

Dans le mode déplié, le bloc devient :

```text
Collecte vendeurs

Ferme combo — Dzaoudzi
Point de retrait
Adresse / GPS
• Cannes à sucre × 1

Ferme Abdallah — Labattoir
Point de retrait
Adresse / GPS
• Maniocs × 1
```

## Fichiers concernés

```text
src/Entity/Seller.php
src/Controller/Admin/SellerCrudController.php
src/Controller/Courier/CourierDashboardController.php
src/Service/DeliveryLogisticsService.php
templates/courier/dashboard.html.twig
migrations/Version20260621143500.php
tools/assert-delivery-logistics-commune-source.php
docs/README_MAJ_J5M_C2_COLLECTE_VENDEURS_ADRESSES_EXISTANTES.md
```

## Tests attendus

```powershell
php -l src/Entity/Seller.php
php -l src/Controller/Admin/SellerCrudController.php
php -l src/Controller/Courier/CourierDashboardController.php
php -l src/Service/DeliveryLogisticsService.php
php -l migrations/Version20260621143500.php
php bin/console lint:twig templates/courier/dashboard.html.twig
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php tools/assert-delivery-logistics-commune-source.php
```

## Parcours fonctionnel

```text
1. Ouvrir un vendeur dans EasyAdmin.
2. Renseigner son compte client vendeur.
3. Renseigner son adresse / point de retrait existant.
4. Vérifier que sa commune logistique reste renseignée.
5. Ouvrir le portail livreur.
6. Déplier une commande.
7. Vérifier le bloc Collecte vendeurs : adresse, GPS, produits et quantités.
8. Vérifier qu'un avertissement apparaît si la commune du point de retrait diffère de la commune logistique.
9. Vérifier que le calcul de frais reste inchangé, basé sur la commune logistique.
```
