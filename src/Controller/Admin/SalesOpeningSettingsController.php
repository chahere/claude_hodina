<?php

namespace App\Controller\Admin;

use App\Service\SalesOpeningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class SalesOpeningSettingsController extends AbstractController
{
    #[Route('/ouegnewe/commerce/initialiser', name: 'admin_commerce_settings_init', methods: ['GET'])]
    #[Route('/ouegnewe/preouverture/initialiser', name: 'admin_sales_opening_init', methods: ['GET'])]
    public function init(SalesOpeningService $salesOpening): RedirectResponse
    {
        $salesOpening->ensureDefaultSettings();
        $this->addFlash('success', 'Réglages du mode commerce initialisés. Tu peux maintenant les modifier dans Réglages Hodina.');

        return $this->redirectToRoute('backoffice_dashboard');
    }
}
