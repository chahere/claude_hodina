# README — Mise à jour documentation J5Q-A

Date : **24/06/2026**

## Objet

Mettre à jour la documentation après validation recette de J5Q-A : paiements livreurs, historique Djama, suivi admin et réorganisation métier du menu EasyAdmin.

## Sources prises en compte

- Archive docs : `docs_2026-06-24_18-27-34.zip`.
- Archive code : `hodina_code_2026-06-24_18-27-34.zip`.
- Tests recette validés : paiement livreur `PAID` de 30,00 € sur deux commandes.
- Déploiement recette : tag `j5q-paiements-livreurs-recette`, commit `12bb402`.

## Points documentés

- `CourierPayout` et `CourierPayoutLine`.
- `CourierPayoutService`.
- Migration `Version20260624140000`.
- Menu EasyAdmin : `Clients`, `Vendeurs`, `Livreurs`, `Logs`.
- `CourierCrudController` filtré sur `ROLE_COURIER`.
- Portail Djama : section `Mes paiements` avec cartes repliées.
- Règles de paiement par quinzaine.
- Source de vérité `CustomerOrder.deliveredAt`.
- Validation recette complète.

## Incohérences corrigées

Les anciens jalons prévisionnels :

```text
J5O — optimisation automatique images
J5P — suivi financier manuel
```

ne doivent plus être lus comme des jalons numérotés actifs, car les lots réels validés sont :

```text
J5O-A — code de réception client chiffré
J5P-A — notifications client statuts
J5Q-A — paiements livreurs
```

Les sujets images automatiques et finance globale restent en backlog post-MVP sans numéro définitif.
