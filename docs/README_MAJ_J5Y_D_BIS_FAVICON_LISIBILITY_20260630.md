# README MAJ — J5Y-D-bis — Favicon Hodina plus lisible

Date : 2026-06-30

## Contexte

Après J5Y-D, le logo header est plus lisible. Le favicon apparaît bien, mais il reste visuellement trop petit dans l'onglet navigateur.

La cause est normale : un favicon est affiché autour de 16 px. Le logo complet ou l'hippocampe entier devient trop fin à cette taille.

## Solution appliquée

Le favicon utilise maintenant une version symbolique et agrandie de l'hippocampe Hodina : tête et partie haute, dans un carré blanc.

Cette version est plus lisible dans les onglets, y compris sur navigateur en thème sombre.

## Fichiers modifiés

- `templates/base.html.twig`
- `public/favicon.ico`
- `public/images/favicon-16x16.png`
- `public/images/favicon-32x32.png`
- `public/images/apple-touch-icon.png`
- `tools/assert-j5y-d-header-logo-favicon.php`

## Tests recommandés

```powershell
php -l tools\assert-j5y-d-header-logo-favicon.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-d-header-logo-favicon.php
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
git diff --check
```

## Test navigateur

- Ouvrir `/` en navigation privée.
- Vérifier que le favicon est visible dans l'onglet.
- Vérifier que le header n'a pas changé.
- Vérifier que le logo header reste lisible.

Si l'ancien favicon reste visible, fermer l'onglet puis ouvrir une nouvelle navigation privée. Les navigateurs gardent souvent les favicons en cache.

## Statut après test visuel

Le carré blanc a été jugé trop visible et trop peu élégant dans l’onglet. Cette version est donc supersédée par les essais J5Y-D-ter et par les favicons 16x16/32x32 générés ensuite.
