<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Actions\Quote\CreateQuote;
use App\Actions\Quote\GenerateQuoteNumber;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `quotes.number` is backed by a Postgres sequence (T8) formatted in PHP as
 * `Q-{year}-{0000}`; the unique index is only a backstop `CreateQuote`
 * translates a `23505` against and retries — it never checks-then-inserts.
 */
class QuoteNumberingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(Deal $deal): array
    {
        return [
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => [],
        ];
    }

    public function test_the_number_matches_the_q_year_four_digit_format(): void
    {
        $number = app(GenerateQuoteNumber::class)->next();

        $this->assertMatchesRegularExpression('/^Q-\d{4}-\d{4,}$/', $number);
    }

    public function test_generating_several_numbers_yields_distinct_values(): void
    {
        $numbers = [];

        for ($i = 0; $i < 10; $i++) {
            $numbers[] = app(GenerateQuoteNumber::class)->next();
        }

        $this->assertSame($numbers, array_unique($numbers));
    }

    public function test_creating_several_quotes_yields_distinct_numbers(): void
    {
        $user = User::factory()->create();

        $numbers = collect(range(1, 5))
            ->map(fn (): string => app(CreateQuote::class)->handle($this->payload(Deal::factory()->create()), $user)->number)
            ->all();

        $this->assertSame($numbers, array_unique($numbers));
    }

    public function test_a_number_collision_is_translated_into_a_retry_not_a_500(): void
    {
        // Force the next `nextval()` to return 1, then occupy the number it
        // would produce so the first insert attempt collides on purpose.
        DB::statement("select setval('quote_number_seq', 1, false)");
        $year = now()->format('Y');
        Quote::factory()->create(['number' => "Q-{$year}-0001"]);

        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $quote = app(CreateQuote::class)->handle($this->payload($deal), $user);

        $this->assertNotSame("Q-{$year}-0001", $quote->number);
        $this->assertSame("Q-{$year}-0002", $quote->number);
    }
}
