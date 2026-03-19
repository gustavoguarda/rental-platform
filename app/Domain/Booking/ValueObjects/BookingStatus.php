<?php

namespace App\Domain\Booking\ValueObjects;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(BookingStatus $new): bool
    {
        return match ($this) {
            self::Pending => in_array($new, [self::Confirmed, self::Cancelled]),
            self::Confirmed => in_array($new, [self::Cancelled]),
            self::Cancelled => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed]);
    }
}
