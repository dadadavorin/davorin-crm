<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\BoardStatus;
use App\Enums\Concerns\HasTransitions;

/**
 * Lead → Prospect → Customer → Inactive, and Inactive → Lead. No terminal
 * state: every status has somewhere to go next.
 *
 *   Lead ──▶ Prospect ──▶ Customer ──▶ Inactive
 *    ▲                                    │
 *    └────────────────────────────────────┘
 */
enum CompanyStatus: string implements BoardStatus
{
    use HasTransitions;

    case Lead = 'lead';
    case Prospect = 'prospect';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Lead => [self::Prospect],
            self::Prospect => [self::Customer],
            self::Customer => [self::Inactive],
            self::Inactive => [self::Lead],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Prospect => 'Prospect',
            self::Customer => 'Customer',
            self::Inactive => 'Inactive',
        };
    }

    public function boardOrder(): int
    {
        return match ($this) {
            self::Lead => 1,
            self::Prospect => 2,
            self::Customer => 3,
            self::Inactive => 4,
        };
    }
}
