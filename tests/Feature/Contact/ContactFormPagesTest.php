<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactFormPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('contacts.create'))->assertRedirect(route('login'));
    }

    public function test_the_create_page_lists_every_status_owner_and_company_option(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(2)->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('contacts.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/create')
                ->has('statuses', 3)
                ->has('owners', 1)
                ->has('companies', 2));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $contact = Contact::factory()->create();

        $this->get(route('contacts.edit', $contact))->assertRedirect(route('login'));
    }

    public function test_the_edit_page_is_prefilled_with_the_contact(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['first_name' => 'Editable']);

        $this->actingAs($user)
            ->get(route('contacts.edit', $contact))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/edit')
                ->where('contact.first_name', 'Editable'));
    }

    public function test_a_soft_deleted_contact_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->actingAs($user)
            ->get(route('contacts.edit', $contact))
            ->assertNotFound();
    }
}
