# Commit documentation — clôture J5O/J5P

## Message conseillé

```bash
git commit -m "docs(j5p): close customer delivery code and notifications follow-up"
```

## Résumé

Met à jour les documents de référence après validation recette de J5O-A et J5P-A.

## Points couverts

- Architecture Djama après codes vendeur/client.
- Décisions anti-spam et anti-fallback vers `contact@hodina.fr`.
- Entités `CustomerOrder`, `Seller`, `EmailLog`, `SmsLog`, `HodinaSetting`.
- Workflow complet commande → livraison par code.
- Roadmap actuelle et clarification des anciens libellés J5O/J5P.
- TODO opérationnel après validation J5P-A.
- Prompt réutilisable pour les futures mises à jour documentaires.

## Contrôles

```powershell
git diff --check
git status
```

Ne pas ajouter les archives `.zip`, `.patch`, `.bak` ou fichiers temporaires.
