<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * `Expired` is a stored status set by the `quotes:expire` scheduled command
 * (T8), never derived at render time — the board must be able to trust it.
 */
class QuoteExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sent_quote_past_its_valid_until_date_is_expired(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create([
            'valid_until' => Date::yesterday()->toDateString(),
        ]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Expired, $quote->fresh()->status);
    }

    public function test_a_sent_quote_valid_until_today_is_not_expired_yet(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create([
            'valid_until' => Date::today()->toDateString(),
        ]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_a_sent_quote_valid_in_the_future_is_not_expired(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Sent)->create([
            'valid_until' => Date::tomorrow()->toDateString(),
        ]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_a_draft_quote_past_its_valid_until_date_is_left_alone(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Draft)->create([
            'valid_until' => Date::yesterday()->toDateString(),
        ]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Draft, $quote->fresh()->status);
    }

    public function test_an_already_terminal_quote_is_left_alone(): void
    {
        $quote = Quote::factory()->status(QuoteStatus::Accepted)->create([
            'valid_until' => Date::yesterday()->toDateString(),
        ]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }
}
