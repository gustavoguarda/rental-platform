<?php

namespace App\Contracts;

use App\DTOs\PricingQuote;
use App\DTOs\StayRequest;

interface PricingEngineInterface
{
    public function calculateQuote(StayRequest $request): PricingQuote;
}
