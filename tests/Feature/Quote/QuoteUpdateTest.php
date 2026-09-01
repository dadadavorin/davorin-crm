<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'issue_date' => '2026-02-01',
            'valid_until' => '2026-03-01',
            'tax_rate' => '0.2500',
            'notes' => 'Updated notes',
            'items' => [
                ['description' => 'Support', 'quantity' => 3, 'unit_price' => '20.00'],
            ],
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $quote = Quote::factory()->create();

        $this->put(route('quotes.update', $quote), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_a_draft_quotes_items_can_be_replaced_and_totals_recompute(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        QuoteItem::factory()->for($quote)->create([
            'quantity' => 1,
            'unit_price_minor' => Money::fromMinorUnits(1000),
            'line_total_minor' => Money::fromMinorUnits(1000),
        ]);

        $this->actingAs($user)->put(route('quotes.update', $quote), $this->validPayload());

        $fresh = $quote->fresh();
        $this->assertCount(1, $fresh->items);
        $this->assertSame('Support', $fresh->items->first()->description);
        $this->assertSame(6_000, $fresh->subtotal_minor->minorUnits);
        $this->assertSame(1_500, $fresh->tax_minor->minorUnits);
        $this->assertSame(7_500, $fresh->total_minor->minorUnits);
    }

    public function test_notes_and_terms_can_be_updated_regardless_of_status(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $payload = $this->validPayload();
        unset($payload['items']);
        $payload['notes'] = 'Sent-quote note';
        $payload['terms'] = 'Net 30';

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), $payload)
            ->assertRedirect(route('quotes.show', $quote));

        $fresh = $quote->fresh();
        $this->assertSame('Sent-quote note', $fresh->notes);
        $this->assertSame('Net 30', $fresh->terms);
    }

    public function test_submitting_items_for_a_sent_quote_is_refused(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        QuoteItem::factory()->for($quote)->create();
        $quote->update(['status' => QuoteStatus::Sent]);

        $countBefore = $quote->items()->count();

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('quote_not_editable');

        $this->assertSame($countBefore, $quote->fresh()->items()->count());
    }

    public function test_changing_the_tax_rate_on_a_sent_quote_is_refused(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create(['tax_rate' => '0.2500']);

        $payload = $this->validPayload();
        unset($payload['items']);
        $payload['tax_rate'] = '0.1000';

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('quote_not_editable');

        $this->assertSame('0.2500', $quote->fresh()->tax_rate);
    }

    public function test_a_sent_quote_can_move_forward_to_accepted_via_the_edit_form(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $payload = $this->validPayload();
        unset($payload['items']);
        $payload['status'] = QuoteStatus::Accepted->value;

        $this->actingAs($user)->put(route('quotes.update', $quote), $payload);

        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_a_sent_quote_cannot_revert_to_draft(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $payload = $this->validPayload();
        unset($payload['items']);
        $payload['status'] = QuoteStatus::Draft->value;

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('illegal_status_transition');

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_a_soft_deleted_quote_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();
        $quote->delete();

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), $this->validPayload())
            ->assertNotFound();
    }

    public function test_valid_until_before_issue_date_is_rejected(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create();

        $payload = $this->validPayload();
        $payload['issue_date'] = '2026-05-01';
        $payload['valid_until'] = '2026-04-01';

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), $payload)
            ->assertSessionHasErrors('valid_until');
    }
}
