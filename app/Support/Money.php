<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * An amount in integer minor units (cents). Single currency, EUR, as an
 * application constant — see ADR-0002.
 *
 * Rounding pipeline — the only place a fraction of a minor unit is ever
 * resolved:
 *
 *   unit price (exact) × quantity (whole number)
 *                     │
 *                     ▼  multiplyBy() — exact integer multiplication
 *              line total (exact)
 *                     │
 *                     ▼  add() — exact integer sum
 *               subtotal (exact)
 *                     │
 *                     ▼  percentage(taxRate) — the ONE rounding point,
 *                        half-up to the nearest minor unit
 *                  tax (rounded once)
 *                     │
 *                     ▼  add() — exact integer sum
 *                 total
 *
 * Every operation upstream of percentage() is exact integer arithmetic.
 * Nothing else in this class rounds.
 */
final readonly class Money
{
    public const string CURRENCY = 'EUR';

    private function __construct(public int $minorUnits) {}

    public static function fromMinorUnits(int $minorUnits): self
    {
        return new self($minorUnits);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->minorUnits + $other->minorUnits);
    }

    public function subtract(self $other): self
    {
        return new self($this->minorUnits - $other->minorUnits);
    }

    /**
     * Exact integer multiplication — a quantity is a count, never a
     * fraction. A numeric string is accepted because Eloquent returns
     * decimal columns as strings, but it must represent a whole number.
     */
    public function multiplyBy(int|string $quantity): self
    {
        $factor = is_string($quantity) ? self::wholeNumberFromString($quantity) : $quantity;

        return new self($this->minorUnits * $factor);
    }

    /**
     * Apply a decimal rate (e.g. "0.25" for 25%), half-up to the nearest
     * minor unit. The only rounding operation in this class. Computed with
     * exact integer arithmetic — never floats — so the same rate always
     * produces the same result regardless of platform.
     */
    public function percentage(string $rate): self
    {
        [$numerator, $scale] = self::decimalToFraction($rate);

        return new self(self::divideHalfUp($this->minorUnits * $numerator, $scale));
    }

    private static function wholeNumberFromString(string $value): int
    {
        if (! preg_match('/^-?\d+$/', $value)) {
            throw new InvalidArgumentException("Money::multiplyBy() requires a whole-number quantity, got \"{$value}\".");
        }

        return (int) $value;
    }

    /**
     * @return array{0: int, 1: int} the rate as numerator/scale, e.g.
     *                               "0.25" → [25, 100]
     */
    private static function decimalToFraction(string $rate): array
    {
        if (! preg_match('/^\d+(\.\d+)?$/', $rate)) {
            throw new InvalidArgumentException("Money::percentage() requires a non-negative decimal rate, got \"{$rate}\".");
        }

        [$whole, $decimals] = array_pad(explode('.', $rate, 2), 2, '');
        $scale = 10 ** strlen($decimals);
        $numerator = (int) ($whole.$decimals);

        return [$numerator, $scale];
    }

    private static function divideHalfUp(int $numerator, int $scale): int
    {
        $quotient = intdiv($numerator, $scale);
        $remainder = $numerator % $scale;

        return $remainder * 2 >= $scale ? $quotient + 1 : $quotient;
    }
}
