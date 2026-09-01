<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * The contract every status-holding backed enum implements. Each enum
 * defines its own `allowedTransitions()`, `label()` and `boardOrder()`;
 * `canTransitionTo()` and `isTerminal()` are derived once here so no enum
 * can define one without the other staying consistent.
 */
trait HasTransitions
{
    /**
     * @return list<self>
     */
    abstract public function allowedTransitions(): array;

    abstract public function label(): string;

    abstract public function boardOrder(): int;

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
