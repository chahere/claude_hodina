# COMMIT — J5G-SUPPORT-ADRESSES-FRONT — validation front livraison / facturation

## Contexte

Après validation EasyAdmin des adresses, les tests côté client ont révélé plusieurs écarts UX et métier :

```text
- messages d'erreur mal placés ;
- formulaire qui pouvait se vider ou mal conserver les champs ;
- e-mail existant qui devait être bloqué ;
- zone de facturation absente côté front ;
- facturation Mayotte possible mais pas correctement modélisée au départ ;
- zone de facturation écrasée par la zone de livraison dans le checkout.
```

Ce commit complète donc le support adresses côté front.

## Décisions prises

### 1. Zone de livraison front

La zone de livraison côté client ne propose pas `AUTRE`.

Règle :

```text
Livraison = PT ou GT uniquement.
```

Cela évite qu'un client demande une livraison hors zone.

### 2. Zone de facturation front

La zone de facturation est affichée explicitement.

Choix disponibles :

```text
AUTRE — Autre
Petite-Terre (PT)
Grande-Terre (GT)
```

Raison : une facturation peut être hors zone, mais elle peut aussi correspondre à une adresse livrable à Mayotte.

### 3. Validation selon la zone de facturation

```text
Facturation AUTRE
→ code postal 5 chiffres seulement
→ commune libre

Facturation PT/GT
→ commune livrable obligatoire
→ code postal cohérent avec la commune
→ zone cohérente avec la commune
```

### 4. E-mail déjà utilisé

Checkout invité :

```text
email existant → erreur et pas de commande
```

Inscription :

```text
email existant → erreur et pas de compte
```

Les messages sont différents car les parcours sont différents.

## Fichiers concernés

```text
src/Controller/CheckoutController.php
src/Controller/RegistrationController.php
src/Form/CheckoutType.php
src/Form/RegistrationFormType.php
src/Validator/DeliverableAddressValidator.php
templates/checkout/index.html.twig
templates/registration/register.html.twig
public/css/style_mobile.css
```

## Tests validés

### Checkout

```text
E-mail déjà existant
→ KO propre, message rouge sous le champ e-mail

Livraison / Labattoir / 97619 / PT
→ KO propre, message sous le code postal

Facturation / Rennes / 35000 / AUTRE
→ OK

Facturation / Labattoir / 97615 / PT
→ OK

Facturation / Labattoir / 97615 / GT
→ KO attendu

Facturation / Labattoir / 97619 / PT
→ KO attendu
```

### Inscription

```text
E-mail déjà existant
→ KO propre, un seul message, pas de doublon

Livraison incohérente
→ KO propre

Formulaire conservé après erreur
→ OK
```

### EasyAdmin rappel

```text
Adresses du client
→ livraison et facturation dans la même collection
→ type d'adresse explicite
→ validation cohérente avec le front
```

## Incidents notables

### Doublon e-mail à l'inscription

Cause :

```text
UniqueEntity sur Customer.email
+ contrôle manuel dans RegistrationController
```

Décision :

```text
supprimer UniqueEntity
garder le contrôle manuel avec message métier
```

### Zone de facturation écrasée

Cause : le checkout lisait mal certains champs `mapped => false`, ce qui revenait à utiliser la zone de livraison.

Correction : lire les champs de facturation directement depuis les champs du formulaire, pas depuis une donnée recopiée par défaut.

## Statut

```text
Validé localement
À déployer en recette après commit et nettoyage
```
