<?php

namespace App\DTOs;

final readonly class AvailabilityResult
{
    /**
     * @param array<string> $unavailableDates
     */
    public function __construct(
        public int $propertyId,
        public bool $isAvailable,
        public string $reason,
        public array $unavailableDates = [],
    ) {}

    public static function available(int $propertyId): self
    {
        return new self(
            propertyId: $propertyId,
            isAvailable: true,
            reason: 'Property is available for the requested dates.',
        );
    }

    public static function unavailable(int $propertyId, string $reason, array $dates = []): self
    {
        return new self(
            propertyId: $propertyId,
            isAvailable: false,
            reason: $reason,
            unavailableDates: $dates,
        );
    }

    public function toArray(): array
    {
        return [
            'property_id' => $this->propertyId,
            'is_available' => $this->isAvailable,
            'reason' => $this->reason,
            'unavailable_dates' => $this->unavailableDates,
        ];
    }
}
