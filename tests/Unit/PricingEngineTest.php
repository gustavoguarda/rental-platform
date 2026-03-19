<?php

namespace Tests\Unit;

use App\DTOs\PricingQuote;
use App\DTOs\StayRequest;
use App\Models\PricingRule;
use App\Models\Property;
use App\Services\Pricing\PricingEngine;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PricingEngine();
    }

    public function test_calculates_base_price_for_simple_stay(): void
    {
        $property = Property::factory()->create([
            'base_price_cents' => 15000, // $150/night
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-13'),
            guests: 2,
        );

        $quote = $this->engine->calculateQuote($request);

        $this->assertInstanceOf(PricingQuote::class, $quote);
        $this->assertEquals(3, $quote->nights);
        $this->assertEquals(45000, $quote->totalPriceCents); // 3 * $150
        $this->assertCount(3, $quote->nightlyBreakdown);
    }

    public function test_applies_percentage_pricing_rule(): void
    {
        $property = Property::factory()->create([
            'base_price_cents' => 10000, // $100/night
        ]);

        PricingRule::factory()->create([
            'property_id' => $property->id,
            'name' => 'Summer Premium',
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => 20, // +20%
            'start_date' => '2025-06-01',
            'end_date' => '2025-08-31',
            'is_active' => true,
            'priority' => 10,
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-07-01'),
            checkOut: new DateTimeImmutable('2025-07-03'),
            guests: 2,
        );

        $quote = $this->engine->calculateQuote($request);

        // $100 * 1.20 = $120/night * 2 nights = $240
        $this->assertEquals(24000, $quote->totalPriceCents);
        $this->assertContains('Summer Premium', $quote->appliedRules);
    }

    public function test_applies_long_stay_discount_for_weekly(): void
    {
        $property = Property::factory()->create([
            'base_price_cents' => 20000, // $200/night
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-03-01'),
            checkOut: new DateTimeImmutable('2025-03-08'),
            guests: 2,
        );

        $quote = $this->engine->calculateQuote($request);

        // 7 nights * $200 * 0.90 (10% discount) = $1,260
        $this->assertEquals(7, $quote->nights);
        $this->assertEquals(126000, $quote->totalPriceCents);
    }

    public function test_applies_long_stay_discount_for_monthly(): void
    {
        $property = Property::factory()->create([
            'base_price_cents' => 10000, // $100/night
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-01-01'),
            checkOut: new DateTimeImmutable('2025-01-31'),
            guests: 1,
        );

        $quote = $this->engine->calculateQuote($request);

        // 30 nights * $100 * 0.80 (20% discount) = $2,400
        $this->assertEquals(30, $quote->nights);
        $this->assertEquals(240000, $quote->totalPriceCents);
    }

    public function test_quote_includes_service_fee(): void
    {
        $property = Property::factory()->create([
            'base_price_cents' => 10000,
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-05-01'),
            checkOut: new DateTimeImmutable('2025-05-02'),
            guests: 1,
        );

        $quote = $this->engine->calculateQuote($request);

        // $100 + 10% service fee = $110
        $this->assertEquals(10000, $quote->totalPriceCents);
        $this->assertEquals(11000, $quote->totalWithFeesCents());
    }
}
