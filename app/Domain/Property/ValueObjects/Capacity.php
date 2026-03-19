<?php

namespace App\Domain\Property\ValueObjects;

use InvalidArgumentException;

final readonly class Capacity
{
    public function __construct(
        public int $bedrooms,
        public int $bathrooms,
        public int $maxGuests,
    ) {
        if ($bedrooms < 0 || $bathrooms < 0 || $maxGuests < 1) {
            throw new InvalidArgumentException('Invalid capacity values');
        }
    }

    public function canAccommodate(int $guests): bool
    {
        return $guests <= $this->maxGuests;
    }
}
