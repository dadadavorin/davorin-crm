<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Exceptions\RecordHasDependentsException;
use App\Models\Deal;

/**
 * Refuses to delete a deal that live quotes still depend on (ADR-0005) —
 * `quotes.deal_id` is a required FK (T8), the same shape as
 * `DeleteCompany`.
 */
final class DeleteDeal
{
    public function handle(Deal $deal): void
    {
        $dependents = $deal->dependentCounts();

        if ($dependents !== []) {
            throw new RecordHasDependentsException('deal', $dependents);
        }

        $deal->delete();
    }
}
