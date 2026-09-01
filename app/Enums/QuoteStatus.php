<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\BoardStatus;
use App\Enums\Concerns\HasTransitions;

/**
 * Draft → Sent → Accepted|Rejected|Expired. `Accepted`, `Rejected` and
 * `Expired` are terminal in the enum's own transition graph — the board
 * (`MoveCardAction::canTransitionTo()`) refuses every drag out of them.
 *
 *   Draft ──▶ Sent ─┬─▶ Accepted  (terminal)
 *                    ├─▶ Rejected  (terminal)
 *                    └─▶ Expired   (terminal, set by a scheduled command,
 *                                   never derived — see ExpireQuotes)
 *                         ▲
 *                         │
 *             explicit reopen action only, back to Sent,
 *             never a drag (Quote::booted() guard)
 *
 * Reopening a terminal quote to `Sent` is deliberately not a transition this
 * enum allows — it stays possible only because `Quote`'s model-level guard
 * carves out that one case for itself (`ReopenQuote`), the same shape as
 * `Deal`/`DealStage` (T7). `Quote::booted()` also enforces that `Sent` can
 * never revert to `Draft` directly, since nothing here would otherwise stop
 * an edit from doing so.
 */
enum QuoteStatus: string implements BoardStatus
{
    use HasTransitions;

    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Accepted, self::Rejected, self::Expired],
            self::Accepted, self::Rejected, self::Expired => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function boardOrder(): int
    {
        return match ($this) {
            self::Draft => 1,
            self::Sent => 2,
            self::Accepted => 3,
            self::Rejected => 4,
            self::Expired => 5,
        };
    }
}
