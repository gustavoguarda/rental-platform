<?php

namespace App\Domain\Property\ValueObjects;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    public function isBookable(): bool
    {
        return $this === self::Active;
    }

    public function canTransitionTo(PropertyStatus $new): bool
    {
        return match ($this) {
            self::Draft => in_array($new, [self::Active, self::Inactive]),
            self::Active => in_array($new, [self::Inactive]),
            self::Inactive => in_array($new, [self::Active]),
        };
    }
}
