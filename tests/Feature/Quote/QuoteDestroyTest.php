<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Enums\UserRole;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $quote = Quote::factory()->create();

        $this->delete(route('quotes.destroy', $quote))
            ->assertRedirect(route('login'));
    }

    public function test_the_owner_can_delete_their_quote(): void
    {
        $owner = User::factory()->create();
        $quote = Quote::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('quotes.destroy', $quote))
            ->assertRedirect(route('quotes.index'));

        $this->assertSoftDeleted($quote);
    }

    public function test_an_admin_can_delete_a_quote_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $quote = Quote::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->delete(route('quotes.destroy', $quote))
            ->assertRedirect(route('quotes.index'));

        $this->assertSoftDeleted($quote);
    }

    public function test_a_non_owner_non_admin_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['role' => UserRole::Member]);
        $quote = Quote::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->delete(route('quotes.destroy', $quote))
            ->assertForbidden();

        $this->assertNotSoftDeleted($quote);
    }
}
