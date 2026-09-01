<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Company;
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
