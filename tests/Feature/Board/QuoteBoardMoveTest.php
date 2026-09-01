<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteBoardMoveTest extends TestCase
{
    use RefreshDatabase;

    private function moveUrl(Quote $quote): string
    {
        return "/api/v1/boards/quotes/{$quote->id}/move";
    }

    public function test_a_valid_move_returns_204_and_persists(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Draft)->create();

        $this->actingAs($user)
            ->postJson($this->moveUrl($quote), ['status' => QuoteStatus::Sent->value])
            ->assertNoContent();

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_an_illegal_transition_returns_a_problem_json_422(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Draft)->create();

        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($quote), ['status' => QuoteStatus::Accepted->value]);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'illegal_status_transition']);
        $this->assertSame(QuoteStatus::Draft, $quote->fresh()->status);
    }

    public function test_dragging_an_accepted_quote_to_any_other_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Accepted)->create();

        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($quote), ['status' => QuoteStatus::Sent->value]);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'illegal_status_transition']);
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_dragging_an_expired_quote_to_any_other_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Expired)->create();

        $this->actingAs($user)
            ->postJson($this->moveUrl($quote), ['status' => QuoteStatus::Sent->value])
            ->assertStatus(422);

        $this->assertSame(QuoteStatus::Expired, $quote->fresh()->status);
    }

    public function test_reordering_within_accepted_is_still_allowed(): void
    {
        $user = User::factory()->create();
        $before = Quote::factory()->status(QuoteStatus::Accepted)->create(['position' => '100']);
        $moving = Quote::factory()->status(QuoteStatus::Accepted)->create(['position' => '50']);
        $after = Quote::factory()->status(QuoteStatus::Accepted)->create(['position' => '200']);

        $this->actingAs($user)
            ->postJson($this->moveUrl($moving), [
                'status' => QuoteStatus::Accepted->value,
                'before_id' => $before->id,
                'after_id' => $after->id,
            ])
            ->assertNoContent();

        $position = (float) $moving->fresh()->position;
        $this->assertGreaterThan(100.0, $position);
        $this->assertLessThan(200.0, $position);
    }

    public function test_a_soft_deleted_quote_cannot_be_moved(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->status(QuoteStatus::Draft)->create();
        $quote->delete();

        $this->actingAs($user)
            ->postJson($this->moveUrl($quote), ['status' => QuoteStatus::Sent->value])
            ->assertNotFound();
    }
}
