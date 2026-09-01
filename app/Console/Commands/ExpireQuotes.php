<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

/**
 * Moves every `Sent` quote whose `valid_until` date has passed to `Expired`
 * (T8) — a stored status, never derived at render time, so the board can
 * never misrepresent a quote as still `Sent` once it has lapsed. Scheduled
 * daily in `routes/console.php`.
 */
final class ExpireQuotes extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Move Sent quotes whose valid_until date has passed to Expired.';

    public function handle(): int
    {
        $today = Date::today();

        Quote::query()
            ->where('status', QuoteStatus::Sent)
            ->where('valid_until', '<', $today)
            ->each(fn (Quote $quote) => $quote->update(['status' => QuoteStatus::Expired]));

        return self::SUCCESS;
    }
}
