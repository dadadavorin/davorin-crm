<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Models\Deal;

/**
 * Nothing depends on a deal yet (quotes, T8, will), so unlike
 * `DeleteCompany` this is never refused.
 */
final class DeleteDeal
{
    public function handle(Deal $deal): void
    {
        $deal->delete();
    }
}
