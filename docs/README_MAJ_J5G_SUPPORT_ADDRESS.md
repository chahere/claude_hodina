# README MAJ — J5G-SUPPORT-ADRESSES

## But

Fiabiliser les adresses avant de clôturer J5G-B4.

## À retenir

```text
Livraison = commune livrable Hodina + PT/GT
Facturation = peut être hors zone + AUTRE
```

## Tests minimum avant commit

```text
EasyAdmin livraison valide
EasyAdmin livraison invalide
EasyAdmin facturation AUTRE valide
EasyAdmin facturation code postal invalide
Inscription client
Checkout
schema:validate
lint:container
git diff --check
```

## Ne pas oublier

Les patchs et ZIP temporaires doivent être supprimés avant commit.

---

# Complément 2026-06-12 — état final après tests front

## État final

```text
EasyAdmin : validé
Checkout : validé sur les cas critiques
Inscription : validée sur les cas critiques
UX erreurs front : améliorée
```

## Règle corrigée importante

La facturation n'est pas forcément hors zone. Elle peut être :

```text
AUTRE — Autre
PT — Petite-Terre
GT — Grande-Terre
```

Mais si elle est PT/GT, elle suit les mêmes contrôles géographiques qu'une livraison.

## Point critique

Ne pas confondre :

```text
zone de livraison
zone de facturation
```

Le checkout a été corrigé pour ne plus écraser la zone de facturation avec la zone de livraison.
