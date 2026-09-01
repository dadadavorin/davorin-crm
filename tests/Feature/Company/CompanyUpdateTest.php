<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Renamed Corp',
            'status' => CompanyStatus::Customer->value,
            'industry' => 'Retail',
            'website' => 'https://renamed.example.com',
            'email' => 'contact@renamed.example.com',
            'phone' => '+385 91 999 0000',
            'address' => 'Nova ulica 5, Split',
            'notes' => 'Upgraded after a successful pilot.',
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $company = Company::factory()->create();

        $this->put(route('companies.update', $company), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_any_authenticated_user_can_update_a_company_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->put(route('companies.update', $company), $this->validPayload())
            ->assertRedirect(route('companies.show', $company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Renamed Corp',
            'status' => CompanyStatus::Customer->value,
        ]);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $payload = $this->validPayload();
        unset($payload['name']);

        $this->actingAs($user)
            ->put(route('companies.update', $company), $payload)
            ->assertSessionHasErrors('name');
    }

    public function test_status_is_required(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $payload = $this->validPayload();
        unset($payload['status']);

        $this->actingAs($user)
            ->put(route('companies.update', $company), $payload)
            ->assertSessionHasErrors('status');
    }

    public function test_name_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->put(route('companies.update', $company), [...$this->validPayload(), 'name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('name');
    }

    public function test_an_invalid_email_is_rejected_with_a_field_error(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->put(route('companies.update', $company), [...$this->validPayload(), 'email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_a_soft_deleted_company_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->delete();

        $this->actingAs($user)
            ->put(route('companies.update', $company), $this->validPayload())
            ->assertNotFound();
    }
}
