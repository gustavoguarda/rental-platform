<?php

namespace Tests\Unit;

use App\DTOs\StayRequest;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StayRequestTest extends TestCase
{
    public function test_calculates_nights_correctly(): void
    {
        $request = new StayRequest(
            propertyId: 1,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-15'),
            guests: 2,
        );

        $this->assertEquals(5, $request->nights());
    }

    public function test_creates_from_array(): void
    {
        $request = StayRequest::fromArray([
            'property_id' => 42,
            'check_in' => '2025-07-01',
            'check_out' => '2025-07-05',
            'guests' => 3,
        ]);

        $this->assertEquals(42, $request->propertyId);
        $this->assertEquals(3, $request->guests);
        $this->assertEquals(4, $request->nights());
    }

    public function test_single_night_stay(): void
    {
        $request = new StayRequest(
            propertyId: 1,
            checkIn: new DateTimeImmutable('2025-06-10'),
            checkOut: new DateTimeImmutable('2025-06-11'),
            guests: 1,
        );

        $this->assertEquals(1, $request->nights());
    }
}
