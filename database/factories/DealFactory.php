<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    private static int $positionSequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(3), '.'),
            'value_minor' => Money::fromMinorUnits(fake()->numberBetween(50_000, 5_000_000)),
            'stage' => fake()->randomElement(DealStage::cases()),
            'expected_close_date' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'company_id' => Company::factory(),
            'primary_contact_id' => null,
            'owner_id' => User::factory(),
            'position' => (string) (++self::$positionSequence * 1024),
        ];
    }

    public function stage(DealStage $stage): static
    {
        return $this->state(fn (array $attributes): array => [
            'stage' => $stage,
        ]);
    }

    public function withoutValue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'value_minor' => null,
        ]);
    }
}
