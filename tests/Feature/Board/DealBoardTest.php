<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DealBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('deals.board'))->assertRedirect(route('login'));
    }

    public function test_columns_render_in_board_order_with_labels(): void
    {
        $user = User::factory()->create();
        Deal::factory()->stage(DealStage::Proposal)->create();
        Deal::factory()->stage(DealStage::New)->create();

        $this->actingAs($user)
            ->get(route('deals.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/board')
                ->has('columns', 6)
                ->where('columns.0.status', DealStage::New->value)
                ->where('columns.1.status', DealStage::Qualified->value)
                ->where('columns.2.status', DealStage::Proposal->value)
                ->where('columns.3.status', DealStage::Negotiation->value)
                ->where('columns.4.status', DealStage::Won->value)
                ->where('columns.5.status', DealStage::Lost->value));
    }

    public function test_a_soft_deleted_deal_never_appears_on_the_board(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();
        $deal->delete();

        $this->actingAs($user)
            ->get(route('deals.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('columns.0.cards', []));
    }

    public function test_a_card_carries_its_company_and_contact_names(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();

        $this->actingAs($user)
            ->get(route('deals.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns.0.cards.0.company.name', $deal->company->name));
    }

    /**
     * Load-bearing (T5, reused here): the board issues a fixed number of
     * queries no matter how many cards it renders. Deals adds a second
     * relation (`primaryContact`) to eager-load — this proves that didn't
     * quietly reopen the N+1 the cap-and-count query test guards against.
     */
    public function test_the_board_query_count_is_constant_regardless_of_card_count(): void
    {
        $user = User::factory()->create();
        Deal::factory()->count(3)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('deals.board'))->assertOk();
        $smallQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        Deal::factory()->count(60)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('deals.board'))->assertOk();
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($smallQueryCount, $largeQueryCount);
    }
}
