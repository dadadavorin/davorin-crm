<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Models\Company;

/**
 * Exercises `DeleteCompany`'s dependent-check branch before any real
 * dependent relation (contacts, deals) exists. T6 and T7 give
 * `Company::dependentCounts()` real cases; this fixture stands in for that
 * until then.
 */
class CompanyWithDependents extends Company
{
    protected $table = 'companies';

    public function dependentCounts(): array
    {
        return ['contacts' => 2, 'deals' => 1];
    }
}
