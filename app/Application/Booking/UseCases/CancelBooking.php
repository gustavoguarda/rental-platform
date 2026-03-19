<?php

namespace App\Application\Booking\UseCases;

use App\Domain\Booking\ValueObjects\BookingStatus;
use App\Models\Booking;
use InvalidArgumentException;

final class CancelBooking
{
    public function execute(Booking $booking): Booking
    {
        $currentStatus = BookingStatus::from($booking->status);

        if (!$currentStatus->canTransitionTo(BookingStatus::Cancelled)) {
            throw new InvalidArgumentException(
                "Cannot cancel booking with status: {$currentStatus->value}"
            );
        }

        $booking->update(['status' => BookingStatus::Cancelled->value]);

        return $booking->fresh();
    }
}
