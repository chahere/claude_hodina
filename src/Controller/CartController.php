<?php

namespace App\Controller;

use App\Dto\CartLogisticsPreview;
use App\Entity\Address;
use App\Entity\AddressLocality;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\DeliveryCommune;
use App\Entity\DeliveryPoint;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\DeliveryLogisticsService;
use App\Service\DeliveryScheduleService;
use App\Service\DeliveryPointCartService;
use App\Service\DeliveryCommuneMatcherService;
use App\Service\DeliveryFeeReasonFormatter;
use App\Service\SalesOpeningService;
use App\Service\PhoneNumberNormalizer;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    private const LOGISTICS_SESSION_KEY = 'cart_logistics_preview';
    private const LOGISTICS_PREVIEW_CACHE_VERSION = 'j5z-delivery-fee-reason-v1';
    private const DELIVERY_QUOTE_SESSION_KEY = 'checkout_delivery_quote';

    #[Route('/panier', name: 'cart_index', methods: ['GET'])]
    public function index(
        CartService $cart,
        DeliveryLogisticsService $logistics,
        DeliveryScheduleService $deliveryScheduleService,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        DeliveryPointCartService $deliveryPointCartService,
        EntityManagerInterface $em,
        PhoneNumberNormalizer $phoneNumberNormalizer,
        Request $request
    ): Response {
        $detailedCart = $cart->getDetailedCart();
        $deliveryPointAnalysis = $deliveryPointCartService->analyzeCart($detailedCart);
        $deliveryPointChoices = $deliveryPointCartService->buildPointChoices($deliveryPointAnalysis);

        $connectedCustomer = $this->getUser() instanceof Customer ? $this->getUser() : null;
        $checkoutDefaults = $this->buildCheckoutDefaults($connectedCustomer, $em, $deliveryCommuneMatcher, $phoneNumberNormalizer);
        $checkoutDefaults['deliveryMethod'] = $deliveryPointAnalysis['defaultMethod'];
        if (($deliveryPointAnalysis['requiresDeliveryPoint'] ?? false) && isset($deliveryPointChoices[0])) {
            $checkoutDefaults['deliveryPointId'] = (string) $deliveryPointChoices[0]['id'];
            $checkoutDefaults['address'] = (string) ($deliveryPointChoices[0]['line1'] ?? '');
            $checkoutDefaults['addressLocalityId'] = '';
            $checkoutDefaults['localityText'] = '';
            $checkoutDefaults['postalCode'] = (string) ($deliveryPointChoices[0]['postalCode'] ?? '');
            $checkoutDefaults['commune'] = (string) ($deliveryPointChoices[0]['commune'] ?? '');
            $checkoutDefaults['zone'] = (string) ($deliveryPointChoices[0]['zone'] ?? '');
            if (!empty($deliveryPointChoices[0]['timeWindows'][0]['id'])) {
                $checkoutDefaults['deliveryPointTimeWindowId'] = (string) $deliveryPointChoices[0]['timeWindows'][0]['id'];
            }
        }

        $deliveryAddress = $this->getPreferredDeliveryAddress();
        if (($checkoutDefaults['deliveryMethod'] ?? DeliveryPointCartService::METHOD_STANDARD) === DeliveryPointCartService::METHOD_DELIVERY_POINT) {
            $defaultDeliveryPoint = $this->findDeliveryPointFromAnalysis($deliveryPointAnalysis, (int) ($checkoutDefaults['deliveryPointId'] ?? 0));
            if ($defaultDeliveryPoint instanceof DeliveryPoint) {
                $deliveryAddress = $this->buildDeliveryAddressFromPoint($defaultDeliveryPoint, $deliveryCommuneMatcher);
            }
        }

        $logisticsPreview = $this->getCachedLogisticsPreview($request, $logistics, $deliveryAddress, $detailedCart);
        $deliverySchedulePreview = ($checkoutDefaults['deliveryMethod'] ?? DeliveryPointCartService::METHOD_STANDARD) === DeliveryPointCartService::METHOD_STANDARD
            ? $this->buildDeliverySchedulePreview($deliveryScheduleService, $logisticsPreview)
            : null;
        $form = $this->createForm(CheckoutType::class, $checkoutDefaults, [
            'delivery_communes' => $deliveryCommuneMatcher->findActiveLogisticsCommunes(),
        ]);
        $this->applyCheckoutDefaultsToUnmappedFields($form, $checkoutDefaults);

        return $this->render('cart/index.html.twig', [
            'cart' => $detailedCart,
            'logisticsPreview' => $logisticsPreview,
            'deliverySchedulePreview' => $deliverySchedulePreview,
            'form' => $form->createView(),
            'customerAddresses' => $this->getCustomerDeliveryAddresses($connectedCustomer),
            'billingAddresses' => $this->getCustomerBillingAddresses($connectedCustomer),
            'defaultDeliveryAddress' => $connectedCustomer?->getDeliveryAddress(),
            'billingAddress' => $connectedCustomer?->getBillingAddress(),
            'deliveryPointOptions' => $deliveryPointAnalysis,
            'deliveryPointChoices' => $deliveryPointChoices,
            'addressLocalities' => $em->getRepository(AddressLocality::class)->findActiveForCheckout(),
            'cartHasAppointmentDeliveryPromise' => $this->cartHasAppointmentDeliveryPromise($detailedCart),
        ]);
    }

    #[Route('/panier/adresse-livraison/{id}/defaut', name: 'cart_set_default_delivery_address', methods: ['POST'])]
    public function setDefaultDeliveryAddress(int $id, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            $this->addFlash('warning', 'Connecte-toi pour définir une adresse de livraison par défaut.');
            return $this->redirectToRoute('cart_index');
        }

        $address = $em->getRepository(Address::class)->findOneBy([
            'id' => $id,
            'customer' => $customer,
            'type' => Address::TYPE_DELIVERY,
        ]);

        if (!$address instanceof Address) {
            $this->addFlash('warning', 'Adresse de livraison introuvable ou non disponible.');
            return $this->redirectToRoute('cart_index');
        }

        $customer->setDeliveryAddress($address);
        $em->flush();
        $request->getSession()->remove(self::LOGISTICS_SESSION_KEY);
        $request->getSession()->remove(self::DELIVERY_QUOTE_SESSION_KEY);
        $this->addFlash('success', 'Adresse de livraison par défaut mise à jour.');

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/panier/adresse-facturation/{id}/defaut', name: 'cart_set_default_billing_address', methods: ['POST'])]
    public function setDefaultBillingAddress(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            $this->addFlash('warning', 'Connecte-toi pour définir une adresse de facturation par défaut.');
            return $this->redirectToRoute('cart_index');
        }

        $address = $em->getRepository(Address::class)->findOneBy([
            'id' => $id,
            'customer' => $customer,
            'type' => Address::TYPE_BILLING,
        ]);

        if (!$address instanceof Address) {
            $this->addFlash('warning', 'Adresse de facturation introuvable ou non disponible.');
            return $this->redirectToRoute('cart_index');
        }

        $customer->setBillingAddress($address);
        $em->flush();
        $this->addFlash('success', 'Adresse de facturation par défaut mise à jour.');

        return $this->redirectToRoute('cart_index');
    }


    #[Route('/panier/logistique/apercu', name: 'cart_logistics_preview', methods: ['POST'])]
    public function logisticsPreview(
        Request $request,
        CartService $cart,
        DeliveryLogisticsService $logistics,
        DeliveryScheduleService $deliveryScheduleService,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        DeliveryPointCartService $deliveryPointCartService,
        DeliveryFeeReasonFormatter $deliveryFeeReasonFormatter,
    ): JsonResponse {
        $cartData = $cart->getDetailedCart();

        if (empty($cartData['items'])) {
            return $this->json([
                'ok' => false,
                'message' => 'Le panier est vide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $deliveryPointAnalysis = $deliveryPointCartService->analyzeCart($cartData);
        $deliveryMethod = trim((string) $request->request->get('deliveryMethod', DeliveryPointCartService::METHOD_STANDARD));
        if (($deliveryPointAnalysis['requiresDeliveryPoint'] ?? false) === true) {
            $deliveryMethod = DeliveryPointCartService::METHOD_DELIVERY_POINT;
        }

        if ($deliveryMethod === DeliveryPointCartService::METHOD_DELIVERY_POINT) {
            if (($deliveryPointAnalysis['allowsDeliveryPoint'] ?? false) !== true) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Aucun point de remise commun actif n’est disponible pour ce panier.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $deliveryPointId = (int) $request->request->get('deliveryPointId', 0);
            $deliveryPoint = $this->findDeliveryPointFromAnalysis($deliveryPointAnalysis, $deliveryPointId);

            if (!$deliveryPoint instanceof DeliveryPoint) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Choisis un point de remise disponible pour recalculer les frais.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $address = $this->buildDeliveryAddressFromPoint($deliveryPoint, $deliveryCommuneMatcher);

            if (!$address instanceof Address) {
                return $this->json([
                    'ok' => false,
                    'message' => 'La commune du point de remise est inactive ou absente des zones de livraison.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $preview = $logistics->previewForCart($address, $cartData);
        } else {
            if (($deliveryPointAnalysis['allowsStandardDelivery'] ?? true) !== true) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Ce panier impose un point de remise. Choisis un point Hodina pour recalculer les frais.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $postalCode = trim((string) $request->request->get('postalCode', ''));
            $communeName = trim((string) $request->request->get('commune', ''));

            if ($postalCode === '' && $communeName === '') {
                $preview = $logistics->previewForCart(null, $cartData);

                return $this->jsonLogisticsPreview($preview, (float) ($cartData['total'] ?? 0), null, $deliveryFeeReasonFormatter);
            }

            if ($postalCode === '' || !$deliveryCommuneMatcher->isValidFrenchPostalCode($postalCode)) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Choisis un code postal Hodina valide pour recalculer les frais.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($communeName === '') {
                return $this->json([
                    'ok' => false,
                    'message' => 'Choisis une commune compatible avec ce code postal pour recalculer les frais.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $deliveryCommune = $deliveryCommuneMatcher->resolveCanonicalActiveLogisticsCommune($communeName, $postalCode);

            if (!$deliveryCommune) {
                return $this->json([
                    'ok' => false,
                    'message' => $deliveryCommuneMatcher->buildValidationMessage($communeName, $postalCode),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $expectedPostalCode = trim((string) $deliveryCommune->getPostalCode());
            if ($postalCode !== $expectedPostalCode) {
                return $this->json([
                    'ok' => false,
                    'message' => sprintf(
                        'Le code postal %s ne correspond pas à la commune %s. Choisis un couple code postal / commune proposé par Hodina.',
                        $postalCode,
                        $deliveryCommune->getName()
                    ),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $deliveryZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($deliveryCommune);

            if (!$deliveryZone) {
                return $this->json([
                    'ok' => false,
                    'message' => sprintf('La zone %s est absente ou inactive dans les zones de livraison.', $deliveryCommune->getTerritory()),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $address = (new Address())
                ->setType(Address::TYPE_DELIVERY)
                ->setLine1(trim((string) $request->request->get('address', 'Adresse panier')))
                ->setPostalCode($expectedPostalCode)
                ->setCommune($deliveryCommune->getName())
                ->setDeliveryZone($deliveryZone);

            $preview = $logistics->previewForCart($address, $cartData);
        }

        $deliveryFee = $preview->estimatedDeliveryFee;
        $totalWithDelivery = $deliveryFee !== null ? ((float) ($cartData['total'] ?? 0)) + $deliveryFee : (float) ($cartData['total'] ?? 0);

        $request->getSession()->set(self::DELIVERY_QUOTE_SESSION_KEY, [
            'signature' => $this->buildDeliveryQuoteSignature($address, $cartData),
            'deliveryFee' => $deliveryFee !== null ? $this->normalizeMoney($deliveryFee) : null,
            'totalWithDelivery' => $this->normalizeMoney($totalWithDelivery),
            'message' => $preview->message,
            'createdAt' => time(),
        ]);

        $deliverySchedulePreview = $deliveryMethod === DeliveryPointCartService::METHOD_STANDARD
            ? $this->buildDeliverySchedulePreview($deliveryScheduleService, $preview)
            : null;

        return $this->jsonLogisticsPreview($preview, (float) ($cartData['total'] ?? 0), $deliverySchedulePreview, $deliveryFeeReasonFormatter);
    }

    #[Route('/panier/ajouter/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        CartService $cart,
        SalesOpeningService $salesOpening,
        ProductRepository $productRepository,
    ): Response {
        if ($salesOpening->isCartLocked()) {
            $message = $salesOpening->getCartLockedMessage();

            if ($this->wantsJson($request)) {
                return $this->json([
                    'ok' => false,
                    'message' => $message,
                    'cartCount' => $cart->getTotalQty(),
                ], Response::HTTP_LOCKED);
            }

            $this->addFlash('warning', $message);

            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('product_catalogue'));
        }

        $product = $productRepository->find($id);

        if (!$product instanceof Product || !$product->isActive()) {
            $message = 'Ce produit n’est plus disponible.';

            if ($this->wantsJson($request)) {
                return $this->json([
                    'ok' => false,
                    'message' => $message,
                    'cartCount' => $cart->getTotalQty(),
                ], Response::HTTP_NOT_FOUND);
            }

            $this->addFlash('warning', $message);

            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('product_catalogue'));
        }

        $qty = max(1, (int) $request->request->get('qty', 1));
        $cart->add((int) $product->getId(), $qty);
        $request->getSession()->remove(self::DELIVERY_QUOTE_SESSION_KEY);
        $request->getSession()->remove(self::LOGISTICS_SESSION_KEY);

        if ($this->wantsJson($request)) {
            return $this->json([
                'ok' => true,
                'message' => sprintf('%s ajouté au panier.', $product->getName()),
                'cartCount' => $cart->getTotalQty(),
                'productId' => $product->getId(),
                'qtyAdded' => $qty,
                'cartUrl' => $this->generateUrl('cart_index'),
            ]);
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('cart_index'));
    }

    #[Route('/panier/maj', name: 'cart_update', methods: ['POST'])]
    public function update(Request $request, CartService $cart, SalesOpeningService $salesOpening): RedirectResponse
    {
        if ($salesOpening->isCartLocked()) {
            $this->addFlash('warning', $salesOpening->getCartLockedMessage());

            return $this->redirectToRoute('cart_index');
        }

        $quantities = $request->request->all('qty');
        if (is_array($quantities)) {
            foreach ($quantities as $productId => $qty) {
                $cart->setQty((int) $productId, (int) $qty);
            }
        }

        $request->getSession()->remove(self::DELIVERY_QUOTE_SESSION_KEY);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/panier/supprimer/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(int $id, Request $request, CartService $cart): RedirectResponse
    {
        $cart->remove($id);
        $request->getSession()->remove(self::DELIVERY_QUOTE_SESSION_KEY);

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('cart_index'));
    }

    #[Route('/panier/vider', name: 'cart_clear', methods: ['POST'])]
    public function clear(CartService $cart, Request $request): RedirectResponse
    {
        $cart->clear();
        $request->getSession()->remove(self::LOGISTICS_SESSION_KEY);
        $request->getSession()->remove(self::DELIVERY_QUOTE_SESSION_KEY);

        return $this->redirectToRoute('cart_index');
    }


    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }
	
    /**
	 * Les champs techniques du panier sont volontairement `mapped => false`.
	 * Symfony ne reprend donc pas toujours les valeurs du tableau initial dans
	 * la vue. On les réapplique ici pour que le panier affiche bien l'adresse
	 * de facturation/livraison choisie à l'ouverture.
	 *
	 * @param array<string, mixed> $defaults
	 */
	private function applyCheckoutDefaultsToUnmappedFields(\Symfony\Component\Form\FormInterface $form, array $defaults): void
    {
        $fields = [
            'phoneCountryCode',
            'existingAddressId',
            'addressLocalityId',
            'localityText',
            'makeDeliveryDefault',
            'deliveryInstructions',
            'deliveryMethod',
            'deliveryPointId',
            'deliveryPointTimeWindowId',
            'deliveryPointCustomerInstructions',
            'gpsLatitude',
            'gpsLongitude',
            'gpsAccuracyMeters',
            'useBillingSameAsDelivery',
            'billingExistingAddressId',
            'makeBillingDefault',
            'billingAddress',
            'billingPostalCode',
            'billingCommune',
            'billingZone',
        ];

        foreach ($fields as $field) {
            if ($form->has($field) && array_key_exists($field, $defaults)) {
                $form->get($field)->setData($defaults[$field]);
            }
        }
    }

    private function buildCheckoutDefaults(
        ?Customer $connectedCustomer,
        EntityManagerInterface $em,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        PhoneNumberNormalizer $phoneNumberNormalizer
    ): array {
        $defaults = [
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
            'deliveryMethod' => DeliveryPointCartService::METHOD_STANDARD,
            'deliveryPointId' => '',
            'deliveryPointTimeWindowId' => '',
            'deliveryPointCustomerInstructions' => '',
            'useBillingSameAsDelivery' => true,
            'makeDeliveryDefault' => false,
            'billingExistingAddressId' => '',
            'makeBillingDefault' => false,
            'billingAddress' => '',
            'billingPostalCode' => '',
            'billingCommune' => '',
            'billingZone' => DeliveryCommuneMatcherService::ZONE_AUTRE,
        ];

        if (!$connectedCustomer) {
            return $defaults;
        }

        $defaults['firstName'] = $connectedCustomer->getFirstName() ?? '';
        $defaults['lastName'] = $connectedCustomer->getLastName() ?? '';
        $phoneParts = $phoneNumberNormalizer->splitForForm($connectedCustomer->getPhone() ?? '');
        $defaults['phoneCountryCode'] = $phoneParts['dialCode'];
        $defaults['phone'] = $phoneParts['localNumber'];
        $defaults['email'] = $connectedCustomer->getEmail() ?? '';

        $deliveryAddress = $connectedCustomer->getDeliveryAddress();

        if (!$deliveryAddress instanceof Address || !$deliveryAddress->isDelivery()) {
            $deliveryAddress = $this->findPreferredDeliveryAddress($connectedCustomer);
        }

        if ($deliveryAddress instanceof Address) {
            $defaults['existingAddressId'] = (string) $deliveryAddress->getId();
            $defaults['address'] = $deliveryAddress->getLine1() ?? '';
            $defaults['addressLocalityId'] = $deliveryAddress->getAddressLocality()?->getId() ? (string) $deliveryAddress->getAddressLocality()?->getId() : '';
            $defaults['localityText'] = $deliveryAddress->getLocalityLabel() ?? '';
            $defaults['deliveryInstructions'] = $deliveryAddress->getDeliveryInstructions() ?? '';
            $defaults['gpsLatitude'] = $deliveryAddress->getGpsLatitude() ?? '';
            $defaults['gpsLongitude'] = $deliveryAddress->getGpsLongitude() ?? '';
            $defaults['gpsAccuracyMeters'] = $deliveryAddress->getGpsAccuracyMeters() ?? '';

            $lastDeliveryCommune = $deliveryCommuneMatcher->resolveByCommuneName($deliveryAddress->getCommune() ?? '');
            if ($lastDeliveryCommune instanceof DeliveryCommune) {
                $defaults['postalCode'] = (string) $lastDeliveryCommune->getPostalCode();
                $defaults['commune'] = $lastDeliveryCommune->getName();
                $defaults['zone'] = $lastDeliveryCommune->getTerritory();
            }
        }

        $billingAddress = $this->findOrCreatePreferredBillingAddress($connectedCustomer, $em);

        if ($billingAddress instanceof Address) {
            $defaults['useBillingSameAsDelivery'] = false;
            $defaults['billingExistingAddressId'] = (string) $billingAddress->getId();
            $defaults['billingAddress'] = $billingAddress->getLine1() ?? '';
            $defaults['billingPostalCode'] = $billingAddress->getPostalCode() ?? '';
            $defaults['billingCommune'] = $billingAddress->getCommune() ?? '';
            $defaults['billingZone'] = $billingAddress->getDeliveryZone()?->getCode() ?? DeliveryCommuneMatcherService::ZONE_AUTRE;
        }

        return $defaults;
    }

    /** @return list<Address> */
    private function getCustomerDeliveryAddresses(?Customer $connectedCustomer): array
    {
        if (!$connectedCustomer) {
            return [];
        }

        $addresses = [];
        foreach ($connectedCustomer->getAddresses() as $address) {
            if ($address instanceof Address && $address->isDelivery()) {
                $addresses[] = $address;
            }
        }

        usort($addresses, function (Address $left, Address $right) use ($connectedCustomer): int {
            $leftDefault = $connectedCustomer->getDeliveryAddress() === $left ? 1 : 0;
            $rightDefault = $connectedCustomer->getDeliveryAddress() === $right ? 1 : 0;

            if ($leftDefault !== $rightDefault) {
                return $rightDefault <=> $leftDefault;
            }

            return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
        });

        return $addresses;
    }

    /** @return list<Address> */
    private function getCustomerBillingAddresses(?Customer $connectedCustomer): array
    {
        if (!$connectedCustomer) {
            return [];
        }

        $addresses = [];
        foreach ($connectedCustomer->getAddresses() as $address) {
            if ($address instanceof Address && $address->isBilling()) {
                $addresses[] = $address;
            }
        }

        usort($addresses, function (Address $left, Address $right) use ($connectedCustomer): int {
            $leftDefault = $connectedCustomer->getBillingAddress() === $left ? 1 : 0;
            $rightDefault = $connectedCustomer->getBillingAddress() === $right ? 1 : 0;

            if ($leftDefault !== $rightDefault) {
                return $rightDefault <=> $leftDefault;
            }

            return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
        });

        return $addresses;
    }

    private function findPreferredDeliveryAddress(Customer $customer): ?Address
    {
        $deliveryAddress = $customer->getDeliveryAddress();

        if ($deliveryAddress instanceof Address && $deliveryAddress->isDelivery()) {
            return $deliveryAddress;
        }

        $addresses = [];
        foreach ($customer->getAddresses() as $address) {
            if ($address instanceof Address && $address->isDelivery()) {
                $addresses[] = $address;
            }
        }

        usort($addresses, function (Address $left, Address $right): int {
            $leftScore = ($left->hasGpsCoordinates() ? 1000 : 0) + (trim((string) $left->getDeliveryInstructions()) !== '' ? 500 : 0) + (trim((string) $left->getCourierNotes()) !== '' ? 100 : 0);
            $rightScore = ($right->hasGpsCoordinates() ? 1000 : 0) + (trim((string) $right->getDeliveryInstructions()) !== '' ? 500 : 0) + (trim((string) $right->getCourierNotes()) !== '' ? 100 : 0);

            if ($leftScore === $rightScore) {
                return ($right->getId() ?? 0) <=> ($left->getId() ?? 0);
            }

            return $rightScore <=> $leftScore;
        });

        return $addresses[0] ?? null;
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

    /**
     * @param array<string, mixed> $analysis
     */
    private function findDeliveryPointFromAnalysis(array $analysis, int $deliveryPointId): ?DeliveryPoint
    {
        $fallback = null;

        foreach (($analysis['availablePoints'] ?? []) as $point) {
            if (!$point instanceof DeliveryPoint) {
                continue;
            }

            $fallback ??= $point;

            if ($deliveryPointId > 0 && $point->getId() === $deliveryPointId) {
                return $point;
            }
        }

        return $deliveryPointId > 0 ? null : $fallback;
    }

    private function buildDeliveryAddressFromPoint(DeliveryPoint $deliveryPoint, DeliveryCommuneMatcherService $deliveryCommuneMatcher): ?Address
    {
        $deliveryCommune = $deliveryPoint->getDeliveryCommune();
        $deliveryZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($deliveryCommune);

        if (!$deliveryZone) {
            return null;
        }

        return (new Address())
            ->setType(Address::TYPE_DELIVERY)
            ->setLabel('Point de remise')
            ->setLine1($deliveryPoint->getLine1())
            ->setLine2($deliveryPoint->getLine2())
            ->setPostalCode((string) ($deliveryPoint->getPostalCode() ?: $deliveryCommune->getPostalCode()))
            ->setCommune($deliveryPoint->getCommuneName() ?: $deliveryCommune->getName())
            ->setDeliveryZone($deliveryZone)
            ->setDeliveryInstructions($deliveryPoint->getPublicInstructions())
            ->setGpsLatitude($deliveryPoint->getGpsLatitude())
            ->setGpsLongitude($deliveryPoint->getGpsLongitude())
            ->setGpsAccuracyMeters($deliveryPoint->getGpsAccuracyMeters());
    }

    /**
     * @param array<string, mixed> $detailedCart
     */
    private function cartHasAppointmentDeliveryPromise(array $detailedCart): bool
    {
        foreach (($detailedCart['items'] ?? []) as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            if ($item['product']->isAppointmentDeliveryPromise()) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed>|null */
    private function buildDeliverySchedulePreview(DeliveryScheduleService $deliveryScheduleService, CartLogisticsPreview $preview): ?array
    {
        $schedulePreview = $deliveryScheduleService->buildPreviewFromLogisticsPreview($preview)
            ?? $deliveryScheduleService->buildPreviewFromClientCommuneName($preview->clientCommuneName);

        return $schedulePreview?->toArray();
    }

    /** @param array<string, mixed>|null $deliverySchedulePreview */
    private function jsonLogisticsPreview(
        CartLogisticsPreview $preview,
        float $cartTotal,
        ?array $deliverySchedulePreview = null,
        ?DeliveryFeeReasonFormatter $deliveryFeeReasonFormatter = null,
    ): JsonResponse
    {
        $previewData = $preview->toArray();
        $deliveryFee = $preview->estimatedDeliveryFee;
        $totalWithDelivery = $deliveryFee !== null ? $cartTotal + $deliveryFee : $cartTotal;

        return $this->json([
            'ok' => true,
            'preview' => $previewData,
            'deliveryFee' => $deliveryFee,
            'deliveryFeeFormatted' => $deliveryFee !== null ? $this->formatMoney($deliveryFee) : null,
            'deliveryFeeReason' => $deliveryFeeReasonFormatter?->formatReasons($deliveryFeeReasonFormatter->reasonsFromPreviewArray($previewData)) ?? '',
            'totalWithDelivery' => $totalWithDelivery,
            'totalWithDeliveryFormatted' => $this->formatMoney($totalWithDelivery),
            'deliverySchedule' => $deliverySchedulePreview,
        ]);
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    private function normalizeMoney(float $amount): string
    {
        return number_format($amount, 2, '.', '');
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
                $this->normalizeMoney((float) ($item['unitPrice'] ?? 0)),
                $this->normalizeMoney((float) ($item['lineTotal'] ?? 0))
            );
        }

        sort($items, SORT_STRING);

        return hash('sha256', implode('|', [
            mb_strtolower(trim((string) $deliveryAddress->getCommune())),
            trim((string) $deliveryAddress->getPostalCode()),
            $deliveryAddress->getDeliveryZone()?->getCode() ?? '',
            $this->normalizeMoney((float) ($detailedCart['total'] ?? 0)),
            implode(',', $items),
        ]));
    }

    private function getPreferredDeliveryAddress(): ?Address
    {
        $user = $this->getUser();

        if (!$user instanceof Customer) {
            return null;
        }

        return $this->findPreferredDeliveryAddress($user);
    }

    /** @param array<string, mixed> $detailedCart */
    private function getCachedLogisticsPreview(
        Request $request,
        DeliveryLogisticsService $logistics,
        ?Address $deliveryAddress,
        array $detailedCart,
    ): CartLogisticsPreview {
        $session = $request->getSession();
        $signature = $this->buildLogisticsSignature($deliveryAddress, $detailedCart);
        $cached = $session->get(self::LOGISTICS_SESSION_KEY);

        if (
            is_array($cached)
            && ($cached['version'] ?? null) === self::LOGISTICS_PREVIEW_CACHE_VERSION
            && ($cached['signature'] ?? null) === $signature
            && is_array($cached['preview'] ?? null)
        ) {
            return CartLogisticsPreview::fromArray($cached['preview']);
        }

        $preview = $logistics->previewForCart($deliveryAddress, $detailedCart);

        $session->set(self::LOGISTICS_SESSION_KEY, [
            'version' => self::LOGISTICS_PREVIEW_CACHE_VERSION,
            'signature' => $signature,
            'preview' => $preview->toArray(),
        ]);

        return $preview;
    }

    /** @param array<string, mixed> $detailedCart */
    private function buildLogisticsSignature(?Address $deliveryAddress, array $detailedCart): string
    {
        $addressSignature = 'address:none';

        if ($deliveryAddress instanceof Address) {
            $addressSignature = sprintf(
                'address:%s:%s',
                $deliveryAddress->getId() ?? 'new',
                mb_strtolower(trim($deliveryAddress->getCommune())),
            );
        }

        $sellerSignatures = [];

        foreach (($detailedCart['items'] ?? []) as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            $seller = $item['product']->getSeller();
            $sellerCommune = $seller->getDeliveryCommune();

            $sellerSignatures[] = sprintf(
                '%s:%s',
                $seller->getId() ?? 'unknown',
                $sellerCommune?->getId() ?? 'no-commune',
            );
        }

        $sellerSignatures = array_values(array_unique($sellerSignatures));
        sort($sellerSignatures, SORT_STRING);

        return $addressSignature.'|sellers:'.implode(',', $sellerSignatures);
    }
}
