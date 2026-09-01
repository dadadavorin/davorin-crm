<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteNotEditableException;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Support\Money;
use InvalidArgumentException;

/**
 * Replaces a quote's entire item set — `CreateQuote` and `UpdateQuote` share
 * this rather than diffing individual rows, since a quote's line items are
 * always edited as one set (the inline editor submits the whole array) and
 * a full replace keeps `sort_order` trivially correct.
 *
 * Rejects outright once the quote has left `Draft`, checked here before
 * anything else: the replace starts with a bulk `delete()` on the items
 * relation, which — unlike a single model's own `delete()` — does not fire
 * `QuoteItem::booted()`'s per-row `deleting` guard. This is the check that
 * actually stops that bulk path; `QuoteItem::booted()` is what stops a
 * direct `save()`/`delete()` on one row instead.
 */
final class ReplaceQuoteItems
{
    /**
     * @param  mixed  $items  the validated `items` shape from `StoreQuoteRequest`/`UpdateQuoteRequest` — each entry an array with string `description`, numeric `quantity` and string `unit_price`
     */
    public function handle(Quote $quote, mixed $items): void
    {
        if ($quote->status !== QuoteStatus::Draft) {
            throw new QuoteNotEditableException($quote->id);
        }

        if (! is_array($items)) {
            throw new InvalidArgumentException('Quote items must be an array.');
        }

        $quote->items()->delete();

        $sortOrder = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Each quote item must be an array.');
            }

            $description = $item['description'] ?? null;
            $quantity = $item['quantity'] ?? null;
            $unitPriceRaw = $item['unit_price'] ?? null;

            if (! is_string($description) || ! is_numeric($quantity) || ! is_string($unitPriceRaw)) {
                throw new InvalidArgumentException('Malformed quote item.');
            }

            $unitPrice = Money::fromDecimalString($unitPriceRaw);

            QuoteItem::query()->create([
                'quote_id' => $quote->id,
                'sort_order' => $sortOrder,
                'description' => $description,
                'quantity' => (int) $quantity,
                'unit_price_minor' => $unitPrice,
                'line_total_minor' => $unitPrice->multiplyBy((int) $quantity),
            ]);

            $sortOrder++;
        }
    }
}
