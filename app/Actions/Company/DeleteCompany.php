<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Exceptions\RecordHasDependentsException;
use App\Models\Company;

/**
 * Refuses to delete a company that live records still depend on (ADR-0005).
 * `Company::dependentCounts()` is empty today — contacts (T6) and deals
 * (T7) add entries there, not new logic here.
 */
final class DeleteCompany
{
    public function handle(Company $company): void
    {
        $dependents = $company->dependentCounts();

        if ($dependents !== []) {
            throw new RecordHasDependentsException('company', $dependents);
        }

        $company->delete();
    }
}
