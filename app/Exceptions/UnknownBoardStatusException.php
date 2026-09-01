<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a board move targets a status value the entity's enum has
 * no case for.
 */
final class UnknownBoardStatusException extends DomainException
{
    public function __construct(string $status)
    {
        parent::__construct("Unknown board status \"{$status}\".");
    }
}
