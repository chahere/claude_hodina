<?php

namespace App\Controller;

use App\Entity\LaunchSubscriber;
use App\Service\SalesOpeningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LaunchSubscriberController extends AbstractController
{
    #[Route('/preouverture/inscription', name: 'launch_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em, SalesOpeningService $salesOpening): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('launch_subscribe', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Impossible d’enregistrer cet e-mail : jeton de sécurité invalide.');

            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $state = $salesOpening->getState();
        if (empty($state['emailCaptureEnabled'])) {
            $this->addFlash('warning', 'La liste d’alerte ouverture n’est pas active pour le moment.');

            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $email = mb_strtolower(trim((string) $request->request->get('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', 'Entre une adresse e-mail valide pour être averti à l’ouverture.');

            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $repo = $em->getRepository(LaunchSubscriber::class);
        $subscriber = $repo->findOneBy(['email' => $email]);

        if (!$subscriber instanceof LaunchSubscriber) {
            $subscriber = (new LaunchSubscriber())
                ->setEmail($email)
                ->setIpAddress($request->getClientIp())
                ->setUserAgent($request->headers->get('User-Agent'));

            $em->persist($subscriber);
            $em->flush();
        }

        $this->addFlash('success', (string) ($state['successMessage'] ?? 'Merci, ton e-mail est bien enregistré.'));

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
    }
}
