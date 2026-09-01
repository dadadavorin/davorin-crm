<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Deal $deal): array
    {
        return [
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => [
                ['description' => 'Consulting', 'quantity' => 2, 'unit_price' => '100.00'],
                ['description' => 'Onboarding', 'quantity' => 1, 'unit_price' => '49.99'],
            ],
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $deal = Deal::factory()->create();

        $this->post(route('quotes.store'), $this->validPayload($deal))
            ->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_create_a_standalone_quote_with_a_deal_picker(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $response = $this->actingAs($user)->post(route('quotes.store'), $this->validPayload($deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $response->assertRedirect(route('quotes.show', $quote));

        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame($deal->id, $quote->deal_id);
        $this->assertCount(2, $quote->items);
    }

    public function test_the_quote_number_is_formatted_q_year_four_digits(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('quotes.store'), $this->validPayload($deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^Q-\d{4}-\d{4,}$/', $quote->number);
    }

    public function test_the_customer_block_is_snapshotted_from_the_deals_company_and_primary_contact(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Acme d.o.o.', 'address' => 'Ilica 1, Zagreb']);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'first_name' => 'Ivana', 'last_name' => 'Horvat', 'email' => 'ivana@example.com']);
        $deal = Deal::factory()->create(['company_id' => $company->id, 'primary_contact_id' => $contact->id]);

        $this->actingAs($user)->post(route('quotes.store'), $this->validPayload($deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertSame('Acme d.o.o.', $quote->bill_to_company_name);
        $this->assertSame('Ilica 1, Zagreb', $quote->bill_to_address);
        $this->assertSame('Ivana Horvat', $quote->bill_to_contact_name);
        $this->assertSame('ivana@example.com', $quote->bill_to_contact_email);
    }

    public function test_a_deal_with_no_primary_contact_snapshots_a_null_contact_block(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create(['primary_contact_id' => null]);

        $this->actingAs($user)->post(route('quotes.store'), $this->validPayload($deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertNull($quote->bill_to_contact_name);
        $this->assertNull($quote->bill_to_contact_email);
    }

    public function test_a_zero_item_quote_has_zero_totals(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('quotes.store'), [
            ...$this->validPayload($deal),
            'items' => [],
        ]);

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertSame(0, $quote->subtotal_minor->minorUnits);
        $this->assertSame(0, $quote->tax_minor->minorUnits);
        $this->assertSame(0, $quote->total_minor->minorUnits);
    }

    public function test_totals_are_computed_from_items_and_the_tax_rate(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('quotes.store'), $this->validPayload($deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();

        // 2 * 100.00 + 1 * 49.99 = 249.99 -> 24999 minor units.
        $this->assertSame(24_999, $quote->subtotal_minor->minorUnits);
        // 24999 * 0.25 = 6249.75 -> half-up -> 6250.
        $this->assertSame(6_250, $quote->tax_minor->minorUnits);
        $this->assertSame(31_249, $quote->total_minor->minorUnits);
    }

    public function test_deal_id_is_required(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();
        $payload = $this->validPayload($deal);
        unset($payload['deal_id']);

        $this->actingAs($user)
            ->post(route('quotes.store'), $payload)
            ->assertSessionHasErrors('deal_id');
    }

    public function test_an_unknown_deal_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('quotes.store'), [...$this->validPayload($deal), 'deal_id' => 999_999])
            ->assertSessionHasErrors('deal_id');
    }

    public function test_omitting_the_items_key_entirely_creates_a_zero_item_quote(): void
    {
        // A plain HTML form has no way to submit an explicit empty array —
        // omitting the key is how the inline editor expresses "no items".
        $user = User::factory()->create();
        $deal = Deal::factory()->create();
        $payload = $this->validPayload($deal);
        unset($payload['items']);

        $this->actingAs($user)->post(route('quotes.store'), $payload);

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertCount(0, $quote->items);
        $this->assertSame(0, $quote->subtotal_minor->minorUnits);
    }

    public function test_a_zero_quantity_item_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('quotes.store'), [
                ...$this->validPayload($deal),
                'items' => [['description' => 'x', 'quantity' => 0, 'unit_price' => '10.00']],
            ])
            ->assertSessionHasErrors('items.0.quantity');
    }

    public function test_a_negative_quantity_item_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('quotes.store'), [
                ...$this->validPayload($deal),
                'items' => [['description' => 'x', 'quantity' => -1, 'unit_price' => '10.00']],
            ])
            ->assertSessionHasErrors('items.0.quantity');
    }

    public function test_a_malformed_unit_price_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('quotes.store'), [
                ...$this->validPayload($deal),
                'items' => [['description' => 'x', 'quantity' => 1, 'unit_price' => '12.345']],
            ])
            ->assertSessionHasErrors('items.0.unit_price');
    }

    public function test_a_malformed_tax_rate_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)
            ->post(route('quotes.store'), [...$this->validPayload($deal), 'tax_rate' => '-0.25'])
            ->assertSessionHasErrors('tax_rate');
    }

    public function test_the_creator_becomes_the_owner_when_none_is_given(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $this->actingAs($user)->post(route('quotes.store'), $this->validPayload($deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertSame($user->id, $quote->owner_id);
    }

    public function test_fifty_items_can_be_created(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $items = [];
        for ($i = 0; $i < 50; $i++) {
            $items[] = ['description' => "Item {$i}", 'quantity' => 1, 'unit_price' => '10.00'];
        }

        $this->actingAs($user)->post(route('quotes.store'), [...$this->validPayload($deal), 'items' => $items]);

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertCount(50, $quote->items);
        $this->assertSame(50_000, $quote->subtotal_minor->minorUnits);
    }
}
