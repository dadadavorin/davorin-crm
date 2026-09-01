<?php

declare(strict_types=1);

namespace App\Board;

use App\Enums\Concerns\BoardStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Exceptions\InvalidBoardNeighbourException;
use App\Exceptions\UnknownBoardStatusException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Moves one card to a target status and position, inside a single
 * transaction with every row it touches locked:
 *
 *   1. resolve the target status ──── unknown value  → 422 UnknownBoardStatusException
 *   2. same status as today? ── no ──▶ canTransitionTo() false → 422 IllegalStatusTransitionException
 *   3. resolve the given before/after neighbour ids, locked,
 *      scoped to the target column ── not found there → 422 InvalidBoardNeighbourException
 *   4. compute the new position:
 *
 *        before   after    position
 *        ------   -----    -----------------------------
 *          –        –      GAP                   (column was empty)
 *          –        B      B.position / 2        (dropped above the top card)
 *          A        –      A.position + GAP      (dropped below the bottom card)
 *          A        B      midpoint(A, B)         (dropped between two cards)
 *
 *      when A and B are closer than MIN_GAP, the column is rebalanced
 *      (every card respaced GAP apart) before the midpoint is taken, so
 *      repeated drops into the same gap can never run the decimal column
 *      out of precision.
 *   5. the card's status and position are written, still inside the
 *      transaction from step 3's locks.
 */
final class MoveCardAction
{
    /**
     * Spacing between freshly (re)spaced cards. Large enough that a column
     * absorbs many drops before its gaps need rebalancing.
     */
    private const GAP = '1024';

    /**
     * A gap this small leaves less than `10^-6` of headroom for the next
     * midpoint split — well before the `decimal(20,10)` column's actual
     * floor, so rebalancing here never risks two cards landing on the same
     * value.
     */
    private const MIN_GAP = '0.000001';

    private const int SCALE = 10;

    /**
     * @param  class-string<Model>  $modelClass  a model implementing `HasBoardStatus`
     */
    public function handle(
        string $modelClass,
        int $id,
        string $targetStatusValue,
        ?int $beforeId,
        ?int $afterId,
    ): Model {
        /** @var class-string<Model&HasBoardStatus> $boardModelClass */
        $boardModelClass = $modelClass;

        return DB::transaction(function () use ($modelClass, $boardModelClass, $id, $targetStatusValue, $beforeId, $afterId): Model {
            $statusColumn = $boardModelClass::boardStatusColumn();
            $statusEnumClass = $boardModelClass::boardStatusEnum();

            $card = $modelClass::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            $targetStatus = $statusEnumClass::tryFrom($targetStatusValue)
                ?? throw new UnknownBoardStatusException($targetStatusValue);

            $currentStatus = $this->statusOf($card, $statusColumn);

            if ($currentStatus !== $targetStatus && ! $currentStatus->canTransitionTo($targetStatus)) {
                throw new IllegalStatusTransitionException($currentStatus, $targetStatus);
            }

            if ($beforeId === $id || $afterId === $id) {
                throw new InvalidBoardNeighbourException($id);
            }

            $before = $this->lockNeighbour($modelClass, $statusColumn, $targetStatus, $beforeId);
            $after = $this->lockNeighbour($modelClass, $statusColumn, $targetStatus, $afterId);

            $position = $this->resolvePosition($modelClass, $statusColumn, $targetStatus, $before, $after, $id);

            $card->setAttribute($statusColumn, $targetStatus);
            $card->setAttribute('position', $position);
            $card->save();

            return $card;
        });
    }

    private function statusOf(Model $card, string $statusColumn): BoardStatus
    {
        $status = $card->getAttribute($statusColumn);

        if (! $status instanceof BoardStatus) {
            throw new LogicException("Attribute \"{$statusColumn}\" is not a BoardStatus enum.");
        }

        return $status;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function lockNeighbour(string $modelClass, string $statusColumn, BoardStatus $status, ?int $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        return $modelClass::query()->where($statusColumn, $status)->lockForUpdate()->find($id)
            ?? throw new InvalidBoardNeighbourException($id);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function resolvePosition(
        string $modelClass,
        string $statusColumn,
        BoardStatus $status,
        ?Model $before,
        ?Model $after,
        int $excludeId,
    ): string {
        if ($before === null && $after === null) {
            return self::GAP;
        }

        if ($before === null) {
            return self::bcdiv(self::positionOf($after), '2');
        }

        if ($after === null) {
            return self::bcadd(self::positionOf($before), self::GAP);
        }

        $beforePosition = self::positionOf($before);
        $afterPosition = self::positionOf($after);

        $gap = self::bcsub($afterPosition, $beforePosition);

        if (bccomp($gap, self::MIN_GAP, self::SCALE) <= 0) {
            $this->rebalance($modelClass, $statusColumn, $status, $excludeId);

            $before = $modelClass::query()->whereKey($before->getKey())->lockForUpdate()->firstOrFail();
            $after = $modelClass::query()->whereKey($after->getKey())->lockForUpdate()->firstOrFail();

            $beforePosition = self::positionOf($before);
            $afterPosition = self::positionOf($after);
        }

        return self::bcdiv(self::bcadd($beforePosition, $afterPosition), '2');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function rebalance(string $modelClass, string $statusColumn, BoardStatus $status, int $excludeId): void
    {
        $rows = $modelClass::query()
            ->where($statusColumn, $status)
            ->where('id', '!=', $excludeId)
            ->orderBy('position')
            ->lockForUpdate()
            ->get();

        foreach ($rows->values() as $index => $row) {
            $row->update(['position' => self::bcmul((string) ($index + 1), self::GAP)]);
        }
    }

    /**
     * @return numeric-string
     */
    private static function positionOf(Model $model): string
    {
        $position = $model->getAttribute('position');

        if (! is_string($position) || ! is_numeric($position)) {
            throw new LogicException('Attribute "position" is not a numeric string.');
        }

        return $position;
    }

    /**
     * @param  numeric-string  $left
     * @param  numeric-string  $right
     * @return numeric-string
     */
    private static function bcadd(string $left, string $right): string
    {
        return bcadd($left, $right, self::SCALE);
    }

    /**
     * @param  numeric-string  $left
     * @param  numeric-string  $right
     * @return numeric-string
     */
    private static function bcsub(string $left, string $right): string
    {
        return bcsub($left, $right, self::SCALE);
    }

    /**
     * @param  numeric-string  $left
     * @param  numeric-string  $right
     * @return numeric-string
     */
    private static function bcmul(string $left, string $right): string
    {
        return bcmul($left, $right, self::SCALE);
    }

    /**
     * @param  numeric-string  $left
     * @param  numeric-string  $right
     * @return numeric-string
     */
    private static function bcdiv(string $left, string $right): string
    {
        return bcdiv($left, $right, self::SCALE);
    }
}
