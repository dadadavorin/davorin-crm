<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('quotes.index'))->assertRedirect(route('login'));
    }

    public function test_quotes_are_listed(): void
    {
        $user = User::factory()->create();
        Quote::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('quotes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('quotes/index')
                ->has('quotes.data', 3));
    }

    public function test_searching_by_number_escapes_metacharacters(): void
    {
        $user = User::factory()->create();
        Quote::factory()->create(['number' => 'Q-2026-0001']);
        Quote::factory()->create(['number' => 'Q-2026-0002']);

        $this->actingAs($user)
            ->get(route('quotes.index', ['search' => '0001']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 1));
    }

    public function test_a_soft_deleted_quote_never_appears_in_the_index(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        $quote->delete();

        $this->actingAs($user)
            ->get(route('quotes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('quotes.data', 0));
    }
}
