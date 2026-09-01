<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `MoveCardAction`, `BoardMoveController` and the transition mechanics are
 * already exhaustively tested against companies (T5) — this proves the
 * generic engine is wired up correctly for a second entity, not that the
 * mechanics themselves work.
 */
class ContactBoardMoveTest extends TestCase
{
    use RefreshDatabase;

    private function moveUrl(Contact $contact): string
    {
        return "/api/v1/boards/contacts/{$contact->id}/move";
    }

    public function test_a_valid_move_returns_204_and_persists(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['status' => ContactStatus::New]);

        $this->actingAs($user)
            ->postJson($this->moveUrl($contact), ['status' => ContactStatus::Active->value])
            ->assertNoContent();

        $this->assertSame(ContactStatus::Active, $contact->fresh()->status);
    }

    public function test_an_illegal_transition_returns_a_problem_json_422(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['status' => ContactStatus::New]);

        // New can only move to Active, never straight to Inactive.
        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($contact), ['status' => ContactStatus::Inactive->value]);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'illegal_status_transition']);
        $this->assertSame(ContactStatus::New, $contact->fresh()->status);
    }

    public function test_a_soft_deleted_contact_cannot_be_moved(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['status' => ContactStatus::New]);
        $contact->delete();

        $this->actingAs($user)
            ->postJson($this->moveUrl($contact), ['status' => ContactStatus::Active->value])
            ->assertNotFound();
    }
}
