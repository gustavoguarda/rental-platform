<?php

namespace App\Services\Pricing;

use App\Contracts\PricingEngineInterface;
use App\DTOs\PricingQuote;
use App\DTOs\StayRequest;
use App\Models\Property;
use DateInterval;
use DatePeriod;

/**
 * Core pricing engine shared across all product lines.
 *
 * Applies pricing rules in priority order to compute nightly rates.
 * Designed as a platform service consumed by the booking engine,
 * the channel manager, and the owner portal.
 */
class PricingEngine implements PricingEngineInterface
{
    public function calculateQuote(StayRequest $request): PricingQuote
    {
        $property = Property::with('pricingRules')->findOrFail($request->propertyId);

        $rules = $property->pricingRules
            ->where('is_active', true)
            ->sortByDesc('priority');

        $nightlyBreakdown = [];
        $appliedRules = [];
        $totalCents = 0;

        $period = new DatePeriod(
            $request->checkIn,
            new DateInterval('P1D'),
            $request->checkOut,
        );

        foreach ($period as $date) {
            $nightRate = $property->base_price_cents;
            $dateKey = $date->format('Y-m-d');

            foreach ($rules as $rule) {
                if (! $rule->appliesToDate($date)) {
                    continue;
                }

                $nightRate = $this->applyModifier($nightRate, $rule);

                if (! in_array($rule->name, $appliedRules)) {
                    $appliedRules[] = $rule->name;
                }
            }

            // Long-stay discount: 10% off for 7+ nights, 20% off for 30+ nights
            $nightRate = $this->applyLongStayDiscount($nightRate, $request->nights());

            $nightlyBreakdown[$dateKey] = $nightRate;
            $totalCents += $nightRate;
        }

        return new PricingQuote(
            propertyId: $property->id,
            totalPriceCents: $totalCents,
            currency: $property->currency ?? 'USD',
            nights: $request->nights(),
            nightlyBreakdown: $nightlyBreakdown,
            appliedRules: $appliedRules,
        );
    }

    private function applyModifier(int $baseCents, $rule): int
    {
        return match ($rule->modifier_type) {
            'percentage' => (int) round($baseCents * (1 + $rule->modifier_value / 100)),
            'fixed' => $baseCents + (int) ($rule->modifier_value * 100),
            'override' => (int) ($rule->modifier_value * 100),
            default => $baseCents,
        };
    }

    private function applyLongStayDiscount(int $nightRate, int $totalNights): int
    {
        if ($totalNights >= 30) {
            return (int) round($nightRate * 0.80);
        }

        if ($totalNights >= 7) {
            return (int) round($nightRate * 0.90);
        }

        return $nightRate;
    }
}
