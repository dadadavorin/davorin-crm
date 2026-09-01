<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\EmailAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Wires `EmailAddress` — the single place email validity is decided
 * (ADR-0001) — into the validation pipeline, so an invalid address fails as
 * an ordinary field error instead of an uncaught exception when the value
 * object is constructed later.
 */
final class ValidEmailAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        try {
            new EmailAddress($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid email address.');
        }
    }
}
