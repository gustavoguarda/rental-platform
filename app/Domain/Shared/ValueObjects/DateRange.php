<?php

namespace App\Domain\Shared\ValueObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class DateRange
{
    public CarbonImmutable $start;
    public CarbonImmutable $end;

    public function __construct(
        CarbonImmutable|string $start,
        CarbonImmutable|string $end,
    ) {
        $this->start = $start instanceof CarbonImmutable ? $start : CarbonImmutable::parse($start);
        $this->end = $end instanceof CarbonImmutable ? $end : CarbonImmutable::parse($end);

        if ($this->end->lte($this->start)) {
            throw new InvalidArgumentException('End date must be after start date');
        }
    }

    public function nights(): int
    {
        return (int) $this->start->diffInDays($this->end);
    }

    public function overlaps(DateRange $other): bool
    {
        return $this->start->lt($other->end) && $this->end->gt($other->start);
    }

    public function contains(CarbonImmutable|string $date): bool
    {
        $date = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);
        return $date->gte($this->start) && $date->lt($this->end);
    }

    public function eachDay(): array
    {
        $days = [];
        $current = $this->start;
        while ($current->lt($this->end)) {
            $days[] = $current;
            $current = $current->addDay();
        }
        return $days;
    }

    public function equals(DateRange $other): bool
    {
        return $this->start->eq($other->start) && $this->end->eq($other->end);
    }
}
