<?php

use App\Domain\Shared\ValueObjects\Money;

test('creates money from cents', function () {
    $money = Money::fromCents(35000, 'USD');

    expect($money->cents)->toBe(35000);
    expect($money->currency)->toBe('USD');
    expect($money->toDollars())->toBe(350.0);
});

test('creates money from dollars', function () {
    $money = Money::fromDollars(350.00);

    expect($money->cents)->toBe(35000);
});

test('rejects negative amount', function () {
    Money::fromCents(-100);
})->throws(InvalidArgumentException::class);

test('adds two money values', function () {
    $a = Money::fromCents(10000);
    $b = Money::fromCents(5000);

    $result = $a->add($b);

    expect($result->cents)->toBe(15000);
});

test('prevents adding different currencies', function () {
    $usd = Money::fromCents(100, 'USD');
    $eur = Money::fromCents(100, 'EUR');

    $usd->add($eur);
})->throws(InvalidArgumentException::class);

test('calculates percentage', function () {
    $base = Money::fromCents(10000); // $100.00

    $result = $base->percentage(25); // 25% = $25.00

    expect($result->cents)->toBe(2500);
});

test('formats currency', function () {
    $money = Money::fromCents(35099);

    expect($money->format())->toBe('$350.99');
});

test('compares money values', function () {
    $a = Money::fromCents(10000);
    $b = Money::fromCents(5000);

    expect($a->greaterThan($b))->toBeTrue();
    expect($b->greaterThan($a))->toBeFalse();
    expect($a->equals(Money::fromCents(10000)))->toBeTrue();
});
