# README MAJ — J5G-SUPPORT-ADRESSES-FRONT

## But

Documenter la stabilisation finale des adresses côté client et EasyAdmin avant reprise / clôture de J5G-B4.

## Résumé court

Le support adresses distingue désormais clairement :

```text
adresse de livraison = donnée logistique stricte
adresse de facturation = donnée administrative pouvant être hors zone ou livrable
```

## Règles finales

### Livraison

```text
PT ou GT uniquement
commune livrable obligatoire
code postal cohérent avec la commune
zone cohérente avec la commune
```

### Facturation

```text
AUTRE possible
PT ou GT possible
si AUTRE : code postal français à 5 chiffres
si PT/GT : commune livrable + code postal + zone cohérents
```

## Tests locaux validés

```text
EasyAdmin livraison valide
EasyAdmin livraison invalide
EasyAdmin facturation AUTRE valide
EasyAdmin facturation Mayotte valide
EasyAdmin facturation Mayotte incohérente invalide
Checkout e-mail existant
Checkout erreurs adresse rouges et placées sous le champ
Checkout facturation AUTRE
Checkout facturation Mayotte
Inscription e-mail existant sans doublon
Inscription formulaire conservé après erreur
```

## Commandes de contrôle

```powershell
php -l src\Entity\Address.php
php -l src\Entity\Customer.php
php -l src\Service\DeliveryCommuneMatcherService.php
php -l src\Validator\DeliverableAddress.php
php -l src\Validator\DeliverableAddressValidator.php
php -l src\Controller\Admin\AddressCrudController.php
php -l src\Controller\Admin\CustomerCrudController.php
php -l src\Controller\CheckoutController.php
php -l src\Controller\RegistrationController.php
php -l src\Form\CheckoutType.php
php -l src\Form\RegistrationFormType.php

php bin/console cache:clear
php bin/console doctrine:schema:validate
php bin/console lint:container
git diff --check
```

## SQL de vérification

```powershell
php bin/console dbal:run-sql "SELECT a.id, a.type, a.line1, a.postal_code, a.commune, dz.code AS zone_code FROM address a JOIN delivery_zone dz ON dz.id = a.delivery_zone_id ORDER BY a.id DESC LIMIT 10;"

php bin/console dbal:run-sql "SELECT code, name, is_active FROM delivery_zone ORDER BY code;"
```

Résultats attendus :

```text
DELIVERY → PT ou GT cohérent
BILLING → AUTRE ou PT/GT cohérent selon le cas
AUTRE — Autre présent dans delivery_zone
```

## Nettoyage avant commit

Supprimer les patchs et zips temporaires :

```powershell
Remove-Item .\j5g_*.patch -ErrorAction SilentlyContinue
Remove-Item .\hodina_docs_j5g_support_address_update_git.patch -ErrorAction SilentlyContinue
Remove-Item .\docs*.zip -ErrorAction SilentlyContinue
Remove-Item .\extrait_sources*.zip -ErrorAction SilentlyContinue
```

Ne pas supprimer les vrais fichiers source modifiés.

## Commit recommandé

```powershell
git add src\Entity\Address.php `
        src\Entity\Customer.php `
        src\Service\DeliveryCommuneMatcherService.php `
        src\Validator `
        src\Controller\Admin\AddressCrudController.php `
        src\Controller\Admin\CustomerCrudController.php `
        src\Controller\CheckoutController.php `
        src\Controller\RegistrationController.php `
        src\Form\CheckoutType.php `
        src\Form\RegistrationFormType.php `
        templates\checkout\index.html.twig `
        templates\registration\register.html.twig `
        public\css\style_mobile.css `
        migrations\Version20260607225500.php

git commit -m "feat(address): validate delivery and billing addresses"
```

Attention : si les fichiers J5G-B4 logistique réelle sont déjà modifiés, vérifier le diff avant d'ajouter tout le projet.
