<?php

declare(strict_types=1);

/*
 * J5AB — Catalogue mobile orienté achat.
 *
 * Objectif : vérifier que le haut du catalogue est devenu achat-first :
 * recherche compacte + bouton Filtres sur la même ligne, filtres avancés
 * repliables, produits affichés rapidement, avec une intro Hodina courte
 * mais sans hero institutionnel.
 *
 * Usage :
 *   php tools/assert-j5ab-catalogue-mobile-buy-first.php
 */

$root = dirname(__DIR__);
$failures = [];

$files = [
    'controller' => $root . '/src/Controller/ProductController.php',
    'catalogue' => $root . '/templates/product/catalogue.html.twig',
    'filters' => $root . '/templates/product/_catalogue_filters.html.twig',
    'results' => $root . '/templates/product/_catalogue_results.html.twig',
    'card' => $root . '/templates/product/_catalogue_product_card.html.twig',
    'css' => $root . '/public/css/style_mobile.css',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        $failures[] = sprintf('Fichier manquant : %s (%s)', $label, $path);
    }
}

$read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

$controller = $read($files['controller']);
$catalogue = $read($files['catalogue']);
$filters = $read($files['filters']);
$results = $read($files['results']);
$card = $read($files['card']);
$css = $read($files['css']);

$mustContain = [
    'Le catalogue reste servi par /' => [$controller, "#[Route('/', name: 'product_catalogue')]"],
    'La route legacy /catalogue reste redirigée' => [$controller, "#[Route('/catalogue', name: 'product_catalogue_legacy')]"],
    'Le fragment AJAX catalogue reste pris en charge' => [$controller, "query->getBoolean('fragment')"],
    'Le template garde le root AJAX progressif' => [$catalogue, 'data-catalogue-page'],
    'Le template affiche une intro Hodina compacte avant la recherche' => [$catalogue, 'catalogue-market-intro'],
    'Le template garde la promesse locale courte' => [$catalogue, 'Le marché local en ligne de Mayotte.'],
    'Le template garde la livraison organisée selon les communes' => [$catalogue, 'livraison organisée selon les communes'],
    'Le template inclut les filtres avant les résultats' => [$catalogue, "include 'product/_catalogue_filters.html.twig'"],
    'Le template garde les résultats AJAX' => [$catalogue, 'data-catalogue-results'],
    'Le JS garde fetch' => [$catalogue, 'fetch('],
    'Le JS garde history.pushState' => [$catalogue, 'history.pushState'],
    'Le JS pilote le tiroir filtres sans changer la route' => [$catalogue, 'data-catalogue-filter-toggle'],
    'Le formulaire reste en GET' => [$filters, 'method="get"'],
    'La recherche reste visible immédiatement' => [$filters, 'catalogue-search-inline'],
    'Le placeholder vendeur reste cohérent avec le repository' => [$filters, 'Rechercher un produit, un vendeur…'],
    'La loupe reste un vrai submit fallback sans JS' => [$filters, 'catalogue-search-submit'],
    'Le bouton Filtres existe' => [$filters, 'data-catalogue-filter-toggle'],
    'Le panneau filtres avancés existe' => [$filters, 'data-catalogue-filter-panel'],
    'Le panneau peut rester ouvert si filtre avancé actif' => [$filters, 'data-open-on-load'],
    'Le champ q est conservé' => [$filters, 'name="q"'],
    'Le champ categorie est conservé' => [$filters, 'name="categorie"'],
    'Le champ tri est conservé' => [$filters, 'name="tri"'],
    'Le tri Ordre Hodina reste disponible' => [$filters, 'Ordre Hodina'],
    'Le bouton d’action devient Appliquer' => [$filters, 'Appliquer'],
    'La réinitialisation reste disponible si filtre actif' => [$filters, 'Réinitialiser'],
    'Le compteur produits reste dans les résultats' => [$results, 'produits trouvés'],
    'Les cartes produits restent inchangées côté panier AJAX' => [$card, 'data-ajax-cart-form'],
    'Le CSS marque le lot J5AB' => [$css, 'J5AB — Catalogue mobile orienté achat'],
    'Le CSS contient l’intro compacte Hodina' => [$css, 'catalogue-market-intro'],
    'Le CSS aligne recherche et filtres sur une ligne' => [$css, 'grid-template-columns:minmax(0, 1fr) auto'],
    'Le CSS masque proprement le panneau replié' => [$css, '.catalogue-filter-panel[hidden]'],
];

foreach ($mustContain as $label => [$haystack, $needle]) {
    if (!str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif absent `%s`.', $label, $needle);
    }
}

$introPosition = strpos($catalogue, 'catalogue-market-intro');
$filterPosition = strpos($catalogue, "include 'product/_catalogue_filters.html.twig'");
$resultsPosition = strpos($catalogue, 'data-catalogue-results');
if ($filterPosition === false || $resultsPosition === false || $filterPosition > $resultsPosition) {
    $failures[] = 'Les filtres compacts doivent apparaître avant le compteur et les cartes produits.';
}
if ($introPosition === false || $filterPosition === false || !($introPosition < $filterPosition)) {
    $failures[] = 'L’intro Hodina compacte doit rester juste au-dessus de la recherche, sans repousser les résultats avec un hero.';
}

$quickbarPosition = strpos($filters, 'catalogue-quickbar');
$searchPosition = strpos($filters, 'catalogue-search-inline');
$togglePosition = strpos($filters, 'data-catalogue-filter-toggle');
$panelPosition = strpos($filters, 'data-catalogue-filter-panel');
if ($quickbarPosition === false || $searchPosition === false || $togglePosition === false || $panelPosition === false) {
    $failures[] = 'Structure quickbar/panneau filtres introuvable.';
} elseif (!($quickbarPosition < $searchPosition && $searchPosition < $togglePosition && $togglePosition < $panelPosition)) {
    $failures[] = 'La recherche, la loupe et Filtres doivent être dans la quickbar avant le panneau catégorie/tri.';
}

$mustNotContain = [
    'Le catalogue ne doit plus afficher le gros eyebrow institutionnel' => [$catalogue, 'Marketplace locale de Mayotte'],
    'Le catalogue ne doit plus afficher le titre marketing au-dessus des produits' => [$catalogue, 'Produits locaux de Mayotte'],
    'Le catalogue ne doit plus afficher le long texte livraison au-dessus des produits' => [$catalogue, 'Hodina t’indique les frais'],
    'Le catalogue ne doit plus pousser les produits avec le CTA Découvrir' => [$catalogue, "path('app_discover_hodina')"],
    'Le formulaire ne doit plus utiliser le libellé lourd Voir les produits' => [$filters, 'Voir les produits'],
    'J5AB ne doit pas toucher au calcul de livraison' => [$catalogue . $filters . $results, 'DeliveryLogisticsService'],
    'J5AB ne doit pas introduire une pagination non existante' => [$catalogue . $filters . $results, 'pagination'],
    'J5AB ne doit pas mélanger la commune dans le catalogue' => [$catalogue . $filters, 'commune='],
];

foreach ($mustNotContain as $label => [$haystack, $needle]) {
    if (str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif interdit détecté `%s`.', $label, $needle);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[J5AB][KO] Catalogue mobile achat-first non conforme.\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[J5AB][OK] Catalogue mobile achat-first : intro Hodina courte, recherche + loupe + Filtres sur une ligne, panneau catégorie/tri repliable, compteur et produits rapprochés, sans hero institutionnel.\n";
