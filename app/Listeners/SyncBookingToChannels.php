<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Jobs\SyncChannelAvailability;

/**
 * When a booking is confirmed, push updated availability
 * to all connected distribution channels (Airbnb, VRBO, Booking.com).
 *
 * This is dispatched as a queued job to avoid blocking the
 * booking confirmation flow.
 */
class SyncBookingToChannels
{
    public function handle(BookingConfirmed $event): void
    {
        SyncChannelAvailability::dispatch(
            $event->booking->property_id,
        )->onQueue('channel-sync');
    }
}
