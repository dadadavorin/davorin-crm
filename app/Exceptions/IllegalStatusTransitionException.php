<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Concerns\BoardStatus;

/**
 * Thrown when a board move requests a status transition the enum's own
 * `allowedTransitions()` does not permit (`HasTransitions::canTransitionTo()`
 * returned false).
 */
final class IllegalStatusTransitionException extends DomainException
{
    public function __construct(BoardStatus $from, BoardStatus $to)
    {
        parent::__construct(sprintf(
            'Cannot move from "%s" to "%s": that transition is not allowed.',
            $from->label(),
            $to->label(),
        ));
    }
}
