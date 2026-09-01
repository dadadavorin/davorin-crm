<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Enums\QuoteStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Exceptions\QuoteNotEditableException;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `Quote::booted()` and `QuoteItem::booted()` enforce the freeze directly on
 * the models, not only in `UpdateQuoteRequest` or `UpdateQuote` — so a
 * direct `save()`/`delete()` that bypasses both is rejected too (T8).
 */
class QuoteFreezeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_direct_save_of_a_frozen_field_on_a_sent_quote_is_rejected(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $this->expectException(QuoteNotEditableException::class);

        $quote->bill_to_company_name = 'Someone Else d.o.o.';
        $quote->save();
    }

    public function test_a_direct_save_of_the_tax_rate_on_a_sent_quote_is_rejected(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create(['tax_rate' => '0.2500']);

        $this->expectException(QuoteNotEditableException::class);

        $quote->tax_rate = '0.1000';
        $quote->save();
    }

    public function test_a_direct_save_that_does_not_touch_a_frozen_field_is_unaffected(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $quote->notes = 'A note added after sending';
        $quote->save();

        $this->assertSame('A note added after sending', $quote->fresh()->notes);
        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_a_direct_update_of_an_item_on_a_sent_quote_is_rejected(): void
    {
        $quote = Quote::factory()->create();
        $item = QuoteItem::factory()->for($quote)->create();
        $quote->update(['status' => QuoteStatus::Sent]);

        $this->expectException(QuoteNotEditableException::class);

        $item->description = 'Changed after sending';
        $item->save();
    }

    public function test_a_direct_update_of_an_item_does_not_persist(): void
    {
        $quote = Quote::factory()->create();
        $item = QuoteItem::factory()->for($quote)->create(['description' => 'Original']);
        $quote->update(['status' => QuoteStatus::Sent]);

        try {
            $item->description = 'Changed after sending';
            $item->save();
        } catch (QuoteNotEditableException) {
            // expected
        }

        $this->assertSame('Original', $item->fresh()->description);
    }

    public function test_a_direct_delete_of_an_item_on_a_sent_quote_is_rejected(): void
    {
        $quote = Quote::factory()->create();
        $item = QuoteItem::factory()->for($quote)->create();
        $quote->update(['status' => QuoteStatus::Sent]);

        $this->expectException(QuoteNotEditableException::class);

        $item->delete();
    }

    public function test_creating_a_new_item_on_a_sent_quote_directly_is_rejected(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $this->expectException(QuoteNotEditableException::class);

        QuoteItem::factory()->for($quote)->create();
    }

    public function test_items_can_still_be_created_and_edited_while_draft(): void
    {
        $quote = Quote::factory()->create();

        $item = QuoteItem::factory()->for($quote)->create();
        $item->description = 'Edited while draft';
        $item->save();

        $this->assertSame('Edited while draft', $item->fresh()->description);
    }

    public function test_a_direct_save_off_a_terminal_status_to_a_non_sent_status_is_rejected(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Accepted)->create();

        $this->expectException(IllegalStatusTransitionException::class);

        $quote->status = QuoteStatus::Draft;
        $quote->save();
    }

    public function test_a_direct_save_off_a_terminal_status_to_sent_is_accepted(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Rejected)->create();

        $quote->status = QuoteStatus::Sent;
        $quote->save();

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_creating_a_quote_already_sent_is_unaffected_by_the_guard(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create();

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }
}
