<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $products,
        private ProductPricingService $pricing,
    ) {}

    /** @return array<int,int> [productId => qty] */
    public function getRaw(): array
    {
        $session = $this->requestStack->getSession();
        /** @var array<int,int> $cart */
        $cart = $session->get(self::SESSION_KEY, []);
        return is_array($cart) ? $cart : [];
    }

    public function getTotalQty(): int
    {
        $qty = 0;
        foreach ($this->getRaw() as $q) {
            $qty += max(0, (int) $q);
        }
        return $qty;
    }

    public function add(int $productId, int $qty = 1): void
    {
        $qty = max(1, $qty);
        $cart = $this->getRaw();
        $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
        $this->save($cart);
    }

    public function setQty(int $productId, int $qty): void
    {
        $cart = $this->getRaw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }
        $this->save($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getRaw();
        unset($cart[$productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    /** @return array{items: array<int,array{product: mixed, qty:int, lineTotal: float}>, totalQty:int, total: float} */
    public function getDetailedCart(): array
    {
        $cart = $this->getRaw();

        $items = [];
        $total = 0.0;
        $totalQty = 0;

        foreach ($cart as $id => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $product = $this->products->find((int) $id);
            if (!$product) continue;

            $breakdown = $this->pricing->getPriceBreakdown($product);
            $price = (float) $breakdown['customerPrice'];
            $lineTotal = $price * $qty;

            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'unitPrice' => $price,
                'producerUnitPrice' => (float) $breakdown['producerPrice'],
                'appliedMarginRate' => (float) $breakdown['marginRate'],
                'hodinaMarginAmount' => (float) $breakdown['hodinaMarginAmount'],
                'lineTotal' => $lineTotal,
            ];

            $total += $lineTotal;
            $totalQty += $qty;
        }

        return [
            'items' => $items,
            'totalQty' => $totalQty,
            'total' => $total,
        ];
    }

    /** @param array<int,int> $cart */
    private function save(array $cart): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $cart);
    }
}
