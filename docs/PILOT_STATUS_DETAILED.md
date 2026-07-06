# HODINA � SUIVI D�TAILL� PHASE PILOTE

> Document de pilotage technique & fonctionnel
> Objectif : s�curiser le socle, suivre l�avancement, r�duire les risques.

---

## ?? Vision du pilote Hodina

Le pilote Hodina vise � :
- d�montrer la faisabilit� technique
- permettre aux vendeurs de se projeter
- tester le flux commande ? validation ? livraison
- pr�parer une mont�e en charge ma�tris�e

Le pilote est volontairement :
- sans paiement en ligne
- avec validation manuelle admin
- avec slots de livraison fixes

---

# ? J1�J2 � Socle technique & Back-office (VALID�)

## ?? Objectifs
- Base technique saine et testable
- Mod�le de donn�es stable
- Back-office admin op�rationnel
- Capacit� � repartir de z�ro sans risque

---

## 1?? Infrastructure & environnement

### Symfony
- Version : Symfony WebApp
- Environnement local Windows
- CLI Symfony fonctionnelle

### PHP
- Extension `intl` activ�e ?
- Encodage UTF-8 ma�tris� (important pour accents)

### Base de donn�es
- MySQL 8.x (MySQL Installer)
- Charset DB : `utf8mb4`
- Collation : `utf8mb4_0900_ai_ci`
- Doctrine ORM + Migrations

---

## 2?? Mod�le de donn�es (entit�s)

### ?? DeliveryZone
**R�le** : structurer la logistique du pilote

Champs cl�s :
- id
- code (PT / GT)
- name
- createdAt

Statut : ? OK

---

### ?? Seller
**R�le** : vendeur / producteur local

Champs cl�s :
- id
- name
- phone
- email
- deliveryZone (ManyToOne)
- createdAt

Points techniques :
- `__toString()` impl�ment� (EasyAdmin)
- Zone obligatoire

Statut : ? OK

---

### ?? Category
**R�le** : classification produits

Champs cl�s :
- id
- name
- slug (auto)
- createdAt

Points techniques :
- Slug auto � partir du nom
- Accents g�r�s correctement

Statut : ? OK

---

### ?? Product
**R�le** : produit vendu sur Hodina

Champs cl�s (pilote) :
- id
- name
- price
- seller
- category
- isActive
- deliveryDays
- isPreorder
- manufacturingDays
- stockQty / unlimited
- createdAt

Statut : ? OK

---

### ?? Entit�s pr�tes pour J3
- Customer
- CustomerOrder
- OrderItem
- Address

Statut : ?? structure OK, logique m�tier � impl�menter

---

## 3?? Back-office Admin (EasyAdmin)

### Dashboard
- Acc�s `/admin`
- Menu structur� (Logistique / Catalogue)

### CRUD fonctionnels
- DeliveryZone
- Seller
- Category
- Product

Bonnes pratiques appliqu�es :
- `createdAt` visible mais non �ditable
- Relations fonctionnelles
- Interface en fran�ais

Statut : ? OK

---

## 4?? Validation automatique (CI locale)

### Commande cr��e
sur bash
	- php bin/console hodina:ci:j1j2:json --cleanup --progress
	Ce que la commande valide r�ellement

	  - PHP : extension intl
	  -
	  - DB : connexion r�elle
	  -
	  - Doctrine :

			migrations � jour

			pas de migration fant�me

			Insertion r�elle en base

	  - Callbacks automatiques :

			createdAt

			slug

	  - Compatibilit� EasyAdmin

	  - Nettoyage complet des donn�es test

	R�sultat attendu
	  "status": "success"

	  Statut : ? VALID�
---

## ?? �tat des lieux � Backoffice & Authentification
**Date : 2026-02-06**

### URLs valid�es
- http://127.0.0.1:8000/ ? accueil public
- http://127.0.0.1:8000/hodi ? login
- http://127.0.0.1:8000/caribou ? inscription
- http://127.0.0.1:8000/lawa ? logout
- http://127.0.0.1:8000/ouegnewe ? backoffice (admin only)
- http://127.0.0.1:8000/ouegnewe/dashboard ? dashboard admin

### S�curit�
- Acc�s `/ouegnewe/*` restreint � ROLE_ADMIN
- Redirection automatique apr�s login :
  - Admin ? dashboard
  - User ? accueil

### Statut global
?? Backoffice fonctionnel
?? Authentification stable
?? Base saine pour attaquer J4 (logique m�tier & front)

---

# Mise à jour 12/06/2026 — support adresses validé localement

## Pourquoi cette étape a été ajoutée

Pendant J5G-B4, le calcul de trajet réel a mis en évidence une dépendance forte : l'adresse client doit être fiable.

Hodina a donc ajouté un jalon intermédiaire :

```text
J5G-SUPPORT-ADRESSES
```

## Résultat

```text
EasyAdmin : OK
Checkout : OK sur les cas critiques
Inscription : OK sur les cas critiques
Validation adresse : centralisée
Erreurs UX : améliorées
```

## Règles métier finales

```text
Livraison : PT/GT uniquement + commune livrable + cohérence code postal/zone
Facturation : AUTRE possible ou PT/GT si adresse Mayotte cohérente
```

## Tests validés

```text
Livraison Labattoir / 97615 / PT → OK
Livraison Labattoir / 97619 / PT → KO
Livraison Labattoir / 97615 / AUTRE → KO
Facturation Rennes / 35000 / AUTRE → OK
Facturation Rennes / 35A00 / AUTRE → KO
Facturation Labattoir / 97615 / PT → OK
Facturation Labattoir / 97615 / GT → KO
E-mail existant checkout → KO
E-mail existant inscription → KO sans doublon
```

## Risque restant

Le diff local mélange encore :

```text
support adresses
J5G-B4 logistique réelle
docs
patchs temporaires
```

Avant commit, il faut nettoyer et isoler les changements.

---

# Mise à jour 13/06/2026 — décisions préouverture et e-mails

## État validé avant cette décision

Les tests locaux du support adresses sont OK : EasyAdmin, checkout, inscription, e-mail existant checkout et e-mail existant inscription sans doublon.

## Décisions ajoutées

```text
J5I : préouverture commerciale avec compte à rebours
J5H : e-mails transactionnels avec SMTP o2switch
```

## Priorité immédiate

La priorité immédiate est J5I, car elle permet de publier le site sans autoriser les commandes avant la date officielle.

## Critère de passage en production

```text
Recette validée sur mobile
Recette validée sur PC
EasyAdmin de préouverture validé
Aucune commande possible avant ouverture
Date officielle d'ouverture décidée
```


---

# Mise à jour 13/06/2026 — J5I préouverture validée en recette

## Objectif

Mettre en place une phase de préouverture avant les premières ventes.

Pendant cette période, le client peut voir Hodina et laisser son e-mail, mais ne peut pas commander.

## Réalisé

```text
Bannière de préouverture globale
Compte à rebours paramétrable
Capture e-mail
Table launch_subscriber
CRUD EasyAdmin Abonnés ouverture
Réglages EasyAdmin via hodina_setting
Blocage panier côté serveur
Blocage checkout côté serveur
Déploiement recette
Correction Basic Auth / HTTPS / 401.shtml
Injection des paramètres dev en recette
```

## Commit

```text
branche : pilot/j5i-preouverture-countdown
commit  : 5bf3e0e feat: add J5I sales opening countdown and launch email capture
```

## Tests validés

```text
Local : bannière activable par paramètres
Local : capture e-mail OK
Local : launch_subscriber alimenté
Local : Doctrine schema validate OK
Recette : branche déployée
Recette : migrations débloquées
Recette : schema validate OK
Recette : paramètres injectés
Recette : Basic Auth / HTTPS OK
```

## Risque restant

L'ordre des migrations J5I doit être corrigé avant production. En recette, une correction manuelle a été appliquée pour continuer les tests.

---

# J5J — État détaillé mode commerce

## Statut

J5J est validé en recette.

## Points validés

```text
- migration Version20260613130000 exécutée ;
- paramètres commerce_* présents ;
- rôle ROLE_COMMERCE_TESTER utilisable ;
- utilisateur testeur capable de commander ;
- mode open sans bannière ;
- mode preopening avec blocage public ;
- EasyAdmin amélioré avec switchs et choix.
```

## Point de vigilance

La table `hodina_setting_backup_20260613` peut faire échouer `schema:validate` tant qu'elle existe. Ce n'est pas un problème applicatif. La supprimer après validation si elle n'est plus utile.
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

# J5H-A — Validation recette e-mails

## Statut

Validé en recette le 15/06/2026.

## Points validés

- SMTP o2switch `contact@hodina.fr`.
- E-mail automatique de création de commande.
- EmailLog en base.
- EasyAdmin > Journaux e-mails.
- Bouton `Envoyer manuellement`.
- Messenger async consommé par cron.
- Table `messenger_messages` vide après consommation.
- Template e-mail avec articles, quantités, prix, frais et total.
- Recette propre côté Git.

## Risque restant maîtrisé

Le statut `SENT` de `EmailLog` est accepté pour le pilote même s'il signifie techniquement “accepté par Symfony / Messenger”. Le cron validé réduit le risque opérationnel.

---

# Mise à jour 15/06/2026 — J5G-E0 snapshot adresse commande validé recette

## Résumé

J5G-E0 est validé en recette. Le modèle adresse commande est maintenant plus robuste : les adresses client sont supprimables et les commandes conservent leur propre snapshot.

## Tests validés

- Migration recette exécutée.
- Cache prod vidé et réchauffé.
- Schema Doctrine synchronisé.
- Anciennes commandes reprises avec snapshot livraison.
- Anciennes commandes reprises avec snapshot facturation.
- Nouvelle commande créée sans doublon d'adresse supplémentaire sur le cas testé.
- Suppression EasyAdmin d'une adresse liée à une commande validée.
- La commande concernée conserve son snapshot même quand `delivery_address_id` devient nul.

## Exemple validé

Une commande liée à l'adresse supprimée a conservé :

```text
delivery_address_line1 = 3 rue Mariam Ali
delivery_address_postal_code = 97615
delivery_address_commune = Labattoir
delivery_address_zone_code = PT
```

## Conséquence pilote

La gestion des adresses est désormais assez saine pour continuer les tests logistiques sans bloquer le client sur des adresses historiques.

---

# Mise à jour 16/06/2026 — Cadrage J5G-E1 adresse par commune livrée

## Statut

J5G-E0 est validé. Avant de passer à J5G-B4, une remarque UX importante a été retenue : la saisie adresse actuelle crée trop de friction.

## Décision

J5G-E1 sera traité en prochaine discussion pour simplifier la saisie adresse :

- commune livrée comme source de vérité ;
- code postal prérempli ;
- zone déduite automatiquement ;
- validation backend obligatoire ;
- aucun doublon de services logistiques.

## Incident navigateur observé

Un écran Brave mobile `ERR_TOO_MANY_REDIRECTS` a été observé sur `recette.hodina.fr`. Le site fonctionnait en navigation privée. Conclusion : problème local de cookies/session navigateur, pas un incident serveur ni un motif de rollback.

## Suite

Ouvrir une nouvelle discussion à partir des docs mises à jour et du code de référence transmis pour traiter J5G-E1 proprement.

---

# Mise à jour 17/06/2026 — J5G-E1 → J5G-E2-bis-A validés localement

## Statut

```text
Branche : pilot/j5g-e1-commune-livree
Développement local : OK
Tests locaux : OK
Commit : OK
Push GitHub : OK
Recette : OK
Production : OK
Tag : j5g-e1-e2bis-prod
```

## Commits

```text
7831c1b feat(j5g): simplify delivery commune checkout and secure delivery totals
a70127c feat(j5g): move delivery validation before cart summary
```

## Validation fonctionnelle

- La commune livrée est choisie depuis les communes `DeliveryCommune` actives.
- Le code postal est prérempli.
- La zone est déduite et non modifiable par le client.
- Le changement de commune déclenche un recalcul de chemin et de prix.
- Le passage PT ↔ GT détecte la barge.
- Le prix passe bien sur le forfait local PT / GT, avec ajout du coût fixe barge si configuré.
- La page de confirmation affiche un récapitulatif commande complet.
- Le panier affiche `Livraison et validation` avant le récapitulatif.
- Le formulaire de modification d'adresse est replié par défaut.

## Commandes de validation utilisées

```powershell
php -l src/Service/DeliveryLogisticsService.php
php -l src/Controller/Admin/DeliveryCommuneConnectionCrudController.php
php -l src/Controller/CheckoutController.php
php bin/console cache:clear
php bin/console doctrine:schema:validate
```

Résultat : OK.

## Production

```text
1. Déploiement recette effectué et validé.
2. Tests mobile / navigateur effectués : Labattoir, Mamoudzou, barge, confirmation.
3. Zones locales PT_LOCAL / GT_LOCAL et coût fixe barge vérifiés fonctionnellement.
4. Déploiement production effectué.
5. Tag production créé : j5g-e1-e2bis-prod.
```

---

# Mise à jour 17/06/2026 — J5G-E1 → J5G-E2-bis-A validés en production

## Statut final

```text
Recette : OK
Production : OK
Tag : j5g-e1-e2bis-prod
```

## Déploiement production

Production mise à jour dans :

```text
/home/vopu3712/hodina.fr
```

Branche :

```text
pilot/j5j-commerce-mode-role-tester
```

Mise à jour effectuée en fast-forward :

```text
933d70b → 36cc357
```

## Base production

Avant migration, la production avait 2 migrations en attente :

```text
Version20260615140801
Version20260615225836
```

Après migration :

```text
Executed : 29
New : 0
Current : DoctrineMigrations\Version20260615225836
Schema : in sync
```

## Tests production validés

- Accueil accessible.
- Catalogue accessible.
- Ajout au panier OK.
- Panier OK.
- Bloc `Livraison et validation` avant le récapitulatif.
- Changement commune PT / GT OK.
- Frais recalculés OK.
- Total cohérent OK.
- Validation commande OK.
- Confirmation avec récapitulatif OK.
- EasyAdmin : commande, adresse, zone et total OK.

## Conclusion pilote

J5G-E1 à E2-bis-A est clôturé côté code, documentation, recette et production.

# Statut pilote — J5G-B4 intégré

Date : **17/06/2026**
Merge : `10ff512 merge(j5g): integrate BFS delivery logistics rules`

## État actuel

La branche principale pilote `pilot/j5j-commerce-mode-role-tester` contient maintenant J5G-B4.

Fonctionnalités disponibles :

```text
BFS entre communes
coût LAND spécifique ou fallback global
barge affichée dans le trajet
plafond global frais client
supplément multicommunes de collecte
snapshot logistique commande
page admin Logistique
```

## Validation locale

```text
cache:clear : OK
schema:validate : OK
migrations:status : Version20260617162000, New = 0
```

## À valider en recette / production

```text
settings globaux présents
création commande simple
création commande avec barge
création commande multicommunes
plafond 40 €
admin Logistique
snapshot JSON
```

## Décision pilote

Pendant le pilote, Hodina privilégie un modèle compréhensible : trajet le plus contraignant + supplément multicommunes, plutôt qu'une facturation complète par vendeur.

## Mise à jour 18/06/2026 — J5G-B4 production validée

Le jalon J5G-B4 est déployé en production via le tag :

```text
j5g-b4-20260618-v7
```

Statut technique :

- calcul logistique BFS livré ;
- snapshot logistique commande livré ;
- affichage admin Logistique livré ;
- aperçu image produit EasyAdmin corrigé ;
- script de déploiement par tag validé recette / prod ;
- sauvegarde DB automatique via `/bin/mariadb-dump` ;
- protection uploads runtime ;
- cron Messenger prod ajouté ;
- Doctrine schema OK.

Points de vigilance restants :

- vérifier fonctionnellement le parcours client complet après MEP ;
- sortir les env et uploads du suivi Git ;
- traiter les dépréciations Doctrine/EasyAdmin.

# Statut détaillé 19/06/2026 — v11 production validée

## Version active validée

```text
Tag : j5g-b4-20260618-v11
Commit : b998b63
Environnement : recette + production
```

## Tests validés

- Site public accessible.
- Admin accessible après MEP en fenêtre privée.
- Menus EasyAdmin repliables / dépliables.
- Section Réglages / Préouverture accessible.
- Section Utilisateurs corrigée : item cliquable.
- Miniatures images produit visibles.
- Ajax ajout panier catalogue OK.
- Ajax ajout panier fiche produit OK.
- E-mail de commande reçu après correction `MAILER_DSN`.
- Cron Messenger actif.
- Pas de nouvelle erreur PHP récente dans `public/error_log`.

## Incident observé et classé

Un plantage admin a été observé pendant la MEP. Il ne s'est pas reproduit hors fenêtre de déploiement.

Conclusion : incident transitoire, probablement requête pendant `checkout/cache/assets`.

## Point bloquant résolu

`MAILER_DSN=null://null` en production empêchait les mails réels. Après configuration d'un DSN SMTP réel côté `.env.local`, les e-mails de commande sont reçus.

## Suite validée

```text
1. Docs v11.
2. Dette technique env/uploads/assets/MAILER_DSN.
3. J5K GPS livraison.
4. J5L admin terrain.
5. J5M livreur terrain.
6. Paiement plus tard.
```


## Point de statut — Dette technique pré-J5K

Après J5G-B4 v11, la prochaine étape courte est le nettoyage Git env/uploads/assets et la clarification `MAILER_DSN`.

### À valider avant J5K

```text
.env.local / .env.prod.local / prod.env.local hors Git
public/uploads/products hors Git sauf .gitkeep
public/assets hors Git
public/error_log hors Git
MAILER_DSN réel documenté côté serveur
Validation mail = EmailLog SENT + réception réelle
```

### Pourquoi c'est important pour le pilote

J5K va ajouter de la donnée de livraison. Avant cela, il faut éviter que Git mélange :

- secrets serveur ;
- photos produits uploadées ;
- assets générés ;
- futures données GPS métier.

---

# Mise à jour 19/06/2026 soir — J5K-v8-quater — Validation locale panier adresses

## Statut

Validation locale annoncée comme bonne avant reprise en recette.

## Périmètre validé localement

- Panier avec séparation adresse de livraison / adresse de facturation.
- Adresse de livraison : commune, instructions livreur et GPS si présent.
- Adresse de facturation : adresse administrative uniquement, sans GPS, sans instructions livreur et sans zone affichée côté client.
- Cartes cliquables pour sélectionner l'adresse de la commande en cours.
- Suppression du bouton redondant `Sélectionner cette adresse`.
- Case `Utiliser cette adresse par défaut` visible uniquement en ajout ou modification d'adresse.
- Prise en compte de la case à la validation du panier.
- Création automatique d'une adresse `BILLING` si le client possède seulement une adresse de livraison.
- Non-régression attendue : snapshot commande, admin, fiche terrain et portail livreur inchangés.

## Reprise

À la prochaine discussion : repartir de l'état local validé, vérifier le commit/tag final, puis déployer en recette avec le script standard par tag.


---

# Mise à jour 20/06/2026 — Planning fin juin et images catalogue production

## Statut actuel

Le planning de suite est réaligné sur l’exploitation réelle du pilote.

Ordre de priorité :

```text
1. J5K final — panier / adresses / GPS / facturation
2. J5L — portail client MVP
3. J5M — portail livreur MVP
4. J5N — admin exploitation
5. J5O — images automatiques MVP
6. J5P — suivi financier manuel
```

## Images catalogue

Des images produits de démarrage ont été optimisées et mises en production manuellement.

Format :

```text
WebP
600 x 600
moins de 200 Ko par image
```

Produits concernés :

- ananas ;
- canne à sucre ;
- mangues ;
- manioc ;
- jackfruit / jacquier.

## Lecture critique

Cette mise en production règle le besoin immédiat de performance visuelle du catalogue. Elle permet de développer et présenter le portail avec un chargement plus léger.

Elle ne clôture pas le chantier technique d’optimisation automatique des images. Celui-ci reste à développer plus tard, en priorité moyenne à haute, surtout avant d’ouvrir largement la création / modification de produits à plusieurs administrateurs ou vendeurs.

## Point de vigilance

Ne pas relancer un chantier images avancé avant que le portail client et le portail livreur soient utilisables. En cas de retard, garder le MVP client/livreur prioritaire.


---

# Mise à jour 20/06/2026 soir — J5K-v8-quater clôturé recette

## Statut final recette

```text
Jalon : J5K-v8-quater
Statut : terminé fonctionnellement et techniquement en recette
Référence finale : devops-deploy-composer-before-console-v2
Commit : 48dae1d
```

## Validations fonctionnelles

Les 12 points fonctionnels recette ont été validés :

- adresse livraison par défaut ;
- adresse facturation par défaut ;
- facturation auto si client possède seulement une livraison ;
- clic carte livraison ;
- clic carte facturation ;
- case `Utiliser cette adresse par défaut` visible uniquement en ajout / modification ;
- GPS facultatif ;
- instructions livraison ;
- validation commande ;
- admin commande ;
- passage statut `prête` ;
- portail livreur existant en non-régression.

## Validations techniques

- `vendor/` retiré du suivi Git.
- Script de déploiement corrigé pour lancer Composer avant le premier `bin/console` si `vendor/autoload.php` est absent.
- Script validé en recette de bout en bout avec `RUN_COMPOSER=1`.
- Backup DB OK via `/bin/mariadb-dump`.
- Migrations Doctrine latest.
- Doctrine schema validate OK.
- AssetMapper : assets compilés.
- Cache prod clear/warmup OK.
- Colonnes GPS J5K détectées.
- Colonnes adresse enrichie J5K-bis détectées.
- URL recette HTTP 200.
- Cron Messenger recette corrigé.
- `git status --short` propre.

## Points non bloquants conservés en dette

- Dépréciation DoctrineBundle `doctrine.orm.controller_resolver.auto_mapping`.
- Dépréciation EasyAdmin `#[AdminDashboard]`.
- `public/uploads/products/.gitkeep` reste suivi volontairement pour conserver le dossier.

## Planning de suite validé

```text
20/06 — DevOps script + docs
21/06 — Prod J5K-v8-quater
22/06 — J5L-A UX panier mobile PWA
23/06 — Tests panier mobile
24/06 — J5L portail client MVP
25/06 — Tests portail client
26/06 — J5M portail livreur MVP
27/06 — Test parcours complet
28/06 — Admin exploitation + finance manuelle
29/06 — Gel fonctionnel
30/06 — Ouverture contrôlée
```

---

# Mise à jour 21/06/2026 — J5L validé recette

## Résumé

J5L est validé en recette. Le panier mobile est désormais plus court, plus lisible et plus exploitable en PWA.

## J5L-A — UX panier mobile

Validé :

- réorganisation du panier en ordre client logique ;
- masquage des détails techniques de chemin de livraison ;
- total et frais de livraison remontés juste après la liste des articles ;
- livraison, facturation puis validation ;
- sélection visuelle adresse corrigée ;
- champ texte GPS côté livraison ;
- suppression du bouton de retrait GPS.

## J5L-B — Sélecteur compact d'adresses

Validé :

- remplacement des sous-menus longs par un panneau compact ;
- séparation entre sélection et confirmation ;
- fermeture uniquement après `Utiliser cette adresse` ;
- maintien du GPS et des adresses par défaut ;
- compatibilité mobile / PWA.

Référence recette :

```text
Tag : recette-j5l-b-selecteur-adresses-20260621
Commit : 235a51f
```

## J5L-C — Facturation admin

Validé :

- affichage de l'adresse de facturation dans la fiche terrain admin ;
- affichage de l'adresse de facturation dans la vue détail EasyAdmin ;
- aucune modification du stockage.

## Contrôles recette

- tests navigateur panier OK ;
- validation commande OK ;
- fiche admin livraison OK ;
- fiche admin facturation OK ;
- non-régression logique J5K OK.

## Prochaine étape

Le chantier suivant est `J5M-A — Workflow livreur enrichi`.

Objectif : distinguer clairement :

```text
ready_for_delivery
→ picked_up
→ out_for_delivery
→ delivered
```


---

# Point détaillé J5M-C2 à J5M-C3-ter — Collecte vendeurs et identité vendeur

Date : 2026-06-21 soir.
Statut : validé localement, recette à faire.

## Ce qui a été validé

Le portail livreur sait maintenant afficher les informations de collecte vendeur dans une commande dépliée : point de retrait, GPS si disponible, produits et quantités à récupérer.

Le backoffice vendeur a été stabilisé autour d’une règle simple : un vendeur est aussi un client Hodina. Le formulaire vendeur demande donc un prénom et un nom obligatoires, et peut recevoir un nom de structure optionnel.

## Historique des couacs à conserver

1. Une première approche dupliquait adresse/GPS dans `Seller`. Elle a été rejetée pour éviter la dette technique, car `Address` portait déjà les informations nécessaires.
2. Le lien propre retenu est `Seller.customerAccount` + `Seller.pickupAddress`.
3. Un garde-fou PowerShell a été envisagé puis remplacé par un script PHP portable Windows/Linux.
4. La migration initiale `Version20260621143500` n’a appliqué que les colonnes en local. La migration corrective `Version20260621145500` a ajouté les index/FK manquants.
5. Le formulaire vendeur avec sélecteur d’adresse existante a été jugé peu naturel en création. Il a été remplacé par une saisie directe de l’adresse de retrait.
6. Les champs texte commune/code postal ont été remplacés par une sélection `DeliveryCommune` seedée.
7. Le prénom vendeur a été ajouté comme obligatoire, et le nom de structure a été séparé de l’identité du client vendeur.

## Règle logistique centrale

```text
Seller.deliveryCommune = source de vérité coût / trajet / barge / BFS.
Seller.pickupAddress = aide terrain livreur uniquement.
```

Le script suivant doit rester vert :

```powershell
php tools/assert-delivery-logistics-commune-source.php
```

## Migrations concernées

```text
Version20260621143500 — seller.customer_account_id + seller.pickup_address_id
Version20260621145500 — index/FK correctifs
Version20260621215500 — seller.business_name
```

## Validation locale

Validé localement : syntaxe PHP, Twig, Doctrine schema, migrations, garde-fou logistique, création vendeur, affichage catalogue, portail livreur et bloc collecte vendeurs.

## Suite

Avant déploiement : validation recette complète du flux vendeur + commande + livreur.

---

# Point J5M-C4 — Portail livreur renommé en `/djama`

Date : 2026-06-22.
Statut : validé localement, recette à faire avec J5M-C3-ter.

## Décision

Avant recette, l'entrée publique du portail livreur change :

```text
/livreur → /djama
```

`Djama` porte l'idée d'assembler / rassembler en mahorais, ce qui correspond au rôle du portail : regrouper commandes, collectes vendeurs, informations client, GPS, notes terrain et actions livreur.

## Changements

- `CourierDashboardController` expose désormais les routes sous `/djama`.
- `security.yaml` protège `^/djama` par `ROLE_COURIER`.
- Le libellé EasyAdmin du rôle livreur mentionne `/djama`.
- Les noms de routes Symfony ne changent pas.
- Aucune migration Doctrine.

## Recette attendue

Tester `/djama` avec un compte `ROLE_COURIER`, puis vérifier le parcours :

```text
READY_FOR_PICKUP → PICKED_UP → OUT_FOR_DELIVERY → DELIVERED
```

Vérifier aussi qu'un utilisateur non livreur n'accède pas à `/djama`.

---

# Mise à jour 24/06/2026 — J5O/J5P/J5Q validés recette

## Statut global

Le pilote dispose maintenant d'un parcours terrain complet côté livreur :

```text
commande prête
→ prise en charge
→ collecte vendeurs avec code
→ livraison démarrée
→ code réception client
→ livraison validée
→ notifications client
→ rémunération livreur historisée
```

## J5O-A validé recette

- Code réception client chiffré.
- Code généré au démarrage livraison.
- Renvoi du même code possible.
- Validation obligatoire pour passer `DELIVERED` côté Djama.
- Logs SMS/e-mail OK.

## J5P-A validé recette

- Notifications e-mail client sur statuts principaux.
- Notifications SMS déjà existantes conservées.
- Notification `ORDER_SELLER_COLLECTIONS_COMPLETED` ajoutée.
- Pas d'e-mail générique `OUT_FOR_DELIVERY` pour éviter le doublon avec le code client.
- Bouton `Voir` sur les logs e-mails EasyAdmin.

## J5Q-A validé recette

- Tables `courier_payout` et `courier_payout_line` créées.
- EasyAdmin `Livreurs > Livreurs` disponible.
- EasyAdmin `Livreurs > Rémunérations livreurs` disponible.
- EasyAdmin `Livreurs > Lignes rémunération` disponible.
- Génération de période validée.
- Paiement `DRAFT → VALIDATED → PAID` validé.
- Portail Djama `Mes paiements` validé.
- Test réel : paiement `PAID` de 30,00 € sur deux commandes.

## Risques restants

- Le SMS `customer_order_out_for_delivery` peut encore faire doublon avec `customer_delivery_code`.
- Pas encore d'export financier CSV.
- Pas encore de ligne d'ajustement manuel sur rémunération.
- Pas encore de reversement vendeur structuré.
- Pas encore de portail client MVP.

## Statut pilote après J5Q-A

```text
Socle commande / admin / livreur / notifications / rémunération livreur : validé recette.
Ouverture contrôlée possible seulement après validation du portail client MVP et des procédures support.
```

---

# Statut pilote — J5Q-C préparé

## Statut

```text
J5Q-C — Automatisation paiements livreurs
Statut : patch préparé, non encore appliqué au moment de cette documentation.
```

## Objectif de validation

- commande Symfony disponible ;
- dry-run fiable ;
- mode auto-due fiable ;
- récap admin envoyé ;
- cron recette installé ;
- paiement réel toujours manuel.

## Risque principal

Le risque n'est pas technique mais financier : ne jamais laisser croire que le récap vaut paiement réel. La validation admin et le marquage payé restent obligatoires.

# Statut pilote — J5Q-C-1 préparé

J5Q-C-1 prépare la structuration des réglages globaux par groupe dans EasyAdmin.

Statut attendu avant recette :

- migration `Version20260624233000` prête ;
- `HodinaSetting` enrichi ;
- menu EasyAdmin `Réglages` structuré ;
- vues filtrées par groupe ;
- pas de modification des e-mails.

Objectif pilote : réduire le risque d'erreur admin en évitant que les paramètres commerce, livraison, paiements, notifications et technique restent mélangés dans une seule liste.

# Statut pilote — J5Q-C-2 préparé

J5Q-C-2 prépare le branding e-mail paramétrable.

Statut attendu avant recette :

- migration `Version20260625090000` prête ;
- groupe EasyAdmin `Branding e-mail` disponible ;
- quatre réglages `email_branding_*` initialisés ;
- tous les e-mails existants raccordés à `EmailBrandingService` ;
- SMS non modifiés.

Objectif pilote : éviter toute confusion entre e-mails dev, recette et production à la réception.

---

# Statut pilote — J5Q-C / J5Q-C-1 / J5Q-C-2 validés recette

Date : 25/06/2026

## J5Q-C — Automatisation paiements livreurs

Statut : validé recette.

- tag recette : `j5q-c-cron-recap-admin-recette` ;
- commande `hodina:courier-payouts:generate` disponible ;
- dry-run validé ;
- mode `--auto-due` validé ;
- cron recette installé ;
- hors dates dues, le cron répond correctement qu'aucune génération n'est lancée.

Règle conservée : génération automatique de brouillons uniquement, validation et paiement réel restent manuels.

## J5Q-C-1 — Structuration des réglages

Statut : validé recette.

- tag recette : `j5q-c1-settings-groups-recette` ;
- migrations `Version20260624233000` et `Version20260624234500` exécutées en recette ;
- `HodinaSetting` structuré par groupes ;
- section EasyAdmin `Réglages` structurée ;
- paramètres paiements livreurs présents et fonctionnels.

Test clé validé : désactiver `Génération cron paiements livreurs` bloque bien la commande `--auto-due`.

## J5Q-C-2 — Branding e-mail

Statut : validé recette techniquement, tests e-mails réels à compléter.

- tag recette : `j5q-c2-branding-email-recette` ;
- commit : `3586560` ;
- migration `Version20260625090000` exécutée en recette ;
- `doctrine:schema:validate` OK ;
- `lint:twig` OK sur les 6 templates d'e-mail ;
- groupe `email_branding` présent ;
- EasyAdmin > Réglages > Branding e-mail visible.

Reste à valider opérationnellement : réception réelle des e-mails avec préfixe `[Recette]`, formule, signature et `EmailLog.subject` cohérent.

## Incident intermittent recette

Un `ERR_CONNECTION_CLOSED` a été observé côté navigateur mobile après J5Q-C-2, mais les logs serveur disponibles ne prouvent pas une régression applicative : les accès récents observés sont majoritairement `200` ou `302`, PHP web est en 8.4.21, Doctrine et cache sont OK.

Statut : surveillance / debug, pas rollback.

Document de référence : `DEBUG_RECETTE_HODINA.md`.


# Mise à jour 27/06/2026 — Statut détaillé J5T à J5W

## Validé recette

- J5T-A / J5T-A-bis : formulaire checkout simple nouveau client validé en recette. Le client invité ne saisit pas de mot de passe avant validation ; le compte est créé automatiquement et `ORDER_CREATED` contient le lien sécurisé de création du mot de passe.
- J5U-A : expéditeur e-mails paramétrable EasyAdmin validé en recette. Les e-mails sont bien envoyés avec `commande@hodina.fr`; `ORDER_CREATED` est aussi envoyé en copie interne à `commande@hodina.fr`.

## Présent dans les sources / validation à confirmer

- J5S-B-bis : date/heure client pour point de remise, avec correctif contre `AlreadySubmittedException`. Le code est présent ; si la recette complète point de remise n’a pas été rejouée, elle reste à confirmer.
- J5V-A : délai minimum de commande par produit. Le code et la migration sont présents dans les sources du 27/06/2026 ; aucun résultat de test recette n’est documenté dans cette mise à jour.

## Prévu, non codé

- Ancien J5W-A supersédé : restriction produit par communes livrables, repoussé en J5Y-A pour éviter une collision avec le nouveau J5W-A zones tarifaires locales.
- J5W-B : DeliveryArea extensible, séparée de DeliveryZone.
- J5W-C : planning par DeliveryArea avec cutoff 10h00 la veille.
- J5W-D : livraison express hors créneau standard avec supplément paramétrable.
- J5W-E : proposition d’heure livreur pour point de remise.

## Point de vigilance majeur

La future `DeliveryArea` ne doit pas casser la barge : Mamoudzou, Grande-Terre Sud et Grande-Terre Centre/Nord restent en Grande-Terre. Petite-Terre reste l’autre grande zone. Labattoir reste une `DeliveryCommune` seedée en Petite-Terre.

# Mise à jour 28/06/2026 — Stabilisation point de remise / checkout

## Validé ou amélioré localement

- J5S-B-ter : séparation stricte entre point de remise et adresse standard. En point de remise, la commune du point est utilisée pour le preview des frais ; en standard, la commune de l’adresse client reste utilisée.
- J5S-B-quater : feedback global sous le header et état visuel des boutons `Valider` améliorés.
- Correctif de timing UX : aucun message rouge global ne s’affiche avant tentative de validation.
- Correctif `CheckoutType` : `address` et `commune` sont obligatoires uniquement en mode standard ; `deliveryPointTimeWindowId` reste non obligatoire ; les messages d’identité sont en français.
- Correctif référence commande : `OrderReferenceGenerator` évite les collisions en utilisant `MAX(dailyOrderNumber) + 1` puis une vérification d’existence.

## État supersédé après recette du 28/06/2026

Cette section correspondait à l’état avant les confirmations recette. Elle est supersédée par les mises à jour du 28/06/2026 en fin de fichier : J5S-B-ter/quater est validé recette ; J5V-A a été corrigé par `3b508d0` puis revalidé recette sous `recette-j5v-a-checkout-lead-time-fix-20260628` ; J5W reste prévu, non codé.

## Risques résiduels

- Les tests doivent encore couvrir le produit `DELIVERY_POINT_OPTIONAL` en bascule standard → point → standard.
- Le bouton sticky et le bouton principal doivent être testés tous les deux sur mobile.
- Les erreurs serveur doivent rester en français pour les cas maîtrisés.
- Les calculs de barge ne doivent pas être modifiés par ces correctifs.

## Mise à jour 28/06/2026 — J5T-C checkout invité avec compte existant

Statut : patch préparé, validation locale/recette à jouer.

Objectif : permettre une commande invitée avec un e-mail déjà connu sans créer de doublon client. Le rattachement au compte existant doit être confirmé par popup avant création de commande.

Points à valider :

- pas de doublon `Customer` ;
- popup après soumission complète, pas à la saisie de l’e-mail ;
- conservation des données point de remise ou adresse standard ;
- mention de rattachement dans `ORDER_CREATED` et `EmailLog.body` ;
- aucune régression sur les frais ou Djama.

## Mise à jour 28/06/2026 — Pause après diagnostic J5T-C

État au moment de la pause :

- J5S-B-ter/quater est déployé recette sous le tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628` et doit encore être validé fonctionnellement côté navigateur.
- J5T-C est présent dans les sources locales transmises, sans migration.
- Le test e-mail nouveau a été annoncé comme passé.
- Le test e-mail existant a d’abord révélé deux problèmes : un `AlreadySubmittedException` causé par une tentative de mutation du formulaire soumis, puis l’ancien bloc `Un compte existe déjà...` encore actif.
- Dans les sources du 28/06/2026, la logique attendue est visible : popup de confirmation piloté par `showExistingAccountConfirmation`, rattachement au `Customer` existant et mention dans `ORDER_CREATED`.

Statut historique supersédé : J5T-C était non clôturé au moment de cette pause. La mise à jour du 28/06/2026 acte ensuite la validation locale + recette. La production reste non actée.

# Mise à jour 28/06/2026 — Validations recette J5S-B-ter/quater, J5T-C, J5V-A

## Validé recette

- J5S-B-ter/quater : séparation stricte livraison standard / point de remise validée en recette sous le tag `recette-j5s-b-ter-quater-checkout-point-standard-20260628`.
- J5T-C : checkout invité avec e-mail existant validé localement puis recette. Commit `38f9e23`, tag `recette-j5t-c-checkout-existing-account-20260628`.
- J5U-A : expéditeur e-mails paramétrable EasyAdmin confirmé avec `commande@hodina.fr`.
- J5V-A : délai minimum de commande par produit corrigé puis revalidé localement et recette le 28/06/2026. Correctif `3b508d0`, tag `recette-j5v-a-checkout-lead-time-fix-20260628`.

## État historique supersédé — avant MEP production

Cette section décrivait l’état avant MEP. Elle est supersédée par la validation production du 29/06/2026 sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

## Incohérence technique J5V-A résolue avant production

L’incohérence a été résolue le 28/06/2026. Le correctif `3b508d0` ajoute l’appel serveur à `DeliveryPointCartService::validateMinimumOrderLeadTime()` dans `CheckoutController` pour le flux point de remise. La recette est revalidée sous `recette-j5v-a-checkout-lead-time-fix-20260628` : produit à délai 48 h, rendez-vous trop proche refusé, message global affiché, panier conservé. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

# Mise à jour 28/06/2026 — J5V-A corrigé et revalidé recette

J5V-A est désormais corrigé et revalidé en recette. La régression détectée venait d’un branchement serveur manquant : le champ produit et le service existaient, mais le checkout n’appelait plus `DeliveryPointCartService::validateMinimumOrderLeadTime()`.

Correctif validé :

- commit `3b508d0 fix(j5v-a): enforce product minimum order lead time at checkout` ;
- tag recette `recette-j5v-a-checkout-lead-time-fix-20260628` ;
- validation recette : produit à délai 48 h, rendez-vous trop proche refusé, message global checkout affiché, panier conservé.

Statut : validé localement et recette. Production ensuite validée sous `prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628`.

Contrôles à conserver pour les prochaines MEP : délai trop proche/refusé, délai valide/accepté, J5S point de remise/standard et J5T-C e-mail invité existant.

# Mise à jour 29/06/2026 — Production validée J5S / J5T-C / J5U / J5V

## Statut production

Production validée sous le tag :

```text
prod-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628
```

Commit promu :

```text
d5466fe docs(j5s-j5t-j5v): record recette validations and j5v fix
```

Le tag candidat `prod-candidate-j5s-j5t-j5u-j5v-checkout-stabilisation-20260628` a d’abord été aligné en recette sur `d5466fe`, puis le tag production final a été créé et poussé.

## Lots concernés

- J5S-B-ter/quater : séparation stricte livraison standard / point de remise, frais basés sur la commune du point en mode point, frais basés sur l’adresse client en mode standard.
- J5T-C : checkout invité avec e-mail existant, popup au premier clic, aucune commande avant confirmation, rattachement au `Customer` existant après confirmation.
- J5U-A : expéditeur e-mails paramétrable EasyAdmin, `commande@hodina.fr`.
- J5V-A : régression de branchement serveur corrigée par `3b508d0`, délai minimum produit appliqué au checkout point de remise.

## Validations production annoncées

Les tests minimum production sont annoncés comme fonctionnels : site/panier, point de remise avec délai trop proche refusé, délai valide accepté, e-mail existant invité avec popup/rattachement, et vérification minimale `ORDER_CREATED`.

## Risques résiduels non bloquants

- `public/uploads/products/.gitkeep` reste suivi par Git ; le dossier upload doit rester traité comme runtime.
- Dépréciations connues : Doctrine `controller_resolver.auto_mapping` et attribut EasyAdmin `#[AdminDashboard]` à traiter plus tard.
- J5W / `DeliveryArea` reste prévu/non codé ; ne pas mélanger avec les calculs `DeliveryZone`.


## Mise à jour 29/06/2026 — J5W-A zones tarifaires locales par secteur

Statut : **validé localement côté fonctionnel et base / validé recette / validé production**.

Objectif : découper la tarification locale Grande-Terre par secteur sans remplacer les territoires techniques `PT` / `GT`.

Validation locale annoncée :

- branche `develop` créée depuis `main` après validation production checkout ;
- migration `Version20260629083000` exécutée localement ;
- `doctrine:schema:validate` OK ;
- zones tarifaires visibles en EasyAdmin ;
- pas de doublon `PETITE_TERRE_LOCAL` après correction ;
- `Dzaoudzi`, `Labattoir`, `Pamandzi` rattachées à `PT_LOCAL` ;
- panier standard fonctionnel ;
- panier point de remise fonctionnel ;
- champ `deliveryPointCustomerInstructions` replacé dans le bloc attendu.

Point technique corrigé : l’archive de validation a montré un garde-fou `tools/assert-j5w-a-local-pricing-zones.php` trop strict, qui détectait `PETITE_TERRE_LOCAL` même lorsqu’il était seulement mentionné dans la logique de nettoyage. Le garde-fou a été corrigé et rejoué OK avant le commit local.

État recette/prod : J5W-A validé recette sous `recette-j5w-a-local-pricing-zones-20260629` (commit `162fcb4`) puis validé production sous `prod-j5w-a-local-pricing-zones-20260629` (commit `cea4d19`).

### Validation recette J5W-A — 29/06/2026

Recette validée sous le tag `recette-j5w-a-local-pricing-zones-20260629`, commit `162fcb4 merge(j5w-a): local pricing zones by sector`.

Contrôles recette : dépôt propre, schéma Doctrine synchronisé, migration `Version20260629083000` current/latest, garde-fou J5W-A OK, `PETITE_TERRE_LOCAL` absent, mapping des 18 communes conforme.

Tests fonctionnels recette annoncés OK : EasyAdmin zones tarifaires, EasyAdmin communes livrées, panier standard, champ instructions point de remise correctement rangé.

### Validation production J5W-A — 29/06/2026

Production validée sous le tag `prod-j5w-a-local-pricing-zones-20260629`, commit runtime `cea4d19 docs(j5w-a): record recette validation`. Le commit documentaire post-MEP `62d0363 docs(j5w-a): record production validation` ne nécessite pas de redéploiement production.

Contrôles production actés : déploiement terminé avec succès, dépôt propre, `doctrine:schema:validate` OK, migrations current/latest sur `Version20260629083000`, garde-fou J5W-A OK, `PETITE_TERRE_LOCAL` absent, zones tarifaires locales présentes, et tests navigateur production OK sur EasyAdmin zones tarifaires, EasyAdmin communes livrées et panier standard.




## Mise à jour 29/06/2026 — J5X-A tarifs zones tarifaires

Statut : **préparé pour validation locale**.

J5X-A ajuste les frais client par secteur après validation production de J5W-A. Le lot conserve la formule de livraison existante et ne modifie pas la structure des communes ni les territoires PT/GT.

Tarifs attendus :

```text
PT_LOCAL = 12 €
MAMOUDZOU_LOCAL = 12 €
CENTRE_LOCAL = 17 €
SUD_LOCAL = 21 €
NORD_LOCAL = 21 €
GT_LOCAL = 21 € fallback technique
```

Contrôles attendus avant recette : migration exécutée localement, schéma Doctrine OK, garde-fou J5X-A OK, `PETITE_TERRE_LOCAL` absent, rattachements J5W-A inchangés, tests panier par secteur réalisés.

Production : non actée à ce stade.

## J5X-B — Statut calendrier livraison

État actuel : livré pour validation locale ciblée sur `develop`. Non validé recette et non validé production.

Le lot ajoute les jours de passage par secteur et le cutoff 10h J-1 paramétrables dans EasyAdmin. La promesse affichée au client reste un prochain passage possible, pas une garantie ferme.

## J5X-C — Statut promesse produit

Statut : **implémentation préparée sur `develop`, validation locale à faire**.

J5X-C améliore la confiance client sur la fiche produit : produit standard selon commune, ou produit sur créneau. Le lot ne modifie pas la formule de livraison ni les frais J5X-A/J5X-B.

Contrôles attendus : migration locale, schéma Doctrine, garde-fou `tools/assert-j5x-c-product-delivery-promises.php`, fiche produit standard avec commune connue/inconnue, fiche produit sur créneau configurée depuis EasyAdmin.

## Statut J5X-D — Catalogue

J5X-D est préparé sur `develop` pour améliorer le catalogue public : recherche, filtre catégorie, tri, priorité admin et rendu AJAX progressif.

Statuts :

- Local : à valider.
- Recette : non validé.
- Production : non validé.

Point de vigilance : ne pas confondre ce lot avec la disponibilité produit par commune, qui reste repoussée.

# Mise à jour 01/07/2026 — Statut J5X/J5Y avant validation finale J5Y

Cette section est conservée comme trace de reprise, mais elle est désormais **supersédée pour J5Y** par la validation production `prod-j5y-carnet-livraison-footer-20260701` documentée juste après.

## J5X — Statut consolidé

J5X-A/B/C/D sont présents dans le code et validés par les garde-fous techniques. Le lot groupé J5X a été déployé en recette sous :

```text
recette-j5x-livraison-catalogue-20260630-1440
```

Les contrôles connus restent : schéma Doctrine OK, migrations exécutées, assets compilés, cache warmup OK, asserts J5X OK. La validation J5Y a rejoué les parcours sensibles liés au catalogue et au panier afin de vérifier l’absence de régression.

Production J5X : non actée dans cette mise à jour, sauf les lots antérieurs déjà explicitement validés production (`J5W-A`, `J5S/J5T/J5U/J5V`).

## J5Y — Ancien état de reprise supersédé

Au début du 01/07/2026, J5Y-A/B/C/D étaient encore décrits comme non recette ou en arbitrage favicon. Cet état n’est plus le statut courant.

Statut courant : J5Y-A/B/C/D/E/F/G/H validé production sous :

```text
prod-j5y-carnet-livraison-footer-20260701
```

Ne pas utiliser les anciennes mentions `non mergé main`, `non déployé recette`, `favicon à arbitrer` ou `commit/push à confirmer` pour piloter la suite J5Y. Ces mentions correspondent à un état intermédiaire maintenant clos.

# Mise à jour 01/07/2026 — J5Y-E/F/G/H validé production

## Statut global

J5Y-E/F/G/H est **validé localement, validé recette, déployé production et validé production**.

Tag recette final validé :

```text
recette-j5y-carnet-livraison-footer-clean-20260701
```

Commit recette validé :

```text
b1bbab6 chore(j5y): remove delivery guide backup template
```

Tag production validé :

```text
prod-j5y-carnet-livraison-footer-20260701
```

Commit production validé :

```text
200d84b merge: document j5y recette validation
```

Production : **déployée et validée le 01/07/2026**.

## Contenu validé

- `/` sert le catalogue public.
- `/catalogue` redirige vers `/`.
- `/decouvrir-hodina` est la page institutionnelle canonique.
- `/blog` et `/blog/decouvrir-hodina` redirigent vers `/decouvrir-hodina`.
- `/carnet` est actif comme page d’entrée pédagogique.
- `/carnet/livraison` est actif comme page de réassurance livraison.
- Header public : `Infos livraison` vers `/carnet/livraison`.
- Footer public compact : réassurance, Explorer, Livraison, Pratique.
- Images WebP de zones livraison affichées et légères.
- Djama reste privé et absent des pages publiques.
- Les jours de livraison restent indicatifs ; le panier reste la source de vérité pour les frais, dates et créneaux.

## Validations annoncées

Validations techniques locales et recette : `lint:twig`, `lint:container`, assertions J5Y-C, J5Y-D, J5Y-F, J5X-C-quater et J5X-D.

Validations navigateur recette puis production annoncées OK : catalogue, Découvrir Hodina, Carnet, livraison, header/footer, panier standard, point de remise, GPS, admin/livreur minimal.

## Points de vigilance non bloquants

- Ne pas déployer l’ancien tag `recette-j5y-carnet-livraison-footer-20260701`, supersédé car un fichier `.bk` avait été embarqué.
- Le tag propre est `recette-j5y-carnet-livraison-footer-clean-20260701`.
- Le script de MEP recette a signalé que `PUBLIC_URL` n’était pas renseigné ; les tests HTTP publics restent donc manuels pour cette MEP.
- `public/uploads/products/.gitkeep` reste suivi par Git : dette runtime à traiter plus tard.
- Symfony 8.0.5 non-LTS et les dépréciations Doctrine/EasyAdmin observées en warmup sont à planifier hors J5Y.


# Mise à jour 01/07/2026 — J5Y production validée

## Statut de clôture

J5Y-A/B/C/D/E/F/G/H est clos côté MVP public : validation locale, recette et production réalisées.

```text
Tag recette final : recette-j5y-carnet-livraison-footer-clean-20260701
Tag production validé : prod-j5y-carnet-livraison-footer-20260701
Commit production : 200d84b merge: document j5y recette validation
```

## Production validée

La MEP production a été réalisée sur `~/hodina.fr` avec `PUBLIC_URL=https://hodina.fr`. Le script de déploiement a confirmé : tag contenu dans `origin/main`, sauvegarde DB, uploads runtime restaurés, assets compilés, cache prod réchauffé, Doctrine schema synchronisé, migrations à jour, cron Messenger présent et URL publique `https://hodina.fr` en HTTP 200.

Les tests navigateur production ont ensuite été annoncés validés.

## Décision de suite

Ne plus rouvrir J5Y sauf bug bloquant. Les évolutions suivantes doivent repartir dans un nouveau lot : contenu Carnet supplémentaire, portraits vendeurs, saisonnalité produits, disponibilité produit par commune, paiement en ligne ou pagination avancée catalogue.

# Mise à jour 02/07/2026 — J5Z checkout/admin UX validé production

## Statut de clôture

J5Z est le lot de finition checkout/admin réalisé après clôture J5Y. Il est **validé localement, validé recette, déployé production et validé production**.

```text
Tag recette final validé : recette-j5z-delivery-fee-reason-refresh-20260702
Tag production final : prod-j5z-delivery-fee-reason-refresh-20260702
Commit hotfix : ed2e873 fix(cart): keep delivery fee reason after logistics refresh
Merge final main/develop : 09243d2 merge: fix j5z delivery fee reason refresh
```

Les tags recette suivants sont supersédés et ne doivent plus être utilisés pour un déploiement de référence :

```text
recette-j5z-checkout-admin-ux-20260702
recette-j5z-checkout-admin-ux-fix-mobile-20260702
```

## Contenu validé

- EasyAdmin Produit : ordre des champs opérationnels repositionné juste après `Marge produit Hodina (%)`.
- Catalogue : phrase d’aide livraison clarifiée pour expliquer que le panier confirme frais, jours possibles et créneaux selon la commune.
- Téléphone client : champ `Indicatif` explicite avant le téléphone pour inscription et checkout invité ; Mayotte / La Réunion `+262` proposé en premier ; France `+33`, Comores `+269`, Madagascar `+261` disponibles.
- Téléphone client connecté : le champ `Indicatif` technique est caché et ne traîne plus en bas du panier.
- Rattrapage recette : commande `hodina:customers:normalize-phones` exécutée en simulation puis en application, avec 84 numéros modifiés et 0 non normalisable sur la base recette.
- Frais de livraison : annotation factorisée sous les frais quand elle est justifiée : `Inclus : barge.`, `Inclus : 1 commune traversée + barge.`, `Inclus : X communes traversées + barge.`.
- Frais standard / trajet simple : aucune annotation affichée, volontairement, pour éviter d’ajouter un bruit inutile.
- Panier connecté : l’annotation apparaît dès le premier affichage lorsque le trajet le justifie.
- Recalcul AJAX : après changement d’adresse, le flash apparaît et l’annotation reste visible sans rafraîchir la page.
- Flash frais recalculés : message en haut du panier, fond opaque marron clair, croix de fermeture, sans persistance base.
- Mobile : champ `Date de rendez-vous` aligné et contenu dans la carte sur checkout invité et connecté, y compris Safari/iPhone.
- Email commande : annotation frais ajoutée sous les frais de livraison dans le HTML et le texte.
- SMS / récapitulatif commande : annotation frais ajoutée au récapitulatif lorsque disponible.

## Règles métier validées

```text
Commune = source de vérité pour frais, barge, jours et créneaux.
Frais standard simple = pas d’annotation.
Frais avec barge / commune(s) traversée(s) = annotation affichée.
Village / localité futur = précision terrain, pas source tarifaire.
```

## Points de vigilance

- Le rattrapage téléphone production n’est pas journalisé dans les extraits fournis à cette mise à jour documentaire : ne pas le relancer sans simulation préalable et lecture des lignes proposées.
- Le cache de prévisualisation logistique du panier est versionné (`j5z-delivery-fee-reason-v1`) pour forcer le recalcul des sessions anciennes après ajout de l’annotation.
- Les warnings techniques déjà connus restent hors J5Z : Symfony 8.0.5 non-LTS, dépréciations Doctrine/EasyAdmin, cron recette avec `--time-limit=50--memory-limit=128M`, `public/uploads/products/.gitkeep` suivi par Git.

## Suite

Ne plus rouvrir J5Z sauf bug bloquant réel. La prochaine évolution terrain envisagée est J5AA — `AddressLocality`, pour ajouter une localité d’adresse affichée comme `Localité` avec l’aide `Village / quartier / lieu-dit`, sans remplacer la commune de livraison.

# Statut détaillé 03/07/2026 — J5AB / J5AC

## Statut global

Le MVP Hodina est validé production jusqu’à J5AC.

Derniers lots production :

```text
J5AB : prod-j5ab-catalogue-mobile-achat-20260703
J5AC : prod-j5ac-espace-client-ajax-20260703
```

## J5AB — Catalogue mobile orienté achat

Statut : validé localement, recette et production.

Apports :

- catalogue `/` plus orienté achat ;
- suppression du bloc institutionnel haut ;
- recherche + loupe + filtres sur une ligne mobile ;
- panneau catégorie/tri compact ;
- compteur et produits rapprochés ;
- logique catalogue J5X-D conservée.

Risques résiduels :

- qualité images / prix / disponibilité produits devient encore plus visible ;
- ne pas réintroduire de contenu marketing lourd avant les produits.

## J5AC — Espace client

Statut : validé localement, recette v2 et production.

Apports :

- hub `/mon-compte` ;
- commandes en cours/passées ;
- détail commande protégé propriétaire ;
- profil modifiable ;
- mot de passe avec ancien mot de passe ;
- reset connecté via `SmsLog` ;
- email unique nullable ;
- AJAX progressif discret.

Qualité DB production après correction :

- 9 clients ;
- 0 email null ;
- 0 email vide ;
- 0 doublon email normalisé ;
- 0 email invalide simple ;
- `UNIQ_CUSTOMER_EMAIL` présent ;
- `customer.phone` non unique.

Correction production documentée :

```text
customer.id=13 : chahere.kdu → chahere.kdu@outlook.fr
```

## Validations techniques

Asserts à conserver :

```bash
php tools/assert-j5ab-catalogue-mobile-buy-first.php
php tools/assert-j5ac-customer-email-db-readiness.php
php tools/assert-j5ac-client-account-finalization.php
php tools/assert-j5ac-client-account-ajax.php
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:schema:update --dump-sql --env=prod
```

## Points de vigilance

- Le script de déploiement affiche encore des tests navigateur génériques parfois libellés `recette` même en production.
- `PUBLIC_URL` n’a pas toujours été fourni, donc l’URL publique n’est pas systématiquement testée automatiquement.
- Les dépréciations Symfony/Doctrine/EasyAdmin restent hors J5AC.
- Les uploads produits restent une donnée runtime à sortir entièrement du suivi Git hors urgence.

# Statut détaillé 04/07/2026 — documentation réalignée avant J5AA

> ⚠️ Bloc historique (état AVANT codage de J5AA). Le « Statut produit actuel » ci-dessous n’est plus à jour — voir « Statut détaillé 05/07/2026 — J5AA livré » en fin de fichier.

## Incohérence corrigée

Un ancien bloc `Prochaine priorité P1 — Portail client MVP` dans `TODO.md` contredisait l’état réel : J5AC a finalisé l’espace client. `/mon-compte` est un hub compte client ; `/mon-compte/profil` et `/mon-compte/mot-de-passe` existent ; `/mon-compte/adresses` reste non codé.

## Statut produit actuel

```text
MVP validé production jusqu’à J5AC.
J5Z clos.
J5AB clos.
J5AC clos.
J5AA prévu, non codé, non migré, non recette, non production.
```

## Cadrage J5AA complété

J5AA doit couvrir la localité d’adresse, mais aussi la cohérence code postal / commune avec le seed existant. Le code postal et la localité restent des aides de saisie / localisation ; `DeliveryCommune` reste la source de vérité logistique et tarifaire.

Point de vigilance : le checkout et l’inscription n’ont pas exactement le même niveau d’UX aujourd’hui. Le checkout s’appuie déjà sur une commune livrée sélectionnée et un code postal déduit ; l’inscription garde des champs texte validés côté serveur. J5AA doit corriger cette asymétrie sans réécrire le checkout.

# Statut détaillé 05/07/2026 — J5AA livré (recette + production)

## Supersession

Le bloc « Statut détaillé 04/07/2026 » ci-dessus décrivait l’état **avant** codage de J5AA (« J5AA prévu, non codé, non migré, non recette, non production »). Cet état est **supersédé** : J5AA a été codé puis validé recette et production le 2026-07-04.

## Statut produit actuel

```text
MVP validé production jusqu’à J5AA.
J5Z clos.
J5AB clos.
J5AC clos.
J5AA livré : J5AA-0, J5AA-A, J5AA-B validés recette + production 2026-07-04.
```

## Détail des sous-lots J5AA

- **J5AA-0 — Audit strict des adresses DELIVERY** : outil read-only `tools/assert-j5aa-delivery-address-commune-audit.php`. Aucune migration, aucune entité, aucun champ ajouté.
- **J5AA-A — Localité d’adresse** : entité `AddressLocality` + `AddressLocalityRepository`, `Address.addressLocality` / `Address.localityText`, snapshot `CustomerOrder.deliveryAddressLocalityName`, CRUD EasyAdmin `Localités d’adresse`, commande idempotente `hodina:address-localities:seed` (seed initial Mamoudzou), champ optionnel `Localité` au checkout. Migration `Version20260704210000`.
- **J5AA-B — Couple code postal + commune au checkout** : contrôle serveur strict du couple `postalCode + commune` au checkout et dans l’aperçu AJAX des frais, via `DeliveryCommuneMatcherService`. Sans migration, sans nouvelle relation Doctrine ; persistance inchangée dans `Address.postalCode` / `Address.commune`.

## Invariants confirmés

`DeliveryCommune` reste la seule source de vérité logistique et tarifaire. La localité et le code postal restent des aides de saisie/localisation et ne calculent jamais les frais, la barge, les jours ni les créneaux. Aucun `Address.deliveryCommune` n’a été introduit.

## Tags

```text
recette-j5aa-address-locality-20260704
prod-j5aa-address-locality-20260704
```
