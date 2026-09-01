<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Acme Corp',
            'status' => CompanyStatus::Prospect->value,
            'industry' => 'Manufacturing',
            'website' => 'https://acme.example.com',
            'email' => 'hello@acme.example.com',
            'phone' => '+385 91 234 5678',
            'address' => 'Ilica 1, Zagreb',
            'notes' => 'Met at a trade fair.',
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post(route('companies.store'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_create_a_company(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('companies.store'), $this->validPayload());

        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Corp',
            'status' => CompanyStatus::Prospect->value,
            'email' => 'hello@acme.example.com',
        ]);

        $company = Company::query()->where('name', 'Acme Corp')->firstOrFail();
        $response->assertRedirect(route('companies.show', $company));
    }

    public function test_the_creator_becomes_the_owner_when_none_is_given(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('companies.store'), $this->validPayload());

        $company = Company::query()->where('name', 'Acme Corp')->firstOrFail();
        $this->assertSame($user->id, $company->owner_id);
    }

    public function test_an_explicit_owner_is_respected(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();

        $this->actingAs($user)->post(route('companies.store'), [
            ...$this->validPayload(),
            'owner_id' => $owner->id,
        ]);

        $company = Company::query()->where('name', 'Acme Corp')->firstOrFail();
        $this->assertSame($owner->id, $company->owner_id);
    }

    public function test_omitting_status_defaults_to_lead(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['status']);

        $this->actingAs($user)->post(route('companies.store'), $payload);

        $company = Company::query()->where('name', 'Acme Corp')->firstOrFail();
        $this->assertSame(CompanyStatus::Lead, $company->status);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['name']);

        $this->actingAs($user)
            ->post(route('companies.store'), $payload)
            ->assertSessionHasErrors('name');
    }

    public function test_name_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('name');
    }

    public function test_industry_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'industry' => str_repeat('a', 256)])
            ->assertSessionHasErrors('industry');
    }

    public function test_website_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'website' => str_repeat('a', 256)])
            ->assertSessionHasErrors('website');
    }

    public function test_phone_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'phone' => str_repeat('1', 31)])
            ->assertSessionHasErrors('phone');
    }

    public function test_address_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'address' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('address');
    }

    public function test_notes_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'notes' => str_repeat('a', 5001)])
            ->assertSessionHasErrors('notes');
    }

    public function test_an_invalid_email_is_rejected_with_a_field_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('companies', ['name' => 'Acme Corp']);
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'status' => 'not-a-status'])
            ->assertSessionHasErrors('status');
    }

    public function test_an_unknown_owner_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [...$this->validPayload(), 'owner_id' => 999_999])
            ->assertSessionHasErrors('owner_id');
    }
}
