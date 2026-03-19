<?php

namespace App\Contracts;

use App\DTOs\AvailabilityResult;
use App\DTOs\StayRequest;

interface AvailabilityCheckerInterface
{
    public function check(StayRequest $request): AvailabilityResult;
}
