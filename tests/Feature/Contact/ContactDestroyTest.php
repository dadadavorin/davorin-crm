<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $contact = Contact::factory()->create();

        $this->delete(route('contacts.destroy', $contact))
            ->assertRedirect(route('login'));
    }

    public function test_the_owner_can_delete_their_contact(): void
    {
        $owner = User::factory()->create();
        $contact = Contact::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('contacts.destroy', $contact))
            ->assertRedirect(route('contacts.index'));

        $this->assertSoftDeleted($contact);
    }

    public function test_an_admin_can_delete_a_contact_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $contact = Contact::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->delete(route('contacts.destroy', $contact))
            ->assertRedirect(route('contacts.index'));

        $this->assertSoftDeleted($contact);
    }

    public function test_a_non_owner_non_admin_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['role' => UserRole::Member]);
        $contact = Contact::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->delete(route('contacts.destroy', $contact))
            ->assertForbidden();

        $this->assertNotSoftDeleted($contact);
    }
}
