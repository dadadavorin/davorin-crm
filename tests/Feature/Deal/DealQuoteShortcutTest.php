<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * `POST /deals/{deal}/quotes` — the deal-board shortcut (T9). An additional
 * entry point onto `CreateQuote`'s snapshot mechanics (T8), never a
 * different one, so this covers only what the shortcut adds: no items to
 * submit, defaults for the fields it doesn't ask for, and the deal-scoped
 * routing (soft-deleted deal, authorization).
 */
class DealQuoteShortcutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $deal = Deal::factory()->create();

        $this->post(route('deals.quotes.store', $deal))
            ->assertRedirect(route('login'));
    }

    public function test_the_shortcut_creates_a_linked_draft_quote_with_the_deals_company_and_contact_snapshotted(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Acme d.o.o.', 'address' => 'Ilica 1, Zagreb']);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'first_name' => 'Ivana', 'last_name' => 'Horvat', 'email' => 'ivana@example.com']);
        $deal = Deal::factory()->create(['company_id' => $company->id, 'primary_contact_id' => $contact->id]);

        $response = $this->actingAs($user)->post(route('deals.quotes.store', $deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $response->assertRedirect(route('quotes.edit', $quote));

        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame($deal->id, $quote->deal_id);
        $this->assertCount(0, $quote->items);
        $this->assertSame('Acme d.o.o.', $quote->bill_to_company_name);
        $this->assertSame('Ilica 1, Zagreb', $quote->bill_to_address);
        $this->assertSame('Ivana Horvat', $quote->bill_to_contact_name);
        $this->assertSame('ivana@example.com', $quote->bill_to_contact_email);
    }

    public function test_the_shortcut_prefills_a_default_valid_until_and_tax_rate(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('deals.quotes.store', $deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertSame('0.2500', $quote->tax_rate);
        $this->assertSame(now()->addDays(30)->toDateString(), $quote->valid_until->toDateString());
        $this->assertSame(now()->toDateString(), $quote->issue_date->toDateString());
    }

    public function test_a_chosen_valid_until_and_tax_rate_override_the_defaults(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('deals.quotes.store', $deal), [
            'valid_until' => '2030-01-01',
            'tax_rate' => '0.1000',
        ]);

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertSame('2030-01-01', $quote->valid_until->toDateString());
        $this->assertSame('0.1000', $quote->tax_rate);
    }

    public function test_a_malformed_tax_rate_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('deals.quotes.store', $deal), ['tax_rate' => '-0.25'])
            ->assertSessionHasErrors('tax_rate');

        $this->assertSame(0, Quote::query()->where('deal_id', $deal->id)->count());
    }

    public function test_a_soft_deleted_deal_is_not_found(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();
        $deal->delete();

        $this->actingAs($user)
            ->post(route('deals.quotes.store', $deal))
            ->assertNotFound();
    }

    public function test_a_user_without_the_create_ability_receives_403(): void
    {
        // QuotePolicy::create() is unconditionally true today — there is no
        // real user for whom this shortcut is currently forbidden. Swapping
        // in a deny-everything policy exercises the authorization branch the
        // controller really has, the same way BoardMoveControllerTest does
        // for CompanyPolicy::update.
        $denyAll = new class
        {
            public function create(User $user): bool
            {
                return false;
            }
        };

        Gate::policy(Quote::class, $denyAll::class);

        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('deals.quotes.store', $deal))
            ->assertForbidden();
    }

    public function test_double_submitting_the_shortcut_creates_two_independent_draft_quotes(): void
    {
        // The shortcut has no server-side dedup key, and standalone create
        // (T8) already allows a deal to carry more than one quote (e.g. a
        // revision) — so two requests are two quotes. The double-submit
        // guarantee named in the task lives on the client: the modal's
        // submit control disables itself while a request is in flight, so a
        // second click never reaches the server as a second request. See
        // the Vitest coverage on the dialog component for that guarantee.
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('deals.quotes.store', $deal));
        $this->actingAs($user)->post(route('deals.quotes.store', $deal));

        $this->assertSame(2, Quote::query()->where('deal_id', $deal->id)->count());
    }

    public function test_the_created_quote_appears_on_the_quotes_board_in_draft(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('deals.quotes.store', $deal));
        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('quotes.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns.0.status', QuoteStatus::Draft->value)
                ->where('columns.0.cards.0.id', $quote->id));
    }

    public function test_the_deal_detail_page_lists_its_quotes(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('deals.quotes.store', $deal));
        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('deals.show', $deal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deals/show')
                ->has('quotes', 1)
                ->where('quotes.0.id', $quote->id)
                ->where('quotes.0.status', 'draft'));
    }
}
