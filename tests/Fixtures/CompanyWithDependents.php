<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Models\Company;

/**
 * A fixed, hand-picked count pair for asserting the exact
 * `RecordHasDependentsException` message shape independent of whatever
 * counts the real `contacts`/`deals` cases happen to use elsewhere.
 */
class CompanyWithDependents extends Company
{
    protected $table = 'companies';

    public function dependentCounts(): array
    {
        return ['contacts' => 2, 'deals' => 1];
    }
}
