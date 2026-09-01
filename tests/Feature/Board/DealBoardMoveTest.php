<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `MoveCardAction` and `BoardMoveController` are already exhaustively
 * tested against companies (T5) — this proves the generic engine is wired
 * up correctly for deals, and specifically that a terminal stage (`Won`)
 * can never be dragged out of, per T7.
 */
class DealBoardMoveTest extends TestCase
{
    use RefreshDatabase;

    private function moveUrl(Deal $deal): string
    {
        return "/api/v1/boards/deals/{$deal->id}/move";
    }

    public function test_a_valid_move_returns_204_and_persists(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();

        $this->actingAs($user)
            ->postJson($this->moveUrl($deal), ['status' => DealStage::Qualified->value])
            ->assertNoContent();

        $this->assertSame(DealStage::Qualified, $deal->fresh()->stage);
    }

    public function test_an_illegal_transition_returns_a_problem_json_422(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();

        // New can only move to Qualified, never straight to Negotiation.
        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($deal), ['status' => DealStage::Negotiation->value]);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'illegal_status_transition']);
        $this->assertSame(DealStage::New, $deal->fresh()->stage);
    }

    public function test_dragging_a_won_deal_to_any_other_stage_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($deal), ['status' => DealStage::Negotiation->value]);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'illegal_status_transition']);
        $this->assertSame(DealStage::Won, $deal->fresh()->stage);
    }

    public function test_dragging_a_lost_deal_to_any_other_stage_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::Lost)->create();

        $this->actingAs($user)
            ->postJson($this->moveUrl($deal), ['status' => DealStage::Won->value])
            ->assertStatus(422);

        $this->assertSame(DealStage::Lost, $deal->fresh()->stage);
    }

    public function test_reordering_within_won_is_still_allowed(): void
    {
        $user = User::factory()->create();
        $before = Deal::factory()->stage(DealStage::Won)->create(['position' => '100']);
        $moving = Deal::factory()->stage(DealStage::Won)->create(['position' => '50']);
        $after = Deal::factory()->stage(DealStage::Won)->create(['position' => '200']);

        $this->actingAs($user)
            ->postJson($this->moveUrl($moving), [
                'status' => DealStage::Won->value,
                'before_id' => $before->id,
                'after_id' => $after->id,
            ])
            ->assertNoContent();

        $position = (float) $moving->fresh()->position;
        $this->assertGreaterThan(100.0, $position);
        $this->assertLessThan(200.0, $position);
    }

    public function test_a_soft_deleted_deal_cannot_be_moved(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();
        $deal->delete();

        $this->actingAs($user)
            ->postJson($this->moveUrl($deal), ['status' => DealStage::Qualified->value])
            ->assertNotFound();
    }
}
