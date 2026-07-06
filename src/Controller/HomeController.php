<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/decouvrir-hodina', name: 'app_discover_hodina')]
    public function discover(): Response
    {
        return $this->render('pages/decouvrir_hodina.html.twig');
    }

    #[Route('/blog/decouvrir-hodina', name: 'app_discover_hodina_legacy')]
    public function legacyDiscover(): Response
    {
        return $this->redirectToRoute('app_discover_hodina', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/blog', name: 'app_blog')]
    public function blog(): Response
    {
        return $this->redirectToRoute('app_discover_hodina', [], Response::HTTP_MOVED_PERMANENTLY);
    }


    #[Route('/carnet', name: 'app_carnet')]
    public function carnet(): Response
    {
        return $this->render('pages/carnet/index.html.twig');
    }

    #[Route('/carnet/livraison', name: 'app_carnet_livraison')]
    public function carnetLivraison(): Response
    {
        return $this->render('pages/carnet/livraison.html.twig');
    }
}
