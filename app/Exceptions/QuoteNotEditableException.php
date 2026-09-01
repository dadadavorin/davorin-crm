<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a write touches a quote's line items, money fields or
 * customer-block snapshot after it has left `Draft` (T8). Enforced by
 * `Quote::booted()` and `QuoteItem::booted()`, so no write path — including
 * a direct `save()` on either model — can bypass it.
 */
final class QuoteNotEditableException extends DomainException
{
    public function __construct(public readonly int $quoteId)
    {
        parent::__construct("Cannot edit quote #{$quoteId}: it is no longer a draft.");
    }
}
