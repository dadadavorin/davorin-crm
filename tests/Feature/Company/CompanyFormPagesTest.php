<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyFormPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('companies.create'))->assertRedirect(route('login'));
    }

    public function test_the_create_page_lists_every_status_and_owner_option(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('companies.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/create')
                ->has('statuses', 4)
                ->has('owners', 1));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $company = Company::factory()->create();

        $this->get(route('companies.edit', $company))->assertRedirect(route('login'));
    }

    public function test_the_edit_page_is_prefilled_with_the_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Editable Corp']);

        $this->actingAs($user)
            ->get(route('companies.edit', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/edit')
                ->where('company.name', 'Editable Corp'));
    }

    public function test_a_soft_deleted_company_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->delete();

        $this->actingAs($user)
            ->get(route('companies.edit', $company))
            ->assertNotFound();
    }
}
