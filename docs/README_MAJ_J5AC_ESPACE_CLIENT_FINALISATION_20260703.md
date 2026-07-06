# J5AC — Finalisation espace client

Date : 2026-07-03  
Statut : validé localement, validé recette v2, validé production le 03/07/2026.

## Décision produit

L’espace client Hodina devient un vrai point d’entrée compte, sans transformer le MVP en back-office client.

Objectif : permettre au client connecté de comprendre rapidement :

- où en sont ses commandes ;
- quelles informations personnelles sont liées à son compte ;
- comment modifier son mot de passe ou demander un lien de réinitialisation.

Le suivi de commandes J5R existant n’est pas refait. Il reste la source pour la liste et le détail des commandes.

## Décision DB

Avant d’autoriser la modification d’email côté client, J5AC ajoute une contrainte base de données :

- `customer.email` reste nullable pour ne pas casser les comptes vendeurs incomplets ;
- les emails existants sont normalisés en `LOWER(TRIM(email))` ;
- les emails vides deviennent `NULL` ;
- la migration échoue explicitement si des doublons normalisés existent ;
- un index unique nullable `UNIQ_CUSTOMER_EMAIL` est ajouté.

Aucune contrainte unique n’est ajoutée sur `customer.phone`.

## Routes ajoutées / stabilisées

- `/mon-compte` : tableau de bord compte.
- `/mon-compte/commandes` : liste commandes existante conservée.
- `/mon-compte/commandes/{id}` : détail commande propriétaire conservé.
- `/mon-compte/profil` : modification prénom, nom, email, téléphone.
- `/mon-compte/mot-de-passe` : modification avec ancien mot de passe.
- `POST /mon-compte/mot-de-passe/lien-reinitialisation` : génération d’un lien reset pour le compte connecté via SmsLog pilote.

## Hors périmètre volontaire

- pas de carnet d’adresses ;
- pas de modification des adresses de livraison/facturation ;
- pas de GPS profil ;
- pas de refonte panier / checkout ;
- pas de refonte Djama ;
- pas de paiement en ligne ;
- pas de facture PDF.

## Anti-régression

- `^/mon-compte` reste protégé par `ROLE_USER` ;
- une commande ne peut être lue que par son propriétaire ;
- les commandes `DRAFT` restent exclues du portail client ;
- le code de réception n’est pas affiché en clair ;
- le téléphone profil réutilise `PhoneNumberNormalizer` ;
- le mot de passe connecté vérifie l’ancien mot de passe avec `isPasswordValid()` ;
- le reset public continue de passer par `/hodi/mot-de-passe-oublie`.

## Validation recette / production

État final du lot :

```text
Commit fonctionnel : 60d3dee feat(j5ac): finalize client account space with ajax
Commit correctif migration : 0966429 fix(j5ac): mark email migration non transactional
Tag recette initial : recette-j5ac-espace-client-ajax-20260703
Tag recette propre : recette-j5ac-espace-client-ajax-v2-20260703
Tag production : prod-j5ac-espace-client-ajax-20260703
Statut : validé local + recette + production
```

La recette initiale a validé le comportement fonctionnel, puis une recette v2 a été faite pour rendre la migration `Version20260703093000` non transactionnelle (`isTransactional(): false`) avant production. Cette décision évite le warning Doctrine/MariaDB lié aux commits implicites sur `CREATE UNIQUE INDEX`.

Contrôles validés :

```bash
php tools/assert-j5ac-customer-email-db-readiness.php
php tools/assert-j5ac-client-account-finalization.php
php tools/assert-j5ac-client-account-ajax.php
php bin/console doctrine:schema:validate --env=prod
php bin/console doctrine:schema:update --dump-sql --env=prod
```

État DB final production :

- `customer.email` est unique nullable via `UNIQ_CUSTOMER_EMAIL` ;
- `customer.phone` n’est pas unique ;
- aucun doublon email normalisé ;
- aucun email invalide simple après correction manuelle ;
- mapping Doctrine synchronisé.

Correction manuelle de donnée en production après déploiement :

```text
customer.id = 13
email avant : chahere.kdu
email après : chahere.kdu@outlook.fr
raison : nettoyage donnée révélée par l’audit J5AC avant clôture.
```

Règle anti-régression : ne pas rendre `customer.email` `NOT NULL` sans audit des comptes vendeurs et comptes incomplets. Ne pas ajouter de contrainte unique sur `customer.phone`.
