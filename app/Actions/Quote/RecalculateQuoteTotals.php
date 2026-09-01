<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Support\Money;

/**
 * Recomputes a quote's stored totals from its line items — the load-bearing
 * guarantee the totals-invariant test checks: a fresh recomputation always
 * equals what is stored. Runs once per request, after the whole item set has
 * been written, inside the same transaction (`CreateQuote`, `UpdateQuote`).
 *
 *   quote_items.line_total_minor (each exact: quantity × unit price,
 *                                  already computed by Money::multiplyBy())
 *                     │
 *                     ▼  Money::add() summed over every item — exact,
 *                        no rounding
 *                subtotal
 *                     │
 *                     ▼  Money::percentage(quote.tax_rate) — the ONE
 *                        rounding point in the whole pipeline, half-up,
 *                        applied once at the document level
 *                    tax
 *                     │
 *                     ▼  Money::add() — exact
 *                  total
 *
 * `apply()` only sets attributes; it never saves. The caller combines it
 * into the same `save()` call as any other change to the quote (e.g. a
 * status transition), so `Quote::booted()`'s freeze guard — which reads the
 * status *before* this save — never sees a quote whose status has already
 * moved off `Draft` reject its own totals.
 */
final class RecalculateQuoteTotals
{
    public function apply(Quote $quote): void
    {
        $subtotal = $quote->items()->get()->reduce(
            fn (Money $carry, QuoteItem $item): Money => $carry->add($item->line_total_minor),
            Money::zero(),
        );

        $tax = $subtotal->percentage($quote->tax_rate);

        $quote->subtotal_minor = $subtotal;
        $quote->tax_minor = $tax;
        $quote->total_minor = $subtotal->add($tax);
    }
}
