# Déploiement préproduction Hodina — recette.hodina.fr

## État au 05/06/2026

Préproduction ciblée :

```text
https://recette.hodina.fr
```

Serveur o2switch :

```text
/home/vopu3712/recette.hodina.fr
```

Document root :

```text
/home/vopu3712/recette.hodina.fr/public
```

## Protection

La préprod est protégée par Basic Auth.

Fichiers :

```text
/home/vopu3712/recette.hodina.fr/public/.htaccess
/home/vopu3712/recette.hodina.fr/.htpasswd
```

Le `.htpasswd` doit rester hors `public`.

## Ordre attendu dans `.htaccess`

```text
1. redirection HTTPS
2. Basic Auth
3. règles Symfony
```

Test attendu :

```bash
curl -I http://recette.hodina.fr
# doit retourner 301 vers https://recette.hodina.fr/

curl -I https://recette.hodina.fr
# doit retourner 401 avec WWW-Authenticate tant que non authentifié
```

## Base de données

Base recette :

```text
vopu3712_hodina_recette
```

Utilisateur :

```text
vopu3712_hodina_recette_user
```

Important : encoder les caractères spéciaux dans `DATABASE_URL`.

Exemple :

```text
@ → %40
```

## Dump dev vers recette

Le dump doit être en UTF-8.

À éviter : dump ou fichier réécrit en UTF-16 via PowerShell.

Commande conseillée depuis Windows :

```powershell
cmd /c "mysqldump -u root -p --single-transaction --routines --triggers --events --default-character-set=utf8mb4 hodina_db > E:\hodina\db_dumps\hodina_dev_to_recette.sql"
```

Puis compresser si besoin :

```powershell
Compress-Archive -Path E:\hodina\db_dumps\hodina_dev_to_recette.sql -DestinationPath E:\hodina\db_dumps\hodina_dev_to_recette.sql.zip -Force
```

## Commandes Symfony utiles sur o2switch

```bash
cd /home/vopu3712/recette.hodina.fr
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console dbal:run-sql "SELECT COUNT(*) AS total FROM customer;" --env=prod
```

Si la version PHP par défaut n'est pas la bonne :

```bash
/opt/cpanel/ea-php82/root/usr/bin/php bin/console cache:clear --env=prod
```

## Checklist minimale — TOUJOURS en premier, avant toute checklist spécifique à un lot

Règle ajoutée le 2026-07-08. Objectif : vérifier que le client peut commander et utiliser le site sans friction, et que l'admin/le livreur peuvent utiliser leurs portails, **avant** de dérouler les tests spécifiques au lot déployé. Si un point échoue, ne pas continuer sur le reste des tests — c'est prioritaire sur tout, y compris sur la checklist du lot en cours.

- [ ] HTTPS valide sur mobile et desktop.
- [ ] HTTP redirige vers HTTPS.
- [ ] Basic Auth fonctionne (recette uniquement).
- [ ] Catalogue accessible (accueil + fiche produit, images chargent).
- [ ] Inscription nouveau client fonctionne (nom obligatoire).
- [ ] **Connexion d'un client existant (créé avant ce déploiement) fonctionne.** Le test le plus important après un lot qui touche `Customer`, la sécurité ou EasyAdmin : un souci de mapping, de firewall ou de `UserChecker` peut bloquer tout le monde, pas seulement le cas visé par le lot.
- [ ] Checkout exige CGU/CGV, CGU/CGV lisibles sur mobile.
- [ ] Panier → checkout → commande validée, de bout en bout (livraison + frais calculés).
- [ ] Commande invité, si le site l'autorise.
- [ ] Statut commande + SMS fonctionne (SmsLog ouvre Messages iPhone).
- [ ] Mot de passe oublié génère un SmsLog, reset password fonctionne.
- [ ] Backoffice `/ouegnewe` accessible aux admins.
- [ ] Portail livreur `/djama` accessible aux livreurs, prise en charge d'une commande OK.
- [ ] Footer public ne contient pas le lien Admin.

Seulement après ces points validés : dérouler la checklist spécifique au lot déployé (section correspondante plus bas dans ce document, ou le `README_MAJ_*` du lot).


---

# Mise à jour préproduction — J5C du 06/06/2026

## Objet

Déployer les données livraison et la préparation du dashboard livreur sur `recette.hodina.fr`.

## Déploiement réalisé

Depuis :

```bash
cd /home/vopu3712/recette.hodina.fr
```

Mise à jour :

```bash
git pull
```

Changements récupérés :

```text
config/packages/security.yaml
migrations/Version20260606091936.php
migrations/Version20260606101500.php
migrations/Version20260606103000.php
src/Controller/Admin/CustomerOrderCrudController.php
src/Entity/CustomerOrder.php
src/Service/CustomerOrderWorkflowService.php
```

## Incident de commande

Une faute de frappe a été faite :

```bash
hp bin/console doctrine:migrations:migrate --env=prod
```

au lieu de :

```bash
php bin/console doctrine:migrations:migrate --env=prod
```

Conséquence : les fichiers étaient déployés, mais les migrations n'étaient pas encore exécutées.

## Incident migration d'index

Lors de la première exécution des migrations, la migration `Version20260606091936` a échoué car elle cherchait à renommer un index qui n'existait pas encore en préproduction.

Erreur :

```text
Key 'idx_3cf0a31e4b1e148f' doesn't exist in table 'customer_order'
```

Cause : timestamp de migration incorrect.

Correction :

- migration `Version20260606091936` rendue no-op ;
- migration `Version20260606103000` ajoutée avec vérification conditionnelle des index.

## Migration finale validée

Après correction :

```bash
php bin/console doctrine:migrations:migrate --env=prod
```

Résultat :

```text
[OK] Successfully migrated to version: DoctrineMigrations\Version20260606103000
```

Validation :

```bash
php bin/console doctrine:schema:validate --env=prod
```

Résultat :

```text
[OK] The mapping files are correct.
[OK] The database schema is in sync with the mapping files.
```

Cache :

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultat : OK.

## Test préprod J5C

Dans EasyAdmin, détail commande :

```text
Livreur assigné      Null
Livreur assigné le   Null
Départ livraison le  Null
```

État attendu avant la création du dashboard livreur.

## Règle de déploiement maintenue

Pour la suite :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## Points d'attention toujours ouverts

- alerte navigateur `Non sécurisé` à diagnostiquer ;
- dépréciation DoctrineBundle `controller_resolver.auto_mapping` ;
- dépréciation EasyAdmin `#[AdminDashboard]` ;
- dépréciation Doctrine Migrations sur transactions implicites.


---

# Mise à jour préproduction — J5D du 06/06/2026

## Objet

Déployer le dashboard livreur `/djama` et la sélection améliorée des rôles dans EasyAdmin.

## Déploiement réalisé

Depuis :

```bash
cd /home/vopu3712/recette.hodina.fr
```

Commandes exécutées :

```bash
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:schema:validate --env=prod
```

Résultats :

- `git pull` OK ;
- dépendances Composer déjà à jour ;
- migrations déjà à jour ;
- cache prod clear OK ;
- cache prod warmup OK ;
- mapping Doctrine OK ;
- base synchronisée OK.

## Incident mineur Git

Lors du premier `git pull`, plusieurs commandes avaient été collées accidentellement dans le champ mot de passe GitHub.

Résultat :

```text
Invalid username or token
```

Correction : relancer `git pull` et saisir uniquement l'identifiant et le token / mot de passe attendu.

## Tests préproduction J5D

Validé :

- utilisateur non connecté → accès `/djama` refusé ou redirection ;
- utilisateur sans `ROLE_COURIER` → accès refusé ;
- utilisateur avec `ROLE_COURIER` → dashboard accessible ;
- commande `READY_FOR_PICKUP` visible dans les commandes prêtes ;
- prise en charge OK ;
- passage `OUT_FOR_DELIVERY` OK ;
- affichage dans `Mes livraisons en cours` OK ;
- marquage livré OK ;
- passage `DELIVERED` OK.

## Point de vigilance test

Une commande doit être marquée prête côté admin avant d'apparaître côté livreur.

```text
Admin → Marquer prête
→ status READY_FOR_PICKUP
→ visible dans /djama
```

Si elle reste en `PREPARING`, `CONFIRMED` ou `DELIVERED`, elle n'apparaît pas dans les commandes à prendre.

---

# Préproduction future J5E / J5F / J5G

Les prochains jalons introduiront des migrations plus sensibles :

- champs de marge ;
- zones tarifaires ;
- communes ;
- communes voisines ;
- champs de snapshot commande.

Règle de déploiement à conserver :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Avant d'appliquer ces jalons en préproduction, vérifier en local :

```powershell
php bin/console cache:clear
php bin/console lint:container
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:migrate
```


---

# Mise à jour préproduction — J5E du 07/06/2026

## Objet

Déployer la marge produit Hodina sur `recette.hodina.fr`.

## Déploiement réalisé

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
```

Le `git pull` a récupéré notamment :

```text
migrations/Version20260607120000.php
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
templates/cart/index.html.twig
templates/product/catalogue.html.twig
templates/product/show.html.twig
```

## Incident mineur — migration annulée en interactif

La première tentative interactive :

```bash
php bin/console doctrine:migrations:migrate --env=prod
```

a abouti à :

```text
[ERROR] Migration cancelled!
```

Correction :

```bash
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

Résultat :

```text
[OK] Successfully migrated to version: DoctrineMigrations\Version20260607120000
```

## Dépréciation Doctrine Migrations observée

Doctrine a affiché une dépréciation liée aux transactions implicites. Elle n'a pas bloqué la migration. À corriger plus tard si nécessaire avec `isTransactional(): false` ou configuration équivalente.

## Diagnostic schema

Une première validation a affiché temporairement un écart, mais :

```bash
php bin/console doctrine:schema:update --dump-sql --env=prod
```

a répondu :

```text
[OK] Nothing to update - your database is already in sync with the current entity metadata.
```

Puis :

```bash
php bin/console doctrine:migrations:status --env=prod
```

a confirmé :

```text
Current = DoctrineMigrations\Version20260607120000
New = 0
Already at latest version
```

Validation finale :

```bash
php bin/console doctrine:schema:validate --env=prod
```

Résultat :

```text
[OK] The mapping files are correct.
[OK] The database schema is in sync with the mapping files.
```

## Cache prod

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultat : OK.

## Tests recette J5E validés

- [x] `global_margin_rate = 20.00` présent dans Réglages Hodina ;
- [x] produit avec prix producteur 10,00 € ;
- [x] marge globale 20 % appliquée ;
- [x] catalogue affiche 12,00 € ;
- [x] panier utilise 12,00 € ;
- [x] checkout fonctionne ;
- [x] anciennes commandes inchangées ;
- [x] nouvelle commande fige les valeurs économiques.

## Commandes de référence après J5E

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Ne pas utiliser `schema:update --force` en préproduction sans validation explicite.

---

# Note préproduction J5F — Clarification barge avant déploiement

Avant de déployer J5F / J5G, vérifier que les données de recette permettent de tester :

```text
Dzaoudzi = PT
Pamandzi = PT
Mamoudzou = GT
```

Tests attendus après implémentation du service logistique :

```text
Dzaoudzi client + Pamandzi vendeur
→ pas de barge

Dzaoudzi client + Mamoudzou vendeur
→ barge

Mamoudzou client + Dzaoudzi vendeur
→ barge
```

Le déploiement J5F-A ne doit pas encore changer le panier ni le checkout. Il doit seulement permettre de paramétrer les communes, les territoires et les zones tarifaires.


---

# Mise à jour préproduction — J5F-A communes et zones tarifaires

## Objet

Déployer le socle J5F-A sur `recette.hodina.fr`.

## Déploiement réalisé

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Migrations exécutées :

```text
Version20260607170000
Version20260607173000
```

Résultat final :

```text
Mapping OK
Database schema in sync OK
Cache prod OK
```

## Incident schema temporaire

Après exécution des migrations, `schema:validate` a d'abord affiché un écart.

Après `cache:clear` / `cache:warmup`, la validation est repassée au vert.

Règle maintenue : si l'erreur persiste, diagnostiquer avec :

```bash
php bin/console doctrine:schema:update --dump-sql --env=prod
php bin/console doctrine:migrations:status --env=prod
```

Ne pas utiliser `schema:update --force` sans analyse.

## Jeu de test recette J5F-A

### Zones tarifaires

```bash
php bin/console dbal:run-sql "INSERT INTO delivery_pricing_zone (name, code, customer_delivery_fee, courier_payout, is_active, internal_note, created_at, updated_at) VALUES ('Petite-Terre local', 'PT_LOCAL', 6.00, 5.00, 1, 'Zone locale Petite-Terre pour tests J5F-A', NOW(), NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), customer_delivery_fee = VALUES(customer_delivery_fee), courier_payout = VALUES(courier_payout), is_active = VALUES(is_active), internal_note = VALUES(internal_note), updated_at = NOW();" --env=prod

php bin/console dbal:run-sql "INSERT INTO delivery_pricing_zone (name, code, customer_delivery_fee, courier_payout, is_active, internal_note, created_at, updated_at) VALUES ('Grande-Terre local', 'GT_LOCAL', 6.00, 5.00, 1, 'Zone locale Grande-Terre pour tests J5F-A', NOW(), NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), customer_delivery_fee = VALUES(customer_delivery_fee), courier_payout = VALUES(courier_payout), is_active = VALUES(is_active), internal_note = VALUES(internal_note), updated_at = NOW();" --env=prod
```

### Communes

```bash
php bin/console dbal:run-sql "INSERT INTO delivery_commune (name, territory, is_active, local_pricing_zone_id, barge_pricing_zone_id, internal_note, created_at, updated_at) SELECT 'Dzaoudzi', 'PT', 1, local_zone.id, barge_zone.id, 'Commune Petite-Terre de test J5F-A', NOW(), NOW() FROM delivery_pricing_zone local_zone, delivery_pricing_zone barge_zone WHERE local_zone.code = 'PT_LOCAL' AND barge_zone.code = 'GT_LOCAL' ON DUPLICATE KEY UPDATE territory = VALUES(territory), is_active = VALUES(is_active), local_pricing_zone_id = VALUES(local_pricing_zone_id), barge_pricing_zone_id = VALUES(barge_pricing_zone_id), internal_note = VALUES(internal_note), updated_at = NOW();" --env=prod

php bin/console dbal:run-sql "INSERT INTO delivery_commune (name, territory, is_active, local_pricing_zone_id, barge_pricing_zone_id, internal_note, created_at, updated_at) SELECT 'Labattoir', 'PT', 1, local_zone.id, barge_zone.id, 'Commune Petite-Terre de test J5F-A', NOW(), NOW() FROM delivery_pricing_zone local_zone, delivery_pricing_zone barge_zone WHERE local_zone.code = 'PT_LOCAL' AND barge_zone.code = 'GT_LOCAL' ON DUPLICATE KEY UPDATE territory = VALUES(territory), is_active = VALUES(is_active), local_pricing_zone_id = VALUES(local_pricing_zone_id), barge_pricing_zone_id = VALUES(barge_pricing_zone_id), internal_note = VALUES(internal_note), updated_at = NOW();" --env=prod

php bin/console dbal:run-sql "INSERT INTO delivery_commune (name, territory, is_active, local_pricing_zone_id, barge_pricing_zone_id, internal_note, created_at, updated_at) SELECT 'Mamoudzou', 'GT', 1, local_zone.id, barge_zone.id, 'Commune Grande-Terre de test J5F-A', NOW(), NOW() FROM delivery_pricing_zone local_zone, delivery_pricing_zone barge_zone WHERE local_zone.code = 'GT_LOCAL' AND barge_zone.code = 'PT_LOCAL' ON DUPLICATE KEY UPDATE territory = VALUES(territory), is_active = VALUES(is_active), local_pricing_zone_id = VALUES(local_pricing_zone_id), barge_pricing_zone_id = VALUES(barge_pricing_zone_id), internal_note = VALUES(internal_note), updated_at = NOW();" --env=prod
```

### Voisinage

```bash
php bin/console dbal:run-sql "INSERT IGNORE INTO delivery_commune_neighbor (commune_id, neighbor_id) SELECT c1.id, c2.id FROM delivery_commune c1, delivery_commune c2 WHERE c1.name = 'Dzaoudzi' AND c2.name = 'Labattoir';" --env=prod

php bin/console dbal:run-sql "INSERT IGNORE INTO delivery_commune_neighbor (commune_id, neighbor_id) SELECT c1.id, c2.id FROM delivery_commune c1, delivery_commune c2 WHERE c1.name = 'Labattoir' AND c2.name = 'Dzaoudzi';" --env=prod
```

### Vendeur

```bash
php bin/console dbal:run-sql "UPDATE seller s JOIN delivery_commune c ON c.name = 'Mamoudzou' SET s.delivery_commune_id = c.id WHERE LOWER(s.name) = 'ferme houmadi';" --env=prod
```

## Vérifications validées

```bash
php bin/console dbal:run-sql "SELECT code, name, customer_delivery_fee, courier_payout, (customer_delivery_fee - courier_payout) AS delivery_margin, is_active FROM delivery_pricing_zone ORDER BY code;" --env=prod

php bin/console dbal:run-sql "SELECT c.name, c.territory, local_zone.code AS local_zone, barge_zone.code AS barge_zone, c.is_active FROM delivery_commune c LEFT JOIN delivery_pricing_zone local_zone ON local_zone.id = c.local_pricing_zone_id LEFT JOIN delivery_pricing_zone barge_zone ON barge_zone.id = c.barge_pricing_zone_id ORDER BY c.name;" --env=prod

php bin/console dbal:run-sql "SELECT c.name AS commune, n.name AS neighbor FROM delivery_commune_neighbor cn JOIN delivery_commune c ON c.id = cn.commune_id JOIN delivery_commune n ON n.id = cn.neighbor_id ORDER BY c.name, n.name;" --env=prod

php bin/console dbal:run-sql "SELECT s.name AS seller, c.name AS delivery_commune, c.territory FROM seller s LEFT JOIN delivery_commune c ON c.id = s.delivery_commune_id ORDER BY s.name;" --env=prod
```

Résultat attendu :

```text
GT_LOCAL / PT_LOCAL actifs
Dzaoudzi PT
Labattoir PT
Mamoudzou GT
Dzaoudzi ↔ Labattoir
ferme houmadi → Mamoudzou GT
```

## Incident SQL corrigé

Une première commande utilisait `active`. La colonne réelle était `is_active`.

Commande de diagnostic utile :

```bash
php bin/console dbal:run-sql "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_pricing_zone' ORDER BY ORDINAL_POSITION;" --env=prod
```

## Nettoyage Git serveur

Après déploiement :

```bash
git restore vendor
git update-index --skip-worktree .env.local
rm -f git_status.logs git_statu.logs 1
git status
```

Résultat validé :

```text
rien à valider, la copie de travail est propre
```


---

# Mise à jour préproduction — J5F-B DeliveryLogisticsService

## Objet

Déployer le service logistique J5F-B sur `recette.hodina.fr`.

## Déploiement réalisé

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

Fichiers récupérés :

```text
src/Dto/CartLogisticsPreview.php
src/Service/DeliveryLogisticsService.php
```

Aucune migration n'est nécessaire pour J5F-B.

## Résultat

```text
git pull OK
cache prod clear OK
cache prod warmup OK
lint:container prod OK
```

Les dépréciations connues ne bloquent pas :

```text
doctrine.orm.controller_resolver.auto_mapping
EasyAdmin #[AdminDashboard]
```

## Nettoyage Git serveur

Après déploiement, le serveur recette a été remis propre :

```bash
git restore vendor
git update-index --skip-worktree .env.local
rm -f git_status.logs git_statu.logs 1
git status
```

Résultat :

```text
Sur la branche pilot/j5-order-delivery-pricing
Votre branche est à jour avec 'origin/pilot/j5-order-delivery-pricing'.

rien à valider, la copie de travail est propre
```


---

# Mise à jour préproduction — navigation header Admin

## Objet

Déployer la correction du header : lien `Admin` visible uniquement pour `ROLE_ADMIN`, lien `Livreur` visible pour `ROLE_COURIER` seulement si l'utilisateur n'est pas admin.

## Déploiement attendu

Aucune migration.

Commandes :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## Règle fonctionnelle

```text
ROLE_ADMIN → Admin
ROLE_COURIER seul → Livreur
sinon → Devenir vendeur
```

---

# Mise à jour préproduction — J5G-A et préparation livraison avancée

## J5G-A — Déploiement attendu

J5G-A ne contient pas de migration.

Déploiement type :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

## Tests recette J5G-A

À vérifier :

```text
client avec adresse Dzaoudzi + vendeur Mamoudzou
→ barge détectée

ajout deuxième produit du même vendeur
→ frais inchangés

ajout produit d'un nouveau vendeur
→ recalcul

modification quantité seulement
→ total produits change mais frais livraison inchangés

suppression dernier produit d'un vendeur
→ recalcul
```

## Attention sur les tarifs de test

Si `PT_LOCAL` et `GT_LOCAL` ont les mêmes montants, l'écran peut afficher les mêmes frais même si la barge est détectée.

Ce n'est pas forcément une erreur de code.

Vérifier les données :

```bash
php bin/console dbal:run-sql "SELECT code, name, customer_delivery_fee, courier_payout, is_active FROM delivery_pricing_zone ORDER BY code;" --env=prod
```

## Préparation J5G-B / J5G-C

Avant de tester les frais avancés, il faudra :

```text
définir les communes voisines
définir les réglages Hodina de supplément par commune
définir les réglages Hodina de barge aller-retour
vérifier que chaque vendeur a une commune logistique
```

## Nettoyage serveur après déploiement

Commande de référence :

```bash
git restore vendor
git update-index --skip-worktree .env.local
rm -f git_status.logs git_statu.logs 1
git status
```


---

# Préproduction future — J5G-B source communes et voisinage

## Objet

Déployer plus tard le modèle base modifiable issu de la source :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

## Règle de déploiement

Cette étape aura probablement une migration Doctrine et un seed.

Commandes attendues :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## Contrôles SQL à prévoir

Après seed :

```bash
php bin/console dbal:run-sql "SELECT name, territory, postal_code, insee_code, parent_insee_code, is_active FROM delivery_commune ORDER BY name;" --env=prod

php bin/console dbal:run-sql "SELECT fc.name AS from_commune, tc.name AS to_commune, c.link_type, c.is_bidirectional, c.is_active FROM delivery_commune_connection c JOIN delivery_commune fc ON fc.id = c.from_commune_id JOIN delivery_commune tc ON tc.id = c.to_commune_id ORDER BY fc.name, tc.name;" --env=prod
```

Ces noms de table / colonnes devront être adaptés au nom exact retenu dans la migration.

## Tests recette à prévoir

```text
Dzaoudzi → Labattoir = LAND
Dzaoudzi → Mamoudzou = BARGE
Mamoudzou → Dzaoudzi → Labattoir = chemin valide
Mamoudzou → Koungou = LAND
```

## Prudence

Ne pas supprimer l'ancien voisinage ManyToMany tant que le nouveau modèle n'est pas validé.

Approche recommandée :

```text
ajouter le nouveau modèle
seed
EasyAdmin
tests
migration de bascule éventuelle plus tard
```

---

# Déploiement recette — historique J5G-B2 / J5G-B3

## J5G-B2 — modèle Doctrine modifiable

Déploiement réalisé sur :

```text
/home/vopu3712/recette.hodina.fr
```

Migrations concernées :

```text
Version20260607213000
Version20260607214500
```

Commandes validées :

```bash
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

Incident conservé : une faute de frappe `hp` au lieu de `php` a empêché la migration de s'exécuter au premier passage. Le `schema:validate` rouge était donc attendu. Correction : relancer avec `php`.

## J5G-B3 — seed communes / liaisons

Migration concernée :

```text
Version20260607220000
```

Validation recette :

```text
18 points logistiques présents
23 liaisons présentes
schema:validate OK
cache OK
lint:container OK
git status propre
```

Requêtes de vérification utilisées :

```bash
php bin/console dbal:run-sql "SELECT slug, name, postal_code, insee_code, parent_insee_code, territory, is_logistics_point, is_active FROM delivery_commune ORDER BY territory DESC, name;" --env=prod

php bin/console dbal:run-sql "SELECT fc.slug AS depart, tc.slug AS arrivee, c.link_type, c.is_bidirectional, c.hop_count, c.is_active FROM delivery_commune_connection c JOIN delivery_commune fc ON fc.id = c.from_commune_id JOIN delivery_commune tc ON tc.id = c.to_commune_id ORDER BY c.link_type, fc.slug, tc.slug;" --env=prod
```

Nettoyage final réalisé :

```bash
git restore vendor
git update-index --skip-worktree .env.local
rm -f git_status.logs git_statu.logs 1
git status
```

Résultat final :

```text
copie de travail recette propre
```

## Dépréciations connues non bloquantes

Les messages suivants restent connus :

```text
doctrine.orm.controller_resolver.auto_mapping déprécié
DashboardController sans #[AdminDashboard] déprécié EasyAdmin 5
```

Décision : ne pas les corriger dans J5G-B3. Prévoir un lot technique séparé pour ne pas mélanger dette technique et logique métier livraison.

## Déploiement futur — support adresses livraison / facturation

Ce support n'est pas encore prêt à déployer tant que les tests locaux inscription + checkout + facturation AUTRE ne sont pas terminés.

### Commandes prévues après validation locale

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

### Vérifications SQL à faire après déploiement

```bash
php bin/console dbal:run-sql "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'address' ORDER BY ORDINAL_POSITION;" --env=prod

php bin/console dbal:run-sql "SELECT code, name, is_active FROM delivery_zone ORDER BY code;" --env=prod
```

Résultats attendus :

```text
address.type présent
DeliveryZone AUTRE — Autre présente et active
schema:validate OK
```

### Prudence

Ne pas déployer tant que le local n'a pas validé :

```text
EasyAdmin utilisateur
inscription
checkout
facturation AUTRE
```

---

# Déploiement futur — support adresses validé localement le 12/06/2026

## Statut

```text
Support adresses : validé localement
Déploiement recette : à faire après commit propre
```

## Préparation locale avant push

```powershell
php -l src\Entity\Address.php
php -l src\Entity\Customer.php
php -l src\Service\DeliveryCommuneMatcherService.php
php -l src\Validator\DeliverableAddressValidator.php
php -l src\Controller\CheckoutController.php
php -l src\Controller\RegistrationController.php
php -l src\Form\CheckoutType.php
php -l src\Form\RegistrationFormType.php

php bin/console cache:clear
php bin/console doctrine:schema:validate
php bin/console lint:container
git diff --check
```

Vérifier également :

```powershell
Test-Path .\public\index.php
```

Si `False`, restaurer :

```powershell
git restore public/index.php
```

## Incident Avast

Avast a mis `public/index.php` en quarantaine. Cela a provoqué :

```text
GET / - No such file or directory
```

Action correcte :

```text
restaurer depuis Git
ajouter une exception Avast ciblée
ne pas recréer le fichier à la main
```

## Commandes recette après push

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
```

## Vérifications SQL recette

```bash
php bin/console dbal:run-sql "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'address' ORDER BY ORDINAL_POSITION;" --env=prod

php bin/console dbal:run-sql "SELECT code, name, is_active FROM delivery_zone ORDER BY code;" --env=prod

php bin/console dbal:run-sql "SELECT a.id, a.type, a.line1, a.postal_code, a.commune, dz.code AS zone_code FROM address a JOIN delivery_zone dz ON dz.id = a.delivery_zone_id ORDER BY a.id DESC LIMIT 10;" --env=prod
```

## Tests recette minimum

```text
EasyAdmin : livraison Labattoir / 97615 / PT → OK
EasyAdmin : livraison Labattoir / 97619 / PT → KO
EasyAdmin : facturation Rennes / 35000 / AUTRE → OK
Checkout invité : e-mail existant → KO
Checkout invité : e-mail nouveau + livraison valide + facturation AUTRE → OK
Inscription : e-mail existant → KO unique
Inscription : e-mail nouveau + adresse valide → OK
```

## Dépréciations connues

Les dépréciations DoctrineBundle / EasyAdmin restent non bloquantes et ne doivent pas être mélangées à ce déploiement fonctionnel.

---

# Préparation recette / production — 13/06/2026 — préouverture et e-mails

## Décision de déploiement

La prochaine mise en recette puis production devra attendre la fonctionnalité de préouverture commerciale.

Jalon attendu avant production :

```text
bannière compte à rebours active
paramétrage EasyAdmin opérationnel
capture e-mail fonctionnelle
blocage panier avant ouverture
blocage checkout avant ouverture
```

## Variables d'environnement e-mail futures

Avant J5H-A, préparer les variables sans commiter de secret :

```env
MAILER_DSN=smtps://...
MAILER_FROM=commandes@hodina.fr
MAILER_FROM_NAME="Hodina"
```

## Tests recette J5I

```text
EasyAdmin : modifier la date d'ouverture
Front : bannière visible
Front : chrono défile
Front : e-mail préouverture enregistrable
Catalogue : visible
Ajouter au panier : désactivé avant ouverture
URL directe ajout panier : refusée
Checkout : refusé avant ouverture
Date passée : panier réactivé automatiquement
```

La production ne doit pas être mise à jour si le blocage panier/commande avant ouverture n'est pas validé côté serveur.


---

# Historique exact recette — 13/06/2026 — J5I préouverture

## Branche déployée

```bash
cd ~/recette.hodina.fr
git fetch origin
git checkout pilot/j5i-preouverture-countdown
git pull
git log --oneline -1
```

Commit déployé :

```text
5bf3e0e feat: add J5I sales opening countdown and launch email capture
```

URL admin réelle recette :

```text
https://recette.hodina.fr/ouegnewe
```

## Correction `.htaccess` Basic Auth / HTTPS / 401.shtml

Problème : après le Basic Auth, le navigateur pouvait être envoyé vers :

```text
https://recette.hodina.fr/401.shtml
```

Correction retenue dans `public/.htaccess` :

```apache
AuthType Basic
AuthName "Hodina Recette"
AuthUserFile /home/vopu3712/recette.hodina.fr/.htpasswd
Require valid-user

ErrorDocument 401 "Authentification requise"

<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{HTTPS} !=on
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

    RewriteRule ^401\.shtml$ - [L]
    RewriteRule ^403\.shtml$ - [L]
    RewriteRule ^404\.shtml$ - [L]
    RewriteRule ^500\.shtml$ - [L]

    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

Test serveur :

```bash
curl -I https://recette.hodina.fr/
```

Résultat attendu sans identifiants Basic Auth :

```text
HTTP/2 401
www-authenticate: Basic realm="Hodina Recette"
```

Ce 401 est normal. Ce qui ne doit plus arriver : redirection vers `/401.shtml` après authentification correcte.

## Migration J5I — problème rencontré

État avant migration :

```text
Executed migrations : 23
New migrations      : 3
Next                : Version20260607225500
Latest              : Version20260613110000
```

Commande lancée :

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Erreur rencontrée :

```text
SQLSTATE[42S02]: Base table or view not found: 1146
Table 'vopu3712_hodina_recette.launch_subscriber' doesn't exist
```

Cause : la migration `Version20260613094055` modifie `launch_subscriber.created_at` avant que `Version20260613110000` ne crée la table.

## Contournement recette appliqué

```bash
php bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260613094055' --add --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:update --dump-sql
php bin/console dbal:run-sql "ALTER TABLE launch_subscriber CHANGE created_at created_at DATETIME NOT NULL"
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
```

Résultat final :

```text
[OK] The mapping files are correct.
[OK] The database schema is in sync with the mapping files.
```

## Dépréciations vues pendant le cache clear

Messages non bloquants :

```text
doctrine.orm.controller_resolver.auto_mapping deprecated
EasyAdmin DashboardController sans #[AdminDashboard] deprecated
```

Décision : ne pas mélanger ces corrections avec J5I. Elles seront traitées dans un jalon technique séparé.

## Injection des paramètres dev en recette

Avant injection, sauvegarder :

```bash
php bin/console dbal:run-sql "CREATE TABLE hodina_setting_backup_20260613 AS SELECT * FROM hodina_setting"
```

Paramètres vérifiés / injectés :

```text
is_countdown_enabled = 1
sales_opening_at = 2026-06-30 18:00
countdown_title = Votre marché en ligne de produits locaux arrive bientôt
countdown_message = Le catalogue est accessible, mais la prise de commande sera possible à la date officielle. Laisse nous ton e-mail pour être informé de l'ouverture.
countdown_button_label = Me faire signe à l’ouverture
is_email_capture_enabled = 1
is_cart_locked_before_opening = 1
countdown_success_message = Merci, ton e-mail est bien enregistré. On te préviendra pour l’ouverture des commandes.
```

Vérification :

```bash
php bin/console dbal:run-sql "SELECT setting_key, value FROM hodina_setting WHERE setting_key LIKE '%countdown%' OR setting_key LIKE '%opening%' OR setting_key LIKE '%cart_locked%' OR setting_key LIKE '%email_capture%'"
```

## Tests recette validés

```text
Basic Auth + HTTPS : OK
Plus de redirection /401.shtml : OK
Branche J5I déployée : OK
Migrations recette débloquées : OK
Schéma Doctrine synchronisé : OK
Paramètres Hodina injectés : OK
Bannière activable depuis les paramètres : OK
```

## Action obligatoire avant production

Ne pas déployer cette séquence telle quelle en production sans traiter l'ordre de migration `Version20260613094055` / `Version20260613110000`.

---

# Déploiement recette J5J — Mode commerce et ROLE_COMMERCE_TESTER

## Branche recette J5J

```bash
cd ~/recette.hodina.fr
git fetch origin
git checkout pilot/j5j-commerce-mode-role-tester
```

Si `git status` indique que la branche est à jour avec `origin/pilot/j5j-commerce-mode-role-tester`, il n'est pas nécessaire de faire `git pull`.

Commit attendu :

```text
0c2b357 feat: add J5J commerce mode with tester role
```

## Migrations

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
```

Migration attendue :

```text
DoctrineMigrations\Version20260613130000
```

## Vérification des paramètres

```bash
php bin/console dbal:run-sql "SELECT setting_key, value, field_type FROM hodina_setting ORDER BY setting_key"
```

Valeurs recette validées :

```text
commerce_mode = preopening
commerce_cart_locked = 1
commerce_allow_testers = 1
commerce_email_capture_enabled = 1
commerce_reopens_at = 2026-06-30 18:00
```

## Table backup

Si `schema:update --dump-sql` ne propose que :

```sql
DROP TABLE hodina_setting_backup_20260613;
```

alors l'écart vient seulement d'une table de sauvegarde manuelle. Elle peut être conservée pendant les tests puis supprimée après validation.

## Tests recette J5J

```text
1. public non testeur : panier bloqué ;
2. client ROLE_COMMERCE_TESTER : commande autorisée ;
3. commerce_mode = open : aucune bannière ni chrono ;
4. EasyAdmin : switchs pour les booléens ;
5. EasyAdmin : liste de choix pour commerce_mode ;
6. EasyAdmin client : choix du rôle ROLE_COMMERCE_TESTER.
```
---

# Remise à plat production — 14/15 juin 2026

## Contexte

La production historique n'était pas iso préproduction. Le domaine `hodina.fr` pointait vers la racine du projet `~/hodina.fr` au lieu de pointer vers `~/hodina.fr/public`. Le dossier de production n'était pas non plus un dépôt Git exploitable : `git status` retournait que le dossier n'était pas un dépôt.

Cette situation exposait un risque structurel : une application Symfony doit publier uniquement le dossier `public/`. La racine du projet contient des fichiers sensibles ou techniques (`.env`, `composer.json`, `src/`, `config/`, `migrations/`, `vendor/`) qui ne doivent pas être accessibles via le web.

## Décision prise

La décision retenue a été de ne pas bricoler l'ancienne production. La production a été remise à plat proprement :

```text
- sauvegarde complète de l'ancien dossier production ;
- correction du DocumentRoot o2switch vers /public ;
- remplacement de l'ancien dossier par un vrai clone Git ;
- déploiement de la branche J5J ;
- remplacement de la base production par un dump de recette ;
- nettoyage des données de test ;
- maintien du mode commerce en préouverture ;
- sécurisation HTTPS via public/.htaccess ;
- retrait de .env.local du suivi Git ;
- rotation des mots de passe et secrets après exposition accidentelle dans le terminal.
```

## Résultat validé

```text
https://hodina.fr/ fonctionne en HTTP 200.
http://hodina.fr/ redirige en 301 vers https://hodina.fr/.
http://www.hodina.fr/ redirige en 301 vers https://www.hodina.fr/.
.env.local est présent sur le serveur mais retiré de Git.
Doctrine migrations est à jour.
Doctrine schema validate est OK.
Le mode commerce est configuré en preopening.
Les commandes, items, logs SMS et adresses de test ont été nettoyés.
```

## Commandes et actions réalisées

### Sauvegarde fichiers production

```bash
cd ~
tar -czf backup_hodina_prod_files_$(date +%Y%m%d_%H%M%S).tar.gz hodina.fr
cp ~/hodina.fr/.htaccess ~/backup_htaccess_prod_root_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
cp ~/hodina.fr/public/.htaccess ~/backup_htaccess_prod_public_$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
```

### Correction hébergement o2switch

Le DocumentRoot du domaine `hodina.fr` a été corrigé dans o2switch pour pointer vers :

```text
/home/vopu3712/hodina.fr/public
```

### Ancienne production conservée

```bash
cd ~
mv hodina.fr hodina.fr_old_$(date +%Y%m%d_%H%M%S)
```

Ancien dossier conservé observé :

```text
/home/vopu3712/hodina.fr_old_20260614_071905
```

### Nouveau clone Git production

```bash
git clone https://github.com/chahere/hodina.git hodina.fr
cd hodina.fr
git checkout pilot/j5j-commerce-mode-role-tester
```

### Récupération configuration production

```bash
cp ~/hodina.fr_old_20260614_071905/.env.local ~/hodina.fr/.env.local
cp ~/hodina.fr_old_20260614_071905/public/.htaccess ~/hodina.fr/public/.htaccess 2>/dev/null || true
```

### Installation production

```bash
composer install --no-dev --optimize-autoloader
```

Cette commande a modifié le dossier `vendor/` parce que le dépôt suivait encore certaines dépendances. Pour éviter de polluer les futurs pulls, `vendor/` a ensuite été restauré côté Git avec :

```bash
git restore vendor
```

## Base de données production

### Décision

La base production existante était désalignée avec l'historique Doctrine. Plutôt que de baseliner migration par migration, la décision a été de remplacer la base production par un dump de la recette, puis de nettoyer les données de test.

Cette option était la plus propre car la recette était déjà validée avec J5J.

### Sauvegarde production

Un backup de la base production a été créé avant remplacement :

```text
backup_prod_before_preprod_restore_20260614_073456.sql
```

### Dump recette

Un dump recette a été créé et vérifié :

```text
dump_recette_for_prod_20260614_074824.sql
Taille observée : 161K
```

### Import recette vers production

La base production a été vidée puis alimentée avec le dump recette. Après import, les tables attendues étaient présentes :

```text
address
category
customer
customer_order
customer_signup
delivery_commune
delivery_commune_connection
delivery_commune_neighbor
delivery_pricing_zone
delivery_zone
doctrine_migration_versions
hodina_setting
launch_subscriber
messenger_messages
order_item
product
product_image
seller
sms_log
```

### Correction MariaDB / Doctrine

La version MariaDB production observée est :

```text
11.4.12-MariaDB
```

Le `DATABASE_URL` production a été ajusté pour utiliser :

```text
serverVersion=mariadb-11.4.12&charset=utf8mb4
```

Les mots de passe et secrets ont ensuite été mis à jour. Aucun secret ne doit être stocké dans Git ou dans la documentation.

### Validation Doctrine après import

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --dump-sql
```

Résultat validé :

```text
Migrations exécutées : 27
Version courante : DoctrineMigrations\Version20260613130000
Nouvelle migration : 0
Mapping files are correct.
Database schema is in sync with the mapping files.
Nothing to update.
```

## Mode commerce production

La production a été forcée en mode préouverture :

```bash
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = 'preopening' WHERE setting_key = 'commerce_mode'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_cart_locked'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_allow_testers'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '1' WHERE setting_key = 'commerce_email_capture_enabled'"
php bin/console dbal:run-sql "UPDATE hodina_setting SET value = '2026-06-30 18:00' WHERE setting_key = 'commerce_reopens_at'"
php bin/console cache:clear --env=prod
```

Valeurs validées :

```text
commerce_allow_testers = 1
commerce_cart_locked = 1
commerce_email_capture_enabled = 1
commerce_mode = preopening
commerce_reopens_at = 2026-06-30 18:00
```

## Nettoyage des données de test importées depuis recette

Volumes avant nettoyage :

```text
customer_order = 9
order_item = 16
launch_subscriber = 0
sms_log = 25
```

Nettoyage réalisé :

```bash
php bin/console dbal:run-sql "DELETE FROM sms_log"
php bin/console dbal:run-sql "DELETE FROM order_item"
php bin/console dbal:run-sql "DELETE FROM customer_order"
php bin/console dbal:run-sql "DELETE FROM address"
php bin/console cache:clear --env=prod
```

Résultat validé :

```text
customer_order = 0
order_item = 0
sms_log = 0
address = 0
```

Les comptes clients n'ont pas été supprimés automatiquement afin de conserver les comptes utiles admin, livreur et testeur.

## HTTPS production

La redirection HTTP vers HTTPS a été ajoutée dans :

```text
public/.htaccess
```

Règle appliquée :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Force HTTPS - o2switch / cPanel
    RewriteCond %{HTTP:X-Forwarded-Proto} !https
    RewriteCond %{HTTPS} !on
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Symfony front controller
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

Tests validés :

```text
http://hodina.fr/      → 301 vers https://hodina.fr/
https://hodina.fr/     → 200
http://www.hodina.fr/  → 301 vers https://www.hodina.fr/
https://www.hodina.fr/ → 200
```

Décision restante : choisir plus tard l'URL canonique entre `hodina.fr` et `www.hodina.fr`. Recommandation actuelle : `https://hodina.fr`.

## Git production

Commit créé depuis la production pour versionner la règle HTTPS et ignorer les fichiers locaux sensibles :

```text
028b7e5 chore: force HTTPS and ignore local production files
```

Actions réalisées :

```bash
git config user.name "chahere"
git config user.email "abdamayot@hotmail.fr"
git add .gitignore public/.htaccess
git commit -m "chore: force HTTPS and ignore local production files"
git push
```

`.env.local` a été retiré du suivi Git mais reste présent sur le serveur. Il doit rester hors dépôt.

Permission recommandée :

```bash
chmod 600 .env.local
```

## État final production

```text
Production remise à plat : OK
Production sous Git : OK
DocumentRoot vers /public : OK
Base production alignée avec recette : OK
Doctrine migrations : OK
Doctrine schema : OK
J5J mode commerce : OK
Mode preopening : OK
HTTPS forcé : OK
Données de test principales nettoyées : OK
Secrets et mots de passe mis à jour : OK
```

## Procédure de déploiement production à partir de maintenant

La production étant désormais un vrai clone Git, les prochains déploiements doivent suivre cette procédure :

```bash
cd ~/hodina.fr
git status --short -- . ':!vendor'
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console cache:clear --env=prod
curl -I http://hodina.fr/
curl -I https://hodina.fr/
```

Important : ne jamais committer `.env.local`, les dumps SQL, les backups `.htaccess`, ni les modifications de `vendor/` générées par `composer install --no-dev`.

---

# Procédure recette validée — J5H-A e-mails

## Déploiement Git

Branche recette utilisée après merge :

```text
pilot/j5j-commerce-mode-role-tester
```

Commits J5H-A intégrés :

```text
911ecac — feat(j5h): add automatic order recap email logging
9dcdf01 — docs(j5h): update order email tracking
47bc28c — feat(j5h): add manual email action from email logs
```

## Attention `.env.local`

Incident rencontré : le `git pull` a été bloqué par `.env.local`.

Procédure validée :

```bash
cp .env.local /home/vopu3712/env.local.recette.backup.$(date +%Y%m%d_%H%M%S)
mv .env.local /home/vopu3712/env.local.recette.current
git pull
cp /home/vopu3712/env.local.recette.current .env.local
chmod 600 .env.local
echo ".env.local" >> .git/info/exclude
```

## Configuration SMTP recette

Dans `.env.local` recette, ne jamais commiter :

```env
MAILER_FROM=contact@hodina.fr
MAILER_FROM_NAME=Hodina
MAILER_DSN=smtps://contact%40hodina.fr:MOT_DE_PASSE_ENCODE@mail.hodina.fr:465
```

Paramètres o2switch validés :

```text
SMTP : mail.hodina.fr
Port : 465
Sécurité : SSL/TLS
Identifiant : contact@hodina.fr
```

## Migration

Migration J5H-A :

```bash
php bin/console doctrine:migrations:migrate --env=prod
```

Version :

```text
DoctrineMigrations\Version20260615140801
```

## Vérifications après pull

```bash
php -l src/Service/OrderEmailService.php
php -l src/Controller/Admin/EmailLogCrudController.php
php bin/console lint:twig templates/emails/order_created.html.twig --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
php bin/console doctrine:schema:validate --env=prod
```

## Cron Messenger recette

Le dossier `var/log` doit exister :

```bash
mkdir -p var/log
chmod 755 var/log
```

Cron validé :

```bash
MAILTO=""
* * * * * cd /home/vopu3712/recette.hodina.fr && mkdir -p var/log && flock -n /tmp/hodina_recette_messenger.lock /usr/local/bin/php bin/console messenger:consume async --env=prod --limit=10 --time-limit=50 --memory-limit=128M --no-interaction >> /home/vopu3712/recette.hodina.fr/var/log/messenger_cron.log 2>&1
```

Validation :

```bash
php bin/console dbal:run-sql --force-fetch "SELECT id, queue_name, created_at, available_at, delivered_at FROM messenger_messages ORDER BY id DESC LIMIT 10"
tail -30 var/log/messenger_cron.log
```

Résultat attendu : `messenger_messages` vide après consommation.

## Table temporaire à nettoyer si besoin

Doctrine peut signaler :

```sql
DROP TABLE messenger_messages_backup_20260615;
```

Cette table est une sauvegarde temporaire, pas une table applicative. Elle peut être supprimée après vérification si elle bloque `schema:validate`.

## Validation recette obtenue

- Commande créée.
- EmailLog créé.
- E-mail reçu.
- Articles affichés.
- Quantités affichées.
- Prix unitaires affichés.
- Frais de livraison affichés.
- Total affiché.
- Cron Messenger validé.
- `git status --short` vide après validation.

---

# Déploiement recette J5G-E0 — Snapshot adresse commande

## Commit déployé

```text
279f49c feat(j5g): snapshot order addresses
```

## Commandes exécutées

```bash
cd /home/vopu3712/recette.hodina.fr
git status --short
git pull --ff-only
php -l src/Entity/CustomerOrder.php
php -l src/Controller/CheckoutController.php
php -l src/Controller/Admin/CustomerOrderCrudController.php
php -l src/Controller/Admin/EmailLogCrudController.php
php -l migrations/Version20260615225836.php
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console lint:container --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:schema:update --dump-sql --env=prod
```

## Incident mineur rencontré

La migration lancée en interactif a été annulée. Elle a été relancée avec `--no-interaction` et exécutée correctement.

Après migration, `doctrine:schema:validate` a d'abord indiqué un écart car le cache prod utilisait encore l'ancien mapping. Après `cache:clear` et `cache:warmup`, le schéma est revenu synchronisé.

## Résultat final

- Migration `Version20260615225836` exécutée.
- Colonnes snapshot présentes dans `customer_order`.
- `doctrine:schema:validate --env=prod` OK.
- `doctrine:schema:update --dump-sql --env=prod` : rien à mettre à jour.
- Test suppression adresse liée à commande OK.

---

# Note recette 16/06/2026 — J5G-E1 cadré, pas encore déployé

J5G-E1 n'est pas encore développé ni déployé. La documentation est mise à jour pour préparer la prochaine discussion.

## Point navigateur Brave

Un `ERR_TOO_MANY_REDIRECTS` a été constaté sur Brave mobile en navigation normale. Le site fonctionnait en navigation privée.

Conclusion opérationnelle :

```text
pas de rollback
pas d'erreur migration détectée
probable cookie/session/redirection navigateur local
nettoyer les cookies du domaine recette.hodina.fr si cela réapparaît
```

## À faire lors du futur déploiement J5G-E1

- vérifier `git status --short` ;
- pull recette ;
- migrations seulement si une migration est créée ;
- `cache:clear --env=prod` ;
- `cache:warmup --env=prod` ;
- `lint:container --env=prod` ;
- `doctrine:schema:validate --env=prod` ;
- test checkout avec commune livrée.

---

# Préproduction — J5G-E1 → J5G-E2-bis-A à déployer

Date documentation : **17/06/2026**
Branche source : `pilot/j5g-e1-commune-livree`

## Statut

```text
Local : OK
GitHub : OK
Recette : à faire
Production : à faire après recette
```

## Déploiement recette recommandé

```bash
cd ~/recette.hodina.fr
git fetch origin
git checkout pilot/j5g-e1-commune-livree
git pull
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console doctrine:schema:validate --env=prod
```

Aucune migration nouvelle n'est attendue pour ce jalon, mais il faut quand même vérifier le schema.

## Tests recette minimum

```text
1. Panier avec vendeur PT, adresse Labattoir : frais PT_LOCAL.
2. Panier avec adresse Mamoudzou : frais GT_LOCAL.
3. Panier avec vendeur sur autre territoire : barge détectée.
4. Coût fixe BARGE ajouté si renseigné.
5. Changement commune : recalcul AJAX visible.
6. Validation commande : total identique au panier.
7. Confirmation : récapitulatif complet.
8. URL confirmation inexistante : 404.
```

## Paramétrage admin à vérifier

```text
DeliveryCommune Labattoir → localPricingZone = PT_LOCAL
DeliveryCommune Mamoudzou → localPricingZone = GT_LOCAL
DeliveryCommuneConnection BARGE → bidirectionnelle = oui
DeliveryCommuneConnection BARGE → supplément client spécifique = coût fixe barge si souhaité
```

---

# Déploiement recette et production — J5G-E1 → J5G-E2-bis-A

Date : **17/06/2026**
Branche finale : `pilot/j5j-commerce-mode-role-tester`
Tag production : `j5g-e1-e2bis-prod`

## Recette

Dossier :

```bash
/home/vopu3712/recette.hodina.fr
```

Commandes exécutées :

```bash
git fetch origin
git checkout pilot/j5g-e1-commune-livree
git pull origin pilot/j5g-e1-commune-livree
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
```

Résultat recette :

```text
Migrations : New = 0
Schema : OK
Tests navigateur : OK
```

Vérification Git : la branche `pilot/j5g-e1-commune-livree` contenait bien l'historique `pilot/j5j-commerce-mode-role-tester` et avait seulement 3 commits d'avance :

```text
7831c1b feat(j5g): simplify delivery commune checkout and secure delivery totals
a70127c feat(j5g): move delivery validation before cart summary
36cc357 docs(j5g): document commune delivery and cart validation flow
```

## Production

Dossier :

```bash
/home/vopu3712/hodina.fr
```

La production était déjà sur :

```text
pilot/j5j-commerce-mode-role-tester
```

Mise à jour :

```bash
git fetch origin
git checkout pilot/j5j-commerce-mode-role-tester
git pull origin pilot/j5j-commerce-mode-role-tester
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
```

Avant migration, `doctrine:migrations:status` indiquait :

```text
Executed : 27
Available : 29
New : 2
Next : DoctrineMigrations\Version20260615140801
Latest : DoctrineMigrations\Version20260615225836
```

Le diagnostic suivant a été utilisé sans modifier la base :

```bash
php bin/console doctrine:schema:update --dump-sql --env=prod
```

Il ne faut pas lancer `schema:update --force` en production.

Migration exécutée :

```bash
php bin/console doctrine:migrations:migrate --env=prod
```

Résultat :

```text
[OK] Successfully migrated to version: DoctrineMigrations\Version20260615225836
```

Après migration :

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Résultat final :

```text
New : 0
Schema : OK
Cache prod : OK
Tests production : OK
```

## Warnings non bloquants observés

```text
doctrine.orm.controller_resolver.auto_mapping deprecated
DashboardController sans #[AdminDashboard] deprecated EasyAdmin 5
Doctrine migrations implicit commit deprecation
```

Ils ne bloquent pas la production. Ils sont à corriger dans un futur jalon technique.

## Tag production

Tag créé et poussé depuis le poste local :

```powershell
git tag j5g-e1-e2bis-prod
git push origin j5g-e1-e2bis-prod
```

## Tests fonctionnels production validés

```text
Accueil OK
Catalogue OK
Ajout panier OK
Panier OK
Livraison avant récapitulatif OK
Changement commune PT / GT OK
Frais recalculés OK
Total affiché cohérent OK
Validation commande OK
Confirmation avec récapitulatif OK
EasyAdmin commande / adresse / zone / total OK
```

# Déploiement J5G-B4 — BFS logistique et snapshot

## Branche à déployer

```text
pilot/j5j-commerce-mode-role-tester
```

Commit attendu :

```text
10ff512 merge(j5g): integrate BFS delivery logistics rules
```

## Commandes recommandées

```powershell
git pull origin pilot/j5j-commerce-mode-role-tester
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:migrations:status --env=prod
```

## Migrations attendues

```text
Version20260617143000
Version20260617150000
Version20260617153000
Version20260617162000
```

## Réglages à vérifier après déploiement

```text
global_commune_crossing_customer_fee
global_commune_crossing_courier_payout
global_delivery_customer_fee_cap
global_multi_seller_extra_customer_fee
global_multi_seller_extra_customer_fee_cap
```

## Tests navigateur obligatoires

```text
panier sans barge
panier avec barge
panier avec plusieurs vendeurs même commune
panier avec plusieurs communes de collecte
commande créée
confirmation commande
admin Commande > Logistique
```

## Nettoyage optionnel commandes avant snapshot

Après backup, pour repartir avec uniquement des commandes snapshotées :

```powershell
php bin/console doctrine:query:sql "DELETE FROM sms_log WHERE customer_order_id IS NOT NULL;"
php bin/console doctrine:query:sql "DELETE FROM email_log WHERE customer_order_id IS NOT NULL;"
php bin/console doctrine:query:sql "DELETE FROM order_item;"
php bin/console doctrine:query:sql "DELETE FROM customer_order;"
```

# Déploiement générique par tag — recette et production

## Décision du 18/06/2026

Après validation recette de J5G-B4, la stratégie de mise en production évolue.

Le déploiement cible doit utiliser :

```text
tools/deploy-hodina-by-tag.sh
```

et non un simple `git pull` sur une branche mouvante.

## Principe

```text
main contient la version validée
tag Git créé depuis main
déploiement recette/prod à partir du tag
```

Le script refuse un tag qui n'est pas contenu dans `origin/main`.

## Préparation SSH GitHub sur o2switch

Générer une clé sur le serveur :

```bash
ssh-keygen -t ed25519 -C "hodina-deploy-o2switch" -f ~/.ssh/hodina_deploy_ed25519
cat ~/.ssh/hodina_deploy_ed25519.pub
```

Ajouter la clé publique dans GitHub :

```text
Repository Hodina
→ Settings
→ Deploy keys
→ Add deploy key
→ Allow write access décoché
```

Configurer SSH :

```bash
nano ~/.ssh/config
```

Contenu :

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/hodina_deploy_ed25519
  IdentitiesOnly yes
```

Tester :

```bash
ssh -T git@github.com
```

Puis passer le remote en SSH :

```bash
git remote set-url origin git@github.com:chahere/hodina.git
git fetch origin
```

## Usage recette

```bash
cd /home/vopu3712/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618   --target recette
```

Avec nettoyage commandes :

```bash
RESET_COMMANDS=1 bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/recette.hodina.fr   --tag j5g-b4-20260618   --target recette
```

## Usage production

```bash
cd /home/vopu3712/hodina.fr
bash tools/deploy-hodina-by-tag.sh   --project-dir /home/vopu3712/hodina.fr   --tag j5g-b4-20260618   --target prod
```

## Cron Messenger

Le script vérifie ou ajoute le cron Messenger.

Recette : lock `/tmp/hodina_recette_messenger.lock`.

Production : lock `/tmp/hodina_prod_messenger.lock`.

## Point critique env local

Le script sauvegarde les fichiers :

```text
.env.local
.env.prod.local
prod.env.local
```

avant le checkout du tag dans :

```text
var/deploy_env_backup/<timestamp>/
```

Puis il les restaure si le checkout les supprime.

Cette protection évite de reproduire l'incident `could not find driver` observé après suppression de `.env.local` pendant le merge vers `main`.

## Mise à jour 18/06/2026 — MEP J5G-B4 par tag v7

La recette et la production utilisent maintenant le même script versionné :

```text
tools/deploy-hodina-by-tag.sh
```

Tag validé :

```text
j5g-b4-20260618-v7
```

### Résultat recette

```text
Projet : /home/vopu3712/recette.hodina.fr
Tag : j5g-b4-20260618-v7
Base : vopu3712_hodina_recette
Backup DB : OK via /bin/mariadb-dump
Migrations : déjà latest Version20260617162000
Cache prod : OK
Doctrine schema : OK
Cron Messenger recette : déjà présent
```

### Résultat production

```text
Projet : /home/vopu3712/hodina.fr
Tag : j5g-b4-20260618-v7
Avant MEP : 36cc357
Déployé : a888a90
Base : vopu3712_hodina_db
Backup DB : OK via /bin/mariadb-dump
Migrations : 29 → 33
Latest : Version20260617162000
Cache prod : OK
Doctrine schema : OK
Cron Messenger prod : ajouté
```

### Procédure de sécurité obligatoire

Ne jamais extraire le script depuis un tag non fetché sans tester que le fichier n'est pas vide :

```bash
git fetch origin main --tags --force
rm -f /tmp/deploy-hodina-by-tag.sh
git show <tag>:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
bash -n /tmp/deploy-hodina-by-tag.sh
```

### Warnings non bloquants observés

- `prod.env.local` est encore suivi par Git.
- Des images dans `public/uploads/products` sont encore suivies par Git.
- EasyAdmin demande à terme l'attribut `#[AdminDashboard]`.
- DoctrineBundle signale `doctrine.orm.controller_resolver.auto_mapping` déprécié.
- Doctrine Migrations signale des commits transactionnels implicites sur certaines migrations.

Ces points sont documentés en dette technique et ne bloquent pas la MEP J5G-B4.

## Mise à jour 19/06/2026 — MEP v11 validée recette / production

Tag final validé :

```text
j5g-b4-20260618-v11
```

Commit final :

```text
b998b63 fix(admin): avoid collapsing menu items matching section names
```

### Commande de déploiement type

```bash
cd /home/vopu3712/hodina.fr

git fetch origin main --tags --force
rm -f /tmp/deploy-hodina-by-tag.sh
git show j5g-b4-20260618-v11:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-by-tag.sh
test -s /tmp/deploy-hodina-by-tag.sh || { echo "ERREUR: script vide"; exit 1; }
chmod +x /tmp/deploy-hodina-by-tag.sh
bash -n /tmp/deploy-hodina-by-tag.sh

bash /tmp/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/hodina.fr \
  --tag j5g-b4-20260618-v11 \
  --target prod
```

### Résultat production observé

```text
Cible : prod
Projet : /home/vopu3712/hodina.fr
Tag : j5g-b4-20260618-v11
Déployé : b998b63
Backup DB : OK via /bin/mariadb-dump
Base : vopu3712_hodina_db
Migrations : already latest Version20260617162000
AssetMapper : 46 assets compilés
Entrypoints : app, admin
Cache prod : clear no-warmup + warmup OK
Doctrine schema : OK
Cron Messenger prod : déjà présent
```

### Points validés après MEP

- Admin stable après test hors déploiement.
- Menu EasyAdmin repliable / dépliable.
- Item `Utilisateurs` cliquable après correctif.
- Miniatures produit visibles dans EasyAdmin.
- Ajax panier validé.
- E-mail commande reçu après correction SMTP.
- Aucun nouveau `public/error_log` récent lié à la MEP.

### Point important : logs

Sur o2switch, `var/log/prod.log` peut ne pas exister. Le fichier observé est :

```text
/home/vopu3712/hodina.fr/public/error_log
```

Lors de la validation v11, ce fichier n'avait pas de modification récente liée au plantage transitoire admin.

### Point important : MAILER_DSN

`.env` contient par défaut :

```env
MAILER_DSN=null://null
```

Cette valeur est sûre pour éviter les envois involontaires, mais elle n'envoie aucun mail réel.

En production, un vrai `MAILER_DSN` doit être défini dans `.env.local` ou `.env.prod.local`, jamais commité.

Validation mail complète :

```bash
grep -n "MAILER_DSN" .env.local prod.env.local .env 2>/dev/null | sed -E 's#://([^:]+):[^@]+@#://\1:***@#'
php bin/console mailer:test adresse@test.fr --env=prod
```

### Point important : fenêtre MEP

Le script actuel déploie dans le dossier actif. Pendant quelques secondes, une requête peut tomber entre :

```text
git checkout
asset-map:compile
cache:clear
cache:warmup
```

Éviter de tester l'admin pendant que la MEP tourne. Attendre `[OK] Déploiement terminé avec succès`, puis ouvrir une nouvelle fenêtre privée.


## Procédure pré-J5K — Nettoyage Git env/uploads/assets/MAILER_DSN

Cette procédure doit être faite une fois dans le dépôt, avant le démarrage J5K.

### Nettoyage local sans suppression physique

```bash
git rm --cached --ignore-unmatch .env.local .env.prod.local prod.env.local
git rm --cached -r --ignore-unmatch public/assets
git rm --cached -r --ignore-unmatch public/uploads/products
mkdir -p public/uploads/products
touch public/uploads/products/.gitkeep
git add .gitignore public/uploads/products/.gitkeep
```

Sur Windows PowerShell, utiliser le script :

```powershell
.\tools\dette-runtime-mailer-pre-j5k.ps1
```

### Vérification avant commit

```bash
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

Résultat attendu :

```text
public/uploads/products/.gitkeep
```

### Vérification après déploiement recette/prod

```bash
ls -la .env.local .env.prod.local prod.env.local 2>/dev/null || true
ls -la public/uploads/products 2>/dev/null || true
ls -la public/assets 2>/dev/null || true
```

Les fichiers `.env.local` / `.env.prod.local` et les images uploadées doivent rester physiquement présents côté serveur même s'ils ne sont plus suivis par Git.

### Vérification MAILER_DSN sans fuite secret

```bash
grep -n "MAILER_DSN" .env.local .env.prod.local prod.env.local .env 2>/dev/null \
  | sed -E 's#://([^:]+):[^@]+@#://\1:***@#'
```

Validation mail réelle :

```bash
php bin/console mailer:test adresse@test.fr --env=prod
```

Un mail est validé uniquement si la boîte cible reçoit réellement le message.


---

# Mise à jour 20/06/2026 soir — Déploiement recette J5K-v8-quater finalisé

## Référence finale recette

```text
Tag : devops-deploy-composer-before-console-v2
Commit : 48dae1d
Cible : recette
Statut : déploiement terminé avec succès
```

## Pourquoi ce tag est la référence finale

Le tag fonctionnel initial `j5k-gps-livraison-recette-v9` validait le flux panier/adresses/facturation. Deux correctifs DevOps ont ensuite été nécessaires :

```text
devops-vendor-untracked-recette-v1
→ vendor/ retiré du suivi Git.

devops-deploy-composer-before-console-v2
→ script corrigé : Composer avant bin/console si vendor/autoload.php absent.
```

## Commande validée en recette

```bash
cd ~/recette.hodina.fr

RUN_COMPOSER=1 \
RESET_COMMANDS=0 \
SKIP_BACKUP=0 \
ASSUME_YES=0 \
PUBLIC_URL=https://recette.hodina.fr \
bash tools/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/recette.hodina.fr \
  --tag devops-deploy-composer-before-console-v2 \
  --target recette
```

## Résultat validé

- Checkout tag OK.
- Backup env OK.
- Backup uploads OK.
- Backup DB OK via `/bin/mariadb-dump`.
- Composer lancé avant `bin/console`.
- Deuxième Composer évité si déjà lancé.
- Symfony 8.0.5 détecté en prod/debug false.
- Migrations déjà latest.
- AssetMapper compile OK.
- Cache clear/warmup OK.
- Doctrine schema OK.
- Colonnes GPS J5K détectées.
- Colonnes J5K-bis détectées.
- URL recette HTTP 200.
- Working tree propre.

## Cron Messenger recette

La ligne cron recette doit rester sous cette forme :

```text
--time-limit=50 --memory-limit=128M
```

Vérification :

```bash
crontab -l | grep "50--memory" || echo "OK aucun time-limit collé"
```

Résultat validé :

```text
OK aucun time-limit collé
```

## Commande type production J5K-v8-quater

À utiliser après création / choix du tag production propre basé sur `devops-deploy-composer-before-console-v2` :

```bash
cd ~/hodina.fr

RUN_COMPOSER=1 \
RESET_COMMANDS=0 \
SKIP_BACKUP=0 \
ASSUME_YES=0 \
PUBLIC_URL=https://hodina.fr \
bash tools/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/hodina.fr \
  --tag <TAG_PROD_J5K_V8_QUATER> \
  --target prod
```

Ne pas utiliser `RESET_COMMANDS=1` pour la production sauf décision explicite et sauvegardée.

---

# Déploiements recette — 24/06/2026 — J5O/J5P/J5Q

## J5O-A — Code réception client

Référence recette :

```text
Tag : j5o-code-reception-client-recette-v2
Commit : 9a7ac76
```

Résultat :

```text
Migration Version20260623210000 exécutée
Doctrine schema validate OK
Routes Djama OK
Code client envoyé / renvoyé / validé OK
Code chiffré supprimé après livraison OK
```

## J5P-A — Notifications client statuts

Référence recette :

```text
Tag : j5p-notifications-statuts-client-recette
Commit : 8ec44f2
```

Résultat :

```text
Déploiement recette OK
Doctrine migrations déjà latest
Doctrine schema validate OK
Cache prod clear/warmup OK
Logs e-mail et SMS validés
```

Logs recette validés :

```text
ORDER_STATUS_CONFIRMED
ORDER_STATUS_PREPARING
ORDER_STATUS_READY_FOR_PICKUP
ORDER_STATUS_PICKED_UP
ORDER_SELLER_COLLECTIONS_COMPLETED
CUSTOMER_DELIVERY_CODE
ORDER_STATUS_DELIVERED
```

## J5Q-A — Paiements livreurs

Référence recette :

```text
Tag : j5q-paiements-livreurs-recette
Commit : 12bb402
Migration : Version20260624140000
```

Commande de déploiement validée :

```bash
cd ~/recette.hodina.fr
git fetch origin --tags
bash tools/deploy-hodina-by-tag.sh --project-dir "$HOME/recette.hodina.fr" --tag j5q-paiements-livreurs-recette --target recette
```

Résultat :

```text
Checkout tag OK
Backup env OK
Backup uploads OK
Backup DB OK via /bin/mariadb-dump
Migration Version20260624140000 exécutée
AssetMapper compile OK
Cache prod clear/warmup OK
Doctrine schema validate OK
Git working tree propre
```

Contrôles serveur validés :

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console debug:router --env=prod | grep -Ei "courier|payout|livreur"
php bin/console dbal:run-sql --force-fetch "SHOW TABLES LIKE 'courier_payout%';"
php bin/console dbal:run-sql --force-fetch "SHOW COLUMNS FROM courier_payout;"
php bin/console dbal:run-sql --force-fetch "SHOW COLUMNS FROM courier_payout_line;"
```

Tables validées :

```text
courier_payout
courier_payout_line
```

Test fonctionnel recette validé :

```text
Paiement id 1
Livreur 10
Statut PAID
Total 30,00 €
2 commandes
Période 16/06/2026 → 30/06/2026
Payé le 24/06/2026 15:17
```

## Warnings connus non bloquants

- `doctrine.orm.controller_resolver.auto_mapping` déprécié.
- `DashboardController` EasyAdmin sans `#[AdminDashboard]`.
- `public/uploads/products/.gitkeep` suivi par Git ; acceptable comme garde-dossier, les images restent runtime.

---

# Déploiement recette — J5Q-C automatisation paiements livreurs

## Contrôles post-déploiement

```bash
php bin/console doctrine:schema:validate --env=prod
php bin/console list hodina --env=prod
php bin/console hodina:courier-payouts:generate --period=current --dry-run --env=prod
php bin/console hodina:courier-payouts:generate --date=2026-06-15 --auto-due --dry-run --env=prod
php bin/console hodina:courier-payouts:generate --date=2026-06-16 --auto-due --dry-run --env=prod
bash -n tools/install-courier-payout-cron.sh
```

## Installation cron recette

```bash
cd /home/vopu3712/recette.hodina.fr
bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/recette.hodina.fr --target recette
crontab -l | grep courier-payouts
```

## Installation cron production après validation recette

```bash
cd /home/vopu3712/hodina.fr
bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/hodina.fr --target prod
crontab -l | grep courier-payouts
```

## Logs

```bash
tail -n 80 var/log/courier_payout_cron.log
```

## Warning connu

Le cron dépend d'administrateurs ayant une adresse e-mail valide. Si aucun admin n'a d'e-mail valide, la génération peut réussir mais aucun récap ne sera envoyé.

# Déploiement recette — J5Q-C-1 structuration réglages

## Migration

J5Q-C-1 ajoute la migration :

```text
DoctrineMigrations\\Version20260624233000
```

Elle ajoute des colonnes à `hodina_setting` et crée l'index `IDX_HODINA_SETTING_GROUP_SORT`.

## Commandes de contrôle recette

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
```

## Contrôles navigateur

- `/ouegnewe` accessible ;
- section `Réglages` visible ;
- liens `Tous les paramètres`, `Général`, `Commerce & commandes`, `Livraison & logistique`, `Notifications`, `Paiements`, `Technique / maintenance` visibles ;
- paramètres commerce et logistique rangés dans les bons groupes.

## Point de vigilance

Ce lot ne touche pas aux e-mails. Si un e-mail change après J5Q-C-1, c'est une régression à investiguer.

# Déploiement recette — J5Q-C-2 Branding e-mail

## Migration

J5Q-C-2 ajoute la migration :

```text
DoctrineMigrations\\Version20260625090000
```

Elle initialise les réglages `email_branding_*` dans `hodina_setting`.

## Commandes de contrôle recette

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console doctrine:schema:validate --env=prod
php bin/console lint:twig templates/emails templates/registration/confirmation_email.html.twig --env=prod
php -d memory_limit=-1 bin/console cache:clear --env=prod --no-warmup
php -d memory_limit=-1 bin/console cache:warmup --env=prod
php bin/console dbal:run-sql --env=prod --force-fetch "SELECT setting_key, value, group_key, group_label, sort_order FROM hodina_setting WHERE group_key = 'email_branding' ORDER BY sort_order;"
```

## Contrôles navigateur

- `/ouegnewe` accessible ;
- Réglages > Branding e-mail visible ;
- quatre réglages présents ;
- préfixe `[Recette]` configurable ;
- un envoi e-mail de recette affiche le préfixe dans l'objet et dans `EmailLog.subject`.

---

# Validation recette effective — J5Q-C-2

Date : 25/06/2026
Tag : `j5q-c2-branding-email-recette`
Commit : `3586560`

## Déploiement réalisé

Le déploiement recette a été exécuté avec le script extrait du tag :

```bash
git show j5q-c2-branding-email-recette:tools/deploy-hodina-by-tag.sh > /tmp/deploy-hodina-j5q-c2.sh
chmod +x /tmp/deploy-hodina-j5q-c2.sh
bash -n /tmp/deploy-hodina-j5q-c2.sh
PHP_MEMORY_LIMIT=-1 bash /tmp/deploy-hodina-j5q-c2.sh \
  --project-dir "$HOME/recette.hodina.fr" \
  --tag j5q-c2-branding-email-recette \
  --target recette
```

Contrôles confirmés :

- checkout du tag `3586560` ;
- migration `Version20260625090000` exécutée ;
- 48 migrations disponibles et exécutées, 0 nouvelle migration restante ;
- assets compilés ;
- cache prod clear/warmup OK ;
- `doctrine:schema:validate --env=prod` OK ;
- `lint:twig templates/emails templates/registration/confirmation_email.html.twig --env=prod` OK ;
- réglages `email_branding_*` présents en base.

## Contrôle SQL validé

Commande correcte :

```bash
php bin/console dbal:run-sql --env=prod --force-fetch "SELECT setting_key, value, group_key, group_label, sort_order FROM hodina_setting WHERE group_key = 'email_branding' ORDER BY sort_order;"
```

Résultat attendu après migration :

```text
email_branding_subject_prefix      vide
email_branding_opening_formula     Bonjour
email_branding_closing_formula     Merci,
email_branding_signature           L’équipe Hodina
```

Attention : la colonne s'appelle `value`, pas `setting_value`.

## Logging recette observé

PHP web réel :

```text
php_version=8.4.21
memory_limit=512M
max_execution_time=600
session_save_path=/opt/alt/php84/var/lib/php/session
```

Le test `_log_test.php` écrit dans `public/error_log`. La directive `.user.ini` `error_log=/home/.../var/log/php_web_error.log` n'a pas été prise en compte : `ini_get('error_log')` retourne `error_log`.

Conséquence : pour le debug recette immédiat, surveiller `public/error_log`, pas `var/log/php_web_error.log`.

## Access logs

Les access logs live utiles sont :

```bash
~/access-logs/recette.hodina.fr-ssl_log
```

Les tests `curl` sur `/` et `/ouegnewe` ont répondu en `200` / `302` / `200`.

Ne pas confondre :

```text
HTTP/1.1" 200 500
```

avec une erreur 500. Ici `200` est le code HTTP, `500` est une taille de réponse.

## Tests restants

- configurer `[Recette]` dans EasyAdmin > Réglages > Branding e-mail ;
- déclencher des e-mails réels ;
- vérifier objet, corps et `EmailLog.subject` ;
- surveiller `public/error_log` pendant les tests.


# Mise à jour 27/06/2026 — Recette J5T/J5U/J5V

## Validations recette actées

### J5T-A / J5T-A-bis

Statut : validé recette sur le formulaire checkout simple nouveau client.

Tag utilisé dans la procédure :

```text
recette-j5t-a-checkout-invite-simplifie-20260626
```

Contrôles métier validés : création commande invité, création compte automatique, e-mail `ORDER_CREATED`, corps e-mail journalisé, lien création mot de passe, disparition des cases parasites.

### J5U-A

Statut : validé recette.

Tag utilisé dans la procédure :

```text
recette-j5u-a-email-sender-settings-20260626
```

Résultat validé : les e-mails sont bien envoyés avec `commande@hodina.fr`. `ORDER_CREATED` est envoyé au client et à `commande@hodina.fr` en copie interne.

Migration concernée :

```text
Version20260626151000
```

## Présent dans les sources mais validation recette à confirmer

### J5V-A

Migration présente :

```text
Version20260626194000
```

Avant déploiement recette, rejouer :

```powershell
php -l src/Entity/Product.php
php -l src/Controller/Admin/ProductCrudController.php
php -l src/Service/DeliveryPointCartService.php
php -l src/Controller/CheckoutController.php
php -l migrations/Version20260626194000.php
php bin/console lint:twig templates/cart/index.html.twig
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Tests recette J5V-A à faire : produit point de remise avec délai 48h, rendez-vous trop proche refusé, rendez-vous valide accepté, produit sans délai inchangé, panier multi-produits avec délai le plus strict.

## Commande de déploiement recette par tag

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag <tag-recette> \
  --target recette
```

Warning récurrent : déplacer `public/error_log` hors de `public` si Git refuse le checkout. Ne jamais committer les logs runtime.

# Mise à jour 28/06/2026 — Préparation recette J5S-B-ter/quater

Statut historique avant recette : correctifs locaux appliqués. État supersédé le 28/06/2026 par la validation recette du tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628`.

Lots concernés :

- J5S-B-ter — séparation point de remise / adresse standard ;
- J5S-B-quater — feedback global checkout ;
- J5S-B-quater-bis — masquage points optionnels en mode standard et affichage unité produit ;
- J5S-B-quater-quinquies — référence commande unique robuste ;
- correctif `CheckoutType` — validation conditionnelle adresse/commune uniquement en standard.

Contrôles recommandés avant tag recette :

```powershell
php -l src/Controller/CartController.php
php -l src/Controller/CheckoutController.php
php -l src/Form/CheckoutType.php
php -l src/Service/DeliveryPointCartService.php
php -l src/Service/OrderReferenceGenerator.php
php -l src/Entity/Product.php
php bin/console lint:twig templates/cart/index.html.twig templates/product/catalogue.html.twig templates/product/show.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Tag recette suggéré après validation locale :

```text
recette-j5s-b-ter-quater-checkout-point-standard-20260628
```

Déploiement recette :

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5s-b-ter-quater-checkout-point-standard-20260628 \
  --target recette
```

Tests recette à rejouer :

- point de remise imposé : point/date/heure, heure hors plage, délai 48 h, validation OK ;
- livraison standard : adresse/commune obligatoires, frais basés sur adresse client ;
- produit standard + point : panneau point masqué en standard, affiché en point ;
- client invité : prénom/nom/téléphone/e-mail obligatoires avec messages français ;
- client connecté : pas de régression adresse existante ;
- référence commande : deux commandes successives avec mêmes données ne provoquent pas de collision unique.

# J5T-C — Préparation recette checkout invité compte existant

Tag recette suggéré après validation locale :

```text
recette-j5t-c-checkout-existing-account-20260628
```

Commandes de déploiement recette :

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5t-c-checkout-existing-account-20260628 \
  --target recette
```

Contrôles recette :

- e-mail nouveau : compte créé automatiquement ;
- e-mail existant : popup affiché, aucune commande avant confirmation ;
- confirmation : commande rattachée au compte existant ;
- `ORDER_CREATED` contient la mention de rattachement ;
- point de remise et livraison standard conservés ;
- pas de migration attendue.

# J5T-C — Reprise après pause avant recette

Statut historique avant recette : développement local en cours. État supersédé le 28/06/2026 par la validation recette du tag `recette-j5t-c-checkout-existing-account-20260628`.

Contrôles locaux recommandés avant commit/tag :

```powershell
php -l src/Controller/CheckoutController.php
php -l src/Form/CheckoutType.php
php -l src/Service/OrderEmailService.php
php bin/console lint:twig templates/cart/index.html.twig templates/emails/order_created.html.twig
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Contrôle spécifique anti-régression checkout :

```powershell
Select-String -Path src\Controller\CheckoutController.php -Pattern "Connecte-toi avant de valider ta commande|utilise une autre adresse e-mail" -Context 3,3
```

Attendu : aucun résultat dans `CheckoutController.php`. Un message similaire peut rester dans `RegistrationController.php`.

Tag recette suggéré seulement après validation locale complète :

```text
recette-j5t-c-checkout-existing-account-20260628
```

Tests recette à rejouer :

- e-mail nouveau : compte créé automatiquement et lien mot de passe dans `ORDER_CREATED` ;
- e-mail existant : premier clic affiche le popup, sans commande ni doublon ;
- confirmation popup : commande rattachée au compte existant ;
- `ORDER_CREATED` et `EmailLog.body` contiennent la mention de rattachement ;
- point de remise : point/date/heure conservés après popup ;
- standard : adresse/commune conservées après popup ;
- aucun changement de frais ou Djama.

# Mise à jour 28/06/2026 — Recette validée J5S-B-ter/quater et J5T-C

## J5S-B-ter/quater

Tag recette validé :

```text
recette-j5s-b-ter-quater-checkout-point-standard-20260628
```

Résultat recette : séparation standard / point de remise validée, frais point basés sur la commune du point, frais standard basés sur l’adresse client, masquage des points en standard, feedback global checkout, validation conditionnelle adresse/commune uniquement en standard, référence commande robuste.

## J5T-C

Commit et tag recette validés :

```text
38f9e23 feat(j5t-c): allow guest checkout with existing account
recette-j5t-c-checkout-existing-account-20260628
```

Résultat recette : e-mail existant accepté en checkout invité, popup au premier clic, aucune commande avant confirmation, aucun doublon `Customer`, commande rattachée au compte existant après confirmation, `ORDER_CREATED` enrichi, pas de migration, pas de changement frais/Djama.

## J5V-A

Validation fonctionnelle recette annoncée le 28/06/2026 : `Product.minimumOrderLeadTimeHours` est utilisé dans le parcours checkout testé. Migration concernée :

```text
Version20260626194000
```

Point corrigé avant production : le commit `3b508d0` ajoute l’appel serveur à `DeliveryPointCartService::validateMinimumOrderLeadTime()` dans `CheckoutController`. Recette validée sous `recette-j5v-a-checkout-lead-time-fix-20260628`, puis production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Contrôles production recommandés avant promotion — appliqués pour la MEP 29/06/2026

```bash
cd ~/recette.hodina.fr
git describe --tags --exact-match HEAD 2>/dev/null || true
php bin/console doctrine:migrations:status --no-interaction
php bin/console doctrine:schema:validate
```

Rejouer au minimum : e-mail invité nouveau, e-mail invité existant avec popup, point de remise imposé, standard + point en mode standard puis point, délai produit trop proche/refusé, délai produit valide/accepté.

## Recette 28/06/2026 — Correctif J5V-A checkout lead time

Tag recette :

```bash
recette-j5v-a-checkout-lead-time-fix-20260628
```

Commit :

```bash
3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout
```

Contrôles attendus après déploiement recette :

```bash
git rev-parse --short HEAD
git describe --tags --exact-match HEAD 2>/dev/null || true
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

Scénario validé : produit avec `minimumOrderLeadTimeHours = 48`, point de remise, rendez-vous trop proche, validation bloquée avec message global. Aucune migration nouvelle. Aucun changement frais, Djama, barge ou livraison standard. Production validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

# Production 29/06/2026 — MEP checkout stabilisation J5S / J5T / J5U / J5V

## Tag production validé

```text
prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628
```

Commit :

```text
d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix
```

Tag candidat recette préalablement aligné :

```text
prod-candidate-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628
```

## Lots inclus

- J5S-B-ter/quater : checkout standard / point de remise stabilisé.
- J5T-C : checkout invité avec e-mail existant et rattachement au compte existant.
- J5U-A : expéditeur e-mails paramétrable EasyAdmin.
- J5V-A : délai minimum produit corrigé et revalidé.

## Commande de MEP production utilisée / recommandée

```bash
cd ~/hodina.fr

git fetch origin main --tags

git log -1 --oneline prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628

bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/hodina.fr \
  --tag prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628 \
  --target prod
```

## Contrôles production attendus après MEP

```bash
cd ~/hodina.fr

git rev-parse --short HEAD
git describe --tags --exact-match HEAD 2>/dev/null || true
git status --short

php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status --no-interaction
```

Attendu : HEAD `d5466fe`, tag exact `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`, working tree propre, schema Doctrine synchronisé, migrations à jour jusqu’à `Version20260626194000`.

## Tests minimum production validés

Tests minimum annoncés OK après MEP :

- accueil / panier accessibles ;
- J5V-A : point de remise avec délai trop proche refusé ;
- J5V-A : point de remise avec délai valide accepté ;
- J5T-C : invité avec e-mail existant, popup puis rattachement ;
- J5S : non-régression livraison standard / point de remise ;
- `ORDER_CREATED` vérifié au minimum.

## Warnings non bloquants à conserver

- `public/uploads/products/.gitkeep` est suivi par Git ; le reste des uploads doit rester runtime.
- Dépréciation Doctrine `doctrine.orm.controller_resolver.auto_mapping`.
- Dépréciation EasyAdmin : `DashboardController` devra adopter `#[AdminDashboard]` avant EasyAdmin 5.


## Préparation déploiement — J5W-A zones tarifaires locales par secteur

Statut : **validé localement, recette et production au 29/06/2026**.

Tag recette J5W-A acté : `recette-j5w-a-local-pricing-zones-20260629`. Tag production J5W-A acté : `prod-j5w-a-local-pricing-zones-20260629`.

Migration concernée :

```text
DoctrineMigrations\Version20260629083000
```

Contrôles locaux réalisés / à conserver avant recette :

```powershell
php -l migrations\Version20260629083000.php
php -l tools\assert-j5w-a-local-pricing-zones.php
php -l src\Service\DeliveryLogisticsService.php
php bin/console lint:twig templates/cart/index.html.twig
php tools\assert-j5w-a-local-pricing-zones.php
php bin/console doctrine:schema:validate
```

Contrôles SQL locaux attendus :

```sql
SELECT z.code, z.name, z.customer_delivery_fee, z.courier_payout
FROM delivery_pricing_zone z
WHERE z.code IN ('GT_LOCAL','PT_LOCAL','MAMOUDZOU_LOCAL','NORD_LOCAL','CENTRE_LOCAL','SUD_LOCAL','PETITE_TERRE_LOCAL')
ORDER BY z.code;
```

Résultat attendu : `PETITE_TERRE_LOCAL` absent, `PT_LOCAL` présent.

```sql
SELECT c.name, c.territory, z.code AS local_pricing_zone
FROM delivery_commune c
INNER JOIN delivery_pricing_zone z ON z.id = c.local_pricing_zone_id
ORDER BY c.name;
```

Résultat attendu : les 18 communes Hodina sont rattachées aux zones locales prévues.

Préconditions avant recette :

1. Garde-fou J5W-A corrigé et repassé OK.
2. Pas de `PETITE_TERRE_LOCAL` en base.
3. Checkout standard et point de remise testés.
4. J5T-C et J5V-A non régressés.
5. Commit J5W-A fait sur `develop`, puis merge contrôlé vers `main` uniquement quand le lot est prêt à être tagué recette.



### Recette validée — 29/06/2026

Tag recette : `recette-j5w-a-local-pricing-zones-20260629`.

Commit déployé : `162fcb4 merge(j5w-a): local pricing zones by sector`.

Contrôles serveur validés :

```bash
git rev-parse --short HEAD
# 162fcb4

git describe --tags --exact-match HEAD 2>/dev/null || true
# recette-j5w-a-local-pricing-zones-20260629

git status --short
# vide

php bin/console doctrine:schema:validate
# Mapping OK, Database OK

php bin/console doctrine:migrations:status --no-interaction
# Current/Latest : DoctrineMigrations\Version20260629083000
# Executed : 55 ; New : 0

php tools/assert-j5w-a-local-pricing-zones.php
# OK
```

Contrôle SQL recette :

- `PETITE_TERRE_LOCAL` absent ;
- `GT_LOCAL`, `PT_LOCAL`, `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL` présents ;
- `Dzaoudzi`, `Labattoir`, `Pamandzi` rattachées à `PT_LOCAL` ;
- les communes Grande-Terre rattachées au découpage `MAMOUDZOU_LOCAL` / `NORD_LOCAL` / `CENTRE_LOCAL` / `SUD_LOCAL`.

Tests fonctionnels recette annoncés OK : zones tarifaires, communes livrées, panier standard, champ instructions point de remise rangé au bon endroit.

État à ce stade : recette validée avant MEP production ; la production est ensuite actée dans la section suivante.



### Validation production J5W-A — 29/06/2026

Production déployée le 29/06/2026 sous :

```text
Tag : prod-j5w-a-local-pricing-zones-20260629
Commit : cea4d19 docs(j5w-a): record recette validation
Avant MEP : d5466fe
Déployé : cea4d19
Migration : DoctrineMigrations\Version20260629083000
```

Contrôles automatiques validés :

```text
[OK] Checkout tag : prod-j5w-a-local-pricing-zones-20260629 @ cea4d19
[OK] Git working tree : propre
[OK] Env local : présent après checkout
[OK] Uploads produits : dossier présent
[OK] Assets compilés
[OK] Cache prod réchauffé
[OK] Doctrine schema : mapping et base synchronisés
[OK] Doctrine migrations : current/latest Version20260629083000
[OK] Cron Messenger : présent
```

Backups créés :

```text
Backup env : /home/vopu3712/hodina.fr/var/deploy_env_backup/20260629_101226
Backup uploads : /home/vopu3712/hodina.fr/var/deploy_runtime_backup/20260629_101226
Backup DB : /home/vopu3712/hodina.fr/var/backups/backup_avant_prod_prod-j5w-a-local-pricing-zones-20260629_20260629_101226.sql
```

Contrôles J5W-A post-MEP :

```bash
php tools/assert-j5w-a-local-pricing-zones.php
php bin/console dbal:run-sql --force-fetch "SELECT z.code, z.name, z.customer_delivery_fee, z.courier_payout FROM delivery_pricing_zone z WHERE z.code IN ('GT_LOCAL','PT_LOCAL','MAMOUDZOU_LOCAL','NORD_LOCAL','CENTRE_LOCAL','SUD_LOCAL','PETITE_TERRE_LOCAL') ORDER BY z.code;"
```

Résultat acté : `PETITE_TERRE_LOCAL` absent ; `GT_LOCAL`, `PT_LOCAL`, `MAMOUDZOU_LOCAL`, `NORD_LOCAL`, `CENTRE_LOCAL`, `SUD_LOCAL` présents ; Petite-Terre reste sur `PT_LOCAL`.

Warnings non bloquants à conserver :

- `doctrine.orm.controller_resolver.auto_mapping` déprécié, à traiter dans dette technique Symfony/Doctrine.
- `DashboardController` EasyAdmin devra adopter `#[AdminDashboard]` avant EasyAdmin 5.
- `public/uploads/products/.gitkeep` reste suivi par Git ; le dossier uploads doit rester traité comme runtime.
- `git describe --tags --exact-match` peut afficher le tag candidat si plusieurs tags pointent sur `cea4d19`. Le tag production final est bien présent et déployé.

## Préparation déploiement — J5X-A tarifs zones tarifaires

Statut : **à déployer uniquement après validation locale complète**.

Branche de développement recommandée :

```powershell
cd E:\hodina\hodina.fr
git switch develop
git pull --ff-only origin develop
git status --short
```

Contrôles locaux :

```powershell
php -l migrations\Version20260629141000.php
php -l tools\assert-j5x-a-delivery-pricing-zones.php
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php tools/assert-j5x-a-delivery-pricing-zones.php
```

Contrôle SQL :

```powershell
php bin/console dbal:run-sql --force-fetch "SELECT code, name, customer_delivery_fee, courier_payout FROM delivery_pricing_zone WHERE code IN ('PT_LOCAL','MAMOUDZOU_LOCAL','CENTRE_LOCAL','SUD_LOCAL','NORD_LOCAL','GT_LOCAL','PETITE_TERRE_LOCAL') ORDER BY code;"
```

Résultat attendu : `PETITE_TERRE_LOCAL` absent ; frais client PT 12, Mamoudzou 12, Centre 17, Sud 21, Nord 21, GT fallback 21 ; rémunération livreur inchangée.

Déploiement recette à préparer après merge `main` :

```bash
cd ~/recette.hodina.fr
git fetch origin main --tags
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5x-a-tarifs-zones-20260629 \
  --target recette
```

Contrôles recette attendus :

```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status --no-interaction
php tools/assert-j5x-a-delivery-pricing-zones.php
```

La production ne doit être taguée qu’après recette fonctionnelle : EasyAdmin zones, SQL tarifs, panier par secteur, contrôle barge/multi-vendeurs non régressé.

## J5X-B — Déploiement à venir

J5X-B n’est pas encore déployé en recette ni production.

Contrôles serveur à prévoir après tag recette :

```bash
php bin/console doctrine:migrations:status --no-interaction
php bin/console doctrine:schema:validate
php tools/assert-j5x-b-delivery-schedules.php
php bin/console dbal:run-sql --force-fetch "SELECT code, public_label, delivery_weekdays, cutoff_time, cutoff_days_before, is_delivery_schedule_active FROM delivery_pricing_zone WHERE code IN ('PT_LOCAL','MAMOUDZOU_LOCAL','SUD_LOCAL','NORD_LOCAL','CENTRE_LOCAL','GT_LOCAL') ORDER BY code;"
```

## J5X-C — Déploiement à venir

J5X-C n’est pas encore déployé en recette ni production.

Avant tag recette :

```powershell
php -l migrations\Version20260629163000.php
php -l src\Entity\Product.php
php -l src\Dto\ProductDeliveryPromise.php
php -l src\Service\ProductDeliveryPromiseService.php
php -l src\Controller\Admin\ProductCrudController.php
php -l src\Controller\ProductController.php
php -l tools\assert-j5x-c-product-delivery-promises.php
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-c-product-delivery-promises.php
```

Tests navigateur minimum : EasyAdmin produit, fiche produit standard sans commune, fiche produit standard avec commune connue, fiche produit sur créneau.

## J5X-D — Préparation recette catalogue

J5X-D n’est pas encore déployé en recette.

Contrôles attendus avant tag recette :

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5x-d-catalogue-search-filters.php
```

Tests navigateur minimum : catalogue sans filtre, recherche, filtre catégorie, tri, produit mis en avant, ajout panier AJAX.

## Mise à jour 01/07/2026 — J5X déjà recette, J5Y à préparer

### J5X

J5X-A/B/C/D ont été déployés en recette sous le tag :

```text
recette-j5x-livraison-catalogue-20260630-1440
```

Statut : déploiement recette réussi, mais validation navigateur complète à terminer avant production.

Contrôles recette à rejouer si besoin :

```bash
cd ~/recette.hodina.fr
php bin/console doctrine:schema:validate --env=prod
php tools/assert-j5x-a-delivery-pricing-zones.php
php tools/assert-j5x-b-delivery-schedules.php
php tools/assert-j5x-c-product-delivery-promises.php
php tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php tools/assert-j5x-d-catalogue-search-filters.php
```

### J5Y

J5Y-A/B/C/D ne sont pas encore documentés comme déployés en recette ou production.

Avant tag recette J5Y, faire en local :

```powershell
cd E:\hodina\hodina.fr

git switch develop
git pull --ff-only origin develop
git status --short

php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container

php tools/assert-j5x-a-delivery-pricing-zones.php
php tools/assert-j5x-b-delivery-schedules.php
php tools/assert-j5x-c-product-delivery-promises.php
php tools/assert-j5x-c-quater-cart-delivery-schedule-address-block.php
php tools/assert-j5x-d-catalogue-search-filters.php
php tools/assert-j5y-a-delivery-point-window-ui.php
php tools/assert-j5y-b-delivery-point-half-hour-slots.php
php tools/assert-j5y-c-homepage-catalogue-discover.php
php tools/assert-j5y-d-header-logo-favicon.php
```

Vérifier aussi qu’aucun fichier temporaire ne sera committé :

```powershell
git status --short
```

Ne pas ajouter : `.zip`, `.patch`, `.bak`, `.old`, `.corrected.php`, images de test ou archives locales.

### Merge et tag recette J5Y, uniquement après validation locale

```powershell
git switch main
git pull --ff-only origin main

git merge --no-ff develop -m "merge: prepare j5y public UX and delivery point slots for recette"

php bin/console doctrine:schema:validate
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-a-delivery-point-window-ui.php
php tools/assert-j5y-b-delivery-point-half-hour-slots.php
php tools/assert-j5y-c-homepage-catalogue-discover.php
php tools/assert-j5y-d-header-logo-favicon.php

git push origin main

$tag = "recette-j5y-public-ux-points-remise-" + (Get-Date -Format "yyyyMMdd-HHmm")
git tag -a $tag -m "Recette J5Y - homepage catalogue, découvrir Hodina, points de remise UX"
git push origin $tag
Write-Host $tag
```

Déploiement recette :

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag NOM_DU_TAG_J5Y \
  --target recette
```

### Tests recette J5Y prioritaires

- `/` affiche le catalogue et pas l’ancienne homepage.
- `/catalogue` redirige vers `/`.
- `/decouvrir-hodina` est lisible sur mobile.
- `/blog/decouvrir-hodina` redirige vers `/decouvrir-hodina`.
- Header : logo lisible, lien `Découvrir Hodina`, compte, panier.
- Favicon : tester en navigation privée, ne pas bloquer si explicitement sorti du périmètre.
- Catalogue : recherche, filtre, tri, AJAX, ajout panier.
- Panier standard : adresse, frais, calendrier J5X-B.
- Panier point de remise : date propre, select créneau visible, créneaux 30 minutes, validation commande.
- EasyAdmin Produit : création rapide d’un point de remise et de plages horaires via l’interface guidée.

### Warning exploitation recette connu

Vérifier le cron Messenger recette. La ligne ne doit pas contenir :

```text
bin/consolemessenger:consume
```

Elle doit contenir :

```text
bin/console messenger:consume
```

# Déploiement recette 01/07/2026 — J5Y Carnet / Livraison / Footer

## Tag recette final validé

```text
recette-j5y-carnet-livraison-footer-clean-20260701
```

Commit déployé :

```text
b1bbab6 chore(j5y): remove delivery guide backup template
```

Commande utilisée :

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5y-carnet-livraison-footer-clean-20260701 \
  --target recette
```

## Résultat MEP recette

Déploiement terminé avec succès.

Contrôles automatiques confirmés par le script :

```text
checkout tag OK
working tree propre
env local restauré
uploads runtime restaurés
assets compilés
cache prod clear + warmup OK
Doctrine schema OK
migrations déjà à la dernière version DoctrineMigrations\Version20260629223000
cron Messenger présent
réglages paiements livreurs présents
commande hodina:courier-payouts:generate disponible
```

## Validations navigateur annoncées après MEP

Tests recette annoncés OK après déploiement :

```text
/
/decouvrir-hodina
/blog
/blog/decouvrir-hodina
/carnet
/carnet/livraison
catalogue AJAX
panier standard
point de remise
GPS depuis mobile HTTPS
admin/livreur minimal
```

## Tags à ne pas confondre

```text
recette-j5y-public-catalogue-discover-branding-perf-20260701
```

Tag utile pour le logo optimisé et la première stabilisation publique, mais supersédé fonctionnellement par le lot Carnet/Footer.

```text
recette-j5y-carnet-livraison-footer-20260701
```

Tag supersédé : il contenait un backup de template `.bk`. Ne pas déployer.

```text
recette-j5y-carnet-livraison-footer-clean-20260701
```

Tag propre validé recette. Il a été promu ensuite en production via le tag `prod-j5y-carnet-livraison-footer-20260701`.

## Warnings / dette non bloquante

- `public/uploads/products/.gitkeep` reste suivi par Git ; à traiter comme dette runtime.
- La MEP a été lancée sans `PUBLIC_URL`; prochaine MEP recommandée : définir `PUBLIC_URL=https://recette.hodina.fr` pour automatiser le test HTTP public.
- Symfony 8.0.5 non-LTS signale une fenêtre de maintenance courte ; planifier la trajectoire framework hors urgence J5Y.
- Dépréciations warmup connues : Doctrine `controller_resolver.auto_mapping` et EasyAdmin `#[AdminDashboard]`.

## Commande recommandée pour une prochaine recette avec test URL

```bash
cd ~/recette.hodina.fr
PUBLIC_URL=https://recette.hodina.fr bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag <tag-recette> \
  --target recette
```


# Déploiement production 01/07/2026 — J5Y Carnet / Livraison / Footer

## Tag production validé

```text
prod-j5y-carnet-livraison-footer-20260701
```

Commit déployé :

```text
200d84b merge: document j5y recette validation
```

Commande utilisée :

```bash
cd ~/hodina.fr
PUBLIC_URL=https://hodina.fr bash tools/deploy-hodina-by-tag.sh   --project-dir ~/hodina.fr   --tag prod-j5y-carnet-livraison-footer-20260701   --target prod
```

## Résultat MEP production

Déploiement terminé avec succès.

Résumé serveur :

```text
Cible       : prod
Projet      : /home/vopu3712/hodina.fr
Avant MEP   : cea4d19
Déployé     : 200d84b
URL publique: https://hodina.fr répond HTTP 200
```

Contrôles automatiques confirmés : checkout tag, working tree propre, env local présent, uploads produits restaurés, assets compilés, backup DB créé, cache prod présent après warmup, Doctrine schema synchronisé, migrations à la dernière version `DoctrineMigrations\Version20260629223000`, colonnes GPS/J5K/J5Q vérifiées, cron Messenger prod présent, log Messenger présent.

## Migrations production

Avant MEP, la production avait 55 migrations exécutées et 4 migrations nouvelles. Après `doctrine:migrations:migrate`, la production est passée à 59 migrations exécutées et aucune migration nouvelle.

Certaines migrations J5X/J5Y n’ont pas généré de SQL effectif ; c’est cohérent avec des migrations de garde-fou ou déjà alignées, mais les warnings Doctrine `implicit commits` restent une dette technique à traiter hors urgence.

## Validation navigateur production

Tests production annoncés validés après MEP : catalogue public, `/decouvrir-hodina`, redirections `/blog*`, `/carnet`, `/carnet/livraison`, header/footer, panier standard, point de remise, GPS, admin/livreur minimal.

## Warnings / dette non bloquante production

- `public/uploads/products/.gitkeep` reste suivi par Git : dette runtime à traiter séparément.
- Symfony 8.0.5 non-LTS : trajectoire framework à planifier.
- Dépréciations connues : Doctrine `controller_resolver.auto_mapping`, EasyAdmin `#[AdminDashboard]`, Doctrine migrations implicit commit / `isTransactional()`.
- Le libellé final du script affiche encore “Tests navigateur restants : créer une commande de recette...” même en prod ; c’est un wording du script à corriger plus tard, pas un échec de MEP.

## Statut final

J5Y-A/B/C/D/E/F/G/H est validé production. Ne plus déployer les tags recette J5Y pour ce périmètre ; la référence production est `prod-j5y-carnet-livraison-footer-20260701`.

# Déploiement 02/07/2026 — J5Z checkout/admin UX

## Tags

Tag recette initial supersédé :

```text
recette-j5z-checkout-admin-ux-20260702
```

Tag recette correctif mobile supersédé :

```text
recette-j5z-checkout-admin-ux-fix-mobile-20260702
```

Tag recette final validé :

```text
recette-j5z-delivery-fee-reason-refresh-20260702
```

Tag production final :

```text
prod-j5z-delivery-fee-reason-refresh-20260702
```

Commit / merge final :

```text
ed2e873 fix(cart): keep delivery fee reason after logistics refresh
09243d2 merge: fix j5z delivery fee reason refresh
```

## Commande recette finale

```bash
cd ~/recette.hodina.fr

PUBLIC_URL=https://recette.hodina.fr bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5z-delivery-fee-reason-refresh-20260702 \
  --target recette
```

## Commande production finale

```bash
cd ~/hodina.fr

PUBLIC_URL=https://hodina.fr bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/hodina.fr \
  --tag prod-j5z-delivery-fee-reason-refresh-20260702 \
  --target prod
```

## Rattrapage téléphones

Commande disponible :

```bash
php bin/console hodina:customers:normalize-phones
php bin/console hodina:customers:normalize-phones --apply
```

Règle : toujours lancer la simulation avant `--apply`.

État documenté : en recette, la simulation puis l’application ont modifié 84 numéros, 0 non normalisable. L’exécution production n’est pas incluse dans les extraits fournis à cette mise à jour ; ne pas supposer qu’elle est faite si aucun log serveur n’a été conservé.

## Contrôles recette/prod J5Z

Contrôles techniques locaux validés pendant le lot :

```bash
php tools/assert-admin-product-form-order.php
php tools/assert-customer-phone-prefix.php
php tools/assert-checkout-delivery-fee-reason.php
php tools/assert-delivery-fee-update-flash.php
php tools/assert-delivery-point-date-field-mobile-width.php
php tools/assert-cart-logistics-preview-cache-version.php
php tools/assert-j5x-c-product-delivery-promises.php
php tools/assert-j5x-d-catalogue-search-filters.php
php bin/console lint:twig templates
php bin/console lint:container
```

Contrôles navigateur production annoncés validés : catalogue, panier invité avec indicatif visible, panier connecté sans indicatif parasite, date rendez-vous mobile, flash frais recalculés, EasyAdmin Produit, annotation frais dans les scénarios justifiés, absence d’annotation dans les frais simples.

## Warnings non bloquants observés / à maintenir hors J5Z

- Symfony 8.0.5 non-LTS.
- Dépréciation `doctrine.orm.controller_resolver.auto_mapping`.
- Dépréciation EasyAdmin `DashboardController` sans `#[AdminDashboard]`.
- `public/uploads/products/.gitkeep` suivi par Git.
- Cron recette Messenger affiché avec `--time-limit=50--memory-limit=128M` sans espace.

Ces points doivent être traités dans un lot technique dédié, pas mélangés à J5Z.

# Déploiement 03/07/2026 — J5AB et J5AC

## J5AB — Catalogue mobile orienté achat

Tags :

```text
recette-j5ab-catalogue-mobile-achat-20260703
prod-j5ab-catalogue-mobile-achat-20260703
```

Commit :

```text
bab469e feat(j5ab): compact mobile catalogue filters
```

Commandes de déploiement utilisées :

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5ab-catalogue-mobile-achat-20260703 \
  --target recette

cd ~/hodina.fr
PUBLIC_URL=https://hodina.fr bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/hodina.fr \
  --tag prod-j5ab-catalogue-mobile-achat-20260703 \
  --target prod
```

Contrôles validés :

```bash
php bin/console lint:twig templates/product/catalogue.html.twig templates/product/_catalogue_filters.html.twig templates/product/_catalogue_results.html.twig templates/product/_catalogue_product_card.html.twig
php tools/assert-j5x-d-catalogue-search-filters.php
php tools/assert-j5y-c-homepage-catalogue-discover.php
php tools/assert-j5ab-catalogue-mobile-buy-first.php
curl -I https://hodina.fr/
curl -I https://hodina.fr/catalogue
curl -I https://hodina.fr/decouvrir-hodina
```

## J5AC — Espace client finalisé avec AJAX

Tags :

```text
recette-j5ac-espace-client-ajax-20260703
recette-j5ac-espace-client-ajax-v2-20260703
prod-j5ac-espace-client-ajax-20260703
```

Commits :

```text
60d3dee feat(j5ac): finalize client account space with ajax
0966429 fix(j5ac): mark email migration non transactional
```

Le tag recette initial a validé le fonctionnel. Le tag recette v2 a validé le correctif propre de migration `isTransactional(): false`. La production utilise le commit `0966429`.

Commandes de déploiement :

```bash
cd ~/recette.hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/recette.hodina.fr \
  --tag recette-j5ac-espace-client-ajax-v2-20260703 \
  --target recette

cd ~/hodina.fr
bash tools/deploy-hodina-by-tag.sh \
  --project-dir ~/hodina.fr \
  --tag prod-j5ac-espace-client-ajax-20260703 \
  --target prod
```

## Audits DB avant migration J5AC

Recette avant migration :

```text
total_customers = 6
emails_null = 0
emails_vides = 0
doublons normalisés = 0
emails invalides simples = 0
```

Production avant migration :

```text
total_customers = 9
emails_null = 0
emails_vides = 0
doublons normalisés = 0
email invalide détecté : customer.id=13, chahere.kdu
```

Décision : la migration unique nullable pouvait être appliquée car l’unicité n’était pas menacée. L’email invalide isolé a été corrigé après déploiement :

```text
customer.id=13 : chahere.kdu → chahere.kdu@outlook.fr
```

Après correction production :

```bash
php tools/assert-j5ac-customer-email-db-readiness.php
php tools/assert-j5ac-client-account-finalization.php
php tools/assert-j5ac-client-account-ajax.php
php bin/console doctrine:schema:validate --env=prod
```

Tous les contrôles sont OK.

## Warnings connus

Non bloquants et hors J5AB/J5AC :

- Symfony 8.0.5 non-LTS.
- Dépréciation `doctrine.orm.controller_resolver.auto_mapping`.
- Dépréciation EasyAdmin `DashboardController` sans `#[AdminDashboard]`.
- `public/uploads/products/.gitkeep` suivi par Git.
- `PUBLIC_URL` non renseigné pendant certains déploiements, donc URL publique non testée automatiquement.
- Le texte final du script de déploiement contient encore des tests navigateur génériques historiques.

Le warning Doctrine migration implicite J5AC a été traité avant production par `isTransactional(): false`.

# Documentation 04/07/2026 — alignement avant J5AA

Cette mise à jour est documentaire uniquement. Elle aligne les fichiers de suivi avec le code actuel après J5AB/J5AC et prépare le cadrage J5AA.

Aucun nouveau tag recette/prod n’est nécessaire pour cette documentation seule. Aucun déploiement serveur n’est requis.

Points actés :

- J5AC est la référence actuelle de l’espace client ; l’ancien bloc `Portail client MVP` est obsolète.
- `/mon-compte/adresses` reste absent du code et doit être cadré séparément si besoin.
- J5AA reste prévu/non codé et devra créer une migration seulement quand le modèle `AddressLocality` sera validé.
- Le code postal / commune doit rester cohérent avec le seed ; le code actuel possède déjà `DeliveryCommune.postalCode` et des validations serveur.

# Déploiement 04/07/2026 — J5AA Localité d’adresse (recette + production)

> Supersède le point ci-dessus « J5AA reste prévu/non codé » : J5AA a été codé et déployé.

Sous-lots livrés : J5AA-0 (audit strict read-only), J5AA-A (`AddressLocality` + localité au checkout), J5AA-B (couple code postal + commune sécurisé au checkout).

Migration à jouer : **`Version20260704210000`** (J5AA-A). J5AA-0 et J5AA-B n’introduisent aucune migration.

Après `git pull` du tag, sur recette puis production :

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console hodina:address-localities:seed --env=prod   # commande idempotente, seed initial Mamoudzou
php bin/console doctrine:schema:validate --env=prod
php bin/console cache:clear --env=prod
```

Contrôles fonctionnels : `97600` + `Mamoudzou` OK ; `97600` + `Koungou` OK ; POST manipulé `97600 + Dzaoudzi` refusé serveur ; localité libre acceptée mais jamais utilisée pour le calcul des frais.

Tags :

```text
recette-j5aa-address-locality-20260704
prod-j5aa-address-locality-20260704
```

# Déploiement 07/07/2026 — J5AD Chatbot IA + support client

## Objet

Lot J5AD : chatbot IA pour clients connectés dans `/mon-compte`, escalade vers ticket support humain, formulaire de contact anonyme (`/contact`), réglages LLM (clé API chiffrée), flag d'activation `HodinaSetting::ai_chatbot_enabled`. Détail fonctionnel : `README_MAJ_J5AD_CHATBOT_IA_SUPPORT_CLIENT_20260706.md`. Erreurs d'environnement rencontrées en mise en place locale et leurs correctifs : `NOTES_ENVIRONNEMENT_LOCAL_20260707.md`.

## Nouveautés côté déploiement (script `tools/deploy-hodina-by-tag.sh`)

Trois garde-fous ajoutés (additifs, le flux critique backup/migrations/cache est inchangé) :

1. **Publication des assets des bundles** — `php bin/console assets:install public` publie les CSS/JS d'EasyAdmin dans `public/bundles/`. `asset-map:compile` ne gère que `public/assets/` ; sans cette étape, après une montée de version d'EasyAdmin le backoffice s'affiche non stylé (404 sur `/bundles/easyadmin/app.*.css`, « rond bleu »).
2. **Vérification `importmap.php`** — l'entrée AssetMapper `admin` doit être présente (sinon EasyAdmin lève « The entrypoint "admin" does not exist in importmap.php », 500 sur `/ouegnewe`). `importmap.php` est désormais versionné.
3. **Contrôle des dépendances J5AD** — `symfony/rate-limiter` et `symfony/http-client` sont requis par `config/packages/rate_limiter.yaml` et `http_client.yaml`. `composer.json/lock` n'étant pas versionnés, le script avertit tôt et donne la commande `composer require` si `vendor/` ne les contient pas.

## Prérequis serveur — PREMIÈRE mise en recette du lot

Le `composer.lock` du serveur ne contient pas encore les deux nouveaux paquets. Avant le premier déploiement J5AD (ou pendant, via `RUN_COMPOSER=1`) :

```bash
cd /home/vopu3712/recette.hodina.fr
composer require symfony/rate-limiter symfony/http-client
```

Sans eux, `cache:warmup` échoue à compiler le conteneur (config `framework.rate_limiter` / `framework.http_client` sans le composant installé).

## Commande recette

```bash
RUN_COMPOSER=1 bash tools/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/recette.hodina.fr \
  --tag j5ad-chatbot-ia-support-client-20260707 \
  --target recette
```

## Commande production (après validation recette)

```bash
RUN_COMPOSER=1 bash tools/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/hodina.fr \
  --tag j5ad-chatbot-ia-support-client-20260707 \
  --target prod
```

Le tag doit être créé depuis `main` après merge de la PR (règle : déploiement uniquement par tag contenu dans `origin/main`).

## Si déploiement manuel (rappel des étapes sensibles)

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console asset-map:compile --env=prod
php bin/console assets:install public --env=prod          # publie les assets EasyAdmin, evite le « rond bleu »
php -d memory_limit=-1 bin/console cache:clear --env=prod --no-warmup
php -d memory_limit=-1 bin/console cache:warmup --env=prod
php bin/console doctrine:schema:validate --env=prod
```

## Réglages post-déploiement (EasyAdmin)

Le chatbot est **désactivé par défaut** (`ai_chatbot_enabled = 0`, seedé par la migration). Une fois le lot validé :

- Réglages → Technique / maintenance → activer **Chatbot IA activé**.
- Réglages → Réglages IA → choisir le fournisseur (Mock pour tester sans clé, sinon Anthropic/OpenAI + nom du modèle + clé API). La clé est chiffrée (AES-256-GCM dérivée de `APP_SECRET`) et jamais réaffichée. Elle est **propre à chaque environnement** : à ressaisir en recette et en prod (`APP_SECRET` diffère, la clé n'est pas transférable).

Le formulaire de contact `/contact` et les tickets support fonctionnent indépendamment du flag.

## Contrôles recette / prod

- Backoffice `/ouegnewe` stylé (pas de « rond bleu ») → assets EasyAdmin bien publiés (`public/bundles/easyadmin`).
- `/contact` accessible en visiteur non connecté → crée un ticket dans EasyAdmin → Support → Tickets support (e-mail admin logué dans E-mails (logs)).
- `doctrine:schema:validate --env=prod` vert.
- Flag OFF → lien « Assistant » absent de `/mon-compte`, `/mon-compte/assistant` redirige, `/contact` toujours actif.
- Flag ON + provider Mock → `/mon-compte/assistant` répond en texte simulé.

## Warnings connus / non bloquants

- Récap du script : « Dépendances J5AD » en WARN tant que `composer require` n'a pas été fait sur ce serveur → normal au tout premier déploiement, résolu après installation.
- « Assets EasyAdmin » en WARN si `assets:install` n'a pas encore tourné → le script le lance en étape 12.

## Tags

```text
recette-j5ad-chatbot-ia-support-client-20260707
prod-j5ad-chatbot-ia-support-client-20260707
```

# Déploiement 07/07/2026 — J5AE Widget flottant Assistant Hodina

## Objet

Lot J5AE : widget conversationnel flottant "Assistant Hodina" (moteur à règles, aucune IA), disponible sur tout le site public (catalogue, produit, panier, checkout, contact, espace client) pour visiteurs anonymes et clients connectés. Escalade vers `SupportTicket` (origine `CHAT_WIDGET`), réutilise les entités et le service de notification du lot J5AD. Détail : `README_MAJ_J5AE_WIDGET_ASSISTANT_HODINA_20260707.md`.

## Différences avec un déploiement classique

Aucune nouvelle dépendance Composer, aucun nouveau bundle. La seule migration (`Version20260707040000`) ajoute une ligne de réglage dans `hodina_setting` (idempotente). Les 3 garde-fous de déploiement posés pour J5AD (assets EasyAdmin, `importmap.php`, dépendances runtime) restent valables et suffisants : rien de spécifique à ajouter au script pour ce lot.

## Commande recette

```bash
bash tools/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/recette.hodina.fr \
  --tag j5ae-widget-assistant-hodina-20260707 \
  --target recette
```

## Commande production (après validation recette)

```bash
bash tools/deploy-hodina-by-tag.sh \
  --project-dir /home/vopu3712/hodina.fr \
  --tag j5ae-widget-assistant-hodina-20260707 \
  --target prod
```

## Réglage post-déploiement (optionnel)

Le bouton "Continuer sur Messenger" reste masqué tant que le lien n'est pas renseigné. Si souhaité : EasyAdmin → Réglages → Technique / maintenance → "Lien Messenger support" → coller l'URL publique de la page Messenger. Propre à chaque environnement, aucun jeton Meta n'est stocké ici (lien uniquement).

## Contrôles recette / prod

- Le bouton flottant (logo Hodina) apparaît sur catalogue, fiche produit, panier, checkout, contact, espace client.
- Absent sur `/mon-compte/assistant` et `/djama`.
- Une question "frais de livraison" ne renvoie jamais de tarif chiffré, seulement un renvoi vers Infos livraison.
- Une escalade (formulaire ou mot-clé "humain"/"parler à l'équipe") crée un ticket EasyAdmin → Support → Tickets support, origine "Widget assistant Hodina", e-mail admin dans Logs → E-mails (logs).
- `doctrine:schema:validate --env=prod` vert.

## Tags

```text
recette-j5ae-widget-assistant-hodina-20260707
prod-j5ae-widget-assistant-hodina-20260707
```
