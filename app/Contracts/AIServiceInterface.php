<?php

namespace App\Contracts;

use App\Models\Property;

interface AIServiceInterface
{
    public function generatePropertyDescription(Property $property): string;

    public function generateGuestResponse(string $guestMessage, array $context): string;

    public function suggestPricingAdjustment(Property $property, array $marketData): array;
}
