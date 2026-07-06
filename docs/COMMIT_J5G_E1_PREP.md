# COMMIT_J5G_E1_PREP — Cadrage simplification adresse par commune livrée

Date : **16/06/2026**  
Statut : **préparation documentaire, aucun patch appliqué**  
Branche de référence : `pilot/j5j-commerce-mode-role-tester`  
Dernier commit code connu : `279f49c feat(j5g): snapshot order addresses`

## Contexte

Après validation de J5G-E0, la gestion historique des adresses de commande est saine : les commandes gardent un snapshot et les adresses client peuvent être supprimées.

Avant d'ouvrir J5G-B4, une friction UX a été identifiée : le checkout demande encore au client de saisir la commune, le code postal et la zone. Cette saisie est trop technique et expose le client à des erreurs.

## Sources transmises

```powershell
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"; Compress-Archive -Path src, templates, config, assets -DestinationPath "code_j5g_e1_commune_livree_$timestamp.zip" -Force
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"; Compress-Archive -Path docs -DestinationPath "docs_$timestamp.zip" -Force
```

Archives :

```text
code_j5g_e1_commune_livree_2026-06-16_17-48-30.zip
docs_2026-06-16_17-53-34.zip
```

## État du code observé

- `CheckoutType` expose encore `postalCode` en `TextType`.
- `CheckoutType` expose encore `commune` en `TextType`.
- `CheckoutType` expose encore `zone` en `ChoiceType` `PT` / `GT`.
- La facturation conserve une zone séparée avec `AUTRE`, `PT`, `GT`.
- `CheckoutController` utilise déjà `DeliveryCommuneMatcherService` pour valider commune / code postal.
- `DeliveryCommuneMatcherService` sait résoudre une commune par nom ou slug.
- `DeliveryCommuneMatcherService::findDeliveryZoneForCommune()` sait retrouver `DeliveryZone` depuis `DeliveryCommune.territory`.
- `DeliveryCommune` contient déjà `name`, `slug`, `postalCode`, `territory`, `isActive`, `isLogisticsPoint`.
- Le template checkout hydrate déjà les champs depuis une adresse existante via `data-postal-code`, `data-commune`, `data-zone`.

## Décision

Avant J5G-B4, créer J5G-E1 :

```text
Simplifier la saisie adresse par commune livrée.
```

Règle cible :

```text
La commune livrée est choisie par le client.
Le code postal est prérempli.
La zone est déduite par Hodina.
Le backend recalcule tout.
```

## Exclusions

- Ne pas recoder `DeliveryLogisticsService`.
- Ne pas dupliquer `DeliveryCommuneMatcherService`.
- Ne pas créer un référentiel JS autonome non relié à EasyAdmin.
- Ne pas casser J5G-E0.
- Ne pas imposer les communes livrées à la facturation hors zone.

## Tests attendus dans la prochaine discussion

- Labattoir sélectionné → 97615 et PT.
- Commune GT sélectionnée → zone GT.
- Zone non modifiable par le client.
- Manipulation front impossible à valider côté serveur.
- Adresse existante toujours utilisable.
- Snapshot commande toujours alimenté.
