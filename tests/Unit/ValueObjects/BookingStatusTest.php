<?php

use App\Domain\Booking\ValueObjects\BookingStatus;

test('pending can transition to confirmed', function () {
    expect(BookingStatus::Pending->canTransitionTo(BookingStatus::Confirmed))->toBeTrue();
});

test('pending can transition to cancelled', function () {
    expect(BookingStatus::Pending->canTransitionTo(BookingStatus::Cancelled))->toBeTrue();
});

test('confirmed can transition to cancelled', function () {
    expect(BookingStatus::Confirmed->canTransitionTo(BookingStatus::Cancelled))->toBeTrue();
});

test('cancelled cannot transition', function () {
    expect(BookingStatus::Cancelled->canTransitionTo(BookingStatus::Pending))->toBeFalse();
    expect(BookingStatus::Cancelled->canTransitionTo(BookingStatus::Confirmed))->toBeFalse();
});

test('confirmed cannot go back to pending', function () {
    expect(BookingStatus::Confirmed->canTransitionTo(BookingStatus::Pending))->toBeFalse();
});

test('active statuses are pending and confirmed', function () {
    expect(BookingStatus::Pending->isActive())->toBeTrue();
    expect(BookingStatus::Confirmed->isActive())->toBeTrue();
    expect(BookingStatus::Cancelled->isActive())->toBeFalse();
});
