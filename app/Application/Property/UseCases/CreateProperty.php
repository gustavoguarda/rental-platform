<?php

namespace App\Application\Property\UseCases;

use App\Domain\Property\ValueObjects\Address;
use App\Domain\Property\ValueObjects\Capacity;
use App\Domain\Property\ValueObjects\PropertyStatus;
use App\Domain\Shared\ValueObjects\Money;
use App\Models\Property;

final class CreateProperty
{
    public function execute(array $data): Property
    {
        // Validate through Value Objects (domain invariants)
        $address = new Address(
            street: $data['address'],
            city: $data['city'],
            state: $data['state'],
            country: $data['country'],
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
        );

        $capacity = new Capacity(
            bedrooms: $data['bedrooms'],
            bathrooms: $data['bathrooms'],
            maxGuests: $data['max_guests'],
        );

        $basePrice = Money::fromCents($data['base_price_cents'], $data['currency'] ?? 'USD');
        $status = PropertyStatus::from($data['status'] ?? 'draft');

        return Property::create([
            'operator_id' => $data['operator_id'],
            'name' => $data['name'],
            'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            ...$address->toArray(),
            'bedrooms' => $capacity->bedrooms,
            'bathrooms' => $capacity->bathrooms,
            'max_guests' => $capacity->maxGuests,
            'base_price_cents' => $basePrice->cents,
            'currency' => $basePrice->currency,
            'amenities' => $data['amenities'] ?? [],
            'status' => $status->value,
        ]);
    }
}
