<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Models\Quote;

/**
 * The one explicit action that can move a quote off `Accepted`/`Rejected`/
 * `Expired`, back to `Sent` — never reachable by dragging
 * (`QuoteStatus::allowedTransitions()` keeps every terminal status on the
 * board). `Quote`'s own `saving` guard would accept this same write from
 * any caller; this action exists so the reopen is a deliberate, named
 * operation rather than an incidental edit, and so reopening a quote that
 * isn't actually terminal fails clearly. Mirrors `ReopenDeal` (T7).
 */
final class ReopenQuote
{
    public function handle(Quote $quote): Quote
    {
        if (! $quote->status->isTerminal()) {
            throw new IllegalStatusTransitionException($quote->status, QuoteStatus::Sent);
        }

        $quote->update(['status' => QuoteStatus::Sent]);

        return $quote;
    }
}
