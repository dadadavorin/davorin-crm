<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $contact = Contact::factory()->create();

        $this->get(route('contacts.show', $contact))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_view_any_contact(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['first_name' => 'Detail']);

        $this->actingAs($user)
            ->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/show')
                ->where('contact.first_name', 'Detail'));
    }

    public function test_a_contact_with_no_company_renders_correctly(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->withoutCompany()->create();

        $this->actingAs($user)
            ->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('contact.company', null));
    }

    public function test_a_soft_deleted_contact_is_not_found(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->actingAs($user)
            ->get(route('contacts.show', $contact))
            ->assertNotFound();
    }
}
