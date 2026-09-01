<?php

declare(strict_types=1);

use App\Rules\ValidTaxRate;

function taxRateValidationFails(mixed $value): bool
{
    $failed = false;
    (new ValidTaxRate)->validate('tax_rate', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

test('a plain decimal rate passes', function (): void {
    expect(taxRateValidationFails('0.25'))->toBeFalse();
});

test('zero passes', function (): void {
    expect(taxRateValidationFails('0'))->toBeFalse();
});

test('a negative rate fails', function (): void {
    expect(taxRateValidationFails('-0.25'))->toBeTrue();
});

test('a non-numeric string fails', function (): void {
    expect(taxRateValidationFails('a lot'))->toBeTrue();
});

test('a non-string value fails', function (): void {
    expect(taxRateValidationFails(['array']))->toBeTrue();
});
