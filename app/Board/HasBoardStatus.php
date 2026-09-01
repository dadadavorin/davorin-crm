<?php

declare(strict_types=1);

namespace App\Board;

use App\Enums\Concerns\BoardStatus;

/**
 * The model-side contract for a board-driven entity: which enum holds its
 * status, which column that enum lives in (`status` for most entities,
 * `stage` for deals), which relations a card needs eager-loaded, and how to
 * render a card payload. `BoardBuilder` and `MoveCardAction` depend only on
 * this contract, never on a concrete entity, which is what lets one engine
 * serve companies, contacts, deals and quotes.
 */
interface HasBoardStatus
{
    /**
     * @return class-string<BoardStatus&\BackedEnum>
     */
    public static function boardStatusEnum(): string;

    /**
     * The column holding the status enum value — `status` for most
     * entities, `stage` for deals.
     */
    public static function boardStatusColumn(): string;

    /**
     * Relations `BoardBuilder` must eager-load before rendering cards, so
     * no entity's card component can reintroduce an N+1.
     *
     * @return list<string>
     */
    public static function boardCardRelations(): array;

    /**
     * The payload sent to the frontend for one card. Never includes
     * anything a relation listed in `boardCardRelations()` didn't already
     * load.
     *
     * @return array<string, mixed>
     */
    public function toBoardCard(): array;
}
