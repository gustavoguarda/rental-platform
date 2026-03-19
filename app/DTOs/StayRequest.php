<?php

namespace App\DTOs;

use DateTimeImmutable;

final readonly class StayRequest
{
    public function __construct(
        public int $propertyId,
        public DateTimeImmutable $checkIn,
        public DateTimeImmutable $checkOut,
        public int $guests,
    ) {}

    public function nights(): int
    {
        return $this->checkIn->diff($this->checkOut)->days;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            propertyId: (int) $data['property_id'],
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            guests: (int) $data['guests'],
        );
    }
}
