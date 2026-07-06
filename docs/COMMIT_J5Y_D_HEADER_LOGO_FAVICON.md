# J5Y-D — Logo header lisible et favicon Hodina

## Objectif

Améliorer l'identité visuelle de l'en-tête public après le passage du catalogue en homepage.

Le logo affiché en header utilisait la version carrée du logo dans un cadre 38x38 px. Le texte “Hodina” devenait trop petit par rapport aux liens de navigation.

## Décision

- Créer une variante horizontale recadrée du logo pour l'en-tête.
- L'afficher avec une hauteur contrôlée, alignée avec les liens et les icônes du header.
- Ajouter un favicon basé sur l'hippocampe Hodina.
- Ne pas modifier le routing, le catalogue, le panier, le checkout ou le back-office.

## Fichiers concernés

- `templates/base.html.twig`
- `public/css/style_mobile.css`
- `public/images/logo_hodina_header.png`
- `public/favicon.ico`
- `public/images/favicon-32x32.png`
- `public/images/apple-touch-icon.png`
- `tools/assert-j5y-d-header-logo-favicon.php`

## Tests

```bash
php -l tools/assert-j5y-d-header-logo-favicon.php
php bin/console lint:twig templates
php bin/console lint:container
php tools/assert-j5y-d-header-logo-favicon.php
```

## Statut

À valider localement avant merge recette.
