<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * Normalizes and validates an email address. This is the one place
 * normalization happens — the partial unique index on `contacts.email`
 * (T6) depends on every write going through it.
 */
final readonly class EmailAddress implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = self::normalize($value);

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("\"{$value}\" is not a valid email address.");
        }

        $this->value = $normalized;
    }

    public static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
