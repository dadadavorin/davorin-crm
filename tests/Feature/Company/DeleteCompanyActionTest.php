<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Actions\Company\DeleteCompany;
use App\Exceptions\RecordHasDependentsException;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\CompanyWithDependents;
use Tests\TestCase;

/**
 * `Company::dependentCounts()` is empty until T6 (contacts) and T7 (deals)
 * give it real cases; `Tests\Fixtures\CompanyWithDependents` stands in for
 * that so the refusal branch is proven now rather than deferred.
 */
class DeleteCompanyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_with_no_dependents_is_deleted(): void
    {
        $company = Company::factory()->create();

        (new DeleteCompany)->handle($company);

        $this->assertSoftDeleted($company);
    }

    public function test_a_company_with_live_dependents_is_refused(): void
    {
        $company = CompanyWithDependents::query()->findOrFail(Company::factory()->create()->id);

        $this->expectException(RecordHasDependentsException::class);
        $this->expectExceptionMessage('Cannot delete this company: 2 live contacts and 1 live deal depend on it.');

        (new DeleteCompany)->handle($company);
    }

    public function test_a_company_with_live_dependents_is_not_deleted(): void
    {
        $company = CompanyWithDependents::query()->findOrFail(Company::factory()->create()->id);

        try {
            (new DeleteCompany)->handle($company);
        } catch (RecordHasDependentsException) {
            // expected
        }

        $this->assertNotSoftDeleted($company);
    }
}
