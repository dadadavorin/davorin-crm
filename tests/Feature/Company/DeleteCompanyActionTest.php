<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Actions\Company\DeleteCompany;
use App\Exceptions\RecordHasDependentsException;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\CompanyWithDependents;
use Tests\TestCase;

/**
 * `Company::dependentCounts()` had no real case until T6 gave it one
 * (contacts); T7 (deals) adds a second. `Tests\Fixtures\CompanyWithDependents`
 * pre-dates any real dependent and is kept here to exercise the multi-type
 * message format the real cases don't happen to combine on their own.
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

    public function test_a_company_with_live_contacts_is_refused(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->count(2)->create(['company_id' => $company->id]);

        $this->expectException(RecordHasDependentsException::class);
        $this->expectExceptionMessage('Cannot delete this company: 2 live contacts depend on it.');

        (new DeleteCompany)->handle($company->fresh());
    }

    public function test_a_company_with_a_single_live_contact_is_refused_with_a_singular_message(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->create(['company_id' => $company->id]);

        $this->expectException(RecordHasDependentsException::class);
        $this->expectExceptionMessage('Cannot delete this company: 1 live contact depend on it.');

        (new DeleteCompany)->handle($company->fresh());
    }

    public function test_a_company_with_only_soft_deleted_contacts_is_deleted(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);
        $contact->delete();

        (new DeleteCompany)->handle($company->fresh());

        $this->assertSoftDeleted($company);
    }

    public function test_a_company_with_live_deals_is_refused(): void
    {
        $company = Company::factory()->create();
        Deal::factory()->count(2)->create(['company_id' => $company->id]);

        $this->expectException(RecordHasDependentsException::class);
        $this->expectExceptionMessage('Cannot delete this company: 2 live deals depend on it.');

        (new DeleteCompany)->handle($company->fresh());
    }

    public function test_a_company_with_live_contacts_and_deals_names_both_in_the_message(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->create(['company_id' => $company->id]);
        Deal::factory()->create(['company_id' => $company->id]);

        $this->expectException(RecordHasDependentsException::class);
        $this->expectExceptionMessage('Cannot delete this company: 1 live contact and 1 live deal depend on it.');

        (new DeleteCompany)->handle($company->fresh());
    }

    public function test_a_company_with_only_soft_deleted_deals_is_deleted(): void
    {
        $company = Company::factory()->create();
        $deal = Deal::factory()->create(['company_id' => $company->id]);
        $deal->delete();

        (new DeleteCompany)->handle($company->fresh());

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
