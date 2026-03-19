<?php

namespace App\Application\Booking\UseCases;

use App\Domain\Booking\ValueObjects\BookingStatus;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use InvalidArgumentException;

final class ConfirmBooking
{
    public function execute(Booking $booking): Booking
    {
        $currentStatus = BookingStatus::from($booking->status);

        if (!$currentStatus->canTransitionTo(BookingStatus::Confirmed)) {
            throw new InvalidArgumentException(
                "Cannot confirm booking with status: {$currentStatus->value}"
            );
        }

        $booking->update(['status' => BookingStatus::Confirmed->value]);
        event(new BookingConfirmed($booking));

        return $booking->fresh();
    }
}
