# Commit documentation — clôture J5Q-A

## Message conseillé

```bash
git commit -m "docs(j5q): close courier payout follow-up"
```

## Résumé

Met à jour les documents de référence après validation recette de J5Q-A.

## Points couverts

- Architecture des paiements livreurs.
- Entités `CourierPayout` et `CourierPayoutLine`.
- Service `CourierPayoutService`.
- Menu EasyAdmin métier : clients, vendeurs, livreurs, logs.
- Workflow admin de génération / validation / paiement.
- Workflow Djama `Mes paiements`.
- Déploiement recette par tag `j5q-paiements-livreurs-recette`.
- Validation recette avec un paiement `PAID` de 30,00 €.
- Clarification des anciens jalons J5O/J5P prévisionnels.

## Contrôles

```powershell
git diff --check
git status
```

Ne pas ajouter les archives `.zip`, `.patch`, `.bak`, `.corrected.php` ou fichiers temporaires.
