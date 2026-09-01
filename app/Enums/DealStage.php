<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\BoardStatus;
use App\Enums\Concerns\HasTransitions;

/**
 * New → Qualified → Proposal → Negotiation → Won|Lost. Won and Lost are
 * terminal: `allowedTransitions()` returns nothing for either, so the board
 * (`MoveCardAction::canTransitionTo()`) refuses every drag out of them.
 *
 *   New ──▶ Qualified ──▶ Proposal ──▶ Negotiation ─┬─▶ Won  (terminal)
 *                                          ▲         └─▶ Lost (terminal)
 *                                          │
 *                              explicit reopen action only,
 *                              never a drag (Deal::booted() guard)
 *
 * Reopening a `Won`/`Lost` deal back to `Negotiation` is deliberately not a
 * transition this enum allows — it stays possible only because
 * `Deal`'s model-level guard carves out that one case for itself, not
 * because `canTransitionTo()` permits it. See `Deal::booted()`.
 */
enum DealStage: string implements BoardStatus
{
    use HasTransitions;

    case New = 'new';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Qualified],
            self::Qualified => [self::Proposal],
            self::Proposal => [self::Negotiation],
            self::Negotiation => [self::Won, self::Lost],
            self::Won, self::Lost => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function boardOrder(): int
    {
        return match ($this) {
            self::New => 1,
            self::Qualified => 2,
            self::Proposal => 3,
            self::Negotiation => 4,
            self::Won => 5,
            self::Lost => 6,
        };
    }
}
