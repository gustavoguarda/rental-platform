<?php

namespace Database\Factories;

use App\Models\PricingRule;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingRuleFactory extends Factory
{
    protected $model = PricingRule::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => fake()->words(3, true),
            'type' => 'seasonal',
            'modifier_type' => 'percentage',
            'modifier_value' => fake()->numberBetween(5, 30),
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'priority' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
