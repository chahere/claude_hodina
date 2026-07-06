<?php

namespace App\Service;

use App\Entity\HodinaSetting;
use App\Entity\Product;
use App\Entity\Seller;
use Doctrine\ORM\EntityManagerInterface;

final class ProductPricingService
{
    public const DEFAULT_GLOBAL_MARGIN_RATE = '20.00';

    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function getProducerPrice(Product $product): string
    {
        $producerPrice = $product->getProducerPrice();

        if ($producerPrice !== null && (float) $producerPrice > 0) {
            return $this->money($producerPrice);
        }

        return $this->money($product->getPrice());
    }

    public function getEffectiveMarginRate(Product $product): string
    {
        $productMargin = $product->getMarginRate();

        if ($productMargin !== null && trim($productMargin) !== '') {
            return $this->percent($productMargin);
        }

        $seller = $product->getSeller();

        if ($seller instanceof Seller) {
            $sellerMargin = $seller->getMarginRate();

            if ($sellerMargin !== null && trim($sellerMargin) !== '') {
                return $this->percent($sellerMargin);
            }
        }

        return $this->getGlobalMarginRate();
    }

    public function getGlobalMarginRate(): string
    {
        $setting = $this->entityManager
            ->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => HodinaSetting::KEY_GLOBAL_MARGIN_RATE]);

        if (!$setting) {
            return self::DEFAULT_GLOBAL_MARGIN_RATE;
        }

        $value = trim((string) $setting->getValue());

        return $value !== '' ? $this->percent($value) : self::DEFAULT_GLOBAL_MARGIN_RATE;
    }

    public function getCustomerPrice(Product $product): string
    {
        $producerPrice = (float) $this->getProducerPrice($product);
        $marginRate = (float) $this->getEffectiveMarginRate($product);

        return $this->money($producerPrice * (1 + ($marginRate / 100)));
    }

    public function getHodinaMarginAmount(Product $product): string
    {
        return $this->money((float) $this->getCustomerPrice($product) - (float) $this->getProducerPrice($product));
    }

    /** @return array{producerPrice:string, marginRate:string, customerPrice:string, hodinaMarginAmount:string} */
    public function getPriceBreakdown(Product $product): array
    {
        return [
            'producerPrice' => $this->getProducerPrice($product),
            'marginRate' => $this->getEffectiveMarginRate($product),
            'customerPrice' => $this->getCustomerPrice($product),
            'hodinaMarginAmount' => $this->getHodinaMarginAmount($product),
        ];
    }

    private function money(float|string $value): string
    {
        return number_format(max(0, (float) $value), 2, '.', '');
    }

    private function percent(float|string $value): string
    {
        return number_format(max(0, (float) $value), 2, '.', '');
    }
}
