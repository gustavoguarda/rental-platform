<?php

namespace App\Application\Availability\UseCases;

use App\Contracts\AvailabilityCheckerInterface;
use App\Contracts\PricingEngineInterface;
use App\DTOs\AvailabilityResult;
use App\DTOs\PricingQuote;
use App\DTOs\StayRequest;

final class CheckAvailability
{
    public function __construct(
        private readonly AvailabilityCheckerInterface $availability,
        private readonly PricingEngineInterface $pricing,
    ) {}

    /**
     * @return array{availability: AvailabilityResult, quote: PricingQuote|null}
     */
    public function execute(StayRequest $stay): array
    {
        $result = $this->availability->check($stay);
        $quote = $result->isAvailable ? $this->pricing->calculateQuote($stay) : null;

        return [
            'availability' => $result,
            'quote' => $quote,
        ];
    }
}
