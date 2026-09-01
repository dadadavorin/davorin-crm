<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Wires `Money::percentage()`'s rate format into the validation pipeline —
 * the same bridge `ValidMoneyAmount` provides for an amount — so a
 * malformed tax rate fails as an ordinary field error instead of an
 * uncaught exception when `RecalculateQuoteTotals` applies it.
 */
final class ValidTaxRate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        try {
            Money::zero()->percentage($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a non-negative decimal rate, e.g. "0.25".');
        }
    }
}
