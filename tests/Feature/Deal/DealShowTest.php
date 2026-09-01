<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DealShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $deal = Deal::factory()->create();

        $this->get(route('deals.show', $deal))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_view_any_deal(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['title' => 'Detail Deal']);

        $this->actingAs($user)
            ->get(route('deals.show', $deal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/show')
                ->where('deal.title', 'Detail Deal'));
    }

    public function test_a_deal_with_no_primary_contact_renders_correctly(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['primary_contact_id' => null]);

        $this->actingAs($user)
            ->get(route('deals.show', $deal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('deal.primary_contact', null));
    }

    public function test_a_soft_deleted_deal_is_not_found(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();
        $deal->delete();

        $this->actingAs($user)
            ->get(route('deals.show', $deal))
            ->assertNotFound();
    }
}
