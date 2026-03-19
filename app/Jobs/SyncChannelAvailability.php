<?php

namespace App\Jobs;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Syncs property availability to external distribution channels.
 *
 * Runs on a dedicated 'channel-sync' queue to isolate channel API
 * failures from the main booking flow. Retries with exponential
 * backoff since channel APIs are often rate-limited.
 */
class SyncChannelAvailability implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly int $propertyId,
    ) {}

    public function handle(): void
    {
        $property = Property::findOrFail($this->propertyId);

        // In production, this would call each channel's API:
        // - Airbnb API: Update calendar blocks
        // - VRBO/Expedia: Push iCal feed update
        // - Booking.com: OTA availability sync

        Log::info('Syncing availability to channels', [
            'property_id' => $property->id,
            'property_name' => $property->name,
        ]);

        // Placeholder for channel sync implementation
        // Each channel adapter would implement a ChannelSyncInterface
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Channel sync failed after all retries', [
            'property_id' => $this->propertyId,
            'error' => $exception->getMessage(),
        ]);

        // In production: notify operator via email/slack about sync failure
    }
}
