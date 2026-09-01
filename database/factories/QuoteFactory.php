<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\Quote\GenerateQuoteNumber;
use App\Enums\QuoteStatus;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    private static int $positionSequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => app(GenerateQuoteNumber::class)->next(),
            'status' => QuoteStatus::Draft,
            'deal_id' => Deal::factory(),
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'tax_rate' => '0.2500',
            'subtotal_minor' => Money::zero(),
            'tax_minor' => Money::zero(),
            'total_minor' => Money::zero(),
            'bill_to_company_name' => fake()->company(),
            'bill_to_address' => fake()->address(),
            'bill_to_contact_name' => fake()->name(),
            'bill_to_contact_email' => fake()->unique()->safeEmail(),
            'notes' => null,
            'terms' => null,
            'owner_id' => User::factory(),
            'position' => (string) (++self::$positionSequence * 1024),
        ];
    }

    public function status(QuoteStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }
}
