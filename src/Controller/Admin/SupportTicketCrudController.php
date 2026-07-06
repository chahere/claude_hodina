<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\SupportTicket;
use App\Entity\SupportTicketMessage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SupportTicketCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SupportTicket::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ticket support')
            ->setEntityLabelInPlural('Tickets support')
            ->setDefaultSort(['updatedAt' => 'DESC', 'id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $reply = Action::new('reply', 'Répondre', 'fa fa-reply')
            ->linkToCrudAction('reply')
            ->displayIf(static fn (SupportTicket $ticket): bool => $ticket->getStatus() !== SupportTicket::STATUS_CLOSED);

        $close = Action::new('close', 'Clôturer', 'fa fa-check')
            ->linkToCrudAction('close')
            ->displayIf(static fn (SupportTicket $ticket): bool => $ticket->getStatus() !== SupportTicket::STATUS_CLOSED);

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static fn (Action $action): Action => $action->setLabel('Voir'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, static fn (Action $action): Action => $action->setLabel('Retour aux tickets'))
            ->add(Crud::PAGE_INDEX, $reply)
            ->add(Crud::PAGE_INDEX, $close)
            ->add(Crud::PAGE_DETAIL, $reply)
            ->add(Crud::PAGE_DETAIL, $close);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le');
        yield ChoiceField::new('origin', 'Origine')
            ->setChoices(array_flip(self::getOriginLabels()))
            ->formatValue(static fn (?string $value): string => self::getOriginLabels()[$value ?? ''] ?? (string) $value);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_flip(self::getStatusLabels()))
            ->formatValue(static fn (?string $value): string => self::getStatusLabels()[$value ?? ''] ?? (string) $value);
        yield TextField::new('subject', 'Sujet');
        yield TextField::new('contactName', 'Contact');
        yield TextField::new('contactEmail', 'E-mail')->hideOnIndex();
        yield TextField::new('contactPhone', 'Téléphone')->hideOnIndex();
        yield AssociationField::new('customer', 'Client Hodina')->hideOnIndex();
        yield AssociationField::new('chatbotConversation', 'Conversation IA liée')->onlyOnDetail();
        yield AssociationField::new('messages', 'Échanges')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/support_ticket_messages.html.twig');
        yield DateTimeField::new('updatedAt', 'Dernière activité')->hideOnForm();
        yield DateTimeField::new('closedAt', 'Clôturé le')->hideOnForm()->onlyOnDetail();
    }

    public function reply(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): Response
    {
        $ticket = $context->getEntity()->getInstance();

        if (!$ticket instanceof SupportTicket) {
            $this->addFlash('danger', 'Ticket introuvable.');

            return $this->redirectToTicketIndex($adminUrlGenerator);
        }

        if ($ticket->getStatus() === SupportTicket::STATUS_CLOSED) {
            $this->addFlash('warning', 'Ce ticket est clôturé : il ne peut plus recevoir de réponse.');

            return $this->redirectToTicketDetail($adminUrlGenerator, $ticket);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('support_ticket_reply_' . $ticket->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide.');

                return $this->redirectToTicketDetail($adminUrlGenerator, $ticket);
            }

            $content = trim((string) $request->request->get('content', ''));

            if ($content === '') {
                $this->addFlash('danger', 'Le message de réponse ne peut pas être vide.');

                return $this->redirectToTicketAction($adminUrlGenerator, $ticket, 'reply');
            }

            $admin = $this->getUser();

            $message = (new SupportTicketMessage())
                ->setSenderType(SupportTicketMessage::SENDER_ADMIN)
                ->setContent($content);

            if ($admin instanceof Customer) {
                $message->setAuthorCustomer($admin);
            }

            $ticket->addMessage($message);

            if ($ticket->getStatus() === SupportTicket::STATUS_OPEN) {
                $ticket->setStatus(SupportTicket::STATUS_IN_PROGRESS);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Réponse enregistrée sur le ticket.');

            return $this->redirectToTicketDetail($adminUrlGenerator, $ticket);
        }

        return $this->render('admin/support_ticket/reply.html.twig', [
            'ticket' => $ticket,
            'urls' => [
                'detail' => $this->buildTicketCrudUrl($adminUrlGenerator, $ticket, Action::DETAIL),
            ],
        ]);
    }

    public function close(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): Response
    {
        $ticket = $context->getEntity()->getInstance();

        if (!$ticket instanceof SupportTicket) {
            $this->addFlash('danger', 'Ticket introuvable.');

            return $this->redirectToTicketIndex($adminUrlGenerator);
        }

        if ($ticket->getStatus() === SupportTicket::STATUS_CLOSED) {
            $this->addFlash('warning', 'Ce ticket est déjà clôturé.');

            return $this->redirectToTicketDetail($adminUrlGenerator, $ticket);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('support_ticket_close_' . $ticket->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide.');

                return $this->redirectToTicketDetail($adminUrlGenerator, $ticket);
            }

            $ticket->setStatus(SupportTicket::STATUS_CLOSED);
            $entityManager->flush();

            $this->addFlash('success', 'Ticket clôturé.');

            return $this->redirectToTicketDetail($adminUrlGenerator, $ticket);
        }

        return $this->render('admin/support_ticket/close.html.twig', [
            'ticket' => $ticket,
            'urls' => [
                'detail' => $this->buildTicketCrudUrl($adminUrlGenerator, $ticket, Action::DETAIL),
            ],
        ]);
    }

    private function buildTicketCrudUrl(AdminUrlGenerator $adminUrlGenerator, SupportTicket $ticket, string $action): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction($action)
            ->setEntityId($ticket->getId())
            ->generateUrl();
    }

    private function redirectToTicketAction(AdminUrlGenerator $adminUrlGenerator, SupportTicket $ticket, string $action): RedirectResponse
    {
        return $this->redirect($this->buildTicketCrudUrl($adminUrlGenerator, $ticket, $action));
    }

    private function redirectToTicketDetail(AdminUrlGenerator $adminUrlGenerator, SupportTicket $ticket): RedirectResponse
    {
        return $this->redirectToTicketAction($adminUrlGenerator, $ticket, Action::DETAIL);
    }

    private function redirectToTicketIndex(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * @return array<string, string>
     */
    private static function getStatusLabels(): array
    {
        return [
            SupportTicket::STATUS_OPEN => 'Ouvert',
            SupportTicket::STATUS_IN_PROGRESS => 'En cours',
            SupportTicket::STATUS_CLOSED => 'Clôturé',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getOriginLabels(): array
    {
        return [
            SupportTicket::ORIGIN_CONTACT_FORM => 'Formulaire de contact',
            SupportTicket::ORIGIN_CHATBOT_ESCALATION => 'Escalade chatbot IA',
        ];
    }
}
