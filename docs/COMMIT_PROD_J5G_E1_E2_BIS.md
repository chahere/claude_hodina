# COMMIT PROD — J5G-E1 → J5G-E2-bis-A

Date : **17/06/2026**  
Tag : `j5g-e1-e2bis-prod`  
Branche production : `pilot/j5j-commerce-mode-role-tester`  
Commit final : `36cc357 docs(j5g): document commune delivery and cart validation flow`

---

## Objet

Documenter la mise en production validée de J5G-E1 à J5G-E2-bis-A.

Ce jalon met en production :

- commune livrée comme source de vérité ;
- code postal prérempli depuis `DeliveryCommune` ;
- zone non modifiable par le client ;
- recalcul AJAX des frais de livraison ;
- verrouillage du total avant validation ;
- tarif local PT / GT + coût fixe barge ;
- panier réorganisé avec livraison avant récapitulatif ;
- confirmation commande avec récapitulatif complet.

---

## Historique Git

Derniers commits concernés :

```text
36cc357 docs(j5g): document commune delivery and cart validation flow
a70127c feat(j5g): move delivery validation before cart summary
7831c1b feat(j5g): simplify delivery commune checkout and secure delivery totals
```

Tag créé :

```powershell
git tag j5g-e1-e2bis-prod
git push origin j5g-e1-e2bis-prod
```

---

## Recette

Dossier :

```bash
/home/vopu3712/recette.hodina.fr
```

Résultat :

```text
Pull : OK
Composer : OK
Cache clear / warmup : OK
Migrations : New = 0
Schema : OK
Tests navigateur : OK
```

---

## Production

Dossier :

```bash
/home/vopu3712/hodina.fr
```

Mise à jour :

```text
933d70b → 36cc357
```

Avant migration :

```text
Executed : 27
Available : 29
New : 2
```

Migrations exécutées :

```text
DoctrineMigrations\Version20260615140801
DoctrineMigrations\Version20260615225836
```

Après migration :

```text
Executed : 29
New : 0
Current : DoctrineMigrations\Version20260615225836
Schema : in sync
```

---

## Tests production validés

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

---

## Warnings non bloquants

Warnings observés pendant le cache clear / migration :

```text
doctrine.orm.controller_resolver.auto_mapping deprecated
DashboardController sans #[AdminDashboard] deprecated EasyAdmin 5
Doctrine migrations implicit commit deprecation
```

Décision : ne pas bloquer la production. À traiter plus tard dans un jalon technique de nettoyage.

---

## Conclusion

J5G-E1 à J5G-E2-bis-A est clôturé :

```text
Code : OK
Docs : OK
Recette : OK
Production : OK
Tag : j5g-e1-e2bis-prod
```

La suite J5G doit reprendre par J5G-B4 sans recréer la logistique existante.
