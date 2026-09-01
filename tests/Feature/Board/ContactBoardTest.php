<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('contacts.board'))->assertRedirect(route('login'));
    }

    public function test_columns_render_in_board_order_with_labels(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['status' => ContactStatus::Active]);
        Contact::factory()->create(['status' => ContactStatus::New]);

        $this->actingAs($user)
            ->get(route('contacts.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/board')
                ->has('columns', 3)
                ->where('columns.0.status', ContactStatus::New->value)
                ->where('columns.0.label', ContactStatus::New->label())
                ->where('columns.1.status', ContactStatus::Active->value)
                ->where('columns.2.status', ContactStatus::Inactive->value));
    }

    public function test_a_soft_deleted_contact_never_appears_on_the_board(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['status' => ContactStatus::New]);
        $contact->delete();

        $this->actingAs($user)
            ->get(route('contacts.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('columns.0.cards', []));
    }

    public function test_a_contact_with_no_company_renders_correctly_on_the_board(): void
    {
        $user = User::factory()->create();
        Contact::factory()->withoutCompany()->create(['status' => ContactStatus::New]);

        $this->actingAs($user)
            ->get(route('contacts.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('columns.0.cards.0.company', null));
    }

    public function test_a_column_is_capped_at_fifty_cards_with_a_has_more_count(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(55)->create(['status' => ContactStatus::New]);

        $this->actingAs($user)
            ->get(route('contacts.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('columns.0.cards', 50)
                ->where('columns.0.total', 55)
                ->where('columns.0.has_more', true));
    }

    /**
     * The board endpoint issues a fixed number of queries no matter how
     * many cards it renders (ADR-0004) — the same guarantee T5 proved for
     * companies, now proven for the eager-load list contacts actually use.
     */
    public function test_the_board_query_count_is_constant_regardless_of_card_count(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(3)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('contacts.board'))->assertOk();
        $smallQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        Contact::factory()->count(60)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($user)->get(route('contacts.board'))->assertOk();
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($smallQueryCount, $largeQueryCount);
    }
}
