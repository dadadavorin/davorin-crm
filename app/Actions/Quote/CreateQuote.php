<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Creates a standalone quote from a deal picker (T8) — the deal-board
 * shortcut (T9) is an additional entry point onto the same snapshot
 * mechanics, not a different creation path.
 */
final class CreateQuote
{
    private const int MAX_NUMBER_ATTEMPTS = 5;

    public function __construct(
        private readonly GenerateQuoteNumber $numbers,
        private readonly ReplaceQuoteItems $items,
        private readonly RecalculateQuoteTotals $totals,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated `StoreQuoteRequest` shape
     */
    public function handle(array $data, User $creator): Quote
    {
        for ($attempt = 1; $attempt <= self::MAX_NUMBER_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(fn (): Quote => $this->createQuote($data, $creator));
            } catch (QueryException $e) {
                // The sequence-backed number is already unique in practice;
                // `quotes.number`'s unique index is only a backstop
                // (CONVENTIONS.md §4) — never a pre-write existence check.
                if ($e->getCode() !== '23505' || $attempt === self::MAX_NUMBER_ATTEMPTS) {
                    throw $e;
                }
            }
        }

        throw new LogicException('Unreachable: the retry loop above always returns or throws.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createQuote(array $data, User $creator): Quote
    {
        $dealId = $data['deal_id'];

        if (! is_int($dealId) && ! is_string($dealId)) {
            throw new InvalidArgumentException('deal_id must be an integer or numeric string.');
        }

        $deal = Deal::query()->with(['company', 'primaryContact'])->findOrFail((int) $dealId);

        $quote = Quote::query()->create([
            'number' => $this->numbers->next(),
            'status' => QuoteStatus::Draft,
            'deal_id' => $deal->id,
            'issue_date' => $data['issue_date'],
            'valid_until' => $data['valid_until'],
            'tax_rate' => $data['tax_rate'],
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'owner_id' => $data['owner_id'] ?? $creator->id,
            'subtotal_minor' => Money::zero(),
            'tax_minor' => Money::zero(),
            'total_minor' => Money::zero(),
            ...SnapshotCustomerBlock::fromDeal($deal),
        ]);

        $this->items->handle($quote, $data['items'] ?? []);

        $this->totals->apply($quote);
        $quote->save();

        return $quote->refresh();
    }
}
