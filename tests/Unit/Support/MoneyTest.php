<?php

declare(strict_types=1);

use App\Support\Money;

test('zero is zero minor units', function (): void {
    expect(Money::zero()->minorUnits)->toBe(0);
});

test('add sums minor units exactly', function (): void {
    $sum = Money::fromMinorUnits(150)->add(Money::fromMinorUnits(250));

    expect($sum->minorUnits)->toBe(400);
});

test('subtract can go negative', function (): void {
    $diff = Money::fromMinorUnits(100)->subtract(Money::fromMinorUnits(150));

    expect($diff->minorUnits)->toBe(-50);
});

test('multiplyBy an integer quantity is exact', function (): void {
    $total = Money::fromMinorUnits(199)->multiplyBy(3);

    expect($total->minorUnits)->toBe(597);
});

test('multiplyBy accepts a whole-number numeric string', function (): void {
    $total = Money::fromMinorUnits(199)->multiplyBy('3');

    expect($total->minorUnits)->toBe(597);
});

test('multiplyBy rejects a fractional quantity string', function (): void {
    Money::fromMinorUnits(199)->multiplyBy('2.5');
})->throws(InvalidArgumentException::class);

test('multiplyBy rejects a non-numeric quantity string', function (): void {
    Money::fromMinorUnits(199)->multiplyBy('abc');
})->throws(InvalidArgumentException::class);

test('percentage rounds half-up at the exact midpoint', function (): void {
    // 25 minor units at 50% is 12.5 — half-up rounds to 13.
    expect(Money::fromMinorUnits(25)->percentage('0.5')->minorUnits)->toBe(13);
});

test('percentage rounds down below the midpoint', function (): void {
    // 100 at 12% is 12.0 — no rounding needed, but exercises the boundary.
    expect(Money::fromMinorUnits(100)->percentage('0.12')->minorUnits)->toBe(12);
});

test('percentage rounds a fraction just under half down', function (): void {
    // 1000 at 12.44% is 124.4 — the 0.4 remainder rounds down to 124.
    expect(Money::fromMinorUnits(1000)->percentage('0.1244')->minorUnits)->toBe(124);
});

test('percentage rounds a fraction just over half up', function (): void {
    // 1000 at 12.46% is 124.6 — the 0.6 remainder rounds up to 125.
    expect(Money::fromMinorUnits(1000)->percentage('0.1246')->minorUnits)->toBe(125);
});

test('percentage of zero rate is zero', function (): void {
    expect(Money::fromMinorUnits(1000)->percentage('0')->minorUnits)->toBe(0);
});

test('percentage rejects a negative rate', function (): void {
    Money::fromMinorUnits(1000)->percentage('-0.1');
})->throws(InvalidArgumentException::class);

test('the currency is a fixed application constant', function (): void {
    expect(Money::CURRENCY)->toBe('EUR');
});
