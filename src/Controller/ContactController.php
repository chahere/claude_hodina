<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\SupportTicket;
use App\Entity\SupportTicketMessage;
use App\Form\ContactFormType;
use App\Service\SupportTicketNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Formulaire de contact public. Accessible aux visiteurs non connectés :
 * aucun appel IA ici, uniquement la création d'un SupportTicket traçable.
 */
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        SupportTicketNotificationService $notificationService,
    ): Response {
        $customer = $this->getUser();
        $initialData = [];

        if ($customer instanceof Customer) {
            $initialData['name'] = trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName()));
            $initialData['email'] = $customer->getEmail();
        }

        $form = $this->createForm(ContactFormType::class, $initialData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Piège à robots rempli : on fait comme si tout allait bien, sans créer de ticket.
            if (trim((string) ($data['website'] ?? '')) !== '') {
                $this->addFlash('success', 'Merci, votre message a bien été envoyé. Notre équipe vous répond rapidement.');

                return $this->redirectToRoute('app_contact');
            }

            $ticket = (new SupportTicket())
                ->setOrigin(SupportTicket::ORIGIN_CONTACT_FORM)
                ->setContactName((string) $data['name'])
                ->setContactEmail((string) $data['email'])
                ->setContactPhone($data['phone'] !== '' ? (string) $data['phone'] : null)
                ->setSubject((string) $data['subject']);

            if ($customer instanceof Customer) {
                $ticket->setCustomer($customer);
            }

            $message = (new SupportTicketMessage())
                ->setSenderType(SupportTicketMessage::SENDER_CUSTOMER)
                ->setContent((string) $data['message']);

            if ($customer instanceof Customer) {
                $message->setAuthorCustomer($customer);
            }

            $ticket->addMessage($message);

            $entityManager->persist($ticket);
            $entityManager->flush();

            $notificationService->notifyAdminOfNewTicket($ticket);

            $this->addFlash('success', 'Merci, votre message a bien été envoyé. Notre équipe vous répond rapidement.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/form.html.twig', [
            'form' => $form,
        ]);
    }
}
