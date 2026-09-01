<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = Money::fromMinorUnits(fake()->numberBetween(500, 50_000));

        return [
            'quote_id' => Quote::factory(),
            'sort_order' => 0,
            'description' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price_minor' => $unitPrice,
            'line_total_minor' => $unitPrice->multiplyBy($quantity),
        ];
    }
}
