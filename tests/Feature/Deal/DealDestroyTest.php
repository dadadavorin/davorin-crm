<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Enums\UserRole;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $deal = Deal::factory()->create();

        $this->delete(route('deals.destroy', $deal))
            ->assertRedirect(route('login'));
    }

    public function test_the_owner_can_delete_their_deal(): void
    {
        $owner = User::factory()->create();
        $deal = Deal::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('deals.destroy', $deal))
            ->assertRedirect(route('deals.index'));

        $this->assertSoftDeleted($deal);
    }

    public function test_an_admin_can_delete_a_deal_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $deal = Deal::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($admin)
            ->delete(route('deals.destroy', $deal))
            ->assertRedirect(route('deals.index'));

        $this->assertSoftDeleted($deal);
    }

    public function test_a_non_owner_non_admin_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(['role' => UserRole::Member]);
        $deal = Deal::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->delete(route('deals.destroy', $deal))
            ->assertForbidden();

        $this->assertNotSoftDeleted($deal);
    }
}
