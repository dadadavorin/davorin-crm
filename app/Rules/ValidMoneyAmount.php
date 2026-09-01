<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Wires `Money::fromDecimalString()` into the validation pipeline, so a
 * malformed amount fails as an ordinary field error instead of an uncaught
 * exception when the action parses it.
 */
final class ValidMoneyAmount implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        try {
            Money::fromDecimalString($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid amount with at most 2 decimal places.');
        }
    }
}
