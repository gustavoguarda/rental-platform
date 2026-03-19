<?php

namespace Tests\Unit;

use App\DTOs\StayRequest;
use App\Models\Blockout;
use App\Models\Booking;
use App\Models\Property;
use App\Services\Availability\AvailabilityChecker;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityCheckerTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new AvailabilityChecker();
    }

    public function test_available_when_no_conflicts(): void
    {
        $property = Property::factory()->create(['status' => 'active', 'max_guests' => 4]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 2,
        );

        $result = $this->checker->check($request);

        $this->assertTrue($result->isAvailable);
    }

    public function test_unavailable_when_property_inactive(): void
    {
        $property = Property::factory()->create(['status' => 'inactive', 'max_guests' => 4]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 2,
        );

        $result = $this->checker->check($request);

        $this->assertFalse($result->isAvailable);
        $this->assertStringContainsString('not currently accepting', $result->reason);
    }

    public function test_unavailable_when_exceeds_max_guests(): void
    {
        $property = Property::factory()->create(['status' => 'active', 'max_guests' => 2]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 5,
        );

        $result = $this->checker->check($request);

        $this->assertFalse($result->isAvailable);
        $this->assertStringContainsString('max capacity', $result->reason);
    }

    public function test_unavailable_when_overlapping_booking_exists(): void
    {
        $property = Property::factory()->create(['status' => 'active', 'max_guests' => 4]);

        Booking::factory()->create([
            'property_id' => $property->id,
            'check_in' => '2025-06-12',
            'check_out' => '2025-06-18',
            'status' => 'confirmed',
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 2,
        );

        $result = $this->checker->check($request);

        $this->assertFalse($result->isAvailable);
        $this->assertStringContainsString('already booked', $result->reason);
    }

    public function test_unavailable_when_blockout_overlaps(): void
    {
        $property = Property::factory()->create(['status' => 'active', 'max_guests' => 4]);

        Blockout::factory()->create([
            'property_id' => $property->id,
            'start_date' => '2025-06-11',
            'end_date' => '2025-06-14',
            'reason' => 'Owner maintenance',
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 2,
        );

        $result = $this->checker->check($request);

        $this->assertFalse($result->isAvailable);
        $this->assertStringContainsString('Owner maintenance', $result->reason);
    }

    public function test_available_when_booking_is_cancelled(): void
    {
        $property = Property::factory()->create(['status' => 'active', 'max_guests' => 4]);

        Booking::factory()->create([
            'property_id' => $property->id,
            'check_in' => '2025-06-12',
            'check_out' => '2025-06-18',
            'status' => 'cancelled',
        ]);

        $request = new StayRequest(
            propertyId: $property->id,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 2,
        );

        $result = $this->checker->check($request);

        $this->assertTrue($result->isAvailable);
    }
}
