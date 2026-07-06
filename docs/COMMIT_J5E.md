
# Commit J5E — Marge produit Hodina, prix producteur et prix client calculé

## Statut

**Réalisé, corrigé, testé localement, poussé sur GitHub, déployé en préproduction et validé fonctionnellement le 07/06/2026.**

## Branche

```text
pilot/j5b-workflow-service
```

Cette branche contient désormais :

- J5B : refactoring workflow commande admin ;
- J5C : données livraison ;
- J5D : dashboard livreur ;
- J5E : marge produit Hodina.

## Commit connu

Commit déployé en préproduction :

```text
379be5c
```

Le `git pull` préproduction a mis à jour la branche de :

```text
83d65d6..379be5c
```

## Objectif métier

J5E met en place le modèle économique produit de Hodina :

```text
prix producteur + marge Hodina = prix client affiché
```

Formule :

```text
prix client = prix producteur × (1 + taux de marge)
```

Exemple validé :

```text
prix producteur = 10,00 €
marge globale = 20,00 %
prix client = 12,00 €
marge Hodina = 2,00 €
```

## Objectif technique

Créer une logique réutilisable dans :

```text
src/Service/ProductPricingService.php
```

Ce service devient la source de vérité pour :

- récupérer le prix producteur ;
- déterminer la marge effective ;
- calculer le prix client ;
- calculer le montant de marge Hodina ;
- fournir un breakdown complet au catalogue, panier, checkout et futur portail vendeur.

## Hiérarchie de marge

Priorité validée :

```text
Marge produit > Marge vendeur > Marge globale
```

Règle détaillée :

```text
Si Product.marginRate est renseigné
→ utiliser Product.marginRate

Sinon si Seller.marginRate est renseigné
→ utiliser Seller.marginRate

Sinon
→ utiliser HodinaSetting.global_margin_rate
```

Le réglage global est initialisé à :

```text
global_margin_rate = 20.00
```

## Fichiers principaux

```text
src/Service/ProductPricingService.php
src/Entity/Product.php
src/Entity/Seller.php
src/Entity/OrderItem.php
src/Entity/HodinaSetting.php
src/Service/CartService.php
src/Controller/ProductController.php
src/Controller/CheckoutController.php
src/Controller/Admin/ProductCrudController.php
src/Controller/Admin/SellerCrudController.php
templates/product/catalogue.html.twig
templates/product/show.html.twig
templates/cart/index.html.twig
migrations/Version20260607120000.php
```

## Changements réalisés

### Product

Ajouts :

```text
producerPrice
marginRate
```

`producerPrice` représente le prix demandé par le vendeur.

`marginRate` représente une marge spécifique au produit, en pourcentage.

Le champ historique `price` est conservé pour compatibilité. À partir de J5E, il ne doit plus être considéré comme la source métier principale du prix client.

Règle de secours :

```text
Si producerPrice est vide ou nul
→ ProductPricingService réutilise temporairement Product.price
```

### Seller

Ajout :

```text
marginRate
```

Cette marge est utilisée seulement si le produit n'a pas de marge spécifique.

### HodinaSetting

Ajout de :

```text
KEY_GLOBAL_MARGIN_RATE = 'global_margin_rate'
```

### OrderItem

Ajouts :

```text
producerUnitPrice
appliedMarginRate
hodinaMarginAmount
```

Ces champs figent les valeurs économiques au moment du checkout.

Rôles :

```text
producerUnitPrice
→ prix producteur unitaire au moment de la commande

appliedMarginRate
→ taux de marge réellement utilisé au moment de la commande

hodinaMarginAmount
→ marge Hodina unitaire au moment de la commande

unitPrice
→ prix client unitaire figé au moment de la commande

lineTotal
→ total ligne figé selon unitPrice × quantity
```

### ProductPricingService

Méthodes livrées :

```text
getProducerPrice(Product $product)
getEffectiveMarginRate(Product $product)
getGlobalMarginRate()
getCustomerPrice(Product $product)
getHodinaMarginAmount(Product $product)
getPriceBreakdown(Product $product)
```

### CartService

Le panier n'utilise plus directement `Product.price` comme prix client.
Il appelle `ProductPricingService::getPriceBreakdown()` et expose :

```text
unitPrice
producerUnitPrice
appliedMarginRate
hodinaMarginAmount
lineTotal
```

### CheckoutController

Au checkout, les prix sont recalculés et figés dans `OrderItem`.

## Migration Doctrine

Migration :

```text
migrations/Version20260607120000.php
```

Colonnes ajoutées :

```text
product.producer_price
product.margin_rate
seller.margin_rate
order_item.producer_unit_price
order_item.applied_margin_rate
order_item.hodina_margin_amount
```

Initialisation :

```text
UPDATE product SET producer_price = price WHERE producer_price IS NULL
```

Création du réglage :

```text
global_margin_rate = 20.00
```

La migration est défensive : elle vérifie l'existence des colonnes avant ajout.

## Tests locaux validés

```powershell
php -l src\Service\ProductPricingService.php
php -l migrations\Version20260607120000.php
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:container
```

Résultats :

```text
No syntax errors detected
Migration Version20260607120000 exécutée
Mapping Doctrine OK
Database schema in sync OK
Container Symfony OK
```

Tests fonctionnels :

- [x] `global_margin_rate = 20.00` visible dans Réglages Hodina ;
- [x] prix producteur 10,00 € affiché ;
- [x] marge globale 20 % appliquée ;
- [x] prix catalogue 12,00 € ;
- [x] panier utilise le prix client calculé ;
- [x] checkout crée une commande ;
- [x] anciennes commandes inchangées ;
- [x] nouvelle commande avec prix figés.

## Déploiement préproduction

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultats :

```text
git pull OK
composer install OK
Migration DoctrineMigrations\Version20260607120000 OK
Mapping Doctrine OK
Database schema in sync OK
Cache prod clear OK
Cache prod warmup OK
```

## Tests préproduction validés

- [x] Réglage global `global_margin_rate = 20.00` présent ;
- [x] produit avec prix producteur 10,00 € ;
- [x] catalogue affiche 12,00 € ;
- [x] panier fonctionne ;
- [x] checkout fonctionne ;
- [x] anciennes commandes inchangées ;
- [x] nouvelle commande fige les valeurs économiques ;
- [x] tests recette bons.


## Historique des incidents J5E corrigés

J5E a été validé, mais l'historique des incidents doit rester dans les documents pour aider le prochain développeur à comprendre la chronologie et les bons réflexes de diagnostic.

### Incident 1 — Migration J5E absente du premier patch

Après application du premier patch J5E, le code contenait déjà les nouveaux champs Doctrine, mais la base n'avait pas encore les colonnes correspondantes. La commande suivante a donc échoué :

```powershell
php bin/console doctrine:schema:validate
```

Erreur observée :

```text
[ERROR] The database schema is not in sync with the current mapping file.
```

Doctrine indiquait être seulement monté jusqu'à :

```text
DoctrineMigrations\Version20260606103000
```

Correction : ajout de la migration J5E :

```text
migrations/Version20260607120000.php
```

### Incident 2 — `ProductPricingService.php` tronqué

Après ajout du correctif migration/service, Symfony ne pouvait plus démarrer.

Erreur observée :

```text
ParseError: Unclosed '{' on line 78
File: src/Service/ProductPricingService.php
```

Cause : le fichier `ProductPricingService.php` était incomplet. La méthode `getPriceBreakdown()` avait été commencée, mais la fin du service manquait.

Correction : compléter le service avec :

```text
getPriceBreakdown()
money()
percent()
fermeture de la classe ProductPricingService
```

Vérification :

```powershell
php -l src\Service\ProductPricingService.php
```

Résultat :

```text
No syntax errors detected in src\Service\ProductPricingService.php
```

### Incident 3 — Migration J5E tronquée

Après réparation du service, la migration a échoué.

Erreur observée :

```text
In Version20260607120000.php line 94:
Unclosed '{' on line 11
```

Cause : la migration `Version20260607120000.php` était tronquée. Il manquait la méthode utilitaire :

```php
private function columnExists(string $tableName, string $columnName): bool
```

et la fermeture finale de la classe.

Correction : réparation complète de la migration.

Vérification :

```powershell
php -l migrations\Version20260607120000.php
```

Résultat :

```text
No syntax errors detected in migrations\Version20260607120000.php
```

### Validation finale locale

Commandes validées :

```powershell
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:container
```

Résultat :

```text
Migration Version20260607120000 exécutée
Mapping Doctrine OK
Database schema in sync OK
Container Symfony OK
```

### Validation finale préproduction

Commandes validées sur o2switch :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultat :

```text
Migration DoctrineMigrations\Version20260607120000 exécutée
Mapping Doctrine OK
Database schema in sync OK
Cache prod clear OK
Cache prod warmup OK
Tests recette bons
```

### Point pédagogique

Si `doctrine:schema:validate` échoue après un patch Doctrine, vérifier dans cet ordre :

```text
1. La migration existe-t-elle ?
2. La migration est-elle syntaxiquement valide ?
3. La migration a-t-elle été exécutée ?
4. doctrine:schema:update --dump-sql propose-t-il quelque chose ?
5. Après cache clear / warmup, schema:validate repasse-t-il au vert ?
```

Ne jamais utiliser `doctrine:schema:update --force` en préproduction sans comprendre l'écart.


## Décision de clôture

J5E est terminé et validé.

Règle métier à conserver :

```text
Le vendeur saisit son prix producteur.
Hodina calcule le prix client.
La commande fige les prix au moment du checkout.
```

## Suite

Prochaine étape :

```text
J5F — communes, zones tarifaires, barge et rémunération livreur
```

J5F devra suivre la même logique que J5E :

```text
règles métier dans des services
paramètres administrables
valeurs figées dans la commande
pas de calcul fragile dans Twig
pas de duplication dans les contrôleurs
```

---

# Note postérieure — Clarification J5F barge

Avant application de J5F-A, la règle de barge a été précisée :

```text
PT → PT = pas de barge
GT → GT = pas de barge
PT → GT = barge
GT → PT = barge
```

Cette précision devra guider `DeliveryCommune`, `DeliveryPricingZone`, `Seller.deliveryCommune` et surtout le futur `DeliveryLogisticsService`.

Les communes voisines ne déclenchent pas la barge. Elles servent uniquement à qualifier le message logistique.


---

# Note postérieure — J5F-A / J5F-B livrés après la marge produit

Après J5E, la suite logistique a avancé :

```text
J5F-A → DeliveryPricingZone, DeliveryCommune, Seller.deliveryCommune, CRUD EasyAdmin
J5F-B → DeliveryLogisticsService, CartLogisticsPreview
```

Le principe est le même que pour `ProductPricingService` : les règles métier doivent être centralisées dans un service.

`ProductPricingService` gère les prix produit.

`DeliveryLogisticsService` gère la relation commune client / vendeur, la barge et la zone tarifaire.

---

# Note postérieure — séparation marge produit et marge livraison

J5E a stabilisé la marge produit.

J5G avancé ajoute une logique similaire côté livraison.

Il faut garder deux calculs séparés :

```text
marge produit Hodina
→ calculée dans ProductPricingService
→ figée dans OrderItem

marge livraison Hodina
→ calculée dans DeliveryLogisticsService
→ à figer dans CustomerOrder
```

Les deux marges ne doivent pas être mélangées.
