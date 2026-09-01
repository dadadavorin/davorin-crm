<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Actions\Quote\CreateQuote;
use App\Actions\Quote\UpdateQuote;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Load-bearing (T8): a quote's stored `subtotal_minor`/`tax_minor`/
 * `total_minor` must always equal a fresh recomputation from its own line
 * items and its own `tax_rate` — never resolved live, never allowed to
 * silently drift from what a rendered PDF would show.
 */
class QuoteTotalsInvariantTest extends TestCase
{
    use RefreshDatabase;

    private function assertTotalsInvariant(Quote $quote): void
    {
        $fresh = $quote->fresh();

        $recomputedSubtotal = $fresh->items->reduce(
            fn (Money $carry, QuoteItem $item): Money => $carry->add($item->line_total_minor),
            Money::zero(),
        );
        $recomputedTax = $recomputedSubtotal->percentage($fresh->tax_rate);
        $recomputedTotal = $recomputedSubtotal->add($recomputedTax);

        $this->assertSame($recomputedSubtotal->minorUnits, $fresh->subtotal_minor->minorUnits);
        $this->assertSame($recomputedTax->minorUnits, $fresh->tax_minor->minorUnits);
        $this->assertSame($recomputedTotal->minorUnits, $fresh->total_minor->minorUnits);
    }

    /**
     * @param  list<array{description: string, quantity: int, unit_price: string}>  $items
     */
    private function createQuote(array $items, string $taxRate = '0.2500'): Quote
    {
        $deal = Deal::factory()->create();
        $user = User::factory()->create();

        return app(CreateQuote::class)->handle([
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => $taxRate,
            'items' => $items,
        ], $user);
    }

    public function test_the_invariant_holds_with_zero_items(): void
    {
        $quote = $this->createQuote([]);

        $this->assertSame(0, $quote->subtotal_minor->minorUnits);
        $this->assertTotalsInvariant($quote);
    }

    public function test_the_invariant_holds_with_a_single_item(): void
    {
        $quote = $this->createQuote([
            ['description' => 'Design work', 'quantity' => 3, 'unit_price' => '99.99'],
        ]);

        $this->assertTotalsInvariant($quote);
    }

    public function test_the_invariant_holds_with_fifty_items(): void
    {
        $items = [];
        for ($i = 0; $i < 50; $i++) {
            $items[] = ['description' => "Item {$i}", 'quantity' => $i + 1, 'unit_price' => '3.33'];
        }

        $quote = $this->createQuote($items);

        $this->assertCount(50, $quote->items);
        $this->assertTotalsInvariant($quote);
    }

    public function test_the_invariant_holds_at_a_tax_rounding_boundary_that_rounds_up(): void
    {
        // subtotal 25, rate 50% -> 12.5 -> half-up -> 13.
        $quote = $this->createQuote([
            ['description' => 'Boundary item', 'quantity' => 1, 'unit_price' => '0.25'],
        ], '0.5');

        $this->assertSame(25, $quote->subtotal_minor->minorUnits);
        $this->assertSame(13, $quote->tax_minor->minorUnits);
        $this->assertTotalsInvariant($quote);
    }

    public function test_the_invariant_holds_at_a_tax_rounding_boundary_that_rounds_down(): void
    {
        // subtotal 23, rate 50% -> 11.5 -> half-up -> 12 (still rounds up
        // per the half-up rule); use a remainder below the half-way point
        // instead: subtotal 24, rate 20% -> 4.8 -> rounds up to 5. To
        // exercise a genuine round-down, pick a remainder under half:
        // subtotal 21, rate 20% -> 4.2 -> rounds down to 4.
        $quote = $this->createQuote([
            ['description' => 'Boundary item', 'quantity' => 1, 'unit_price' => '0.21'],
        ], '0.2');

        $this->assertSame(21, $quote->subtotal_minor->minorUnits);
        $this->assertSame(4, $quote->tax_minor->minorUnits);
        $this->assertTotalsInvariant($quote);
    }

    public function test_the_invariant_still_holds_after_the_item_set_is_replaced_on_update(): void
    {
        $quote = $this->createQuote([
            ['description' => 'Original', 'quantity' => 1, 'unit_price' => '10.00'],
        ]);

        app(UpdateQuote::class)->handle($quote, [
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => [
                ['description' => 'Replacement one', 'quantity' => 2, 'unit_price' => '15.50'],
                ['description' => 'Replacement two', 'quantity' => 5, 'unit_price' => '4.20'],
            ],
        ]);

        $this->assertCount(2, $quote->fresh()->items);
        $this->assertTotalsInvariant($quote);
    }

    public function test_the_invariant_still_holds_after_the_tax_rate_changes_on_update(): void
    {
        $quote = $this->createQuote([
            ['description' => 'Original', 'quantity' => 1, 'unit_price' => '10.00'],
        ], '0.2500');

        app(UpdateQuote::class)->handle($quote, [
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.1000',
        ]);

        $this->assertSame('0.1000', $quote->fresh()->tax_rate);
        $this->assertTotalsInvariant($quote);
    }
}
