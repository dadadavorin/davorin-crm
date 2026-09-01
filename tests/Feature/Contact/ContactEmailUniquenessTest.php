<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `contacts.email` has a partial unique index — `WHERE deleted_at IS NULL`
 * (T6) — so a soft-deleted contact's address can be reused, while a live
 * duplicate is still rejected. The database is the only thing that can
 * arbitrate this safely under concurrency (`CONVENTIONS.md` §4): these
 * tests exercise the SQLSTATE 23505 → `DuplicateEmailException` translation
 * rather than a pre-write existence check.
 */
class ContactEmailUniquenessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payloadWithEmail(string $email): array
    {
        return [
            'first_name' => 'New',
            'last_name' => 'Contact',
            'email' => $email,
        ];
    }

    public function test_creating_a_contact_with_a_live_duplicate_email_is_rejected(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['email' => 'zoran@example.com']);

        $this->actingAs($user)
            ->post(route('contacts.store'), $this->payloadWithEmail('zoran@example.com'))
            ->assertSessionHasErrors('duplicate_email');

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_a_case_different_duplicate_email_is_still_rejected(): void
    {
        // EmailAddress normalizes (lowercase) on every write, so the
        // partial unique index catches this even though the raw input
        // differs from what's stored.
        $user = User::factory()->create();
        Contact::factory()->create(['email' => 'zoran@example.com']);

        $this->actingAs($user)
            ->post(route('contacts.store'), $this->payloadWithEmail('Zoran@Example.com'))
            ->assertSessionHasErrors('duplicate_email');

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_creating_a_contact_reuses_an_email_freed_by_a_soft_deleted_contact(): void
    {
        $user = User::factory()->create();
        $existing = Contact::factory()->create(['email' => 'jana@example.com']);
        $existing->delete();

        $this->actingAs($user)
            ->post(route('contacts.store'), $this->payloadWithEmail('jana@example.com'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('contacts', [
            'email' => 'jana@example.com',
            'deleted_at' => null,
        ]);
    }

    public function test_updating_a_contact_to_a_live_duplicate_email_is_rejected(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['email' => 'taken@example.com']);
        $contact = Contact::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'status' => $contact->status->value,
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('duplicate_email');

        $this->assertSame('mine@example.com', $contact->fresh()->email?->value);
    }

    public function test_updating_a_contact_without_changing_its_email_succeeds(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['email' => 'stable@example.com']);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [
                'first_name' => 'Changed',
                'last_name' => $contact->last_name,
                'status' => $contact->status->value,
                'email' => 'stable@example.com',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('Changed', $contact->fresh()->first_name);
    }
}
