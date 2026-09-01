<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DealIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('deals.index'))->assertRedirect(route('login'));
    }

    public function test_the_first_page_returns_twenty_five_deals(): void
    {
        $user = User::factory()->create();
        Deal::factory()->count(30)->create();

        $this->actingAs($user)
            ->get(route('deals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/index')
                ->has('deals.data', 25)
                ->where('deals.total', 30));
    }

    public function test_search_escapes_a_literal_percent_sign(): void
    {
        $user = User::factory()->create();
        Deal::factory()->create(['title' => '50% Discount Renewal']);
        Deal::factory()->create(['title' => 'Ordinary Renewal']);

        $this->actingAs($user)
            ->get(route('deals.index', ['search' => '50%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('deals.data', 1)
                ->where('deals.data.0.title', '50% Discount Renewal'));
    }

    public function test_a_soft_deleted_deal_appears_in_no_listing(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['title' => 'Gone Deal']);
        $deal->delete();

        $this->actingAs($user)
            ->get(route('deals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('deals.data', 0));
    }
}
