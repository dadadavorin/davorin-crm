<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Enums\DealStage;
use App\Exceptions\IllegalStatusTransitionException;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `Deal::booted()` enforces the terminal-stage guard directly on the model,
 * not only in `UpdateDealRequest` or `MoveCardAction` — so it also rejects a
 * plain `save()` that bypasses both of those (T7).
 */
class DealTerminalGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_direct_save_off_won_to_a_non_negotiation_stage_is_rejected(): void
    {
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        $this->expectException(IllegalStatusTransitionException::class);

        $deal->stage = DealStage::New;
        $deal->save();
    }

    public function test_a_direct_save_off_lost_to_a_non_negotiation_stage_is_rejected(): void
    {
        $deal = Deal::factory()->stage(DealStage::Lost)->create();

        $this->expectException(IllegalStatusTransitionException::class);

        $deal->stage = DealStage::Qualified;
        $deal->save();
    }

    public function test_a_direct_save_off_won_does_not_persist(): void
    {
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        try {
            $deal->stage = DealStage::Lost;
            $deal->save();
        } catch (IllegalStatusTransitionException) {
            // expected
        }

        $this->assertSame(DealStage::Won, $deal->fresh()->stage);
    }

    public function test_a_direct_save_off_won_to_negotiation_is_accepted(): void
    {
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        $deal->stage = DealStage::Negotiation;
        $deal->save();

        $this->assertSame(DealStage::Negotiation, $deal->fresh()->stage);
    }

    public function test_a_direct_save_that_does_not_touch_stage_is_unaffected(): void
    {
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        $deal->title = 'Renamed while terminal';
        $deal->save();

        $this->assertSame('Renamed while terminal', $deal->fresh()->title);
        $this->assertSame(DealStage::Won, $deal->fresh()->stage);
    }

    public function test_creating_a_deal_already_won_is_unaffected_by_the_guard(): void
    {
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        $this->assertSame(DealStage::Won, $deal->fresh()->stage);
    }

    public function test_a_non_terminal_stage_change_via_save_is_unaffected(): void
    {
        $deal = Deal::factory()->stage(DealStage::Qualified)->create();

        $deal->stage = DealStage::Proposal;
        $deal->save();

        $this->assertSame(DealStage::Proposal, $deal->fresh()->stage);
    }
}
