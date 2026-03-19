<?php

namespace App\Domain\Booking\ValueObjects;

use InvalidArgumentException;

final readonly class GuestInfo
{
    public function __construct(
        public string $name,
        public string $email,
        public int $count,
    ) {
        if (empty($name)) {
            throw new InvalidArgumentException('Guest name is required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid guest email');
        }
        if ($count < 1) {
            throw new InvalidArgumentException('Guest count must be at least 1');
        }
    }
}
