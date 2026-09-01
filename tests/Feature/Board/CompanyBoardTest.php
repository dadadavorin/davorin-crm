<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('companies.board'))->assertRedirect(route('login'));
    }

    public function test_columns_render_in_board_order_with_labels(): void
    {
        $user = User::factory()->create();
        Company::factory()->create(['status' => CompanyStatus::Prospect]);
        Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->actingAs($user)
            ->get(route('companies.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/board')
                ->has('columns', 4)
                ->where('columns.0.status', CompanyStatus::Lead->value)
                ->where('columns.0.label', CompanyStatus::Lead->label())
                ->where('columns.1.status', CompanyStatus::Prospect->value)
                ->where('columns.2.status', CompanyStatus::Customer->value)
                ->where('columns.3.status', CompanyStatus::Inactive->value));
    }

    public function test_a_soft_deleted_company_never_appears_on_the_board(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);
        $company->delete();

        $this->actingAs($user)
            ->get(route('companies.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('columns.0.cards', []));
    }

    public function test_a_column_is_capped_at_fifty_cards_with_a_has_more_count(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(55)->create(['status' => CompanyStatus::Lead]);

        $this->actingAs($user)
            ->get(route('companies.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('columns.0.cards', 50)
                ->where('columns.0.total', 55)
                ->where('columns.0.has_more', true));
    }

    public function test_a_column_under_the_cap_reports_no_more(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(3)->create(['status' => CompanyStatus::Lead]);

        $this->actingAs($user)
            ->get(route('companies.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('columns.0.cards', 3)
                ->where('columns.0.has_more', false));
    }

    /**
     * Load-bearing (T5): the board issues a fixed number of queries no
     * matter how many cards it renders. Losing this test removes the only
     * guard against a missing eager load turning into one query per card.
     */
    public function test_the_board_query_count_is_constant_regardless_of_card_count(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(3)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('companies.board'))->assertOk();
        $smallQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        Company::factory()->count(60)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('companies.board'))->assertOk();
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($smallQueryCount, $largeQueryCount);
    }
}
