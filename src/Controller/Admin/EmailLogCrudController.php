<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EmailLog;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Journal e-mail')
            ->setEntityLabelInPlural('Journaux e-mails')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendManualEmail = Action::new('sendManualEmail', 'Envoyer manuellement', 'fa fa-envelope')
            ->linkToUrl(fn (EmailLog $emailLog): string => $this->buildManualMailtoUrl($emailLog))
            ->setHtmlAttributes([
                'title' => 'Ouvrir le client mail avec le destinataire, le sujet et le corps préremplis',
            ])
            ->setCssClass('btn btn-primary');

        $clearAll = Action::new('clearAllEmailLogs', 'Vider les journaux e-mails', 'fa fa-trash')
            ->createAsGlobalAction()
            ->linkToRoute('admin_email_log_clear')
            ->setCssClass('btn btn-danger');

        return $actions
            // Le détail EasyAdmin permet de lire le corps complet du mail et l'erreur éventuelle.
            ->add(Action::INDEX, Action::DETAIL)
            ->update(Action::INDEX, Action::DETAIL, static function (Action $action): Action {
                return $action
                    ->setLabel('Voir')
                    ->setIcon('fa fa-eye')
                    ->setHtmlAttributes([
                        'title' => 'Voir le détail du journal e-mail',
                    ]);
            })
            ->add(Action::INDEX, $sendManualEmail)
            ->add(Action::DETAIL, $sendManualEmail)
            ->add(Crud::PAGE_INDEX, $clearAll)
            // Suppression unitaire et par lot autorisées : ce sont des journaux, pas des données métier.
            ->disable(Action::NEW, Action::EDIT);
    }

    #[Route('/ouegnewe/email-logs/vider', name: 'admin_email_log_clear')]
    public function clearAll(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $count = (int) $entityManager->createQuery('SELECT COUNT(e.id) FROM '.EmailLog::class.' e')->getSingleScalarResult();

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('clear_email_logs', $token)) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $entityManager->createQuery('DELETE FROM '.EmailLog::class.' e')->execute();

            $this->addFlash('success', sprintf('%d journal(-aux) e-mail(s) supprimé(s).', $count));

            return $this->redirect($this->buildIndexUrl($adminUrlGenerator));
        }

        return $this->render('admin/_clear_all_confirm.html.twig', [
            'title' => 'Vider les journaux e-mails',
            'entityLabel' => 'journal(-aux) e-mail(s)',
            'count' => $count,
            'warningText' => 'Cette action supprime définitivement tous les journaux e-mails enregistrés, y compris ceux liés à des commandes existantes. Elle est irréversible.',
            'csrfTokenId' => 'clear_email_logs',
            'cancelUrl' => $this->buildIndexUrl($adminUrlGenerator),
        ]);
    }

    private function buildIndexUrl(AdminUrlGenerator $adminUrlGenerator): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('createdAt', 'Créé le');
        yield AssociationField::new('customerOrder', 'Commande');
        yield AssociationField::new('customer', 'Client');
        yield EmailField::new('recipientEmail', 'Destinataire');
        yield EmailField::new('fromEmail', 'Expéditeur')->hideOnIndex();
        yield TextField::new('fromName', 'Nom expéditeur')->hideOnIndex();
        yield EmailField::new('replyToEmail', 'Réponse à')->hideOnIndex();
        yield TextField::new('replyToName', 'Nom réponse')->hideOnIndex();
        yield TextField::new('subject', 'Sujet');
        yield TextField::new('eventKey', 'Événement');
        yield TextField::new('status', 'Statut');
        yield DateTimeField::new('sentAt', 'Envoyé le');
        yield TextareaField::new('body', 'Corps')->onlyOnDetail();
        yield TextareaField::new('errorMessage', 'Erreur')->hideOnIndex();
    }

    /**
     * Prépare un mailto de secours pour le pilote.
     *
     * L'admin envoie ensuite le message depuis son client mail configuré avec
     * contact@hodina.fr. Le mail apparaît alors naturellement dans les messages
     * envoyés du client lourd, sans dépendre d'une copie IMAP côté Symfony.
     */
    private function buildManualMailtoUrl(EmailLog $emailLog): string
    {
        $recipient = trim($emailLog->getRecipientEmail());
        $subject = trim($emailLog->getSubject());
        $body = $this->buildManualEmailBody($emailLog);

        if ($recipient === '') {
            return '#';
        }

        return sprintf(
            'mailto:%s?subject=%s&body=%s',
            $this->encodeMailtoRecipient($recipient),
            rawurlencode($subject),
            rawurlencode($body)
        );
    }

    private function buildManualEmailBody(EmailLog $emailLog): string
    {
        $storedBody = trim((string) $emailLog->getBody());
        if ($storedBody !== '') {
            return $storedBody;
        }

        $order = $emailLog->getCustomerOrder();
        $customer = $emailLog->getCustomer() ?? $order?->getCustomer();
        $firstName = $customer ? trim($customer->getFirstName()) : '';
        $orderReference = $order?->getOrderReference() ?: 'votre commande Hodina';

        $lines = [];
        $lines[] = sprintf('Bonjour%s,', $firstName !== '' ? ' '.$firstName : '');
        $lines[] = '';
        $lines[] = sprintf('Nous avons bien reçu votre commande %s.', $orderReference);
        $lines[] = '';
        $lines[] = 'Pendant la phase pilote, un administrateur vérifie la disponibilité des produits avant validation.';
        $lines[] = 'Le paiement se fera à la livraison.';

        if ($order !== null) {
            $lines[] = '';
            $lines[] = 'Articles commandés :';

            if ($order->getItems()->isEmpty()) {
                $lines[] = '- Articles non disponibles dans le journal e-mail. Vérifier la commande dans EasyAdmin avant envoi.';
            } else {
                foreach ($order->getItems() as $item) {
                    $lines[] = sprintf(
                        '- %s x%s : %s €',
                        $item->getProduct()->getName(),
                        $item->getQuantity(),
                        $this->formatMoney($item->getLineTotal())
                    );
                }
            }

            $lines[] = '';
            $lines[] = sprintf('Sous-total produits : %s €', $this->formatMoney($order->getSubtotal()));
            $lines[] = sprintf('Frais de livraison : %s €', $this->formatMoney($order->getDeliveryFee()));
            $lines[] = sprintf('Total : %s €', $this->formatMoney($order->getTotal()));

            if ($order->getDeliveryAddressLine1()) {
                $lines[] = '';
                $lines[] = 'Adresse de livraison :';
                $lines[] = $order->getDeliveryAddressLine1();

                if ($order->getDeliveryAddressLine2()) {
                    $lines[] = $order->getDeliveryAddressLine2();
                }

                $lines[] = trim(sprintf('%s %s', $order->getDeliveryAddressPostalCode(), $order->getDeliveryAddressCommune()));
            }
        }

        if ($customer !== null) {
            $lines[] = '';
            $lines[] = 'Contact client :';
            $lines[] = sprintf('Téléphone : %s', $customer->getPhone());
            $lines[] = sprintf('E-mail : %s', $customer->getEmail());
        }

        $lines[] = '';
        $lines[] = 'Merci pour votre confiance,';
        $lines[] = 'L’équipe Hodina';

        return implode("\r\n", $lines);
    }

    private function encodeMailtoRecipient(string $recipient): string
    {
        return str_replace('%40', '@', rawurlencode($recipient));
    }

    private function formatMoney(string|float|int|null $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ');
    }
}
