<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Board\BoardBuilder;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Load-bearing: a fresh clone's `docker compose up` seeds a CRM that
 * actually looks populated. A board with an empty column, or seeded demo
 * data that fails its own totals invariant, would make the first thing a
 * reviewer sees look broken.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_board_column_is_non_empty_after_seeding(): void
    {
        $this->seed();

        $builder = app(BoardBuilder::class);

        foreach ([Company::class, Contact::class, Deal::class, Quote::class] as $modelClass) {
            foreach ($builder->build($modelClass) as $column) {
                $this->assertNotEmpty(
                    $column->cards,
                    "{$modelClass}'s \"{$column->label}\" column is empty after seeding.",
                );
            }
        }
    }

    public function test_seeded_quotes_satisfy_the_totals_invariant(): void
    {
        $this->seed();

        $quotes = Quote::query()->with('items')->get();

        $this->assertNotEmpty($quotes);

        foreach ($quotes as $quote) {
            $recomputedSubtotal = $quote->items->reduce(
                fn (Money $carry, QuoteItem $item): Money => $carry->add($item->line_total_minor),
                Money::zero(),
            );
            $recomputedTax = $recomputedSubtotal->percentage($quote->tax_rate);
            $recomputedTotal = $recomputedSubtotal->add($recomputedTax);

            $this->assertSame(
                $recomputedSubtotal->minorUnits,
                $quote->subtotal_minor->minorUnits,
                "Quote {$quote->number}'s stored subtotal does not match its line items.",
            );
            $this->assertSame($recomputedTax->minorUnits, $quote->tax_minor->minorUnits);
            $this->assertSame($recomputedTotal->minorUnits, $quote->total_minor->minorUnits);
        }
    }

    public function test_all_seeded_demo_data_is_owned_by_the_admin_account(): void
    {
        $this->seed();

        $admin = User::query()->where('email', config('seed.admin.email'))->firstOrFail();

        $this->assertSame(0, Company::query()->where('owner_id', '!=', $admin->id)->count());
        $this->assertSame(0, Contact::query()->where('owner_id', '!=', $admin->id)->count());
        $this->assertSame(0, Deal::query()->where('owner_id', '!=', $admin->id)->count());
        $this->assertSame(0, Quote::query()->where('owner_id', '!=', $admin->id)->count());
    }
}
