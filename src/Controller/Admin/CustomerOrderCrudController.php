<?php

namespace App\Controller\Admin;

use App\Dto\CartLogisticsPreview;
use App\Entity\CustomerOrder;
use App\Entity\SmsLog;
use App\Service\CustomerOrderWorkflowService;
use App\Service\DeliveryLogisticsService;
use App\Service\OrderReferenceGenerator;
use App\Service\Sms\OrderSmsMessageBuilder;
use App\Service\Sms\SmsService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande client')
            ->setEntityLabelInPlural('Commandes clients')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $confirmOrder = Action::new('confirmOrder', 'Valider + SMS', 'fa fa-comment-check')
            ->linkToCrudAction('confirmOrder')
            ->displayIf(fn (CustomerOrder $order): bool => $order->getStatus() === CustomerOrder::STATUS_PENDING_VALIDATION);

        $prepareOrder = Action::new('prepareOrder', 'Préparer + SMS', 'fa fa-comment-dots')
            ->linkToCrudAction('prepareOrder')
            ->displayIf(fn (CustomerOrder $order): bool => $order->getStatus() === CustomerOrder::STATUS_CONFIRMED);

        $markReady = Action::new('markReady', 'Prête + SMS', 'fa fa-comment')
            ->linkToCrudAction('markReady')
            ->displayIf(fn (CustomerOrder $order): bool => $order->getStatus() === CustomerOrder::STATUS_PREPARING);

        $markDelivered = Action::new('markDelivered', 'Livrée + SMS', 'fa fa-comment-sms')
            ->linkToCrudAction('markDelivered')
            ->displayIf(fn (CustomerOrder $order): bool => in_array($order->getStatus(), [
                CustomerOrder::STATUS_READY_FOR_PICKUP,
                CustomerOrder::STATUS_PICKED_UP,
                CustomerOrder::STATUS_OUT_FOR_DELIVERY,
            ], true));

        $cancelOrder = Action::new('cancelOrder', 'Annuler + SMS', 'fa fa-comment-slash')
            ->linkToCrudAction('cancelOrder')
            ->displayIf(fn (CustomerOrder $order): bool => in_array($order->getStatus(), [
                CustomerOrder::STATUS_PENDING_VALIDATION,
                CustomerOrder::STATUS_CONFIRMED,
            ], true));

        $operationalSheet = Action::new('operationalSheet', 'Fiche terrain', 'fa fa-clipboard-list')
            ->linkToCrudAction('operationalSheet');

        $logisticsDetails = Action::new('logisticsDetails', 'Logistique', 'fa fa-route')
            ->linkToCrudAction('logisticsDetails');

        return $actions
            ->disable(Action::NEW) /*, Action::DELETE, Action::BATCH_DELETE)*/
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action): Action => $action->setLabel('Voir'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action): Action => $action->setLabel('Modifier'))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn (Action $action): Action => $action->setLabel('Modifier'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn (Action $action): Action => $action->setLabel('Retour aux commandes'))
            ->add(Crud::PAGE_INDEX, $operationalSheet)
            ->add(Crud::PAGE_INDEX, $logisticsDetails)
            ->add(Crud::PAGE_INDEX, $confirmOrder)
            ->add(Crud::PAGE_INDEX, $prepareOrder)
            ->add(Crud::PAGE_INDEX, $markReady)
            ->add(Crud::PAGE_INDEX, $markDelivered)
            ->add(Crud::PAGE_INDEX, $cancelOrder)
            ->add(Crud::PAGE_DETAIL, $operationalSheet)
            ->add(Crud::PAGE_DETAIL, $logisticsDetails)
            ->add(Crud::PAGE_DETAIL, $confirmOrder)
            ->add(Crud::PAGE_DETAIL, $prepareOrder)
            ->add(Crud::PAGE_DETAIL, $markReady)
            ->add(Crud::PAGE_DETAIL, $markDelivered)
            ->add(Crud::PAGE_DETAIL, $cancelOrder);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('orderReference', 'Numéro commande')
            ->hideOnForm();

        yield AssociationField::new('customer', 'Client')
            ->setFormTypeOption('disabled', true);

        yield TextareaField::new('deliveryAddressSummary', 'Adresse de livraison')
            ->setFormTypeOption('disabled', true)
            ->setHelp('Snapshot figé au moment de la commande. Ne dépend pas du carnet d’adresses client.');

        yield TextareaField::new('deliveryPointSummary', 'Point de remise')
            ->onlyOnDetail()
            ->formatValue(static fn (?string $value): string => $value ? nl2br(htmlspecialchars($value, ENT_QUOTES)) : 'Non renseigné');

        yield TextField::new('deliveryPointAppointmentSummary', 'Rendez-vous client')
            ->onlyOnDetail();

        yield TextField::new('deliveryPointTimeWindowSummary', 'Plage indicative point de remise')
            ->onlyOnDetail();

        yield TextareaField::new('deliveryPointCustomerInstructions', 'Précision client point de remise')
            ->onlyOnDetail()
            ->formatValue(static fn (?string $value): string => $value ? nl2br(htmlspecialchars($value, ENT_QUOTES)) : 'Non renseignée');

        yield TextareaField::new('billingAddressSummary', 'Adresse de facturation')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true)
            ->setHelp('Snapshot figé au moment de la commande. Ne dépend pas du carnet d’adresses client.');

        yield TextareaField::new('deliveryAddressInstructions', 'Instructions client')
            ->onlyOnDetail()
            ->formatValue(static fn (?string $value): string => $value ? nl2br(htmlspecialchars($value, ENT_QUOTES)) : 'Non renseignées');

        yield TextareaField::new('deliveryAddressCourierNotes', 'Commentaire livreur / terrain')
            ->onlyOnDetail()
            ->formatValue(static fn (?string $value): string => $value ? nl2br(htmlspecialchars($value, ENT_QUOTES)) : 'Non renseigné');

        yield TextField::new('deliveryGpsMapUrl', 'GPS livraison')
            ->onlyOnDetail()
            ->formatValue(static fn (?string $value): string => $value ? sprintf('<a href="%s" target="_blank" rel="noopener">Ouvrir dans Google Maps</a>', htmlspecialchars($value, ENT_QUOTES)) : 'Non renseigné');

        yield TextField::new('deliveryAddressZoneCode', 'Zone adresse')
            ->hideOnForm();

        yield AssociationField::new('deliveryZone', 'Zone de livraison')
            ->setFormTypeOption('disabled', true);

        yield AssociationField::new('assignedCourier', 'Livreur assigné')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('courierAssignedAt', 'Prise en charge le')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('outForDeliveryAt', 'Départ livraison le')
            ->hideOnForm()
            ->onlyOnDetail();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices(self::getStatusChoices())
            ->formatValue(static fn (?string $value, mixed $order = null): string => $order instanceof CustomerOrder
                ? self::formatStatusForOrder($order)
                : (self::getStatusLabels()[$value ?? ''] ?? (string) $value));

        yield ChoiceField::new('paymentStatus', 'Paiement')
            ->setChoices(array_flip(self::getPaymentLabels()));

        yield MoneyField::new('subtotal', 'Sous-total')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setFormTypeOption('disabled', true);

        yield MoneyField::new('deliveryFee', 'Frais livraison')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield MoneyField::new('total', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setFormTypeOption('disabled', true);

        yield TextareaField::new('itemsSummary', 'Lignes de commande')
            ->onlyOnDetail();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        yield DateTimeField::new('submittedAt', 'Soumis le')
            ->hideOnForm();

        yield DateTimeField::new('confirmedAt', 'Confirmée le')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('preparingAt', 'Préparation le')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('readyAt', 'Prête le')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('deliveredAt', 'Livrée le')
            ->hideOnForm()
            ->onlyOnDetail();

        yield DateTimeField::new('canceledAt', 'Annulée le')
            ->hideOnForm()
            ->onlyOnDetail();
    }

    public function operationalSheet(AdminContext $context, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $order = $context->getEntity()->getInstance();

        if (!$order instanceof CustomerOrder) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirectToOrderIndex($adminUrlGenerator);
        }

        return $this->render('admin/customer_order/operational_sheet.html.twig', [
            'order' => $order,
            'statusLabels' => self::getStatusLabels(),
            'paymentLabels' => self::getPaymentLabels(),
            'urls' => [
                'index' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::INDEX),
                'detail' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::DETAIL),
                'edit' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::EDIT),
                'confirm' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'confirmOrder'),
                'prepare' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'prepareOrder'),
                'ready' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'markReady'),
                'delivered' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'markDelivered'),
                'cancel' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'cancelOrder'),
            ],
        ]);
    }

    public function logisticsDetails(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        DeliveryLogisticsService $deliveryLogisticsService,
    ): Response {
        $order = $context->getEntity()->getInstance();

        if (!$order instanceof CustomerOrder) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirectToOrderIndex($adminUrlGenerator);
        }

        $dynamicPreview = $deliveryLogisticsService->previewForOrder($order);
        $snapshot = $order->getDeliveryLogisticsSnapshot();
        $snapshotPreview = null;

        if (is_array($snapshot) && is_array($snapshot['preview'] ?? null)) {
            $snapshotPreview = CartLogisticsPreview::fromArray($snapshot['preview']);
        }

        $preview = $snapshotPreview ?? $dynamicPreview;

        return $this->render('admin/customer_order/logistics_details.html.twig', [
            'order' => $order,
            'preview' => $preview,
            'dynamicPreview' => $dynamicPreview,
            'snapshot' => $snapshot,
            'hasSnapshot' => $snapshotPreview instanceof CartLogisticsPreview,
            'urls' => [
                'index' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::INDEX),
                'detail' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::DETAIL),
                'operationalSheet' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'operationalSheet'),
            ],
        ]);
    }

    public function sendSms(
        AdminContext $context,
        Request $request,
        AdminUrlGenerator $adminUrlGenerator,
        OrderReferenceGenerator $orderReferenceGenerator,
        OrderSmsMessageBuilder $messageBuilder,
        SmsService $smsService
    ): Response {
        $order = $context->getEntity()->getInstance();

        if (!$order instanceof CustomerOrder) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirectToOrderIndex($adminUrlGenerator);
        }

        $orderReference = $orderReferenceGenerator->ensureReference($order);
        $templates = $messageBuilder->getTemplates($order, $orderReference);
        $recipients = $messageBuilder->getRecipients($order);

        $selectedRecipientKey = (string) $request->request->get('recipient_key', 'customer');
        if (!array_key_exists($selectedRecipientKey, $recipients)) {
            $selectedRecipientKey = 'customer';
        }

        $selectedTemplateKey = (string) $request->request->get('template_key', 'customer_confirmed');
        if (!array_key_exists($selectedTemplateKey, $templates)) {
            $selectedTemplateKey = 'customer_confirmed';
        }

        $selectedRecipient = $recipients[$selectedRecipientKey] ?? reset($recipients);
        $phone = (string) $request->request->get('phone', $selectedRecipient['phone'] ?? '');
        $message = (string) $request->request->get('message', $templates[$selectedTemplateKey] ?? '');
        $recipientType = (string) ($selectedRecipient['recipientType'] ?? 'customer');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('send_sms_order_' . $order->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide.');
                return $this->redirectToOrderDetail($adminUrlGenerator, $order);
            }

            $smsLog = $smsService->sendForOrder(
                $order,
                $phone,
                $message,
                'customer_order_manual_' . $selectedTemplateKey,
                $recipientType
            );

            if ($smsLog->getStatus() === SmsLog::STATUS_SENT) {
                $this->addFlash('success', 'SMS envoyé en mode pilote et ajouté aux logs.');
            } else {
                $this->addFlash('danger', 'SMS non envoyé : ' . ($smsLog->getErrorMessage() ?? 'erreur inconnue.'));
            }

            return $this->redirectToOrderDetail($adminUrlGenerator, $order);
        }

        return $this->render('admin/customer_order/send_sms.html.twig', [
            'order' => $order,
            'orderReference' => $orderReference,
            'templates' => $templates,
            'recipients' => $recipients,
            'selectedRecipientKey' => $selectedRecipientKey,
            'selectedTemplateKey' => $selectedTemplateKey,
            'phone' => $phone,
            'message' => $message,
            'urls' => [
                'detail' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::DETAIL),
                'sheet' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'operationalSheet'),
            ],
        ]);
    }

    public function confirmOrder(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, CustomerOrderWorkflowService $workflow): Response
    {
        return $this->applyWorkflowAction(
            $context,
            $adminUrlGenerator,
            fn (CustomerOrder $order): SmsLog => $workflow->confirm($order),
            'Commande validée.'
        );
    }

    public function cancelOrder(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, CustomerOrderWorkflowService $workflow): Response
    {
        return $this->applyWorkflowAction(
            $context,
            $adminUrlGenerator,
            fn (CustomerOrder $order): SmsLog => $workflow->cancel($order),
            'Commande annulée.'
        );
    }

    public function prepareOrder(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, CustomerOrderWorkflowService $workflow): Response
    {
        return $this->applyWorkflowAction(
            $context,
            $adminUrlGenerator,
            fn (CustomerOrder $order): SmsLog => $workflow->markPreparing($order),
            'Commande passée en préparation.'
        );
    }

    public function markReady(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, CustomerOrderWorkflowService $workflow): Response
    {
        return $this->applyWorkflowAction(
            $context,
            $adminUrlGenerator,
            fn (CustomerOrder $order): SmsLog => $workflow->markReady($order),
            'Commande marquée comme prête.'
        );
    }

    public function markDelivered(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, CustomerOrderWorkflowService $workflow): Response
    {
        return $this->applyWorkflowAction(
            $context,
            $adminUrlGenerator,
            fn (CustomerOrder $order): SmsLog => $workflow->markDeliveredByAdmin($order),
            'Commande marquée comme livrée.'
        );
    }

    /**
     * @param callable(CustomerOrder): SmsLog $workflowAction
     */
    private function applyWorkflowAction(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        callable $workflowAction,
        string $successMessage
    ): Response {
        $order = $context->getEntity()->getInstance();

        if (!$order instanceof CustomerOrder) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirectToOrderIndex($adminUrlGenerator);
        }

        try {
            $smsLog = $workflowAction($order);
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());
            return $this->redirectToOrderDetail($adminUrlGenerator, $order);
        }

        $phone = $smsLog->getPhone();
        $message = $smsLog->getMessage();

        return $this->render('admin/customer_order/status_changed_sms.html.twig', [
            'order' => $order,
            'successMessage' => $successMessage,
            'smsLog' => $smsLog,
            'phone' => $phone,
            'message' => $message,
            'smsUrl' => $this->buildSmsUrl($phone, $message),
            'urls' => [
                'detail' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::DETAIL),
                'index' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, Action::INDEX),
                'sheet' => $this->buildOrderCrudUrl($adminUrlGenerator, $order, 'operationalSheet'),
            ],
        ]);
    }

    private function buildSmsUrl(string $phone, string $message): string
    {
        $normalizedPhone = preg_replace('/[^+0-9]/', '', trim($phone)) ?? '';
        $normalizedMessage = trim($message);

        if ($normalizedPhone === '' || $normalizedMessage === '') {
            return '';
        }

        return sprintf('sms:%s&body=%s', $normalizedPhone, rawurlencode($normalizedMessage));
    }

    private function buildOrderCrudUrl(AdminUrlGenerator $adminUrlGenerator, CustomerOrder $order, string $action): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction($action)
            ->setEntityId($order->getId())
            ->generateUrl();
    }

    private function redirectToOrderDetail(AdminUrlGenerator $adminUrlGenerator, CustomerOrder $order): RedirectResponse
    {
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    private function redirectToOrderIndex(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * @return array<string, string>
     */
    private static function getStatusChoices(): array
    {
        return array_flip(self::getStatusLabels());
    }

    /**
     * @return array<string, string>
     */
    private static function getStatusLabels(): array
    {
        return [
            CustomerOrder::STATUS_DRAFT => 'Brouillon',
            CustomerOrder::STATUS_PENDING_VALIDATION => 'En validation admin',
            CustomerOrder::STATUS_CONFIRMED => 'Confirmée',
            CustomerOrder::STATUS_PREPARING => 'En préparation',
            CustomerOrder::STATUS_READY_FOR_PICKUP => 'Prête pour livraison',
            CustomerOrder::STATUS_PICKED_UP => 'Prise en charge',
            CustomerOrder::STATUS_OUT_FOR_DELIVERY => 'En cours de livraison',
            CustomerOrder::STATUS_DELIVERED => 'Livrée',
            CustomerOrder::STATUS_CANCELED => 'Annulée',
        ];
    }

    private static function formatStatusForOrder(CustomerOrder $order): string
    {
        if ($order->getStatus() !== CustomerOrder::STATUS_PICKED_UP) {
            return self::getStatusLabels()[$order->getStatus()] ?? $order->getStatus();
        }

        $courier = $order->getAssignedCourier();
        if ($courier === null) {
            return 'Prise en charge';
        }

        $courierName = trim(($courier->getFirstName() ?? '') . ' ' . ($courier->getLastName() ?? ''));
        if ($courierName === '') {
            $courierName = $courier->getEmail() ?: 'livreur';
        }

        return sprintf('Prise en charge par %s', $courierName);
    }

    /**
     * @return array<string, string>
     */
    private static function getPaymentLabels(): array
    {
        return [
            CustomerOrder::PAY_PENDING => 'En attente',
            CustomerOrder::PAY_PAID => 'Payée',
            CustomerOrder::PAY_REFUNDED => 'Remboursée',
        ];
    }
}
