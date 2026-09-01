<?php

declare(strict_types=1);

use App\Rules\ValidEmailAddress;

function validationFails(mixed $value): bool
{
    $failed = false;
    (new ValidEmailAddress)->validate('email', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

test('a valid address passes', function (): void {
    expect(validationFails('jane@example.com'))->toBeFalse();
});

test('an invalid address fails with a field error, not an exception', function (): void {
    expect(validationFails('not-an-email'))->toBeTrue();
});

test('a non-string value fails', function (): void {
    expect(validationFails(['array']))->toBeTrue();
});
