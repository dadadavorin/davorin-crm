<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Ivana',
            'last_name' => 'Horvat',
            'status' => ContactStatus::Active->value,
            'email' => 'ivana@example.com',
            'phone' => '+385 91 234 5678',
            'job_title' => 'Sales Lead',
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post(route('contacts.store'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_create_a_contact(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('contacts.store'), $this->validPayload());

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Ivana',
            'last_name' => 'Horvat',
            'status' => ContactStatus::Active->value,
            'email' => 'ivana@example.com',
        ]);

        $contact = Contact::query()->where('first_name', 'Ivana')->firstOrFail();
        $response->assertRedirect(route('contacts.show', $contact));
    }

    public function test_a_contact_can_be_created_without_a_company(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contacts.store'), $this->validPayload());

        $contact = Contact::query()->where('first_name', 'Ivana')->firstOrFail();
        $this->assertNull($contact->company_id);
    }

    public function test_a_company_can_be_attached_on_create(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('contacts.store'), [
            ...$this->validPayload(),
            'company_id' => $company->id,
        ]);

        $contact = Contact::query()->where('first_name', 'Ivana')->firstOrFail();
        $this->assertSame($company->id, $contact->company_id);
    }

    public function test_the_creator_becomes_the_owner_when_none_is_given(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contacts.store'), $this->validPayload());

        $contact = Contact::query()->where('first_name', 'Ivana')->firstOrFail();
        $this->assertSame($user->id, $contact->owner_id);
    }

    public function test_omitting_status_defaults_to_new(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['status']);

        $this->actingAs($user)->post(route('contacts.store'), $payload);

        $contact = Contact::query()->where('first_name', 'Ivana')->firstOrFail();
        $this->assertSame(ContactStatus::New, $contact->status);
    }

    public function test_first_name_is_required(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['first_name']);

        $this->actingAs($user)
            ->post(route('contacts.store'), $payload)
            ->assertSessionHasErrors('first_name');
    }

    public function test_last_name_is_required(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['last_name']);

        $this->actingAs($user)
            ->post(route('contacts.store'), $payload)
            ->assertSessionHasErrors('last_name');
    }

    public function test_first_name_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.store'), [...$this->validPayload(), 'first_name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('first_name');
    }

    public function test_phone_rejects_input_past_the_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.store'), [...$this->validPayload(), 'phone' => str_repeat('1', 31)])
            ->assertSessionHasErrors('phone');
    }

    public function test_an_invalid_email_is_rejected_with_a_field_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.store'), [...$this->validPayload(), 'email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('contacts', ['first_name' => 'Ivana']);
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.store'), [...$this->validPayload(), 'status' => 'not-a-status'])
            ->assertSessionHasErrors('status');
    }

    public function test_an_unknown_company_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.store'), [...$this->validPayload(), 'company_id' => 999_999])
            ->assertSessionHasErrors('company_id');
    }

    public function test_an_unknown_owner_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contacts.store'), [...$this->validPayload(), 'owner_id' => 999_999])
            ->assertSessionHasErrors('owner_id');
    }
}
