<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('companies.index'))->assertRedirect(route('login'));
    }

    public function test_the_first_page_returns_twenty_five_companies(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(30)->create();

        $this->actingAs($user)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/index')
                ->has('companies.data', 25)
                ->where('companies.total', 30));
    }

    public function test_the_last_page_returns_the_remainder(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(30)->create();

        $this->actingAs($user)
            ->get(route('companies.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/index')
                ->has('companies.data', 5));
    }

    public function test_a_page_past_the_last_one_returns_no_rows(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('companies.index', ['page' => 99]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/index')
                ->has('companies.data', 0));
    }

    public function test_search_escapes_a_literal_percent_sign(): void
    {
        $user = User::factory()->create();
        Company::factory()->create(['name' => '50% Off Corp']);
        Company::factory()->create(['name' => 'Ordinary Corp']);

        $this->actingAs($user)
            ->get(route('companies.index', ['search' => '50%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('companies.data', 1)
                ->where('companies.data.0.name', '50% Off Corp'));
    }

    public function test_search_escapes_a_literal_underscore(): void
    {
        $user = User::factory()->create();
        Company::factory()->create(['name' => 'A_B Holdings']);
        Company::factory()->create(['name' => 'AXB Holdings']);

        $this->actingAs($user)
            ->get(route('companies.index', ['search' => 'A_B']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('companies.data', 1)
                ->where('companies.data.0.name', 'A_B Holdings'));
    }

    public function test_a_soft_deleted_company_appears_in_no_listing(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Gone Corp']);
        $company->delete();

        $this->actingAs($user)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('companies.data', 0));
    }
}
