<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    private static int $positionSequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'job_title' => fake()->jobTitle(),
            'status' => fake()->randomElement(ContactStatus::cases()),
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
            'position' => (string) (++self::$positionSequence * 1024),
        ];
    }

    public function status(ContactStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function withoutCompany(): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => null,
        ]);
    }
}
