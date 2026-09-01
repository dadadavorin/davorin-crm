<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Enums\Concerns\HasTransitions;

/**
 * Exercises every branch of HasTransitions: an allowed transition, a
 * disallowed one, a same-status transition, and a terminal state.
 */
enum FixtureStatus: string
{
    use HasTransitions;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Active],
            self::Active => [self::Archived],
            self::Archived => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function boardOrder(): int
    {
        return match ($this) {
            self::Draft => 1,
            self::Active => 2,
            self::Archived => 3,
        };
    }
}
