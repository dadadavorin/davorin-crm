<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Enums\DealStage;
use App\Exceptions\IllegalStatusTransitionException;
use App\Models\Deal;

/**
 * The one explicit action that can move a deal off `Won`/`Lost`, back to
 * `Negotiation` — never reachable by dragging (`DealStage::allowedTransitions()`
 * keeps both terminal on the board). `Deal`'s own `saving` guard would accept
 * this same write from any caller; this action exists so the reopen is a
 * deliberate, named operation rather than an incidental edit, and so
 * reopening a deal that isn't actually terminal fails clearly.
 */
final class ReopenDeal
{
    public function handle(Deal $deal): Deal
    {
        if (! $deal->stage->isTerminal()) {
            throw new IllegalStatusTransitionException($deal->stage, DealStage::Negotiation);
        }

        $deal->update(['stage' => DealStage::Negotiation]);

        return $deal;
    }
}
