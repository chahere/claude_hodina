<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$files = [
    'productController' => $root . '/src/Controller/ProductController.php',
    'homeController' => $root . '/src/Controller/HomeController.php',
    'baseTemplate' => $root . '/templates/base.html.twig',
    'catalogueTemplate' => $root . '/templates/product/catalogue.html.twig',
    'discoverTemplate' => $root . '/templates/pages/decouvrir_hodina.html.twig',
    'homeTemplate' => $root . '/templates/home/index.html.twig',
    'css' => $root . '/public/css/style_mobile.css',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        $failures[] = sprintf('Fichier manquant : %s (%s)', $label, $path);
    }
}

$read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

$productController = $read($files['productController']);
$homeController = $read($files['homeController']);
$baseTemplate = $read($files['baseTemplate']);
$catalogueTemplate = $read($files['catalogueTemplate']);
$discoverTemplate = $read($files['discoverTemplate']);
$homeTemplate = $read($files['homeTemplate']);
$css = $read($files['css']);

$mustContain = [
    'Le catalogue devient la home app_home' => [$productController, "#[Route('/', name: 'app_home')]"],
    'La route product_catalogue génère /' => [$productController, "#[Route('/', name: 'product_catalogue')]"],
    'Ancienne URL /catalogue redirigée' => [$productController, "#[Route('/catalogue', name: 'product_catalogue_legacy')]"],
    'Redirection permanente /catalogue vers /' => [$productController, 'Response::HTTP_MOVED_PERMANENTLY'],
    'Page découvrir canonique sous /decouvrir-hodina' => [$homeController, "#[Route('/decouvrir-hodina', name: 'app_discover_hodina')]"],
    'Ancienne URL /blog/decouvrir-hodina redirigée' => [$homeController, "#[Route('/blog/decouvrir-hodina', name: 'app_discover_hodina_legacy')]"],
    'Rendu template page dédié hors blog' => [$homeController, "pages/decouvrir_hodina.html.twig"],
    'Redirection /blog vers découvrir' => [$homeController, "#[Route('/blog', name: 'app_blog')]"],
    'Logo retourne au catalogue-home' => [$baseTemplate, "path('product_catalogue')"],
    'Navigation propose Découvrir Hodina' => [$baseTemplate, "path('app_discover_hodina')"],
    'Catalogue garde le mode AJAX progressif' => [$catalogueTemplate, 'data-catalogue-page'],
    'Catalogue conserve les résultats fragment' => [$catalogueTemplate, 'data-catalogue-results'],
    'Catalogue J5AB garde une recherche compacte' => [$catalogueTemplate, 'catalogue-accessible-title'],
    'Découvrir parle aux clients' => [$discoverTemplate, 'Pour les clients'],
    'Découvrir parle aux vendeurs' => [$discoverTemplate, 'Pour les vendeurs'],
    'Découvrir parle aux livreurs' => [$discoverTemplate, 'Pour les livreurs'],
    'Découvrir conserve l’ancrage territorial Mayotte' => [$discoverTemplate, 'Mayotte'],
    'Découvrir utilise une formulation client claire du marché' => [$discoverTemplate, 'marché local en ligne'],
    'Découvrir garde une promesse de proximité' => [$discoverTemplate, 'gardant le lien'],
    'Découvrir rappelle le paiement manuel pilote' => [$discoverTemplate, 'Paiement manuel pendant le pilote'],
    'Découvrir prépare la saisonnalité produit' => [$discoverTemplate, 'saisonnalité'],
    'Découvrir pointe vers le Carnet Hodina actif' => [$discoverTemplate, "path('app_carnet')"],
    'Découvrir pointe vers la page livraison du Carnet' => [$discoverTemplate, "path('app_carnet_livraison')"],
    'Fallback home template évite une divergence éditoriale' => [$homeTemplate, "extends 'pages/decouvrir_hodina.html.twig'"],
    'CSS dédié page découvrir' => [$css, 'J5Y-C — Catalogue en accueil + page Découvrir Hodina'],
    'CSS cartes découvrir' => [$css, '.discover-card-grid'],
];

foreach ($mustContain as $label => [$haystack, $needle]) {
    if (!str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif absent `%s`.', $label, $needle);
    }
}

$mustNotContain = [
    'HomeController ne doit plus servir / comme ancienne landing' => [$homeController, "#[Route('/', name: 'app_home')]"],
    'product_catalogue ne doit plus pointer vers /catalogue' => [$productController, "#[Route('/catalogue', name: 'product_catalogue')]"],
    'Catalogue ne doit pas mélanger livraison métier dans le template' => [$catalogueTemplate, 'DeliveryLogisticsService'],
    'Catalogue achat-first ne doit plus porter le CTA institutionnel Découvrir' => [$catalogueTemplate, "path('app_discover_hodina')"],
    'Catalogue achat-first ne doit plus afficher le hero institutionnel' => [$catalogueTemplate, 'Marketplace locale de Mayotte'],
    'Découvrir ne doit pas exposer le nom du portail livreur privé' => [$discoverTemplate, 'Djama'],
    'Découvrir ne doit pas utiliser le vocabulaire interne marché digital' => [$discoverTemplate, 'marché digital'],
    'Découvrir ne doit pas exposer les traversées logistiques' => [$discoverTemplate, 'traversées'],
    'Découvrir ne doit pas garder la faute suivis au singulier' => [$discoverTemplate, 'un suivis étape par étape'],
    'Découvrir ne doit pas introduire un paiement en ligne non validé' => [$discoverTemplate, 'paiement en ligne'],
    'Découvrir ne doit pas réutiliser un libellé public de blog' => [$discoverTemplate, 'Blog'],
];

foreach ($mustNotContain as $label => [$haystack, $needle]) {
    if (str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif interdit détecté `%s`.', $label, $needle);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[J5Y-C][KO] Routage homepage catalogue / page Découvrir non conforme.\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[J5Y-C/J5Y-E/J5Y-F/J5AB][OK] Catalogue sur /, Découvrir Hodina canonique sur /decouvrir-hodina, anciennes URLs blog redirigées, Carnet actif, catalogue achat-first sans CTA institutionnel en haut.\n";
