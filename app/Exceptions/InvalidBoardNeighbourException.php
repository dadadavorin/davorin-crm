<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a board move names a neighbour id that isn't a live card in
 * the target column — stale client state (the neighbour moved, was
 * deleted, or was never in that column to begin with).
 */
final class InvalidBoardNeighbourException extends DomainException
{
    public function __construct(int $neighbourId)
    {
        parent::__construct("Card {$neighbourId} is not in the target column.");
    }
}
