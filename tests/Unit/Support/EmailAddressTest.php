<?php

declare(strict_types=1);

use App\Support\EmailAddress;

test('a mixed-case address with surrounding whitespace is normalized', function (): void {
    $email = new EmailAddress('  John.Doe@Example.COM  ');

    expect($email->value)->toBe('john.doe@example.com');
    expect((string) $email)->toBe('john.doe@example.com');
});

test('normalize alone lowercases and trims without validating', function (): void {
    expect(EmailAddress::normalize('  Foo@Bar.com  '))->toBe('foo@bar.com');
});

test('an invalid address is rejected', function (): void {
    new EmailAddress('not-an-email');
})->throws(InvalidArgumentException::class);

test('an empty string is rejected', function (): void {
    new EmailAddress('   ');
})->throws(InvalidArgumentException::class);
