<?php

use App\Domain\Shared\ValueObjects\DateRange;

test('calculates nights correctly', function () {
    $range = new DateRange('2025-07-10', '2025-07-17');

    expect($range->nights())->toBe(7);
});

test('rejects end before start', function () {
    new DateRange('2025-07-17', '2025-07-10');
})->throws(InvalidArgumentException::class);

test('detects overlapping ranges', function () {
    $a = new DateRange('2025-07-10', '2025-07-17');
    $b = new DateRange('2025-07-15', '2025-07-20');

    expect($a->overlaps($b))->toBeTrue();
});

test('detects non-overlapping ranges', function () {
    $a = new DateRange('2025-07-10', '2025-07-15');
    $b = new DateRange('2025-07-15', '2025-07-20');

    expect($a->overlaps($b))->toBeFalse();
});

test('checks if date is contained', function () {
    $range = new DateRange('2025-07-10', '2025-07-17');

    expect($range->contains('2025-07-12'))->toBeTrue();
    expect($range->contains('2025-07-17'))->toBeFalse(); // check-out day
    expect($range->contains('2025-07-09'))->toBeFalse();
});

test('enumerates each day', function () {
    $range = new DateRange('2025-07-10', '2025-07-13');

    expect($range->eachDay())->toHaveCount(3);
});
