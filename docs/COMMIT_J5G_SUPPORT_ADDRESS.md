# COMMIT — J5G-SUPPORT-ADRESSES — validation livraison / facturation

## Contexte

Ce support a été déclenché pendant les tests J5G-B4. Le calcul de trajet réel ne peut pas être fiable si les adresses client sont incohérentes.

## Problème initial

Dans EasyAdmin utilisateur, il était possible d'ajouter une fausse adresse de livraison, par exemple :

```text
La Dominelais
35390
Zone PT
```

Cette adresse n'est pas une commune livrable Hodina, mais elle pouvait être enregistrée.

## Décisions prises

- distinguer adresse de livraison et adresse de facturation ;
- créer un type d'adresse `DELIVERY` / `BILLING` ;
- conserver `PT` et `GT` pour les livraisons ;
- ajouter `AUTRE — Autre` pour les facturations hors zone ;
- valider les adresses de livraison contre `delivery_commune` ;
- autoriser les adresses de facturation hors commune livrable avec code postal français valide ;
- forcer la validation des adresses imbriquées dans `Customer` ;
- corriger les erreurs 500 EasyAdmin sur adresse incomplète.

## Incidents rencontrés

- patchs partiellement appliqués ;
- classes référencées mais absentes ;
- marqueurs PowerShell `@'` / `'@` insérés dans les fichiers PHP ;
- patchs non applicables après dérive des sources ;
- migration manquante pour `address.type` ;
- erreur `Typed property Address::$commune must not be accessed before initialization`.

## État des tests

Validé :

```text
EasyAdmin livraison correcte → OK
EasyAdmin livraison fausse → KO propre
Plus d'erreur 500 sur adresse incomplète/fausse testée
```

À terminer :

```text
facturation AUTRE
inscription
checkout
vérifications SQL
commit final
recette
```

## Statut

```text
EN COURS — ne pas considérer comme clôturé avant tests complets.
```

---

# Mise à jour 2026-06-12 — support adresses finalisé localement

## Contexte de la session

Le support adresses a été déclenché pendant les tests J5G-B4. Au départ, l'objectif principal était de brancher `DeliveryLogisticsService` sur les liaisons réelles entre communes. Les tests ont montré qu'un calcul logistique fiable ne peut pas reposer sur des adresses approximatives.

Le jalon a donc été temporairement priorisé avant la clôture de J5G-B4.

## Problèmes réellement rencontrés

### 1. EasyAdmin acceptait des adresses de livraison fausses

Exemple :

```text
Adresse : le pont des îles 12
Code postal : 35390
Commune : La Dominelais
Zone : PT
Type : adresse de livraison
```

Problème : cette adresse n'est pas livrable par Hodina, mais pouvait être ajoutée dans l'utilisateur EasyAdmin.

### 2. Livraison et facturation étaient mélangées

Au départ, une adresse était seulement une adresse rattachée au client. Cela ne suffisait plus, car :

```text
livraison = donnée logistique stricte
facturation = donnée administrative, parfois hors Mayotte
```

Décision : ajouter un type métier à l'adresse.

```text
DELIVERY = adresse de livraison
BILLING  = adresse de facturation
```

### 3. La zone hors livraison devait être française

La proposition `OTHER` a été rejetée. L'application étant française et destinée à des utilisateurs francophones, la zone s'appelle :

```text
AUTRE — Autre
```

### 4. La facturation peut être livrable

Décision affinée pendant les tests front : une adresse de facturation ne doit pas être systématiquement forcée à `AUTRE`.

Règle finale :

```text
Adresse de livraison
→ PT ou GT uniquement
→ commune livrable obligatoire
→ code postal cohérent avec la commune
→ zone cohérente avec la commune

Adresse de facturation
→ AUTRE possible
→ PT ou GT possible
→ si AUTRE : seul le format du code postal français est contrôlé
→ si PT ou GT : commune livrable + code postal + zone cohérents
```

Exemples validés :

```text
Facturation / Rennes / 35000 / AUTRE
→ OK

Facturation / Labattoir / 97615 / PT
→ OK

Facturation / Labattoir / 97615 / GT
→ KO attendu

Facturation / Labattoir / 97619 / PT
→ KO attendu
```

## Fichiers structurants

```text
src/Entity/Address.php
src/Entity/Customer.php
src/Service/DeliveryCommuneMatcherService.php
src/Validator/DeliverableAddress.php
src/Validator/DeliverableAddressValidator.php
src/Controller/Admin/AddressCrudController.php
src/Controller/Admin/CustomerCrudController.php
src/Controller/CheckoutController.php
src/Controller/RegistrationController.php
src/Form/CheckoutType.php
src/Form/RegistrationFormType.php
templates/checkout/index.html.twig
templates/registration/register.html.twig
public/css/style_mobile.css
migrations/Version20260607225500.php
```

## Règles de validation finales

### Adresse de livraison

Une livraison doit être exploitable par la logistique Hodina.

Validation :

```text
line1 non vide
code postal au format 5 chiffres
commune obligatoire
commune présente dans delivery_commune
code postal cohérent avec la commune seedée
zone PT/GT cohérente avec la commune
zone AUTRE interdite
```

Exemples validés :

```text
Livraison / Rue test / 97615 / Labattoir / PT
→ OK

Livraison / Rue test / 97615 / Labattoir / AUTRE
→ KO : Labattoir appartient à Petite-Terre (PT), pas à la zone Autre.

Livraison / Rue test / 97619 / Labattoir / PT
→ KO : Le code postal attendu est 97615.

Livraison / Rennes / 35000 / AUTRE
→ KO : commune non reconnue comme commune livrable Hodina.
```

### Adresse de facturation

Une facturation peut être administrative uniquement ou correspondre à une adresse livrable.

Validation :

```text
line1 non vide
code postal au format 5 chiffres
zone obligatoire
si zone AUTRE : commune libre
si zone PT ou GT : commune livrable + code postal + zone cohérents
```

Exemples validés :

```text
Facturation / Rennes / 35000 / AUTRE
→ OK

Facturation / Rennes / 35A00 / AUTRE
→ KO : Le code postal doit contenir exactement 5 chiffres.

Facturation / Labattoir / 97615 / PT
→ OK

Facturation / Labattoir / 97615 / GT
→ KO : Labattoir appartient à Petite-Terre (PT).

Facturation / Labattoir / 97619 / PT
→ KO : Le code postal attendu est 97615.
```

## EasyAdmin — état validé

Tests validés :

```text
livraison correcte → OK
livraison hors commune livrable → KO propre
livraison avec mauvaise zone → KO propre
livraison avec mauvais code postal → KO propre
facturation Rennes / 35000 / AUTRE → OK
facturation Rennes / 35A00 / AUTRE → KO propre
facturation Labattoir / 97615 / PT → OK
facturation Labattoir / 97615 / GT → KO propre
```

Ajustements UX EasyAdmin :

```text
bloc "Adresses de livraison" renommé en "Adresses du client"
suppression des messages de validation en double
suppression réellement persistée via orphanRemoval
adresse de facturation sélectionnée désassociée si elle est supprimée
```

## Front client — checkout validé

Corrections réalisées :

```text
champs de livraison et facturation distincts
zone de livraison limitée à PT/GT
zone de facturation affichée explicitement
zone de facturation autorise AUTRE/PT/GT
erreurs affichées en rouge sous le champ concerné
valeurs conservées après erreur
e-mail déjà existant refusé pour un checkout invité
ne plus écraser la zone de facturation avec la zone de livraison
```

Cas importants validés :

```text
checkout invité avec e-mail existant
→ KO propre
→ message rouge sous le champ e-mail
→ aucune commande ne doit être créée

checkout avec code postal livraison incohérent
→ KO propre
→ message rouge sous le code postal

checkout avec facturation Rennes / 35000 / AUTRE
→ OK

checkout avec facturation Labattoir / 97615 / PT
→ OK

checkout avec facturation Labattoir / 97615 / GT
→ KO attendu

checkout avec facturation Labattoir / 97619 / PT
→ KO attendu
```

## Front client — inscription validée

Corrections réalisées :

```text
e-mail déjà existant refusé avec un seul message
suppression du doublon UniqueEntity + message contrôleur
message e-mail rouge et placé sous le champ
champs conservés après erreur
livraison et facturation alignées avec le checkout
```

Décision technique : retirer la validation automatique `UniqueEntity` sur `Customer.email` pour éviter le doublon, et garder un contrôle manuel explicite dans `RegistrationController`.

Message conservé côté inscription :

```text
Un compte existe déjà avec cette adresse e-mail. Connecte-toi ou utilise “Mot de passe oublié”.
```

Message conservé côté checkout invité :

```text
Un compte existe déjà avec cette adresse e-mail. Connecte-toi avant de valider ta commande ou utilise une autre adresse e-mail.
```

## Incidents rencontrés et résolus

### Patchs non applicables

Plusieurs patchs ont échoué parce que les fichiers avaient déjà évolué dans la session.

Décision renforcée :

```text
Toujours générer un patch depuis les sources actuelles.
Tester le patch avant livraison.
```

### Fichiers PHP pollués par PowerShell

Des marqueurs `@'` / `'@` se sont retrouvés dans des fichiers PHP lors de créations manuelles.

Décision :

```text
éviter les gros fichiers PHP créés à la main par here-string
préférer patch Git testé ou fichier complet contrôlé
```

### Erreur typed property non initialisée

Erreur :

```text
Typed property App\Entity\Address::$commune must not be accessed before initialization
```

Correction : initialiser les champs texte et sécuriser la validation.

### Serveur local et public/index.php

Avast a considéré `public/index.php` comme infecté et l'a supprimé / mis en quarantaine. Cela a provoqué :

```text
GET / - No such file or directory
```

Correction :

```powershell
git restore public/index.php
```

Décision : ne jamais recréer `public/index.php` à la main. Le restaurer depuis Git et prévoir une exception Avast ciblée sur le projet ou sur ce fichier.

Commande locale retenue :

```powershell
cd E:\hodina\hodina.fr\public
php -d max_execution_time=120 -S 0.0.0.0:8000
```

Ne pas utiliser `public/index.php` comme routeur du serveur PHP intégré, car cela peut casser les assets CSS/images.

## Statut final local

```text
Support adresse livraison/facturation : validé localement
EasyAdmin : validé
Checkout : validé sur les cas critiques
Inscription : validée sur les cas critiques
Déploiement recette : à faire après commit propre
```

## Point de vigilance avant commit

Le `git status` montre un mélange de deux sujets :

```text
support adresse/facturation/livraison
J5G-B4 service logistique réel / panier
```

Il faut éviter de faire un gros commit illisible si possible.

Recommandation :

```text
Commit 1 : support adresses
Commit 2 : J5G-B4 logistique réelle
```
