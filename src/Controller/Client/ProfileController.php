<?php

namespace App\Controller\Client;

use App\Entity\Customer;
use App\Form\ClientProfileType;
use App\Repository\CustomerRepository;
use App\Service\PhoneNumberNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/mon-compte/profil')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'client_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        PhoneNumberNormalizer $phoneNumberNormalizer,
    ): Response {
        $customer = $this->getCurrentCustomer();
        $phoneParts = $phoneNumberNormalizer->splitForForm($customer->getPhone());

        $form = $this->createForm(ClientProfileType::class, [
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'email' => $customer->getEmail(),
            'phoneCountryCode' => $phoneParts['dialCode'],
            'phone' => $phoneParts['localNumber'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            $normalizedEmail = mb_strtolower(trim((string) ($data['email'] ?? '')));
            $normalizedPhone = $phoneNumberNormalizer->normalizeWithDialCode(
                (string) ($data['phoneCountryCode'] ?? ''),
                (string) ($data['phone'] ?? '')
            );

            if ($normalizedEmail === '') {
                $form->get('email')->addError(new FormError('Veuillez saisir un email.'));
            }

            if ($normalizedPhone === '') {
                $form->get('phone')->addError(new FormError('Veuillez saisir un téléphone valide.'));
            }

            $existingCustomer = null;
            if ($normalizedEmail !== '') {
                $existingCustomer = $customerRepository->createQueryBuilder('customer')
                    ->andWhere('LOWER(customer.email) = :email')
                    ->andWhere('customer.id != :currentCustomerId')
                    ->setParameter('email', $normalizedEmail)
                    ->setParameter('currentCustomerId', $customer->getId())
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            }

            if ($existingCustomer instanceof Customer) {
                $form->get('email')->addError(new FormError('Cet email est déjà utilisé par un autre compte Hodina.'));
            }

            if ($form->isValid()) {
                $customer
                    ->setFirstName(trim((string) ($data['firstName'] ?? '')))
                    ->setLastName($this->normalizeNullableText((string) ($data['lastName'] ?? '')))
                    ->setEmail($normalizedEmail)
                    ->setPhone($normalizedPhone);

                $entityManager->flush();

                $this->addFlash('success', 'Tes informations ont été mises à jour.');

                return $this->redirectToRoute('client_account_index');
            }
        }

        return $this->render('client/profile/edit.html.twig', [
            'customer' => $customer,
            'profileForm' => $form,
        ]);
    }

    private function getCurrentCustomer(): Customer
    {
        $user = $this->getUser();

        if (!$user instanceof Customer) {
            throw $this->createAccessDeniedException('Compte client requis.');
        }

        return $user;
    }

    private function normalizeNullableText(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
