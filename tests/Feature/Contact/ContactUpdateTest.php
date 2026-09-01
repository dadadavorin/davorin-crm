<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Renamed',
            'last_name' => 'Contact',
            'status' => ContactStatus::Inactive->value,
            'email' => 'renamed@example.com',
            'phone' => '+385 91 999 0000',
            'job_title' => 'Retired',
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $contact = Contact::factory()->create();

        $this->put(route('contacts.update', $contact), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_any_authenticated_user_can_update_a_contact_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $contact = Contact::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->put(route('contacts.update', $contact), $this->validPayload())
            ->assertRedirect(route('contacts.show', $contact));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => 'Renamed',
            'status' => ContactStatus::Inactive->value,
        ]);
    }

    public function test_first_name_is_required(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $payload = $this->validPayload();
        unset($payload['first_name']);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), $payload)
            ->assertSessionHasErrors('first_name');
    }

    public function test_status_is_required(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $payload = $this->validPayload();
        unset($payload['status']);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), $payload)
            ->assertSessionHasErrors('status');
    }

    public function test_an_invalid_email_is_rejected_with_a_field_error(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [...$this->validPayload(), 'email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_a_contact_can_be_moved_to_a_different_company(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $newCompany = Company::factory()->create();

        $this->actingAs($user)->put(route('contacts.update', $contact), [
            ...$this->validPayload(),
            'company_id' => $newCompany->id,
        ]);

        $this->assertSame($newCompany->id, $contact->fresh()->company_id);
    }

    public function test_a_contact_can_be_detached_from_its_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->put(route('contacts.update', $contact), [
            ...$this->validPayload(),
            'company_id' => null,
        ]);

        $this->assertNull($contact->fresh()->company_id);
    }

    public function test_a_soft_deleted_contact_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), $this->validPayload())
            ->assertNotFound();
    }
}
