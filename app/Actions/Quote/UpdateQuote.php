<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;

/**
 * Updates a quote. `notes`, `terms`, `owner_id`, the dates and a legal
 * `status` transition (including the terminal-to-`Sent` reopen) can change
 * regardless of status. `tax_rate` and the totals are recomputed
 * unconditionally too, but harmlessly: once a quote has left `Draft` its
 * items can't change, so recomputing from the same items and the same
 * `tax_rate` reproduces the exact values already stored and touches
 * nothing. `items`, when present in `$data`, always goes to
 * `ReplaceQuoteItems` — which is what actually refuses the write once the
 * quote is no longer `Draft` (T8). This action never second-guesses that by
 * skipping the call itself.
 *
 * Every attribute is set on the model before the single `save()` call at the
 * end, so `Quote::booted()`'s freeze guard — which reads the status as it
 * was *before* this write — sees one consistent transition rather than an
 * intermediate state that doesn't actually exist.
 */
final class UpdateQuote
{
    public function __construct(
        private readonly ReplaceQuoteItems $items,
        private readonly RecalculateQuoteTotals $totals,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated `UpdateQuoteRequest` shape
     */
    public function handle(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data): Quote {
            if (array_key_exists('items', $data)) {
                $this->items->handle($quote, $data['items']);
            }

            $quote->fill([
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'],
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'owner_id' => $data['owner_id'] ?? $quote->owner_id,
            ]);

            if (! empty($data['status'])) {
                $quote->fill(['status' => $data['status']]);
            }

            $this->totals->apply($quote);

            $quote->save();

            return $quote->refresh();
        });
    }
}
