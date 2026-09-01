<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('contacts.index'))->assertRedirect(route('login'));
    }

    public function test_the_first_page_returns_twenty_five_contacts(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(30)->create();

        $this->actingAs($user)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/index')
                ->has('contacts.data', 25)
                ->where('contacts.total', 30));
    }

    public function test_the_last_page_returns_the_remainder(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(30)->create();

        $this->actingAs($user)
            ->get(route('contacts.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/index')
                ->has('contacts.data', 5));
    }

    public function test_a_page_past_the_last_one_returns_no_rows(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('contacts.index', ['page' => 99]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contacts/index')
                ->has('contacts.data', 0));
    }

    public function test_search_escapes_a_literal_percent_sign(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => '50%', 'last_name' => 'Off']);
        Contact::factory()->create(['first_name' => 'Ordinary', 'last_name' => 'Person']);

        $this->actingAs($user)
            ->get(route('contacts.index', ['search' => '50%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.first_name', '50%'));
    }

    public function test_search_escapes_a_literal_underscore(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => 'A_B', 'last_name' => 'Holdings']);
        Contact::factory()->create(['first_name' => 'AXB', 'last_name' => 'Holdings']);

        $this->actingAs($user)
            ->get(route('contacts.index', ['search' => 'A_B']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.first_name', 'A_B'));
    }

    public function test_search_matches_the_full_name(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => 'Ivana', 'last_name' => 'Horvat']);
        Contact::factory()->create(['first_name' => 'Marko', 'last_name' => 'Kovac']);

        $this->actingAs($user)
            ->get(route('contacts.index', ['search' => 'Ivana Horvat']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('contacts.data', 1)
                ->where('contacts.data.0.first_name', 'Ivana'));
    }

    public function test_a_soft_deleted_contact_appears_in_no_listing(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->actingAs($user)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('contacts.data', 0));
    }

    public function test_a_contact_with_no_company_renders_correctly(): void
    {
        $user = User::factory()->create();
        Contact::factory()->withoutCompany()->create(['first_name' => 'Solo']);

        $this->actingAs($user)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('contacts.data.0.first_name', 'Solo')
                ->where('contacts.data.0.company', null));
    }
}
