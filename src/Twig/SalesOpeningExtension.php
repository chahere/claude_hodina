<?php

namespace App\Twig;

use App\Service\SalesOpeningService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SalesOpeningExtension extends AbstractExtension
{
    public function __construct(private readonly SalesOpeningService $salesOpeningService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hodina_commerce_state', [$this->salesOpeningService, 'getState']),
            // Legacy alias kept so old templates keep working during the J5I -> J5J transition.
            new TwigFunction('hodina_sales_opening_state', [$this->salesOpeningService, 'getState']),
        ];
    }
}
