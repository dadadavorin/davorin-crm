<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Board\MoveCardAction;
use App\Enums\CompanyStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Exceptions\InvalidBoardNeighbourException;
use App\Exceptions\UnknownBoardStatusException;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveCardActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_move_changes_the_status(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        (new MoveCardAction)->handle(Company::class, $company->id, CompanyStatus::Prospect->value, null, null);

        $this->assertSame(CompanyStatus::Prospect, $company->fresh()->status);
    }

    public function test_reordering_within_a_column_lands_between_its_neighbours(): void
    {
        $before = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '100']);
        $moving = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '50']);
        $after = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '200']);

        (new MoveCardAction)->handle(
            Company::class,
            $moving->id,
            CompanyStatus::Lead->value,
            $before->id,
            $after->id,
        );

        $position = (float) $moving->fresh()->position;

        $this->assertGreaterThan(100.0, $position);
        $this->assertLessThan(200.0, $position);
    }

    public function test_an_illegal_transition_is_rejected(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->expectException(IllegalStatusTransitionException::class);

        // Lead can only move to Prospect, never straight to Customer.
        (new MoveCardAction)->handle(Company::class, $company->id, CompanyStatus::Customer->value, null, null);
    }

    public function test_an_illegal_transition_does_not_change_the_status(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        try {
            (new MoveCardAction)->handle(Company::class, $company->id, CompanyStatus::Customer->value, null, null);
        } catch (IllegalStatusTransitionException) {
            // expected
        }

        $this->assertSame(CompanyStatus::Lead, $company->fresh()->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->expectException(UnknownBoardStatusException::class);

        (new MoveCardAction)->handle(Company::class, $company->id, 'not-a-real-status', null, null);
    }

    public function test_a_neighbour_id_outside_the_target_column_is_rejected(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);
        $strangerColumnCard = Company::factory()->create(['status' => CompanyStatus::Customer]);

        $this->expectException(InvalidBoardNeighbourException::class);

        (new MoveCardAction)->handle(
            Company::class,
            $company->id,
            CompanyStatus::Prospect->value,
            $strangerColumnCard->id,
            null,
        );
    }

    public function test_a_soft_deleted_neighbour_id_is_rejected(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);
        $deleted = Company::factory()->create(['status' => CompanyStatus::Lead]);
        $deleted->delete();

        $this->expectException(InvalidBoardNeighbourException::class);

        (new MoveCardAction)->handle(
            Company::class,
            $company->id,
            CompanyStatus::Prospect->value,
            $deleted->id,
            null,
        );
    }

    public function test_dropping_above_the_top_card_halves_its_position(): void
    {
        $top = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '200']);
        $moving = Company::factory()->create(['status' => CompanyStatus::Inactive]);

        (new MoveCardAction)->handle(Company::class, $moving->id, CompanyStatus::Lead->value, null, $top->id);

        $this->assertSame(100.0, (float) $moving->fresh()->position);
    }

    public function test_dropping_below_the_bottom_card_adds_a_gap(): void
    {
        $bottom = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '200']);
        $moving = Company::factory()->create(['status' => CompanyStatus::Inactive]);

        (new MoveCardAction)->handle(Company::class, $moving->id, CompanyStatus::Lead->value, $bottom->id, null);

        $this->assertSame(1224.0, (float) $moving->fresh()->position);
    }

    public function test_dropping_into_an_empty_column_uses_the_starting_gap(): void
    {
        $moving = Company::factory()->create(['status' => CompanyStatus::Inactive]);

        (new MoveCardAction)->handle(Company::class, $moving->id, CompanyStatus::Lead->value, null, null);

        $this->assertSame(1024.0, (float) $moving->fresh()->position);
    }

    public function test_repeated_drops_into_one_gap_eventually_rebalance_the_column(): void
    {
        $before = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '0']);
        $after = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '1']);

        $insertedInOrder = [];
        $closestToBefore = $after->id;

        // Each drop lands between $before and whichever card is currently
        // closest to it, halving that gap every time. Thirty halvings of a
        // gap of 1 cross the rebalance threshold (1e-6) well before the
        // loop ends, so this exercises the rebalance branch for real.
        for ($i = 0; $i < 30; $i++) {
            $card = Company::factory()->create(['status' => CompanyStatus::Inactive]);

            (new MoveCardAction)->handle(
                Company::class,
                $card->id,
                CompanyStatus::Lead->value,
                $before->id,
                $closestToBefore,
            );

            $insertedInOrder[] = $card->id;
            $closestToBefore = $card->id;
        }

        $orderedIds = Company::query()
            ->where('status', CompanyStatus::Lead)
            ->orderBy('position')
            ->pluck('id')
            ->all();

        // $before stays first, $after stays last, and every inserted card
        // keeps the order it was inserted in (nearest-to-$before first),
        // because each new card was dropped strictly between $before and
        // the previous nearest card.
        $this->assertSame(
            [$before->id, ...array_reverse($insertedInOrder), $after->id],
            $orderedIds,
        );

        $positions = Company::query()
            ->where('status', CompanyStatus::Lead)
            ->orderBy('position')
            ->pluck('position')
            ->map(fn ($position) => (string) $position)
            ->all();

        $this->assertSame($positions, array_unique($positions));
    }

    public function test_sequential_moves_that_touch_the_same_column_both_persist(): void
    {
        $first = Company::factory()->create(['status' => CompanyStatus::Inactive]);
        $second = Company::factory()->create(['status' => CompanyStatus::Inactive]);
        $anchor = Company::factory()->create(['status' => CompanyStatus::Lead, 'position' => '1000']);

        (new MoveCardAction)->handle(Company::class, $first->id, CompanyStatus::Lead->value, $anchor->id, null);
        (new MoveCardAction)->handle(Company::class, $second->id, CompanyStatus::Lead->value, $first->fresh()->id, null);

        $this->assertSame(CompanyStatus::Lead, $first->fresh()->status);
        $this->assertSame(CompanyStatus::Lead, $second->fresh()->status);
        $this->assertNotEquals($first->fresh()->position, $second->fresh()->position);
    }
}
