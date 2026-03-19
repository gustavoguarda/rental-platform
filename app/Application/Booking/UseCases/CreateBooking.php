<?php

namespace App\Application\Booking\UseCases;

use App\Contracts\AvailabilityCheckerInterface;
use App\Contracts\PricingEngineInterface;
use App\Domain\Booking\ValueObjects\BookingStatus;
use App\Domain\Booking\ValueObjects\GuestInfo;
use App\Domain\Shared\ValueObjects\DateRange;
use App\DTOs\StayRequest;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateBooking
{
    public function __construct(
        private readonly AvailabilityCheckerInterface $availability,
        private readonly PricingEngineInterface $pricing,
    ) {}

    public function execute(array $data): Booking
    {
        // Domain validation through Value Objects
        $guest = new GuestInfo(
            name: $data['guest_name'],
            email: $data['guest_email'],
            count: $data['guests_count'],
        );

        $stay = new DateRange($data['check_in'], $data['check_out']);

        $stayRequest = StayRequest::fromArray([
            'property_id' => $data['property_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'guests' => $guest->count,
        ]);

        return DB::transaction(function () use ($data, $guest, $stayRequest) {
            // Pessimistic lock on property
            $property = Property::where('id', $data['property_id'])->lockForUpdate()->firstOrFail();

            // Check availability (domain rule)
            $result = $this->availability->check($stayRequest);
            if (!$result->isAvailable) {
                throw ValidationException::withMessages([
                    'availability' => $result->reason,
                ]);
            }

            // Calculate pricing
            $quote = $this->pricing->calculateQuote($stayRequest);

            $booking = Booking::create([
                'property_id' => $property->id,
                'guest_name' => $guest->name,
                'guest_email' => $guest->email,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests_count' => $guest->count,
                'total_price_cents' => $quote->totalWithFeesCents(),
                'currency' => $property->currency,
                'status' => BookingStatus::Pending->value,
                'source_channel' => $data['source_channel'] ?? 'direct',
                'notes' => $data['notes'] ?? null,
            ]);

            event(new BookingCreated($booking));

            return $booking;
        });
    }
}
