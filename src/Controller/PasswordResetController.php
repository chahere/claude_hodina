<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\ForgotPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\CustomerRepository;
use App\Service\CustomerPasswordResetLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordResetController extends AbstractController
{
    #[Route('/hodi/mot-de-passe-oublie', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function requestReset(
        Request $request,
        CustomerRepository $customerRepository,
        CustomerPasswordResetLinkService $resetLinkService
    ): Response {
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('backoffice');
            }

            return $this->redirectToRoute('product_catalogue');
        }

        $form = $this->createForm(ForgotPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));
            $customer = $customerRepository->findOneBy(['email' => $email]);

            if ($customer instanceof Customer) {
                $resetLinkService->createResetLink($customer);
            }

            return $this->redirectToRoute('app_forgot_password_check_sms');
        }

        return $this->render('security/forgot_password_request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/hodi/mot-de-passe-sms', name: 'app_forgot_password_check_sms', methods: ['GET'])]
    public function checkSms(): Response
    {
        return $this->render('security/forgot_password_check_sms.html.twig');
    }

    #[Route('/hodi/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($token === '') {
            throw $this->createNotFoundException('Lien de réinitialisation invalide.');
        }

        $customer = $customerRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$customer instanceof Customer) {
            return $this->render('security/reset_password_invalid.html.twig');
        }

        $expiresAt = $customer->getResetPasswordTokenExpiresAt();
        if (!$expiresAt instanceof \DateTimeImmutable || $expiresAt < new \DateTimeImmutable()) {
            return $this->render('security/reset_password_invalid.html.twig');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $customer
                ->setPassword($passwordHasher->hashPassword($customer, $plainPassword))
                ->setResetPasswordToken(null)
                ->setResetPasswordTokenExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Ton mot de passe a été réinitialisé. Tu peux te connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form,
        ]);
    }
}
