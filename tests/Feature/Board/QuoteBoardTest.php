<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('quotes.board'))->assertRedirect(route('login'));
    }

    public function test_columns_render_in_board_order_with_labels(): void
    {
        $user = User::factory()->create();
        Quote::factory()->status(QuoteStatus::Sent)->create();
        Quote::factory()->status(QuoteStatus::Draft)->create();

        $this->actingAs($user)
            ->get(route('quotes.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('quotes/board')
                ->has('columns', 5)
                ->where('columns.0.status', QuoteStatus::Draft->value)
                ->where('columns.1.status', QuoteStatus::Sent->value)
                ->where('columns.2.status', QuoteStatus::Accepted->value)
                ->where('columns.3.status', QuoteStatus::Rejected->value)
                ->where('columns.4.status', QuoteStatus::Expired->value));
    }

    public function test_a_soft_deleted_quote_never_appears_on_the_board(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Draft)->create();
        $quote->delete();

        $this->actingAs($user)
            ->get(route('quotes.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('columns.0.cards', []));
    }

    public function test_a_card_carries_its_deal_title(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Draft)->create();

        $this->actingAs($user)
            ->get(route('quotes.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns.0.cards.0.deal.title', $quote->deal->title));
    }

    /**
     * Load-bearing (T5, reused here): the board issues a fixed number of
     * queries no matter how many cards it renders.
     */
    public function test_the_board_query_count_is_constant_regardless_of_card_count(): void
    {
        $user = User::factory()->create();
        Quote::factory()->count(3)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('quotes.board'))->assertOk();
        $smallQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        Quote::factory()->count(60)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('quotes.board'))->assertOk();
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($smallQueryCount, $largeQueryCount);
    }
}
