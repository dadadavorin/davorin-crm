<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DealFormPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('deals.create'))->assertRedirect(route('login'));
    }

    public function test_the_create_page_lists_every_stage_owner_company_and_contact_option(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(2)->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('deals.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/create')
                ->has('stages', 6)
                ->has('owners', 1)
                ->has('companies', 2));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $deal = Deal::factory()->create();

        $this->get(route('deals.edit', $deal))->assertRedirect(route('login'));
    }

    public function test_the_edit_page_is_prefilled_with_the_deal(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['title' => 'Editable']);

        $this->actingAs($user)
            ->get(route('deals.edit', $deal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/edit')
                ->where('deal.title', 'Editable'));
    }

    public function test_a_soft_deleted_deal_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();
        $deal->delete();

        $this->actingAs($user)
            ->get(route('deals.edit', $deal))
            ->assertNotFound();
    }
}
