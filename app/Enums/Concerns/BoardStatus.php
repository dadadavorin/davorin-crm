<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * The type every status enum consumed by the board engine satisfies.
 * `HasTransitions` supplies the implementation; this interface is what
 * `BoardBuilder` and `MoveCardAction` type-hint against so they never need
 * to know which concrete enum a given entity uses.
 *
 * `canTransitionTo()` is deliberately documented rather than declared: PHP
 * requires a `self`-typed parameter to match the interface's own type
 * exactly, which would make `self` mean "BoardStatus" instead of "whichever
 * concrete enum implements this" and break every enum's own signature. The
 * `@method` tag gives callers and static analysis the same guarantee
 * without that conflict.
 *
 * @method bool canTransitionTo(self $to)
 */
interface BoardStatus extends \BackedEnum
{
    /**
     * @return list<self>
     */
    public function allowedTransitions(): array;

    public function isTerminal(): bool;

    public function label(): string;

    public function boardOrder(): int;
}
