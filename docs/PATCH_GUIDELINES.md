# Règles patch Hodina — Windows / PowerShell

## Contexte

Le développement local Hodina se fait depuis :

```text
E:\hodina\hodina.fr
```

Plusieurs incidents de patch ont été rencontrés pendant J5B / J5C :

- chemins incomplets (`Controller/...` au lieu de `src/Controller/...`) ;
- chemins avec préfixes parasites (`/mnt/data/...`) ;
- patchs créant de nouveaux fichiers sans vrai format Git `/dev/null` ;
- nécessité d'utiliser `-p3` ou `-p4`, ce qui ne doit pas être la norme.

## Règle retenue

Tous les patchs fournis pour Hodina doivent être applicables directement depuis :

```powershell
PS E:\hodina\hodina.fr>
```

avec :

```powershell
git apply --check .\nom_du_patch.patch
git apply .\nom_du_patch.patch
```

Sans option `-p`.

## Format attendu

Les chemins doivent être complets depuis la racine du dépôt :

```text
config/packages/security.yaml
src/Controller/...
src/Entity/...
templates/...
migrations/...
```

Le patch doit être un vrai patch Git :

```text
diff --git a/src/... b/src/...
--- a/src/...
+++ b/src/...
```

Pour un nouveau fichier :

```text
new file mode 100644
index 0000000..
--- /dev/null
+++ b/migrations/...
```

## Règle migration

Ne jamais générer une migration corrective dont le timestamp est antérieur à une migration déjà créée mais non encore déployée.

Une migration corrective doit être postérieure à la migration qu'elle corrige.


---

# Règles complémentaires pour J5E / J5F / J5G

## Faire des patchs plus petits

Les prochains jalons touchent des zones sensibles :

- prix ;
- marge ;
- commande ;
- livraison ;
- panier ;
- checkout ;
- données vendeurs.

Un développeur débutant doit éviter un patch géant.

Découpage recommandé :

```text
1 patch entités + migration
1 patch CRUD EasyAdmin
1 patch service métier
1 patch intégration panier / checkout
1 patch docs
```

## Toujours expliquer les données figées

Dès qu'un patch modifie les prix ou la livraison, il doit préciser :

```text
quelles valeurs sont calculées dynamiquement
quelles valeurs sont figées dans la commande
```

## Ne pas anticiper trop fort le portail vendeur

Il faut coder compatible avec le futur portail vendeur, mais ne pas le développer trop tôt.

Règle :

```text
Préparer les services et les relations.
Reporter les interfaces vendeur complètes à J6.
```


---

# Retour d'expérience J5E — règles renforcées

## Migration obligatoire avec les entités

Si un patch modifie une entité Doctrine, il doit inclure ou annoncer immédiatement la migration correspondante.

À vérifier :

```powershell
php -l migrations\VersionXXXX.php
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
```

## Vérification syntaxe PHP

J5E a montré qu'un patch peut laisser un fichier tronqué. Après chaque patch qui ajoute un service ou une migration, vérifier :

```powershell
php -l src\Service\NomDuService.php
php -l migrations\VersionXXXX.php
```

## Diagnostic Doctrine avant force

Si `schema:validate` échoue :

```powershell
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:update --dump-sql
php bin/console cache:clear
php bin/console doctrine:schema:validate
```

En préproduction, ne pas utiliser `schema:update --force` sans validation explicite.

## Règle pour J5F

Découpage impératif :

```text
Patch 1 — entités + migration + php -l
Patch 2 — CRUD EasyAdmin
Patch 3 — service logistique si nécessaire
Patch 4 — intégration panier / checkout seulement après validation des données
Patch 5 — docs
```

---

# Règle patch J5F — Clarification barge avant génération de code

## Règle métier à respecter dans les patchs J5F / J5G

Tout patch qui touche la livraison doit respecter :

```text
requiresBarge = true uniquement si clientTerritory !== sellerTerritory
```

Il est interdit de déclencher la barge sur :

```text
commune non voisine
commune éloignée
distance supposée
absence de relation neighboringCommunes
```

## À vérifier dans chaque patch

- [ ] Le patch ne confond pas `REMOTE_COMMUNE` et `OTHER_TERRITORY`.
- [ ] Le patch ne déduit pas la barge depuis `neighboringCommunes`.
- [ ] Le patch garde `neighboringCommunes` pour le message client.
- [ ] Le patch garde `territory` pour le calcul barge.
- [ ] Le patch contient des textes d'aide explicites si EasyAdmin est touché.

## Tests minimum imposés

Tout patch contenant `DeliveryLogisticsService` devra documenter ou tester :

```text
Dzaoudzi PT + Pamandzi PT = pas de barge
Dzaoudzi PT + Mamoudzou GT = barge
Mamoudzou GT + Dzaoudzi PT = barge
```


---

# Retour d’expérience J5F-A / J5F-B — règles renforcées

## Patch corrompu

Pendant J5F-A, une première version du patch correctif `j5f_a_fix_schema_alignment.patch` était corrompue :

```text
error: corrupt patch at ./j5f_a_fix_schema_alignment.patch:189
```

Correction : fournir une version `v2` du patch, applicable avec :

```powershell
git apply --check .\j5f_a_fix_schema_alignment_v2.patch
git apply .\j5f_a_fix_schema_alignment_v2.patch
```

Règle renforcée : après génération d'un patch, vérifier qu'il est réellement applicable avec `git apply --check` avant de le transmettre.

## Écart schema après migration

Après J5F-A, la migration principale a bien créé les tables, mais `doctrine:schema:validate` restait rouge.

Diagnostic :

```powershell
php bin/console doctrine:schema:update --dump-sql
```

Doctrine demandait surtout :

- renommage d'index ;
- ajout / réalignement de contraintes ;
- ajustement de colonnes datetime ;
- index `seller.delivery_commune_id`.

Correction retenue : migration corrective versionnée `Version20260607173000`.

À ne pas faire :

```powershell
php bin/console doctrine:schema:update --force
```

## Colonnes booléennes en SQL

Doctrine a généré les colonnes SQL :

```text
is_active
```

et non :

```text
active
```

Règle : avant d'écrire des commandes SQL de seed, vérifier les colonnes réelles :

```bash
php bin/console dbal:run-sql "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_pricing_zone' ORDER BY ORDINAL_POSITION;" --env=prod
```

## DESCRIBE avec dbal:run-sql

`DESCRIBE table;` via `dbal:run-sql` peut afficher seulement :

```text
[OK] 0 rows affected.
```

Préférer `information_schema.COLUMNS` pour obtenir un tableau exploitable.

## Services non encore utilisés

Avec `debug:container`, Symfony peut indiquer :

```text
The service or alias has been removed or inlined when the container was compiled.
```

Ce n'est pas une erreur si le service est autowirable et que `lint:container` est OK.

Cela arrive quand un service est prêt mais pas encore injecté ailleurs.

## Nettoyage serveur recette

Sur o2switch, après `composer install --no-dev`, `vendor/` peut redevenir sale si `vendor/` est suivi par Git.

Rituel de nettoyage recette :

```bash
git restore vendor
git update-index --skip-worktree .env.local
rm -f git_status.logs git_statu.logs 1
git status
```

Résultat attendu :

```text
rien à valider, la copie de travail est propre
```

---

# Règles patch J5G avancé — Chemin de communes et frais composés

## Découpage obligatoire

Ne pas faire un patch géant.

Découpage conseillé :

```text
J5G-B → BFS / chemin / hopCount dans DeliveryLogisticsService
J5G-C → réglages Hodina des suppléments
J5G-D → affichage panier détaillé
J5G-E → snapshot checkout dans CustomerOrder
```

## Règle barge à préserver

Tout patch doit conserver :

```text
requiresBarge = clientTerritory !== sellerTerritory
```

Interdit :

```text
déclencher la barge parce que les communes sont éloignées
déclencher la barge parce qu'aucun chemin n'est trouvé
déclencher la barge parce que les communes ne sont pas voisines
```

## Règle chemin

Le plus court chemin doit utiliser :

```text
DeliveryCommune.neighboringCommunes
```

Pour le pilote :

```text
BFS suffit
```

## Tests minimum J5G-B

```text
même commune → hopCount = 0
commune voisine directe → hopCount = 1
chemin avec une commune intermédiaire → hopCount = 2
aucun chemin → warning propre, pas d'erreur 500
PT → GT → requiresBarge = true
PT → PT éloigné → requiresBarge = false
```

## Données figées

Un patch checkout devra explicitement lister :

```text
valeurs calculées dynamiquement
valeurs figées dans CustomerOrder
raison du snapshot
```

## Pédagogie exigée

Chaque patch doit expliquer :

```text
ce qui est calculé
ce qui est affiché
ce qui est stocké
ce qui est volontairement reporté
```


---

# Règles patch J5G-B — Données communes et voisinage

## Source validée

Le fichier :

```text
hodina_communes_voisinage_reference_v1.xlsx
```

est la source initiale validée.

## Règle critique

Ne pas lire ce fichier dans le code métier au runtime.

À faire :

```text
Excel
→ migration / seed
→ base de données
→ EasyAdmin
→ DeliveryLogisticsService
```

À éviter :

```text
CartController lit Excel
DeliveryLogisticsService lit Excel
Twig lit Excel
```

## Patchs à découper

```text
Patch 1 — entités + migration
Patch 2 — seed initial
Patch 3 — CRUD EasyAdmin
Patch 4 — service BFS
Patch 5 — affichage panier / tests
```

## Migration prudente

Ne pas supprimer immédiatement la relation ManyToMany existante `neighboringCommunes`.

Créer d'abord la nouvelle entité riche de liaison, puis migrer progressivement.

## Tests minimum

Tout patch BFS devra tester :

```text
Dzaoudzi → Labattoir
Dzaoudzi → Mamoudzou
Mamoudzou → Labattoir
Mamoudzou → Koungou
```

## Point débutant

Une donnée logistique doit être modifiable par l'admin.

Le code calcule. La base configure.

---

# Retours d'expérience patchs — J5G-B2 / J5G-B3

## Patch corrompu

Un patch peut être téléchargé mais corrompu.

Symptôme :

```text
error: corrupt patch at ...
```

Règle :

```text
ne pas forcer
ne pas modifier au hasard
recréer un patch propre ou créer le fichier manuellement
```

## PowerShell et fichiers PHP

Éviter pour les fichiers PHP :

```powershell
Set-Content -Encoding UTF8
```

Risque : génération d'un BOM invisible en début de fichier.

Symptôme PHP :

```text
strict_types declaration must be the very first statement
```

Solution :

```powershell
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $content, $utf8NoBom)
```

## Diagnostic Doctrine

Règle confirmée :

```text
doctrine:schema:update --dump-sql = diagnostic autorisé
doctrine:schema:update --force = interdit
```

Les écarts doivent être corrigés par migration.

## Déploiement o2switch

Après `git pull`, si des migrations sont ajoutées :

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console doctrine:schema:validate --env=prod
```

Ne pas conclure à un problème de code si la migration n'a pas été exécutée.

## Validation d'un seed

Un seed doit être vérifié par SQL métier.

Exemple J5G-B3 :

```text
la migration affichait 0 SQL queries
tables métier vérifiées
les données étaient bien présentes
```

## Leçons de patch — session support adresses J5G

### Toujours générer les patchs depuis l'état réel courant

Plusieurs patchs n'ont pas pu s'appliquer car ils étaient basés sur un état intermédiaire. Règle renforcée : si un fichier a été modifié plusieurs fois dans une session, demander ou utiliser un extrait réel des sources avant de générer un nouveau patch.

### Éviter les créations manuelles longues via here-string PowerShell

Les marqueurs `@'` / `'@` peuvent finir dans les fichiers si la commande est mal copiée. Pour les fichiers critiques PHP, préférer :

```text
patch Git
ou ZIP de fichiers complets
ou écriture contrôlée UTF-8 sans BOM
```

### Ne pas considérer `cache:clear` comme preuve fonctionnelle

Un `cache:clear` OK prouve que Symfony charge, mais ne prouve pas que les validations imbriquées fonctionnent. Pour les formulaires EasyAdmin avec objets enfants, il faut tester réellement une sauvegarde valide et une sauvegarde invalide.

### Vérifier les migrations séparément

Si une entité change mais que `schema:validate` est rouge, vérifier :

```text
migration présente ?
migration exécutée ?
dump SQL Doctrine ?
```

Ne jamais utiliser `schema:update --force`.

---

# Règle renforcée 12/06/2026 — patchs obligatoirement testés avant livraison

Suite aux incidents de patchs non applicables pendant J5G-SUPPORT-ADRESSES, la règle est renforcée.

## Avant de livrer un patch code

Le patch doit être testé sur les sources réelles fournies.

Commandes minimales :

```bash
git apply --check nom.patch
git apply nom.patch
git diff --check
```

Pour les fichiers PHP modifiés :

```bash
php -l src/...
```

Pour les migrations :

```bash
php -l migrations/...
```

## Limite du sandbox

Si seul un extrait de projet est disponible, le test peut vérifier :

```text
git apply
php -l
git diff --check
```

Mais ne peut pas toujours vérifier :

```text
cache:clear
schema:validate
lint:container
```

Dans ce cas, le message de livraison doit le préciser.

## Interdiction de dire "testé" sans test réel

Ne pas écrire qu'un patch est testé si le test n'a pas été exécuté sur une copie des fichiers concernés.

## Préférence quand un fichier a beaucoup dérivé

Si un fichier a été modifié plusieurs fois dans la session :

```text
demander le fichier actuel
ou utiliser l'extrait source actuel
puis générer un patch depuis cet état
```

## Vérification encodage

Sur Windows, surveiller :

```text
accents cassés
CRLF/LF
UTF-8 avec BOM
marqueurs PowerShell insérés par erreur
```

Les messages métier doivent rester lisibles :

```text
La première ligne de l’adresse ne doit pas être vide.
Petite-Terre
Grande-Terre
AUTRE — Autre
```

## Règle fonctionnelle

Un patch qui "fait passer" le test en masquant l'erreur n'est pas acceptable.

Exemple refusé pendant la session :

```text
rendre tous les setters nullable pour absorber null
```

Décision retenue :

```text
garder la règle métier stricte
renvoyer une erreur formulaire claire
```

---

# Règles patch — 13/06/2026 — e-mails et préouverture

## J5H — patchs e-mail

Tout patch e-mail doit respecter ces règles :

```text
ne jamais commiter de mot de passe SMTP
ne jamais mettre un vrai MAILER_DSN secret dans un fichier suivi Git
logger les échecs SMTP
ne jamais bloquer une commande si l'e-mail échoue
```

## J5I — patchs préouverture

Tout patch préouverture doit bloquer à la fois :

```text
le front
les routes panier
le checkout
la création de commande si centralisée
```

Un bouton désactivé en Twig ne suffit pas.

Découpage recommandé : configuration / service / bannière Twig-CSS-JS / blocage panier-checkout / capture e-mail / docs.


---

# Règle ajoutée — 13/06/2026 — ordre des migrations

## Toujours vérifier l'ordre réel des migrations

Un problème a été rencontré en recette J5I :

```text
Version20260613094055 modifiait launch_subscriber
avant
Version20260613110000 qui créait launch_subscriber
```

Résultat : migration impossible sur recette car la table n'existait pas encore.

Nouvelle règle : avant de livrer un patch contenant plusieurs migrations, vérifier :

```bash
php bin/console doctrine:migrations:status
ls -1 migrations
```

Et lire les fichiers pour confirmer que :

```text
1. les tables sont créées avant d'être modifiées ;
2. les colonnes sont ajoutées avant d'être utilisées ;
3. les données sont insérées seulement quand la table existe ;
4. la migration passe sur une base qui n'a pas les changements dev manuels.
```

## Ne pas générer une migration corrective avant la migration qui crée la table

Si `make:migration` génère une correction sur une table nouvellement créée, s'assurer que son timestamp est postérieur à la migration de création.

Si ce n'est pas le cas :

```text
renommer proprement la migration
ou intégrer la correction dans la migration de création
ou supprimer la migration corrective si elle n'est qu'un artefact local
```

## Recette ≠ production

Un contournement manuel peut débloquer la recette pour continuer les tests, mais il doit être documenté et corrigé avant production.

---

# Règles patch J5J — réglages et rôles

## Ne pas réintroduire de paramètres testeurs par e-mail

Le choix final est `ROLE_COMMERCE_TESTER`. Ne pas ajouter de paramètre `commerce_tester_emails` sauf décision explicite future.

## Ne pas afficher les champs techniques aux admins

`field_type` est technique. Il ne doit pas apparaître à l'édition d'un réglage système existant.

## Ne pas bloquer seulement côté Twig

Tout blocage panier ou checkout doit être validé côté contrôleur/service. Le front ne suffit jamais.

## Mode open

Ne jamais afficher la bannière ou le compte à rebours en mode `open`, même si `commerce_reopens_at` contient une date future.
