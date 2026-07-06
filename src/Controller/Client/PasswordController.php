<?php

namespace App\Controller\Client;

use App\Entity\Customer;
use App\Form\ClientChangePasswordType;
use App\Service\CustomerPasswordResetLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/mon-compte/mot-de-passe')]
final class PasswordController extends AbstractController
{
    #[Route('', name: 'client_security_password', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $customer = $this->getCurrentCustomer();
        $form = $this->createForm(ClientChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = (string) $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($customer, $currentPassword)) {
                $form->get('currentPassword')->addError(new FormError('L’ancien mot de passe est incorrect.'));
            }

            if ($form->isValid()) {
                $plainPassword = (string) $form->get('plainPassword')->getData();
                $customer
                    ->setPassword($passwordHasher->hashPassword($customer, $plainPassword))
                    ->setResetPasswordToken(null)
                    ->setResetPasswordTokenExpiresAt(null);

                $entityManager->flush();

                $this->addFlash('success', 'Ton mot de passe a été modifié.');

                return $this->redirectToRoute('client_account_index');
            }
        }

        return $this->render('client/security/password.html.twig', [
            'customer' => $customer,
            'passwordForm' => $form,
        ]);
    }

    #[Route('/lien-reinitialisation', name: 'client_security_reset_link_request', methods: ['POST'])]
    public function requestResetLink(
        Request $request,
        CustomerPasswordResetLinkService $resetLinkService,
    ): RedirectResponse {
        $customer = $this->getCurrentCustomer();

        if (!$this->isCsrfTokenValid('client_password_reset_link', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'La demande de lien a expiré. Réessaie depuis ton espace client.');

            return $this->redirectToRoute('client_security_password');
        }

        $resetLinkService->createResetLink($customer);
        $this->addFlash('success', 'Un lien de réinitialisation a été préparé pour envoi SMS manuel pendant le pilote Hodina.');

        return $this->redirectToRoute('client_security_password');
    }

    private function getCurrentCustomer(): Customer
    {
        $user = $this->getUser();

        if (!$user instanceof Customer) {
            throw $this->createAccessDeniedException('Compte client requis.');
        }

        return $user;
    }
}
