<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$files = [
    'homeController' => $root . '/src/Controller/HomeController.php',
    'carnetTemplate' => $root . '/templates/pages/carnet/index.html.twig',
    'livraisonTemplate' => $root . '/templates/pages/carnet/livraison.html.twig',
    'discoverTemplate' => $root . '/templates/pages/decouvrir_hodina.html.twig',
    'baseTemplate' => $root . '/templates/base.html.twig',
    'css' => $root . '/public/css/style_mobile.css',
    'livraisonPetiteTerreImage' => $root . '/public/images/carnet/livraison/livraison-petite-terre.webp',
    'livraisonMamoudzouImage' => $root . '/public/images/carnet/livraison/livraison-mamoudzou.webp',
    'livraisonNordCentreImage' => $root . '/public/images/carnet/livraison/livraison-nord-centre.webp',
    'livraisonSudImage' => $root . '/public/images/carnet/livraison/livraison-sud.webp',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        $failures[] = sprintf('Fichier manquant : %s (%s)', $label, $path);
    }
}

$read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

$homeController = $read($files['homeController']);
$carnetTemplate = $read($files['carnetTemplate']);
$livraisonTemplate = $read($files['livraisonTemplate']);
$discoverTemplate = $read($files['discoverTemplate']);
$baseTemplate = $read($files['baseTemplate']);
$css = $read($files['css']);

$headerStart = strpos($baseTemplate, '<header class="site-header">');
$headerEnd = strpos($baseTemplate, '</header>');
$headerMarkup = '';
if ($headerStart !== false && $headerEnd !== false && $headerEnd > $headerStart) {
    $headerMarkup = substr($baseTemplate, $headerStart, $headerEnd - $headerStart);
}

$footerStart = strpos($baseTemplate, '<footer class="site-footer"');
$footerEnd = strpos($baseTemplate, '</footer>');
$footerMarkup = '';
if ($footerStart !== false && $footerEnd !== false && $footerEnd > $footerStart) {
    $footerMarkup = substr($baseTemplate, $footerStart, $footerEnd - $footerStart);
}

$mustContain = [
    'Route /carnet présente' => [$homeController, "#[Route('/carnet', name: 'app_carnet')]"],
    'Route /carnet/livraison présente' => [$homeController, "#[Route('/carnet/livraison', name: 'app_carnet_livraison')]"],
    'Route /carnet rend le bon template' => [$homeController, "pages/carnet/index.html.twig"],
    'Route livraison rend le bon template' => [$homeController, "pages/carnet/livraison.html.twig"],
    'Carnet explique son rôle' => [$carnetTemplate, 'Pourquoi cette rubrique existe'],
    'Carnet liste Livraison Hodina' => [$carnetTemplate, 'Livraison Hodina'],
    'Carnet lie Livraison Hodina' => [$carnetTemplate, "path('app_carnet_livraison')"],
    'Carnet liste fruits légumes saisons sans lien actif' => [$carnetTemplate, 'Fruits, légumes et saisons'],
    'Carnet liste vendeurs producteurs partenaires sans lien actif' => [$carnetTemplate, 'Nos vendeurs et producteurs partenaires'],
    'Carnet marque les pages futures' => [$carnetTemplate, 'À venir'],
    'Livraison contient fonctionnement' => [$livraisonTemplate, '1. Comment fonctionne la livraison Hodina'],
    'Livraison regroupe zones et jours indicatifs' => [$livraisonTemplate, '2. Zones et jours de livraison indicatifs'],
    'Livraison présente les repères par secteur' => [$livraisonTemplate, 'Les repères par secteur'],
    'Livraison contient domicile et points de remise' => [$livraisonTemplate, '3. Livraison à domicile et points de remise'],
    'Livraison contient délais variables' => [$livraisonTemplate, '4. Pourquoi les délais peuvent varier'],
    'Livraison contient photos et vidéos' => [$livraisonTemplate, '5. Photos et vidéos du terrain'],
    'Livraison contient vérification panier' => [$livraisonTemplate, '6. Vérifier ma livraison dans le panier'],
    'Livraison rappelle source de vérité panier' => [$livraisonTemplate, 'Le panier reste la source de vérité'],
    'Livraison protège contre promesse figée' => [$livraisonTemplate, 'indicatives'],
    'Livraison mentionne Petite-Terre' => [$livraisonTemplate, 'Petite-Terre'],
    'Livraison mentionne Mamoudzou' => [$livraisonTemplate, 'Mamoudzou'],
    'Livraison mentionne Nord et Centre' => [$livraisonTemplate, 'Nord et Centre'],
    'Livraison mentionne Sud' => [$livraisonTemplate, 'Sud'],
    'Livraison affiche la carte Petite-Terre' => [$livraisonTemplate, "images/carnet/livraison/livraison-petite-terre.webp"],
    'Livraison affiche la carte Mamoudzou' => [$livraisonTemplate, "images/carnet/livraison/livraison-mamoudzou.webp"],
    'Livraison affiche la carte Nord et Centre' => [$livraisonTemplate, "images/carnet/livraison/livraison-nord-centre.webp"],
    'Livraison affiche la carte Sud' => [$livraisonTemplate, "images/carnet/livraison/livraison-sud.webp"],
    'Livraison précise lundi jeudi Petite-Terre' => [$livraisonTemplate, 'Lundi & jeudi'],
    'Livraison précise mercredi samedi Mamoudzou et Sud' => [$livraisonTemplate, 'Mercredi & samedi'],
    'Livraison précise mardi vendredi Nord Centre' => [$livraisonTemplate, 'Mardi & vendredi'],
    'Découvrir pointe vers Carnet' => [$discoverTemplate, "path('app_carnet')"],
    'Découvrir pointe vers Livraison Hodina' => [$discoverTemplate, "path('app_carnet_livraison')"],
    'Header expose Infos livraison' => [$headerMarkup, 'Infos livraison'],
    'Header pointe vers la page livraison' => [$headerMarkup, "path('app_carnet_livraison')"],
    'Footer présente Hodina' => [$footerMarkup, 'Hodina'],
    'Footer décrit le marché local' => [$footerMarkup, 'Le marché local en ligne de Mayotte.'],
    'Footer contient colonne Explorer' => [$footerMarkup, 'Explorer'],
    'Footer lie Découvrir Hodina' => [$footerMarkup, "path('app_discover_hodina')"],
    'Footer lie Carnet Hodina' => [$footerMarkup, "path('app_carnet')"],
    'Footer lie Catalogue' => [$footerMarkup, "path('product_catalogue')"],
    'Footer contient colonne Livraison' => [$footerMarkup, 'Livraison'],
    'Footer lie Points de remise' => [$footerMarkup, '#modes'],
    'Footer lie vérification panier' => [$footerMarkup, "path('cart_index')"],
    'Footer affiche Hodina à Mayotte' => [$footerMarkup, '© Hodina à Mayotte'],
    'Footer rappelle frais dates créneaux au panier' => [$footerMarkup, 'Les frais, dates et créneaux exacts sont confirmés au panier.'],
    'Footer affiche Produits locaux' => [$footerMarkup, 'Produits locaux'],
    'Footer affiche Vendeurs de Mayotte' => [$footerMarkup, 'Vendeurs de Mayotte'],
    'Footer affiche Livraison selon commune' => [$footerMarkup, 'Livraison selon commune'],
    'Footer lie Infos livraison' => [$footerMarkup, "path('app_carnet_livraison')"],
    'Footer contient colonne Pratique' => [$footerMarkup, 'Pratique'],
    'Footer lie CGV' => [$footerMarkup, "path('app_cgv')"],
    'Footer lie CGU' => [$footerMarkup, "path('app_cgu')"],
    'Footer affiche contact Hodina' => [$footerMarkup, 'contact@hodina.fr'],
    'Footer affiche mailto contact' => [$footerMarkup, 'mailto:contact@hodina.fr'],
    'Footer rappelle paiement manuel' => [$footerMarkup, 'Paiement manuel pilote'],
    'CSS Carnet présent' => [$css, 'J5Y-F — Carnet Hodina et page pédagogique livraison'],
    'CSS guide livraison présent' => [$css, '.carnet-guide-layout'],
    'CSS visuels livraison présent' => [$css, '.carnet-delivery-visual-grid'],
    'CSS Footer J5Y-G-bis présent' => [$css, 'J5Y-G-bis — Footer réassurance marketplace'],
    'CSS grille footer présente' => [$css, '.footer-grid'],
    'CSS bande réassurance présente' => [$css, '.footer-reassurance'],
];

foreach ($mustContain as $label => [$haystack, $needle]) {
    if (!str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif absent `%s`.', $label, $needle);
    }
}

$mustNotContain = [
    'Header ne doit plus exposer Découvrir Hodina' => [$headerMarkup, 'Découvrir Hodina'],
    'Header ne doit pas exposer Carnet Hodina' => [$headerMarkup, 'Carnet Hodina'],
    'Carnet ne doit pas exposer Djama' => [$carnetTemplate, 'Djama'],
    'Livraison ne doit pas exposer Djama' => [$livraisonTemplate, 'Djama'],
    'Carnet ne doit pas se présenter comme blog public' => [$carnetTemplate, 'Blog'],
    'Livraison ne doit pas promettre une livraison garantie' => [$livraisonTemplate, 'livraison garantie'],
    'Livraison ne doit pas promettre un paiement en ligne' => [$livraisonTemplate, 'paiement en ligne'],
    'Livraison ne doit pas exposer les liaisons logistiques internes' => [$livraisonTemplate, 'liaison logistique'],
    'Footer ne doit pas afficher Contact contact' => [$footerMarkup, 'Contact contact@hodina.fr'],
    'Footer ne doit plus utiliser le badge footer-note' => [$css, '.footer-note'],
];

foreach ($mustNotContain as $label => [$haystack, $needle]) {
    if (str_contains($haystack, $needle)) {
        $failures[] = sprintf('%s : motif interdit détecté `%s`.', $label, $needle);
    }
}

foreach (['livraisonPetiteTerreImage', 'livraisonMamoudzouImage', 'livraisonNordCentreImage', 'livraisonSudImage'] as $imageKey) {
    $imagePath = $files[$imageKey] ?? null;
    if (is_string($imagePath) && is_file($imagePath) && filesize($imagePath) > 100000) {
        $failures[] = sprintf('Image livraison trop lourde : %s (%d octets).', basename($imagePath), filesize($imagePath));
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[J5Y-F][KO] Page Carnet / livraison Hodina non conforme.\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[J5Y-F][OK] Carnet Hodina actif, page livraison pédagogique, header orienté Infos livraison et footer public réassurance conforme.\n";
