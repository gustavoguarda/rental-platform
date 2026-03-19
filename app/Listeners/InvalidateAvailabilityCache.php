<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Services\Availability\AvailabilityChecker;

class InvalidateAvailabilityCache
{
    public function handle(BookingCreated|BookingConfirmed $event): void
    {
        AvailabilityChecker::invalidateCache($event->booking->property_id);
    }
}
