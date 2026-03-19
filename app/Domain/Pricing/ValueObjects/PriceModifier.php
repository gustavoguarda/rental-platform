<?php

namespace App\Domain\Pricing\ValueObjects;

use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;

final readonly class PriceModifier
{
    public function __construct(
        public ModifierType $type,
        public float $value,
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException('Modifier value cannot be negative');
        }
    }

    public function apply(Money $base): Money
    {
        return match ($this->type) {
            ModifierType::Percentage => $base->add($base->percentage($this->value)),
            ModifierType::Fixed => $base->add(Money::fromCents((int) $this->value, $base->currency)),
            ModifierType::Override => Money::fromCents((int) $this->value, $base->currency),
        };
    }
}
