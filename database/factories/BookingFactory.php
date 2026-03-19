<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 week', '+3 months');
        $checkOut = (clone $checkIn)->modify('+' . fake()->numberBetween(2, 7) . ' days');

        return [
            'property_id' => Property::factory(),
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'guests_count' => fake()->numberBetween(1, 6),
            'total_price_cents' => fake()->numberBetween(50000, 300000),
            'currency' => 'USD',
            'status' => 'confirmed',
            'source_channel' => 'direct',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
