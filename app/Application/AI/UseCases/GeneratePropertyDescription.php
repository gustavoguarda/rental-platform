<?php

namespace App\Application\AI\UseCases;

use App\Contracts\AIServiceInterface;
use App\Models\Property;

final class GeneratePropertyDescription
{
    public function __construct(
        private readonly AIServiceInterface $ai,
    ) {}

    public function execute(Property $property): Property
    {
        $description = $this->ai->generatePropertyDescription($property);

        $property->update(['ai_description' => $description]);

        return $property->fresh();
    }
}
