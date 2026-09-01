<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $company = Company::factory()->create();

        $this->get(route('companies.show', $company))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_view_any_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Detail Corp']);

        $this->actingAs($user)
            ->get(route('companies.show', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/show')
                ->where('company.name', 'Detail Corp'));
    }

    public function test_the_company_page_lists_its_live_contacts(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        Contact::factory()->create(['company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'Babic']);
        $deleted = Contact::factory()->create(['company_id' => $company->id]);
        $deleted->delete();

        $this->actingAs($user)
            ->get(route('companies.show', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('contacts', 1)
                ->where('contacts.0.name', 'Ana Babic'));
    }

    public function test_the_company_page_shows_an_empty_contacts_list(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->get(route('companies.show', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('contacts', 0));
    }

    public function test_the_company_page_lists_its_deals(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        Deal::factory()->create(['company_id' => $company->id, 'title' => 'Renewal Deal']);
        $deleted = Deal::factory()->create(['company_id' => $company->id]);
        $deleted->delete();

        $this->actingAs($user)
            ->get(route('companies.show', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('deals', 1)
                ->where('deals.0.title', 'Renewal Deal'));
    }

    public function test_the_company_page_shows_an_empty_deals_list(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->get(route('companies.show', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('deals', 0));
    }

    public function test_a_soft_deleted_company_is_not_found(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->delete();

        $this->actingAs($user)
            ->get(route('companies.show', $company))
            ->assertNotFound();
    }
}
