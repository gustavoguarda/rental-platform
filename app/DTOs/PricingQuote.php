<?php

namespace App\DTOs;

final readonly class PricingQuote
{
    /**
     * @param array<string, int> $nightlyBreakdown date => price in cents
     */
    public function __construct(
        public int $propertyId,
        public int $totalPriceCents,
        public string $currency,
        public int $nights,
        public array $nightlyBreakdown,
        public array $appliedRules,
        public int $cleaningFeeCents = 0,
        public int $serviceFeePercent = 10,
    ) {}

    public function totalWithFeesCents(): int
    {
        $serviceFee = (int) round($this->totalPriceCents * $this->serviceFeePercent / 100);

        return $this->totalPriceCents + $this->cleaningFeeCents + $serviceFee;
    }

    public function toArray(): array
    {
        return [
            'property_id' => $this->propertyId,
            'total_price_cents' => $this->totalPriceCents,
            'total_with_fees_cents' => $this->totalWithFeesCents(),
            'currency' => $this->currency,
            'nights' => $this->nights,
            'nightly_breakdown' => $this->nightlyBreakdown,
            'applied_rules' => $this->appliedRules,
            'cleaning_fee_cents' => $this->cleaningFeeCents,
            'service_fee_percent' => $this->serviceFeePercent,
        ];
    }
}
