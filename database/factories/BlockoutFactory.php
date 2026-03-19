<?php

namespace Database\Factories;

use App\Models\Blockout;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockoutFactory extends Factory
{
    protected $model = Blockout::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');
        $end = (clone $start)->modify('+' . fake()->numberBetween(3, 10) . ' days');

        return [
            'property_id' => Property::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'reason' => fake()->sentence(),
        ];
    }
}
