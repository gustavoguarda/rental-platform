<?php

namespace App\Services\Availability;

use App\Contracts\AvailabilityCheckerInterface;
use App\DTOs\AvailabilityResult;
use App\DTOs\StayRequest;
use App\Models\Blockout;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Support\Facades\Cache;

/**
 * Availability service used by the booking engine and channel sync.
 *
 * Uses a cache-aside pattern with Redis to avoid hitting the database
 * on every availability check. Cache is invalidated on booking/blockout
 * changes via domain events.
 */
class AvailabilityChecker implements AvailabilityCheckerInterface
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public function check(StayRequest $request): AvailabilityResult
    {
        $cacheKey = $this->buildCacheKey($request);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($request) {
            return $this->performCheck($request);
        });
    }

    private function performCheck(StayRequest $request): AvailabilityResult
    {
        $property = Property::findOrFail($request->propertyId);

        if (! $property->isAvailable()) {
            return AvailabilityResult::unavailable(
                $property->id,
                'Property is not currently accepting bookings.',
            );
        }

        if ($request->guests > $property->max_guests) {
            return AvailabilityResult::unavailable(
                $property->id,
                "Property max capacity is {$property->max_guests} guests.",
            );
        }

        // Check for overlapping confirmed bookings
        $conflictingBooking = Booking::where('property_id', $request->propertyId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('check_in', '<', $request->checkOut)
            ->where('check_out', '>', $request->checkIn)
            ->exists();

        if ($conflictingBooking) {
            return AvailabilityResult::unavailable(
                $property->id,
                'Property is already booked for some of the requested dates.',
            );
        }

        // Check for owner blockouts (maintenance, personal use, etc.)
        $blockout = Blockout::where('property_id', $request->propertyId)
            ->where('start_date', '<', $request->checkOut)
            ->where('end_date', '>', $request->checkIn)
            ->first();

        if ($blockout) {
            return AvailabilityResult::unavailable(
                $property->id,
                "Property is blocked: {$blockout->reason}",
                [$blockout->start_date->format('Y-m-d'), $blockout->end_date->format('Y-m-d')],
            );
        }

        return AvailabilityResult::available($property->id);
    }

    private function buildCacheKey(StayRequest $request): string
    {
        return sprintf(
            'availability:%d:%s:%s:%d',
            $request->propertyId,
            $request->checkIn->format('Y-m-d'),
            $request->checkOut->format('Y-m-d'),
            $request->guests,
        );
    }

    /**
     * Invalidate all cached availability for a property.
     * Called by event listeners when bookings or blockouts change.
     */
    public static function invalidateCache(int $propertyId): void
    {
        Cache::tags(["property:{$propertyId}:availability"])->flush();
    }
}
