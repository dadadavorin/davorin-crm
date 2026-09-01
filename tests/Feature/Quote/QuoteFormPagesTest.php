<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteFormPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('quotes.create'))->assertRedirect(route('login'));
    }

    public function test_the_create_page_lists_deal_options_for_the_picker(): void
    {
        $user = User::factory()->create();
        Deal::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('quotes.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('quotes/create')
                ->has('deals', 2));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $quote = Quote::factory()->create();

        $this->get(route('quotes.edit', $quote))->assertRedirect(route('login'));
    }

    public function test_the_edit_page_is_prefilled_with_the_quote(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['number' => 'Q-2026-0042']);

        $this->actingAs($user)
            ->get(route('quotes.edit', $quote))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('quotes/edit')
                ->where('quote.number', 'Q-2026-0042'));
    }

    public function test_a_soft_deleted_quote_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        $quote->delete();

        $this->actingAs($user)
            ->get(route('quotes.edit', $quote))
            ->assertNotFound();
    }
}
