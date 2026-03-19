<?php

namespace App\Domain\Pricing\ValueObjects;

enum ModifierType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Override = 'override';
}
