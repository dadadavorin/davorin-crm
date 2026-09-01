<?php

declare(strict_types=1);

namespace App\Board;

use App\Enums\Concerns\BoardStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

/**
 * Builds one board's columns for a `HasBoardStatus` model: one column per
 * enum case, in `boardOrder`, each capped at `COLUMN_CAP` cards with a
 * `has_more` count past the cap.
 *
 * Owns the eager loading so no entity's card component can reintroduce an
 * N+1: every card in every column comes from the same query plus one query
 * per relation in `boardCardRelations()` — a fixed count regardless of how
 * many cards exist, never one query per row.
 */
final class BoardBuilder
{
    private const int COLUMN_CAP = 50;

    /**
     * @param  class-string<Model>  $modelClass  a model implementing `HasBoardStatus`
     * @return list<BoardColumn>
     */
    public function build(string $modelClass): array
    {
        $this->assertBoardModel($modelClass);

        /** @var class-string<Model&HasBoardStatus> $boardModelClass */
        $boardModelClass = $modelClass;

        $statusColumn = $boardModelClass::boardStatusColumn();
        $statusEnumClass = $boardModelClass::boardStatusEnum();

        $byStatus = $modelClass::query()
            ->with($boardModelClass::boardCardRelations())
            ->orderBy($statusColumn)
            ->orderBy('position')
            ->get()
            ->groupBy(fn (Model $row): string => self::statusValue($row, $statusColumn));

        $columns = collect($statusEnumClass::cases())
            ->sortBy(fn (BoardStatus $status): int => $status->boardOrder())
            ->map(function (BoardStatus $status) use ($byStatus): BoardColumn {
                $statusValue = (string) $status->value;
                /** @var Collection<int, Model> $rows */
                $rows = $byStatus->get($statusValue, collect());

                return new BoardColumn(
                    status: $statusValue,
                    label: $status->label(),
                    cards: array_values($rows->take(self::COLUMN_CAP)
                        ->map(fn (Model $row): array => self::toBoardCard($row))
                        ->all()),
                    total: $rows->count(),
                    hasMore: $rows->count() > self::COLUMN_CAP,
                );
            })
            ->all();

        return array_values($columns);
    }

    private static function statusValue(Model $row, string $statusColumn): string
    {
        $status = $row->getAttribute($statusColumn);

        if (! $status instanceof BoardStatus) {
            throw new LogicException("Attribute \"{$statusColumn}\" is not a BoardStatus enum.");
        }

        return (string) $status->value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function toBoardCard(Model $row): array
    {
        if (! $row instanceof HasBoardStatus) {
            throw new LogicException($row::class.' does not implement '.HasBoardStatus::class.'.');
        }

        return $row->toBoardCard();
    }

    /**
     * @param  class-string  $modelClass
     */
    private function assertBoardModel(string $modelClass): void
    {
        if (! is_subclass_of($modelClass, HasBoardStatus::class)) {
            throw new LogicException("{$modelClass} does not implement ".HasBoardStatus::class.'.');
        }
    }
}
