<?php

namespace App\Controller;

use App\Dto\CartLogisticsPreview;
use App\Entity\Address;
use App\Entity\AddressLocality;
use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\CustomerSignup;
use App\Entity\DeliveryCommune;
use App\Entity\DeliveryPoint;
use App\Entity\DeliveryPointTimeWindow;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\SmsLog;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\DeliveryCommuneMatcherService;
use App\Service\DeliveryFeeReasonFormatter;
use App\Service\DeliveryLogisticsService;
use App\Service\DeliveryPointCartService;
use App\Service\OrderReferenceGenerator;
use App\Service\OrderEmailService;
use App\Service\PhoneNumberNormalizer;
use App\Service\SalesOpeningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    private const DELIVERY_QUOTE_SESSION_KEY = 'checkout_delivery_quote';

    #[Route('/checkout', name: 'checkout_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CartService $cart,
        EntityManagerInterface $em,
        OrderEmailService $orderEmailService,
        UserPasswordHasherInterface $hasher,
        OrderReferenceGenerator $orderReferenceGenerator,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        DeliveryLogisticsService $deliveryLogistics,
        DeliveryPointCartService $deliveryPointCartService,
        DeliveryFeeReasonFormatter $deliveryFeeReasonFormatter,
        SalesOpeningService $salesOpening,
        PhoneNumberNormalizer $phoneNumberNormalizer,
    ): Response {
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('cart_index');
        }

        if ($salesOpening->isCartLocked()) {
            $this->addFlash('warning', $salesOpening->getCartLockedMessage());

            return $this->redirectToRoute('cart_index');
        }

        $this->applySubmittedCartQuantities($request, $cart);
        $cartData = $cart->getDetailedCart();

        if (empty($cartData['items'])) {
            return $this->redirectToRoute('cart_index');
        }

    /** @var Customer|null $connectedCustomer */
    $connectedCustomer = $this->getUser() instanceof Customer ? $this->getUser() : null;

    $deliveryCommunes = $deliveryCommuneMatcher->findActiveLogisticsCommunes();

    $checkoutDefaults = [
        'firstName' => '',
        'lastName' => '',
        'phoneCountryCode' => PhoneNumberNormalizer::DEFAULT_DIAL_CODE,
        'phone' => '',
        'email' => '',
        'existingAddressId' => '',
        'address' => '',
        'addressLocalityId' => '',
        'localityText' => '',
        'postalCode' => '',
        'commune' => '',
        'zone' => '',
        'deliveryInstructions' => '',
        'deliveryMethod' => DeliveryPointCartService::METHOD_STANDARD,
        'deliveryPointId' => '',
        'deliveryPointTimeWindowId' => '',
        'deliveryPointCustomerInstructions' => '',
        'gpsLatitude' => '',
        'gpsLongitude' => '',
        'gpsAccuracyMeters' => '',
        'customerTimezone' => '',
        'useBillingSameAsDelivery' => true,
        'makeDeliveryDefault' => false,
        'billingExistingAddressId' => '',
        'makeBillingDefault' => false,
        'billingAddress' => '',
        'billingPostalCode' => '',
        'billingCommune' => '',
        'billingZone' => DeliveryCommuneMatcherService::ZONE_AUTRE,
    ];

    if ($connectedCustomer) {
        $checkoutDefaults['firstName'] = $connectedCustomer->getFirstName() ?? '';
        $checkoutDefaults['lastName'] = $connectedCustomer->getLastName() ?? '';
        $phoneParts = $phoneNumberNormalizer->splitForForm($connectedCustomer->getPhone() ?? '');
        $checkoutDefaults['phoneCountryCode'] = $phoneParts['dialCode'];
        $checkoutDefaults['phone'] = $phoneParts['localNumber'];
        $checkoutDefaults['email'] = $connectedCustomer->getEmail() ?? '';

        $lastAddress = $this->findPreferredDeliveryAddress($connectedCustomer);

        if ($lastAddress) {
            $checkoutDefaults['existingAddressId'] = (string) $lastAddress->getId();
            $checkoutDefaults['address'] = $lastAddress->getLine1() ?? '';
            $checkoutDefaults['addressLocalityId'] = $lastAddress->getAddressLocality()?->getId() ? (string) $lastAddress->getAddressLocality()?->getId() : '';
            $checkoutDefaults['localityText'] = $lastAddress->getLocalityLabel() ?? '';

            $lastDeliveryCommune = $deliveryCommuneMatcher->resolveByCommuneName($lastAddress->getCommune() ?? '');
            if ($lastDeliveryCommune instanceof DeliveryCommune) {
                $checkoutDefaults['postalCode'] = (string) $lastDeliveryCommune->getPostalCode();
                $checkoutDefaults['commune'] = $lastDeliveryCommune->getName();
                $checkoutDefaults['zone'] = $lastDeliveryCommune->getTerritory();
            }

            $checkoutDefaults['deliveryInstructions'] = $lastAddress->getDeliveryInstructions() ?? '';
            $checkoutDefaults['gpsLatitude'] = $lastAddress->getGpsLatitude() ?? '';
            $checkoutDefaults['gpsLongitude'] = $lastAddress->getGpsLongitude() ?? '';
            $checkoutDefaults['gpsAccuracyMeters'] = $lastAddress->getGpsAccuracyMeters() ?? '';
        }

        $billingAddress = $this->findOrCreatePreferredBillingAddress($connectedCustomer, $em);

        if ($billingAddress instanceof Address) {
            $checkoutDefaults['useBillingSameAsDelivery'] = false;
            $checkoutDefaults['billingExistingAddressId'] = (string) $billingAddress->getId();
            $checkoutDefaults['billingAddress'] = $billingAddress->getLine1() ?? '';
            $checkoutDefaults['billingPostalCode'] = $billingAddress->getPostalCode() ?? '';
            $checkoutDefaults['billingCommune'] = $billingAddress->getCommune() ?? '';
            if ($billingAddress->getDeliveryZone()) {
                $checkoutDefaults['billingZone'] = $billingAddress->getDeliveryZone()->getCode();
            }
        }
    }

    $deliveryPointAnalysis = $deliveryPointCartService->analyzeCart($cartData);
    $deliveryPointChoices = $deliveryPointCartService->buildPointChoices($deliveryPointAnalysis);
    $checkoutDefaults['deliveryMethod'] = (string) ($deliveryPointAnalysis['defaultMethod'] ?? DeliveryPointCartService::METHOD_STANDARD);
    if (($deliveryPointAnalysis['requiresDeliveryPoint'] ?? false) && isset($deliveryPointChoices[0])) {
        $checkoutDefaults['deliveryPointId'] = (string) $deliveryPointChoices[0]['id'];
        $checkoutDefaults['address'] = (string) ($deliveryPointChoices[0]['line1'] ?? '');
        $checkoutDefaults['postalCode'] = (string) ($deliveryPointChoices[0]['postalCode'] ?? '');
        $checkoutDefaults['commune'] = (string) ($deliveryPointChoices[0]['commune'] ?? '');
        $checkoutDefaults['zone'] = (string) ($deliveryPointChoices[0]['zone'] ?? '');
        if (!empty($deliveryPointChoices[0]['timeWindows'][0]['id'])) {
            $checkoutDefaults['deliveryPointTimeWindowId'] = (string) $deliveryPointChoices[0]['timeWindows'][0]['id'];
        }
    }

    $form = $this->createForm(CheckoutType::class, $checkoutDefaults, [
        'delivery_communes' => $deliveryCommunes,
    ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['phone'] = $phoneNumberNormalizer->normalizeWithDialCode(
                (string) $form->get('phoneCountryCode')->getData(),
                (string) ($data['phone'] ?? '')
            );

            $deliveryPointAnalysis = $deliveryPointCartService->analyzeCart($cartData);
            $deliveryMethod = trim((string) $form->get('deliveryMethod')->getData());
            if (($deliveryPointAnalysis['requiresDeliveryPoint'] ?? false) === true) {
                $deliveryMethod = DeliveryPointCartService::METHOD_DELIVERY_POINT;
            }
            if ($deliveryMethod === '') {
                $deliveryMethod = DeliveryPointCartService::METHOD_STANDARD;
            }

            if ($deliveryMethod === DeliveryPointCartService::METHOD_STANDARD && !($deliveryPointAnalysis['allowsStandardDelivery'] ?? true)) {
                $form->get('deliveryMethod')->addError(new FormError('Ce panier contient un produit qui impose un point de remise. Choisis un point de remise pour continuer.'));

                return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
            }

            $usesDeliveryPoint = $deliveryMethod === DeliveryPointCartService::METHOD_DELIVERY_POINT;
            $selectedDeliveryPoint = null;
            $selectedTimeWindow = null;
            $deliveryPointInstructions = null;
            $deliveryPointTimeWindowLabel = null;
            $deliveryPointScheduledDate = null;
            $deliveryPointScheduledTime = null;
            $deliveryAddressLocality = null;
            $deliveryLocalityText = null;

            if ($usesDeliveryPoint) {
                if (!empty($deliveryPointAnalysis['conflictMessage'])) {
                    $form->get('deliveryPointId')->addError(new FormError((string) $deliveryPointAnalysis['conflictMessage']));

                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $selectedDeliveryPointId = (int) $form->get('deliveryPointId')->getData();
                $selectedDeliveryPoint = $selectedDeliveryPointId > 0 ? $em->getRepository(DeliveryPoint::class)->find($selectedDeliveryPointId) : null;

                if (!$selectedDeliveryPoint instanceof DeliveryPoint || !$deliveryPointCartService->isDeliveryPointAllowed($deliveryPointAnalysis, $selectedDeliveryPoint)) {
                    $form->get('deliveryPointId')->addError(new FormError('Choisis un point de remise disponible pour ce panier.'));

                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $deliveryPointScheduledDate = $this->parseDeliveryPointRequestedDate($form);
                if (!$deliveryPointScheduledDate instanceof \DateTimeImmutable) {
                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $deliveryPointScheduledTime = $this->parseDeliveryPointRequestedTime($form);
                if (!$deliveryPointScheduledTime instanceof \DateTimeImmutable) {
                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $selectedTimeWindowId = (int) $form->get('deliveryPointTimeWindowId')->getData();
                $selectedTimeWindow = $deliveryPointCartService->findMatchingTimeWindow(
                    $selectedDeliveryPoint,
                    $deliveryPointScheduledDate,
                    $deliveryPointScheduledTime,
                    $selectedTimeWindowId > 0 ? $selectedTimeWindowId : null
                );

                if (!$selectedTimeWindow instanceof DeliveryPointTimeWindow) {
                    $form->get('deliveryPointRequestedTime')->addError(new FormError('Choisis un créneau disponible parmi ceux proposés pour ce point de remise.'));

                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $leadTimeViolation = $deliveryPointCartService->validateMinimumOrderLeadTime(
                    $cartData,
                    $deliveryPointScheduledDate,
                    $deliveryPointScheduledTime
                );

                if ($leadTimeViolation !== null) {
                    $earliestAppointment = $leadTimeViolation['earliestAppointment'];
                    $minimumHours = (int) $leadTimeViolation['minimumHours'];

                    $form->get('deliveryPointRequestedDate')->addError(new FormError(sprintf(
                        'Ce panier contient un produit qui demande au moins %d h de délai. Choisis un rendez-vous à partir du %s à %s.',
                        $minimumHours,
                        $earliestAppointment->format('d/m/Y'),
                        $earliestAppointment->format('H\hi')
                    )));

                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $deliveryPointInstructions = $this->normalizeDeliveryInstructions($form->get('deliveryPointCustomerInstructions')->getData());
                $deliveryPointTimeWindowLabel = $deliveryPointCartService->formatTimeWindowLabel($selectedTimeWindow);
                $deliveryCommune = $selectedDeliveryPoint->getDeliveryCommune();
                $deliveryZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($deliveryCommune);

                if (!$deliveryZone) {
                    $form->get('deliveryPointId')->addError(new FormError(sprintf('Zone de livraison %s inactive ou absente pour ce point de remise.', $this->formatZoneLabel($deliveryCommune->getTerritory()))));

                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $deliveryPostalCode = trim((string) ($selectedDeliveryPoint->getPostalCode() ?: $deliveryCommune->getPostalCode()));
                $deliveryLine1 = $selectedDeliveryPoint->getLine1();
                $deliveryLine2 = $selectedDeliveryPoint->getLine2();
                $deliveryCommuneName = $selectedDeliveryPoint->getCommuneName() ?: $deliveryCommune->getName();
                $deliveryInstructions = $deliveryPointInstructions;
                $deliveryAddressLocality = null;
                $deliveryLocalityText = null;
                $gpsLatitude = $this->normalizeGpsLatitude($selectedDeliveryPoint->getGpsLatitude());
                $gpsLongitude = $this->normalizeGpsLongitude($selectedDeliveryPoint->getGpsLongitude());
                $gpsAccuracyMeters = $selectedDeliveryPoint->getGpsAccuracyMeters() !== null ? (string) $selectedDeliveryPoint->getGpsAccuracyMeters() : null;
            } else {
                $deliveryCommune = $this->validateDeliveryAddressData(
                    $form,
                    $deliveryCommuneMatcher,
                    (string) ($data['commune'] ?? ''),
                    (string) ($data['postalCode'] ?? '')
                );

                if (!$deliveryCommune) {
                    return $this->renderCheckout(
                        $cartData,
                        $form,
                        $connectedCustomer,
                        $em
                    );
                }

                $deliveryZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($deliveryCommune);

                if (!$deliveryZone) {
                    $form->get('commune')->addError(new FormError(sprintf('Zone de livraison %s inactive ou absente.', $this->formatZoneLabel($deliveryCommune->getTerritory()))));

                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $deliveryPostalCode = trim((string) $deliveryCommune->getPostalCode());
                $deliveryLine1 = trim((string) ($data['address'] ?? ''));
                $deliveryLine2 = null;
                $deliveryCommuneName = $deliveryCommune->getName();
                [$deliveryAddressLocality, $deliveryLocalityText] = $this->resolveSubmittedAddressLocality(
                    $em,
                    $form,
                    $deliveryCommune,
                    (string) $form->get('addressLocalityId')->getData(),
                    (string) $form->get('localityText')->getData(),
                    $deliveryPostalCode
                );

                if (!$form->isValid()) {
                    return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
                }

                $deliveryInstructions = $this->normalizeDeliveryInstructions($form->get('deliveryInstructions')->getData());
                $gpsLatitude = $this->normalizeGpsLatitude($form->get('gpsLatitude')->getData());
                $gpsLongitude = $this->normalizeGpsLongitude($form->get('gpsLongitude')->getData());
                $gpsAccuracyMeters = $this->normalizeGpsAccuracyMeters($form->get('gpsAccuracyMeters')->getData());
            }

            $customerTimezone = $this->normalizeCustomerTimezone($form->get('customerTimezone')->getData());

            // Les champs de facturation sont unmapped dans CheckoutType.
            // Il ne faut donc pas les lire depuis $form->getData(), sinon Symfony garde
            // les valeurs initiales et peut écraser la zone de facturation avec la zone de livraison.
            $useBillingSameAsDelivery = (bool) $form->get('useBillingSameAsDelivery')->getData();
            $submittedBillingZoneCode = trim((string) ($form->get('billingZone')->getData() ?? DeliveryCommuneMatcherService::ZONE_AUTRE));

            $billingLine1 = trim((string) ($useBillingSameAsDelivery ? $deliveryLine1 : ($form->get('billingAddress')->getData() ?? '')));
            $billingPostalCode = trim((string) ($useBillingSameAsDelivery ? $deliveryPostalCode : ($form->get('billingPostalCode')->getData() ?? '')));
            $billingCommune = trim((string) ($useBillingSameAsDelivery ? $deliveryCommuneName : ($form->get('billingCommune')->getData() ?? '')));
            $billingZoneCode = trim((string) ($useBillingSameAsDelivery ? $deliveryCommune->getTerritory() : $submittedBillingZoneCode));

            $billingZone = $useBillingSameAsDelivery
                ? $deliveryZone
                : $this->validateBillingAddressData(
                    $form,
                    $deliveryCommuneMatcher,
                    $billingLine1,
                    $billingPostalCode,
                    $billingCommune,
                    $billingZoneCode
                );

            if (!$billingZone) {
                return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
            }

            $quoteAddress = (new Address())
                ->setType(Address::TYPE_DELIVERY)
                ->setLine1($deliveryLine1)
                ->setLine2($deliveryLine2)
                ->setPostalCode($deliveryPostalCode)
                ->setCommune($deliveryCommuneName)
                ->setAddressLocality($deliveryAddressLocality)
                ->setLocalityText($deliveryLocalityText)
                ->setDeliveryZone($deliveryZone)
                ->setDeliveryInstructions($deliveryInstructions)
                ->setGpsLatitude($gpsLatitude)
                ->setGpsLongitude($gpsLongitude)
                ->setGpsAccuracyMeters($gpsAccuracyMeters);

            $finalLogisticsPreview = $deliveryLogistics->previewForCart($quoteAddress, $cartData);
            $deliveryFeeFloat = $finalLogisticsPreview->estimatedDeliveryFee ?? 0.0;
            $deliveryFee = number_format($deliveryFeeFloat, 2, '.', '');
            $deliveryFeeReason = $deliveryFeeReasonFormatter->formatReasons($deliveryFeeReasonFormatter->reasonsFromPreviewArray($finalLogisticsPreview->toArray()));
            $subtotal = number_format((float) $cartData['total'], 2, '.', '');
            $total = number_format(((float) $subtotal) + $deliveryFeeFloat, 2, '.', '');

            $quoteCheck = $this->checkDeliveryQuoteStillMatches($request, $quoteAddress, $cartData, $deliveryFee, $total);

            if ($quoteCheck !== null) {
                $request->getSession()->remove(self::DELIVERY_QUOTE_SESSION_KEY);
                $this->addFlash('warning', $quoteCheck);

                return $this->redirectToRoute('cart_index');
            }

            $customer = $connectedCustomer;
            $attachedToExistingAccount = false;
            $tempPassword = $this->generateTempPassword();

            if (!$customer) {
                $submittedEmail = $this->normalizeEmail((string) ($data['email'] ?? ''));
                $existingCustomer = $submittedEmail !== '' ? $this->findCustomerByEmail($em, $submittedEmail) : null;

                if ($existingCustomer instanceof Customer) {
                    $confirmedExistingAccount = trim((string) $form->get('confirmExistingAccount')->getData()) === '1';
                    $confirmedExistingAccountEmail = $this->normalizeEmail((string) $form->get('confirmedExistingAccountEmail')->getData());

                    if (!$confirmedExistingAccount || $confirmedExistingAccountEmail !== $submittedEmail) {
                        return $this->renderCheckout(
                            $cartData,
                            $form,
                            $connectedCustomer,
                            $em,
                            true,
                            $submittedEmail
                        );
                    }

                    $customer = $existingCustomer;
                    $attachedToExistingAccount = true;
                } else {
                    $customer = new Customer();

                    if (method_exists($customer, 'setEmail')) {
                        $customer->setEmail($submittedEmail !== '' ? $submittedEmail : $data['email']);
                    }

                    if (method_exists($customer, 'setFirstName')) {
                        $customer->setFirstName($data['firstName']);
                    }

                    if (method_exists($customer, 'setLastName')) {
                        $customer->setLastName($data['lastName']);
                    }

                    if (method_exists($customer, 'setPhone')) {
                        $customer->setPhone($data['phone']);
                    }

                    if (method_exists($customer, 'setRoles')) {
                        $customer->setRoles(['ROLE_CUSTOMER']);
                    }

                    if (method_exists($customer, 'setPassword')) {
                        $customer->setPassword($hasher->hashPassword($customer, $tempPassword));
                    }

                    if (method_exists($customer, 'setResetPasswordToken') && method_exists($customer, 'setResetPasswordTokenExpiresAt')) {
                        $customer
                            ->setResetPasswordToken(bin2hex(random_bytes(32)))
                            ->setResetPasswordTokenExpiresAt(new \DateTimeImmutable('+7 days'));
                    }

                    $em->persist($customer);
                }
            }

            $signup = (new CustomerSignup())
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setEmail($data['email'])
                ->setPhone($data['phone'])
                ->setAddress($deliveryLine1)
                ->setZone($deliveryCommune->getTerritory())
                ->setCartSnapshot($this->makeCartSnapshot($cartData));

            $em->persist($signup);

            $existingAddressId = $form->get('existingAddressId')->getData();
            $makeDeliveryDefault = (bool) $form->get('makeDeliveryDefault')->getData();

            $address = null;

            if (!$usesDeliveryPoint && $existingAddressId && $customer) {
                $address = $em->getRepository(Address::class)->findOneBy([
                    'id' => $existingAddressId,
                    'customer' => $customer,
                    'type' => Address::TYPE_DELIVERY,
                ]);
            }

            if ($address instanceof Address) {
                $address
                    ->setLine1($deliveryLine1)
                    ->setPostalCode($deliveryPostalCode)
                    ->setCommune($deliveryCommuneName)
                    ->setAddressLocality($deliveryAddressLocality)
                    ->setLocalityText($deliveryLocalityText)
                    ->setDeliveryZone($deliveryZone)
                    ->setDeliveryInstructions($deliveryInstructions)
                    ->setGpsLatitude($gpsLatitude)
                    ->setGpsLongitude($gpsLongitude)
                    ->setGpsAccuracyMeters($gpsAccuracyMeters);
            }

            if (!$usesDeliveryPoint && !$address) {
                $address = $this->findReusableDeliveryAddress(
                    $customer,
                    $deliveryLine1,
                    $deliveryLine2,
                    $deliveryPostalCode,
                    $deliveryCommuneName,
                    $deliveryZone,
                    $deliveryAddressLocality,
                    $deliveryLocalityText,
                    $deliveryInstructions,
                    $gpsLatitude,
                    $gpsLongitude,
                    $gpsAccuracyMeters
                );
            }

            if (!$usesDeliveryPoint && !$address) {
                $address = (new Address())
                    ->setCustomer($customer)
                    ->setType(Address::TYPE_DELIVERY)
                    ->setLabel('Livraison checkout')
                    ->setLine1($deliveryLine1)
                    ->setPostalCode($deliveryPostalCode)
                    ->setCommune($deliveryCommuneName)
                    ->setAddressLocality($deliveryAddressLocality)
                    ->setLocalityText($deliveryLocalityText)
                    ->setDeliveryZone($deliveryZone)
                    ->setDeliveryInstructions($deliveryInstructions)
                    ->setGpsLatitude($gpsLatitude)
                    ->setGpsLongitude($gpsLongitude)
                    ->setGpsAccuracyMeters($gpsAccuracyMeters);

                $em->persist($address);
            }

            if ($address instanceof Address) {
                $this->applyDeliveryMetadataToAddress($address, $deliveryAddressLocality, $deliveryLocalityText, $deliveryInstructions, $gpsLatitude, $gpsLongitude, $gpsAccuracyMeters);
            }

            $billingExistingAddressId = $form->get('billingExistingAddressId')->getData();
            $makeBillingDefault = (bool) $form->get('makeBillingDefault')->getData();

            $billingAddress = null;

            if ($billingExistingAddressId && $customer) {
                $billingAddress = $em->getRepository(Address::class)->findOneBy([
                    'id' => $billingExistingAddressId,
                    'customer' => $customer,
                    'type' => Address::TYPE_BILLING,
                ]);
            }

            if ($billingAddress instanceof Address) {
                $billingAddress
                    ->setLine1($billingLine1)
                    ->setPostalCode($billingPostalCode)
                    ->setCommune($billingCommune)
                    ->setDeliveryZone($billingZone);
            }

            if (!$billingAddress) {
                $billingAddress = $this->findReusableAddress(
                    $em,
                    $customer,
                    Address::TYPE_BILLING,
                    $billingLine1,
                    null,
                    $billingPostalCode,
                    $billingCommune,
                    $billingZone
                );
            }

            if (!$billingAddress) {
                $billingAddress = (new Address())
                    ->setCustomer($customer)
                    ->setType(Address::TYPE_BILLING)
                    ->setLabel('Facturation checkout')
                    ->setLine1($billingLine1)
                    ->setPostalCode($billingPostalCode)
                    ->setCommune($billingCommune)
                    ->setDeliveryZone($billingZone);

                $customer->addAddress($billingAddress);
                $em->persist($billingAddress);
            }

            if (!$usesDeliveryPoint && $address instanceof Address && ($makeDeliveryDefault || !$customer->getDeliveryAddress())) {
                $customer->setDeliveryAddress($address);
            }

            if ($makeBillingDefault || !$customer->getBillingAddress() || ($connectedCustomer === null && !$attachedToExistingAccount)) {
                $customer->setBillingAddress($billingAddress);
            }

            $order = (new CustomerOrder())
                ->setCustomer($customer)
                ->setDeliveryAddress($usesDeliveryPoint ? null : $address)
                ->snapshotDeliveryAddress($quoteAddress)
                ->snapshotDeliveryPoint(
                    $selectedDeliveryPoint,
                    $selectedTimeWindow,
                    $deliveryPointInstructions,
                    $deliveryPointTimeWindowLabel,
                    $deliveryPointScheduledDate,
                    $deliveryPointScheduledTime
                )
                ->snapshotBillingAddress($billingAddress)
                ->setStatus(CustomerOrder::STATUS_PENDING_VALIDATION)
                ->setPaymentStatus(CustomerOrder::PAY_PENDING)
                ->setSubtotal($subtotal)
                ->setDeliveryFee($deliveryFee)
                ->setTotal($total)
                ->setCustomerTimezone($customerTimezone)
                ->setDeliveryZone($deliveryZone)
                ->setDeliveryLogisticsSnapshot($this->buildDeliveryLogisticsSnapshot($finalLogisticsPreview, $cartData, $deliveryFee, $total))
                ->setSubmittedAt(new \DateTimeImmutable());

            $em->persist($order);
            $orderReference = $orderReferenceGenerator->ensureReference($order);

            foreach ($cartData['items'] as $cartItem) {
                $product = $cartItem['product'];

                $orderItem = (new OrderItem())
                    ->setCustomerOrder($order)
                    ->setProduct($product)
                    ->setSeller($product->getSeller())
                    ->setQuantity((int) $cartItem['qty'])
                    ->setProducerUnitPrice(number_format((float) $cartItem['producerUnitPrice'], 2, '.', ''))
                    ->setAppliedMarginRate(number_format((float) $cartItem['appliedMarginRate'], 2, '.', ''))
                    ->setHodinaMarginAmount(number_format((float) $cartItem['hodinaMarginAmount'], 2, '.', ''))
                    ->setUnitPrice(number_format((float) $cartItem['unitPrice'], 2, '.', ''));

                $em->persist($orderItem);
            }

            $smsDeliveryFeeDetails = sprintf('Frais livraison : %s €.', number_format($deliveryFeeFloat, 2, ',', ' '));
            if ($deliveryFeeReason !== null) {
                $smsDeliveryFeeDetails = sprintf(
                    'Frais livraison : %s € (%s).',
                    number_format($deliveryFeeFloat, 2, ',', ' '),
                    rtrim($deliveryFeeReason, '.')
                );
            }

            $sms = (new SmsLog())
                ->setPhone($data['phone'])
                ->setContext('order_pending_validation')
                ->setMessage(sprintf(
                    "Gégé %s, Hodina – Ta commande %s est enregistrée. Un admin vérifie la disponibilité des produits avant validation. Total : %s €. %s",
                    $data['firstName'] ?: 'client',
                    $orderReference,
                    $total,
                    $smsDeliveryFeeDetails
                ));

            $em->persist($sms);

            $em->flush();

            $orderId = $order->getId();

            try {
                $orderEmailService->sendOrderCreatedToCustomer($order, $attachedToExistingAccount);
            } catch (\Throwable) {
                // J5H-A : l'e-mail récapitulatif ne doit jamais bloquer la commande.
                // La commande est déjà créée et enregistrée à ce stade.
            }

            $cart->clear();

            return $this->redirectToRoute('order_confirmation', [
                'id' => $orderId,
            ]);
        }

        return $this->renderCheckout($cartData, $form, $connectedCustomer, $em);
    }



    private function applySubmittedCartQuantities(Request $request, CartService $cart): void
    {
        $quantities = $request->request->all('qty');

        if (!is_array($quantities)) {
            return;
        }

        foreach ($quantities as $productId => $qty) {
            $cart->setQty((int) $productId, (int) $qty);
        }
    }

    private function parseDeliveryPointRequestedDate(FormInterface $form): ?\DateTimeImmutable
    {
        $value = $form->get('deliveryPointRequestedDate')->getData();

        if ($value instanceof \DateTimeInterface) {
            $date = \DateTimeImmutable::createFromInterface($value);
        } else {
            $raw = trim((string) $value);
            $date = $raw !== '' ? \DateTimeImmutable::createFromFormat('!Y-m-d', $raw) : false;
        }

        if (!$date instanceof \DateTimeImmutable) {
            $form->get('deliveryPointRequestedDate')->addError(new FormError('Indique la date à laquelle tu seras au point de remise.'));

            return null;
        }

        $today = new \DateTimeImmutable('today', new \DateTimeZone('Indian/Mayotte'));
        $dateInMayotte = $date->setTimezone(new \DateTimeZone('Indian/Mayotte'));

        if ($dateInMayotte < $today) {
            $form->get('deliveryPointRequestedDate')->addError(new FormError('La date de rendez-vous ne peut pas être passée.'));

            return null;
        }

        return $dateInMayotte;
    }

    private function parseDeliveryPointRequestedTime(FormInterface $form): ?\DateTimeImmutable
    {
        $value = $form->get('deliveryPointRequestedTime')->getData();

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            $form->get('deliveryPointRequestedTime')->addError(new FormError('Choisis un créneau de remise.'));

            return null;
        }

        $time = \DateTimeImmutable::createFromFormat('!H:i', $raw);
        if (!$time instanceof \DateTimeImmutable) {
            $time = \DateTimeImmutable::createFromFormat('!H:i:s', $raw);
        }

        if (!$time instanceof \DateTimeImmutable) {
            $form->get('deliveryPointRequestedTime')->addError(new FormError('Choisis un créneau valide parmi ceux proposés.'));

            return null;
        }

        return $time;
    }

    /**
     * @param array<string, mixed> $cartData
     */
    private function renderCheckout(
        array $cartData,
        FormInterface $form,
        ?Customer $connectedCustomer,
        EntityManagerInterface $em,
        bool $showExistingAccountConfirmation = false,
        ?string $existingAccountEmail = null
    ): Response {
        $customerDeliveryAddresses = [];
        $customerBillingAddresses = [];
        $defaultDeliveryAddress = null;
        $defaultBillingAddress = null;

        if ($connectedCustomer) {
            foreach ($connectedCustomer->getAddresses() as $customerAddress) {
                if (!$customerAddress instanceof Address) {
                    continue;
                }

                if ($customerAddress->isDelivery()) {
                    $customerDeliveryAddresses[] = $customerAddress;
                    continue;
                }

                if ($customerAddress->isBilling()) {
                    $customerBillingAddresses[] = $customerAddress;
                }
            }

            $customerDeliveryAddresses = $this->sortDeliveryAddressesForCheckout($customerDeliveryAddresses);

            usort($customerBillingAddresses, function (Address $left, Address $right) use ($connectedCustomer): int {
                $leftDefault = $connectedCustomer->getBillingAddress() === $left ? 1 : 0;
                $rightDefault = $connectedCustomer->getBillingAddress() === $right ? 1 : 0;

                if ($leftDefault !== $rightDefault) {
                    return $rightDefault <=> $leftDefault;
                }

                return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
            });

            $customerDefaultDeliveryAddress = $connectedCustomer->getDeliveryAddress();
            if ($customerDefaultDeliveryAddress instanceof Address && $customerDefaultDeliveryAddress->isDelivery()) {
                $defaultDeliveryAddress = $customerDefaultDeliveryAddress;
            } else {
                $defaultDeliveryAddress = $customerDeliveryAddresses[0] ?? null;
            }

            $defaultBillingAddress = $this->findOrCreatePreferredBillingAddress($connectedCustomer, $em);
        }

        $deliveryPointCartService = new DeliveryPointCartService();
        $deliveryPointOptions = $deliveryPointCartService->analyzeCart($cartData);
        $addressLocalities = $em->getRepository(AddressLocality::class)->findActiveForCheckout();

        return $this->render('cart/index.html.twig', [
            'cart' => $cartData,
            'form' => $form->createView(),
            'customerAddresses' => $customerDeliveryAddresses,
            'defaultDeliveryAddress' => $defaultDeliveryAddress,
            'billingAddresses' => $customerBillingAddresses,
            'billingAddress' => $defaultBillingAddress,
            'logisticsPreview' => null,
            'deliveryPointOptions' => $deliveryPointOptions,
            'deliveryPointChoices' => $deliveryPointCartService->buildPointChoices($deliveryPointOptions),
            'addressLocalities' => $addressLocalities,
            'showExistingAccountConfirmation' => $showExistingAccountConfirmation,
            'existingAccountEmail' => $existingAccountEmail,
        ]);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function findCustomerByEmail(EntityManagerInterface $em, string $email): ?Customer
    {
        if ($email === '') {
            return null;
        }

        return $em->getRepository(Customer::class)
            ->createQueryBuilder('customer')
            ->andWhere('LOWER(customer.email) = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * Snapshot métier figé au moment de la validation panier.
     *
     * Il sert à relire plus tard le calcul exact appliqué, même si les settings
     * Hodina, les liaisons inter-communes ou les vendeurs changent après coup.
     *
     * @param array<string, mixed> $cartData
     * @return array<string, mixed>
     */
    private function buildDeliveryLogisticsSnapshot(
        CartLogisticsPreview $preview,
        array $cartData,
        string $deliveryFee,
        string $total
    ): array {
        $items = [];

        foreach (($cartData['items'] ?? []) as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            $product = $item['product'];
            $seller = $product->getSeller();
            $sellerCommune = $seller?->getDeliveryCommune();

            $items[] = [
                'productId' => $product->getId(),
                'productName' => $product->getName(),
                'sellerId' => $seller?->getId(),
                'sellerName' => $seller?->getPublicDisplayName(),
                'sellerCommune' => $sellerCommune?->getName(),
                'sellerTerritory' => $sellerCommune?->getTerritory(),
                'quantity' => (int) ($item['qty'] ?? 0),
                'unitPrice' => number_format((float) ($item['unitPrice'] ?? 0), 2, '.', ''),
                'lineTotal' => number_format((float) ($item['lineTotal'] ?? 0), 2, '.', ''),
            ];
        }

        return [
            'schemaVersion' => 'J5G-B4-LOGISTICS-SNAPSHOT-v1',
            'capturedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'source' => 'checkout_validation',
            'cartSubtotal' => number_format((float) ($cartData['total'] ?? 0), 2, '.', ''),
            'storedDeliveryFee' => $deliveryFee,
            'storedTotal' => $total,
            'preview' => $preview->toArray(),
            'deliveryPoint' => null,
            'items' => $items,
        ];
    }

    /** @param array<string, mixed> $cartData */
    private function checkDeliveryQuoteStillMatches(
        Request $request,
        Address $deliveryAddress,
        array $cartData,
        string $deliveryFee,
        string $total
    ): ?string {
        $quote = $request->getSession()->get(self::DELIVERY_QUOTE_SESSION_KEY);

        if (!is_array($quote)) {
            return 'Les frais de livraison n’ont pas pu être confirmés. Reviens au panier pour vérifier le total avant de valider la commande.';
        }

        $expectedSignature = $this->buildDeliveryQuoteSignature($deliveryAddress, $cartData);
        $quotedSignature = (string) ($quote['signature'] ?? '');
        $quotedDeliveryFee = (string) ($quote['deliveryFee'] ?? '');
        $quotedTotal = (string) ($quote['totalWithDelivery'] ?? '');

        if ($quotedSignature !== $expectedSignature) {
            return 'Le panier ou l’adresse de livraison a changé depuis le dernier calcul des frais. Vérifie le nouveau total avant de valider la commande.';
        }

        if ($quotedDeliveryFee !== $deliveryFee || $quotedTotal !== $total) {
            return sprintf(
                'Les frais de livraison ont changé depuis l’affichage du panier : ancien total %s €, nouveau total %s €. Vérifie le nouveau montant avant de valider.',
                $this->formatMoneyForFlash((float) $quotedTotal),
                $this->formatMoneyForFlash((float) $total)
            );
        }

        return null;
    }

    /** @param array<string, mixed> $detailedCart */
    private function buildDeliveryQuoteSignature(Address $deliveryAddress, array $detailedCart): string
    {
        $items = [];

        foreach (($detailedCart['items'] ?? []) as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            $product = $item['product'];
            $items[] = sprintf(
                '%s:%s:%s:%s',
                $product->getId() ?? 'unknown',
                (int) ($item['qty'] ?? 0),
                number_format((float) ($item['unitPrice'] ?? 0), 2, '.', ''),
                number_format((float) ($item['lineTotal'] ?? 0), 2, '.', '')
            );
        }

        sort($items, SORT_STRING);

        return hash('sha256', implode('|', [
            mb_strtolower(trim((string) $deliveryAddress->getCommune())),
            trim((string) $deliveryAddress->getPostalCode()),
            $deliveryAddress->getDeliveryZone()?->getCode() ?? '',
            number_format((float) ($detailedCart['total'] ?? 0), 2, '.', ''),
            implode(',', $items),
        ]));
    }

    private function formatMoneyForFlash(float $amount): string
    {
        return number_format($amount, 2, ',', ' ');
    }


    private function applyDeliveryMetadataToAddress(
        Address $address,
        ?AddressLocality $addressLocality,
        ?string $localityText,
        ?string $deliveryInstructions,
        ?string $latitude,
        ?string $longitude,
        ?string $accuracyMeters
    ): void {
        $address
            ->setAddressLocality($addressLocality)
            ->setLocalityText($localityText)
            ->setDeliveryInstructions($deliveryInstructions);

        $this->applyGpsCoordinatesToAddress($address, $latitude, $longitude, $accuracyMeters);
    }

    /**
     * Choisit l'adresse de livraison la plus utile par defaut pour le panier.
     *
     * Le projet ne possede pas encore de champ `is_default`. Pour eviter qu'une
     * adresse vide recente remplace une adresse terrain enrichie, le choix
     * privilegie d'abord les adresses avec GPS et instructions, puis l'id le plus
     * recent.
     */
    private function findPreferredDeliveryAddress(Customer $customer): ?Address
    {
        $addresses = [];

        foreach ($customer->getAddresses() as $address) {
            if ($address instanceof Address && $address->isDelivery()) {
                $addresses[] = $address;
            }
        }

        $deliveryAddress = $customer->getDeliveryAddress();

        if ($deliveryAddress instanceof Address && $deliveryAddress->isDelivery()) {
            return $deliveryAddress;
        }

        $addresses = $this->sortDeliveryAddressesForCheckout($addresses);

        return $addresses[0] ?? null;
    }

    /**
     * @param list<Address> $addresses
     * @return list<Address>
     */
    private function sortDeliveryAddressesForCheckout(array $addresses): array
    {
        usort($addresses, function (Address $left, Address $right): int {
            $leftScore = $this->getDeliveryAddressPreferenceScore($left);
            $rightScore = $this->getDeliveryAddressPreferenceScore($right);

            if ($leftScore === $rightScore) {
                return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
            }

            return $rightScore <=> $leftScore;
        });

        return $addresses;
    }

    private function getDeliveryAddressPreferenceScore(Address $address): int
    {
        $score = 0;

        if ($address->hasGpsCoordinates()) {
            $score += 1000;
        }

        if (trim((string) $address->getDeliveryInstructions()) !== '') {
            $score += 500;
        }

        if (trim((string) $address->getCourierNotes()) !== '') {
            $score += 100;
        }

        return $score;
    }

    private function findPreferredBillingAddress(Customer $customer): ?Address
    {
        $billingAddress = $customer->getBillingAddress();

        if ($billingAddress instanceof Address && $billingAddress->isBilling()) {
            return $billingAddress;
        }

        $addresses = [];
        foreach ($customer->getAddresses() as $address) {
            if ($address instanceof Address && $address->isBilling()) {
                $addresses[] = $address;
            }
        }

        usort($addresses, function (Address $left, Address $right): int {
            return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
        });

        return $addresses[0] ?? null;
    }

    private function findOrCreatePreferredBillingAddress(Customer $customer, EntityManagerInterface $em): ?Address
    {
        $billingAddress = $this->findPreferredBillingAddress($customer);

        if ($billingAddress instanceof Address) {
            if ($customer->getBillingAddress() !== $billingAddress) {
                $customer->setBillingAddress($billingAddress);
                $em->flush();
            }

            return $billingAddress;
        }

        $sourceAddress = $this->findFirstAddressForBillingCopy($customer);

        if (!$sourceAddress instanceof Address) {
            return null;
        }

        $billingAddress = (new Address())
            ->setType(Address::TYPE_BILLING)
            ->setLabel('Adresse de facturation')
            ->setLine1($sourceAddress->getLine1())
            ->setLine2($sourceAddress->getLine2())
            ->setPostalCode($sourceAddress->getPostalCode())
            ->setCommune($sourceAddress->getCommune())
            ->setDeliveryZone($sourceAddress->getDeliveryZone())
            ->setDeliveryInstructions(null)
            ->setCourierNotes(null)
            ->setGpsLatitude(null)
            ->setGpsLongitude(null)
            ->setGpsAccuracyMeters(null);

        $customer->addAddress($billingAddress);
        $customer->setBillingAddress($billingAddress);

        $em->persist($billingAddress);
        $em->flush();

        return $billingAddress;
    }

    private function findFirstAddressForBillingCopy(Customer $customer): ?Address
    {
        $deliveryAddress = $customer->getDeliveryAddress();

        if ($deliveryAddress instanceof Address && $deliveryAddress->isDelivery()) {
            return $deliveryAddress;
        }

        $preferredDeliveryAddress = $this->findPreferredDeliveryAddress($customer);

        if ($preferredDeliveryAddress instanceof Address) {
            return $preferredDeliveryAddress;
        }

        $addresses = [];
        foreach ($customer->getAddresses() as $address) {
            if ($address instanceof Address) {
                $addresses[] = $address;
            }
        }

        usort($addresses, function (Address $left, Address $right): int {
            return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
        });

        return $addresses[0] ?? null;
    }

    private function applyGpsCoordinatesToAddress(Address $address, ?string $latitude, ?string $longitude, ?string $accuracyMeters): void
    {
        if ($latitude !== null && $longitude !== null) {
            $address
                ->setGpsLatitude($latitude)
                ->setGpsLongitude($longitude)
                ->setGpsAccuracyMeters($accuracyMeters);

            return;
        }

        $address
            ->setGpsLatitude(null)
            ->setGpsLongitude(null)
            ->setGpsAccuracyMeters(null);
    }

    private function normalizeDeliveryInstructions(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 1000);
    }

    private function normalizeGpsLatitude(mixed $value): ?string
    {
        $number = $this->normalizeGpsNumber($value);

        if ($number === null || $number < -90 || $number > 90) {
            return null;
        }

        return number_format($number, 7, '.', '');
    }

    private function normalizeGpsLongitude(mixed $value): ?string
    {
        $number = $this->normalizeGpsNumber($value);

        if ($number === null || $number < -180 || $number > 180) {
            return null;
        }

        return number_format($number, 7, '.', '');
    }

    private function normalizeGpsAccuracyMeters(mixed $value): ?string
    {
        $number = $this->normalizeGpsNumber($value);

        if ($number === null || $number < 0 || $number > 999999) {
            return null;
        }

        return number_format($number, 2, '.', '');
    }

    private function normalizeGpsNumber(mixed $value): ?float
    {
        $value = trim(str_replace(',', '.', (string) $value));

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function findReusableDeliveryAddress(
        Customer $customer,
        string $line1,
        ?string $line2,
        string $postalCode,
        string $commune,
        mixed $deliveryZone,
        ?AddressLocality $addressLocality,
        ?string $localityText,
        ?string $deliveryInstructions,
        ?string $gpsLatitude,
        ?string $gpsLongitude,
        ?string $gpsAccuracyMeters
    ): ?Address {
        $line1 = trim($line1);
        $line2 = $line2 !== null ? trim($line2) : null;
        $postalCode = trim($postalCode);
        $commune = trim($commune);
        $deliveryZoneId = method_exists($deliveryZone, 'getId') ? $deliveryZone->getId() : null;
        $addressLocalityId = $addressLocality?->getId();
        $localityText = $this->normalizeNullableText($localityText);

        $deliveryInstructions = $this->normalizeNullableText($deliveryInstructions);
        $gpsLatitude = $this->normalizeNullableText($gpsLatitude);
        $gpsLongitude = $this->normalizeNullableText($gpsLongitude);
        $gpsAccuracyMeters = $this->normalizeNullableText($gpsAccuracyMeters);

        foreach ($customer->getAddresses() as $candidate) {
            if (!$candidate instanceof Address || !$candidate->isDelivery()) {
                continue;
            }

            $candidateZoneId = $candidate->getDeliveryZone()?->getId();

            if (
                mb_strtolower(trim($candidate->getLine1())) === mb_strtolower($line1)
                && mb_strtolower(trim((string) $candidate->getLine2())) === mb_strtolower((string) $line2)
                && trim($candidate->getPostalCode()) === $postalCode
                && mb_strtolower(trim($candidate->getCommune())) === mb_strtolower($commune)
                && $candidateZoneId === $deliveryZoneId
                && $candidate->getAddressLocality()?->getId() === $addressLocalityId
                && $this->normalizeNullableText($candidate->getLocalityLabel()) === $localityText
                && $this->normalizeNullableText($candidate->getDeliveryInstructions()) === $deliveryInstructions
                && $this->normalizeNullableText($candidate->getGpsLatitude()) === $gpsLatitude
                && $this->normalizeNullableText($candidate->getGpsLongitude()) === $gpsLongitude
                && $this->normalizeNullableText($candidate->getGpsAccuracyMeters()) === $gpsAccuracyMeters
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    private function findReusableAddress(
        EntityManagerInterface $em,
        Customer $customer,
        string $type,
        string $line1,
        ?string $line2,
        string $postalCode,
        string $commune,
        mixed $deliveryZone
    ): ?Address {
        $line1 = trim($line1);
        $line2 = $line2 !== null ? trim($line2) : null;
        $postalCode = trim($postalCode);
        $commune = trim($commune);
        $deliveryZoneId = method_exists($deliveryZone, 'getId') ? $deliveryZone->getId() : null;

        foreach ($customer->getAddresses() as $candidate) {
            if (!$candidate instanceof Address) {
                continue;
            }

            $candidateZoneId = $candidate->getDeliveryZone()?->getId();

            if (
                $candidate->getType() === $type
                && mb_strtolower(trim($candidate->getLine1())) === mb_strtolower($line1)
                && mb_strtolower(trim((string) $candidate->getLine2())) === mb_strtolower((string) $line2)
                && trim($candidate->getPostalCode()) === $postalCode
                && mb_strtolower(trim($candidate->getCommune())) === mb_strtolower($commune)
                && $candidateZoneId === $deliveryZoneId
            ) {
                return $candidate;
            }
        }

        return $em->getRepository(Address::class)->findOneBy([
            'customer' => $customer,
            'type' => $type,
            'line1' => $line1,
            'line2' => $line2,
            'postalCode' => $postalCode,
            'commune' => $commune,
            'deliveryZone' => $deliveryZone,
        ]);
    }


    /**
     * @return array{0: AddressLocality|null, 1: string|null}
     */
    private function resolveSubmittedAddressLocality(
        EntityManagerInterface $em,
        FormInterface $form,
        DeliveryCommune $deliveryCommune,
        string $addressLocalityId,
        string $localityText,
        string $postalCode
    ): array {
        $addressLocalityId = trim($addressLocalityId);
        $localityText = $this->normalizeNullableText($localityText);
        $postalCode = trim($postalCode);

        if ($localityText !== null) {
            $localityText = mb_substr($localityText, 0, 120);
        }

        $repository = $em->getRepository(AddressLocality::class);
        $knownLocality = null;

        if ($addressLocalityId !== '' && ctype_digit($addressLocalityId)) {
            $candidate = $repository->find((int) $addressLocalityId);

            if ($candidate instanceof AddressLocality && $candidate->isActive()) {
                $candidateCommune = $candidate->getDeliveryCommune();
                $candidatePostalCode = trim((string) $candidate->getPostalCode());

                $isCompatibleCommune = !$candidateCommune instanceof DeliveryCommune
                    || $candidateCommune->getId() === $deliveryCommune->getId();
                $isCompatiblePostalCode = $candidatePostalCode === '' || $candidatePostalCode === $postalCode;

                if ($isCompatibleCommune && $isCompatiblePostalCode) {
                    $knownLocality = $candidate;
                }
                // Si le client a changé la commune ou le code postal après une suggestion,
                // on conserve le texte comme localité libre au lieu de bloquer la commande.
                // La commune livrée reste validée séparément par DeliveryCommune.
            }
        }

        if (!$knownLocality instanceof AddressLocality && $localityText !== null) {
            $candidate = $repository->findOneActiveCompatible($localityText, $deliveryCommune, $postalCode);
            if ($candidate instanceof AddressLocality) {
                $knownLocality = $candidate;
            }
        }

        if ($knownLocality instanceof AddressLocality) {
            return [$knownLocality, $knownLocality->getName()];
        }

        return [null, $localityText];
    }

    private function validateDeliveryAddressData(
        FormInterface $form,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        string $commune,
        string $postalCode
    ): ?DeliveryCommune {
        $commune = trim($commune);
        $postalCode = trim($postalCode);

        if ($postalCode === '') {
            $form->get('postalCode')->addError(new FormError('Le code postal de livraison est obligatoire.'));
            return null;
        }

        if (!$deliveryCommuneMatcher->isValidFrenchPostalCode($postalCode)) {
            $form->get('postalCode')->addError(new FormError('Le code postal de livraison doit contenir exactement 5 chiffres.'));
            return null;
        }

        if ($commune === '') {
            $form->get('commune')->addError(new FormError('La commune de livraison est obligatoire.'));
            return null;
        }

        $deliveryCommune = $deliveryCommuneMatcher->resolveCanonicalActiveLogisticsCommune($commune, $postalCode);

        if (!$deliveryCommune) {
            $form->get('commune')->addError(new FormError($deliveryCommuneMatcher->buildValidationMessage($commune, $postalCode)));
            return null;
        }

        $expectedPostalCode = trim((string) $deliveryCommune->getPostalCode());
        if ($expectedPostalCode === '' || !$deliveryCommuneMatcher->isValidFrenchPostalCode($expectedPostalCode)) {
            $form->get('commune')->addError(new FormError(sprintf(
                'La commune %s est livrable mais son code postal n’est pas correctement renseigné dans Logistique > Communes livrées.',
                $deliveryCommune->getName()
            )));

            return null;
        }

        if ($postalCode !== $expectedPostalCode) {
            $form->get('postalCode')->addError(new FormError(sprintf(
                'Le code postal %s ne correspond pas à la commune %s. Le code postal attendu est %s.',
                $postalCode,
                $deliveryCommune->getName(),
                $expectedPostalCode
            )));

            return null;
        }

        return $deliveryCommune;
    }

    private function validateBillingAddressData(
        FormInterface $form,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        string $line1,
        string $postalCode,
        string $commune,
        string $zoneCode
    ): mixed {
        $line1 = trim($line1);
        $postalCode = trim($postalCode);
        $commune = trim($commune);
        $zoneCode = trim($zoneCode);

        if ($line1 === '') {
            $form->get('billingAddress')->addError(new FormError('La première ligne de l’adresse de facturation ne doit pas être vide.'));
            return null;
        }

        if (!$deliveryCommuneMatcher->isValidFrenchPostalCode($postalCode)) {
            $form->get('billingPostalCode')->addError(new FormError('Le code postal de facturation doit contenir exactement 5 chiffres.'));
            return null;
        }

        if ($commune === '') {
            $form->get('billingCommune')->addError(new FormError('La commune de facturation est obligatoire.'));
            return null;
        }

        if ($zoneCode === DeliveryCommuneMatcherService::ZONE_AUTRE) {
            $otherZone = $deliveryCommuneMatcher->findOtherDeliveryZone();

            if (!$otherZone) {
                $form->get('billingZone')->addError(new FormError('La zone AUTRE — Autre est absente. Elle est nécessaire pour les adresses de facturation hors zone livrable.'));
                return null;
            }

            return $otherZone;
        }

        if (!in_array($zoneCode, ['PT', 'GT'], true)) {
            $form->get('billingZone')->addError(new FormError('La zone de facturation doit être Petite-Terre, Grande-Terre ou AUTRE — Autre.'));
            return null;
        }

        $billingCommune = $deliveryCommuneMatcher->resolveByCommuneName($commune);

        if (!$billingCommune) {
            $form->get('billingCommune')->addError(new FormError($deliveryCommuneMatcher->buildValidationMessage($commune, $postalCode)));
            return null;
        }

        $expectedPostalCode = trim((string) $billingCommune->getPostalCode());
        if ($expectedPostalCode !== '' && $postalCode !== $expectedPostalCode) {
            $form->get('billingPostalCode')->addError(new FormError(sprintf(
                'Le code postal %s ne correspond pas à la commune %s. Le code postal attendu est %s.',
                $postalCode,
                $billingCommune->getName(),
                $expectedPostalCode
            )));

            return null;
        }

        if ($zoneCode !== $billingCommune->getTerritory()) {
            $form->get('billingZone')->addError(new FormError(sprintf(
                'La commune %s appartient à %s, pas à la zone %s.',
                $billingCommune->getName(),
                $this->formatZoneLabel($billingCommune->getTerritory()),
                $this->formatZoneLabel($zoneCode)
            )));

            return null;
        }

        $billingZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($billingCommune);

        if (!$billingZone) {
            $form->get('billingZone')->addError(new FormError(sprintf('Zone de facturation %s inactive ou absente.', $this->formatZoneLabel($billingCommune->getTerritory()))));
            return null;
        }

        return $billingZone;
    }

    private function formatZoneLabel(string $zoneCode): string
    {
        return match ($zoneCode) {
            'PT' => 'Petite-Terre (PT)',
            'GT' => 'Grande-Terre (GT)',
            DeliveryCommuneMatcherService::ZONE_AUTRE => 'Autre',
            default => $zoneCode,
        };
    }

    #[Route('/commande/confirmation/{id}', name: 'order_confirmation', methods: ['GET'])]
    public function confirmation(int $id, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(CustomerOrder::class)->find($id);

        if (!$order instanceof CustomerOrder) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('checkout/confirmation.html.twig', [
            'order' => $order,
            'orderId' => $id,
            'orderReference' => $order->getOrderReference(),
        ]);
    }

    private function guessCommuneFromAddress(string $address): string
    {
        // Pilote : valeur de secours, à améliorer plus tard avec une vraie déduction.
        return 'Non précisée';
    }
    private function generateTempPassword(): string
    {
        return substr(strtoupper(bin2hex(random_bytes(4))), 0, 8);
    }

    private function makeCartSnapshot(array $cartData): array
    {
        $items = [];

        foreach ($cartData['items'] as $item) {
            $p = $item['product'];

            $items[] = [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'producerPrice' => (float) ($item['producerUnitPrice'] ?? $p->getPrice()),
                'appliedMarginRate' => (float) ($item['appliedMarginRate'] ?? 0),
                'hodinaMarginAmount' => (float) ($item['hodinaMarginAmount'] ?? 0),
                'price' => (float) ($item['unitPrice'] ?? $p->getPrice()),
                'qty' => (int) $item['qty'],
                'lineTotal' => (float) $item['lineTotal'],
                'seller' => $p->getSeller()?->getPublicDisplayName(),
            ];
        }

        return [
            'items' => $items,
            'totalQty' => (int) ($cartData['totalQty'] ?? 0),
            'total' => (float) ($cartData['total'] ?? 0),
        ];
    }
    private function normalizeCustomerTimezone(?string $timezone): string
    {
        $timezone = trim((string) $timezone);

        if ($timezone === '') {
            return 'Indian/Mayotte';
        }

        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            return 'Indian/Mayotte';
        }

        return $timezone;
    }
}