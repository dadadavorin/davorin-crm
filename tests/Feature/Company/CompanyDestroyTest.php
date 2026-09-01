<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $company = Company::factory()->create();

        $this->delete(route('companies.destroy', $company))
            ->assertRedirect(route('login'));
    }

    public function test_the_owner_can_delete_their_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        $this->assertSoftDeleted($company);
    }

    public function test_an_admin_can_delete_a_company_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        $this->assertSoftDeleted($company);
    }

    public function test_a_non_owner_non_admin_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['role' => UserRole::Member]);
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->delete(route('companies.destroy', $company))
            ->assertForbidden();

        $this->assertNotSoftDeleted($company);
    }

    public function test_a_company_with_live_contacts_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        Contact::factory()->count(2)->create(['company_id' => $company->id]);

        $this->actingAs($owner)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect()
            ->assertSessionHasErrors('record_has_dependents');

        $this->assertNotSoftDeleted($company);
    }

    public function test_a_company_with_live_deals_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        Deal::factory()->create(['company_id' => $company->id]);

        $this->actingAs($owner)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect()
            ->assertSessionHasErrors('record_has_dependents');

        $this->assertNotSoftDeleted($company);
    }
}
