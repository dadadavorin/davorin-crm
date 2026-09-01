<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Models\Quote;

/**
 * Nothing depends on a quote, so unlike `DeleteDeal` this is never refused.
 */
final class DeleteQuote
{
    public function handle(Quote $quote): void
    {
        $quote->delete();
    }
}
