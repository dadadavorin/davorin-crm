<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Formats the next value of the `quote_number_seq` Postgres sequence
 * (T8) as `Q-{year}-{0000}`. The sequence guarantees the number is unique
 * on its own; `quotes.number`'s unique index is still the backstop
 * `CreateQuote` translates a `23505` against, per `CONVENTIONS.md` §4 —
 * this class never checks-then-inserts.
 */
final class GenerateQuoteNumber
{
    public function next(): string
    {
        $row = DB::selectOne("select nextval('quote_number_seq') as value");

        if (! is_object($row) || ! isset($row->value) || ! is_numeric($row->value)) {
            throw new RuntimeException('quote_number_seq did not return a numeric value.');
        }

        return sprintf('Q-%s-%04d', Date::now()->format('Y'), (int) $row->value);
    }
}
