<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $quote = Quote::factory()->create();

        $this->get(route('quotes.show', $quote))->assertRedirect(route('login'));
    }

    public function test_the_show_page_renders_the_quote_with_its_items(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['number' => 'Q-2026-0001']);
        QuoteItem::factory()->for($quote)->create(['description' => 'Line one']);

        $this->actingAs($user)
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('quotes/show')
                ->where('quote.number', 'Q-2026-0001')
                ->has('quote.items', 1)
                ->where('quote.items.0.description', 'Line one'));
    }

    public function test_a_soft_deleted_quote_returns_404(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        $quote->delete();

        $this->actingAs($user)
            ->get(route('quotes.show', $quote))
            ->assertNotFound();
    }
}
