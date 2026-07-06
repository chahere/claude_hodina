
# Mise à jour documentation Hodina — J5E

Ce dossier contient les documents de référence mis à jour après la validation de J5E.

## Jalon clôturé

```text
J5E — Marge produit Hodina
```

## Points documentés

- prix producteur ;
- marge produit ;
- marge vendeur ;
- marge globale `global_margin_rate` ;
- `ProductPricingService` ;
- valeurs économiques figées dans `OrderItem` ;
- migration `Version20260607120000` ;
- incidents corrigés : migration absente, service tronqué, migration tronquée ;
- validation locale et préproduction ;
- suite J5F.

## Nouveau fichier

```text
COMMIT_J5E.md
```

---

# Note de continuité — J5G avancé

J5E a figé les prix produit dans `OrderItem`.

J5G-E devra appliquer le même principe côté livraison :

```text
calcul dynamique dans le panier
recalcul définitif au checkout
snapshot dans CustomerOrder
```

Le modèle économique livraison reste séparé de la marge produit.
