# Commit J5D — Dashboard livreur, rôles admin et préparation modèle économique

## Statut

**Réalisé, testé localement, poussé sur GitHub, déployé en préproduction et validé fonctionnellement le 06/06/2026.**

## Branche

```text
pilot/j5b-workflow-service
```

La branche conserve le nom J5B, mais contient désormais :

- J5B : refactoring workflow ;
- J5C : données livraison ;
- J5D : dashboard livreur ;
- correction sélection des rôles EasyAdmin.

## Commits connus

Dashboard livreur et docs :

```text
83d65d6 feat(courier): add delivery dashboard and update docs
```

Sélection des rôles :

```text
7ff05d8 feat(admin): improve customer role selection
```

## Objectif J5D

Créer une interface livreur mobile-first permettant à un livreur authentifié de :

- voir les commandes prêtes ;
- prendre une commande ;
- passer la commande en livraison ;
- voir ses livraisons en cours ;
- appeler le client ;
- envoyer un SMS au client ;
- marquer une commande comme livrée.

## Fichiers principaux

```text
src/Controller/Courier/CourierDashboardController.php
templates/courier/dashboard.html.twig
templates/base.html.twig
src/Controller/Admin/CustomerCrudController.php
```

## Fonctionnalités livrées

### Dashboard livreur

Route :

```text
/livreur
```

Accès :

```text
ROLE_COURIER
```

### Commandes prêtes

Le dashboard affiche les commandes :

```text
status = READY_FOR_PICKUP
assignedCourier = NULL
```

### Livraisons en cours

Le dashboard affiche les commandes :

```text
status = OUT_FOR_DELIVERY
assignedCourier = livreur connecté
```

### Actions

Actions disponibles :

```text
Prendre en charge
Marquer livrée
Appeler
SMS client
```

### Service utilisé

Le dashboard utilise :

```text
CustomerOrderWorkflowService
```

Il ne duplique pas les règles de transition.

## Tests locaux

Validé :

- `git apply --check` OK ;
- patch appliqué OK ;
- `php -l CourierDashboardController.php` OK ;
- `php bin/console cache:clear` OK ;
- `php bin/console lint:container` OK ;
- `php bin/console doctrine:schema:validate` OK.

## Déploiement préproduction

Commandes exécutées :

```bash
cd /home/vopu3712/recette.hodina.fr
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:schema:validate --env=prod
```

Résultat : OK.

## Tests préproduction

Validé :

- utilisateur non connecté → accès refusé ou redirection ;
- utilisateur connecté sans `ROLE_COURIER` → accès refusé ;
- utilisateur avec `ROLE_COURIER` → dashboard accessible ;
- commande `READY_FOR_PICKUP` visible dans les commandes prêtes ;
- prise en charge OK ;
- passage `OUT_FOR_DELIVERY` OK ;
- commande visible dans `Mes livraisons en cours` ;
- marquage livrée OK ;
- passage `DELIVERED` OK.

## Point de test documenté

Une commande ne s'affichait pas dans le dashboard livreur.

Cause : elle n'avait pas encore été passée au statut :

```text
READY_FOR_PICKUP
```

Conclusion : le dashboard était correct. La donnée de test n'était pas dans l'état attendu.

Règle :

```text
Pour tester /livreur, l'admin doit d'abord marquer la commande prête.
```

## Correction rôles EasyAdmin

Le formulaire client admin ne doit plus demander de saisir manuellement un tableau de rôles incompréhensible.

Correction livrée : proposer des rôles avec description.

Rôles visibles :

- Client ;
- Livreur ;
- Administrateur.

Rôle futur à ajouter :

```text
ROLE_SELLER
```

## Décisions prises après J5D

La suite du développement est structurée ainsi :

```text
J5E — marge produit Hodina
J5F — zones tarifaires, communes et communes voisines
J5G — aperçu logistique panier
J6 — portail vendeur
```

## J5E — résumé stratégique

Hodina calcule le prix client depuis le prix producteur.

```text
prix client = prix producteur × (1 + marge effective)
```

Priorité marge :

```text
Produit > Vendeur > Global
```

Le vendeur saisira son prix producteur, mais Hodina gardera le contrôle de la marge et du prix client.

## J5F — résumé stratégique

La livraison sera calculée depuis des paramètres backoffice.

```text
une commune appartient à une zone tarifaire
une zone tarifaire définit le prix client et la rémunération livreur
```

L'admin définira aussi les communes voisines.

La barge sera calculée selon le territoire PT / GT du client et des vendeurs.

## J5G — résumé stratégique

Le panier affichera un aperçu logistique.

Exemple de message validé :

```text
Certains produits de ton panier viennent de vendeurs éloignés ou situés sur une autre île. La livraison peut nécessiter une traversée en barge et des frais adaptés seront appliqués.
```

Le panier informe. Le checkout recalcule et fige.

## Portail vendeur futur

Hodina aura un portail vendeur où les vendeurs pourront :

- compléter leur profil ;
- choisir leur commune de retrait / production ;
- créer leurs produits ;
- saisir leurs prix producteur.

Les calculs de prix et de logistique doivent donc être codés dans des services réutilisables.


---

# Note postérieure — J5E livré après dashboard livreur

J5E est désormais livré et validé.

La règle produit est opérationnelle :

```text
prix client = prix producteur × (1 + marge effective)
```

Priorité :

```text
Produit > Vendeur > Global
```

Le dashboard livreur n'a pas été modifié par J5E. La suite reste J5F.

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

# Note postérieure — Navigation header Admin prioritaire

Après J5F-A / J5F-B, le header public a été ajusté.

Règle finale :

```text
ROLE_ADMIN → Admin
ROLE_COURIER seul → Livreur
sinon → Devenir vendeur
```

Si un compte possède `ROLE_ADMIN` et `ROLE_COURIER`, on affiche seulement `Admin`.

Le footer public reste sans lien admin.

---

# Note postérieure — rémunération livreur progressive

J5D a livré le dashboard livreur.

La suite J5G avancée améliore le modèle économique livreur :

```text
rémunération livreur =
payout local
+ supplément par commune traversée
+ compensation barge si nécessaire
```

Cela permettra à un livreur d'être mieux rémunéré quand la livraison demande plus de route ou une traversée PT / GT.
