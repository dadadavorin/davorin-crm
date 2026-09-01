<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\BoardStatus;
use App\Enums\Concerns\HasTransitions;

/**
 * New → Active → Inactive, and Inactive → Active. No terminal state: every
 * status has somewhere to go next.
 *
 *   New ──▶ Active ──▶ Inactive
 *            ▲             │
 *            └─────────────┘
 */
enum ContactStatus: string implements BoardStatus
{
    use HasTransitions;

    case New = 'new';
    case Active = 'active';
    case Inactive = 'inactive';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Active],
            self::Active => [self::Inactive],
            self::Inactive => [self::Active],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function boardOrder(): int
    {
        return match ($this) {
            self::New => 1,
            self::Active => 2,
            self::Inactive => 3,
        };
    }
}
