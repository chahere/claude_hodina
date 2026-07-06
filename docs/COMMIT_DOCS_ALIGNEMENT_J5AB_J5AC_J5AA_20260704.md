# COMMIT — Alignement documentation J5AB/J5AC/J5AA

Date : 2026-07-04

## Résumé

Corrige les incohérences documentaires avant J5AA : le portail client n’est plus une prochaine priorité MVP mais un espace client finalisé en J5AC, tandis que `/mon-compte/adresses` reste non codé.

Complète aussi le cadrage J5AA avec la cohérence code postal / commune seedée, sans inventer de code ou de migration.

## Points importants

- `/mon-compte` = hub compte client.
- `/mon-compte/profil` = fait.
- `/mon-compte/adresses` = non fait.
- `Address` = carnet technique panier/checkout.
- `AddressLocality` = prévu/non codé.
- Code postal et localité ne calculent jamais les frais.
- `DeliveryCommune` reste la source logistique et tarifaire.

## Commit conseillé

```bash
git commit -m "docs: align client account status before address locality"
```
