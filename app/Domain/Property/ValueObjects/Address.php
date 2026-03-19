<?php

namespace App\Domain\Property\ValueObjects;

final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $state,
        public string $country,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    public function fullAddress(): string
    {
        return "{$this->street}, {$this->city}, {$this->state}, {$this->country}";
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function toArray(): array
    {
        return [
            'address' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
