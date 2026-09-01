<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Str;

/**
 * Thrown when a delete is refused because other live records still point
 * to it (ADR-0005) — a company with live contacts or deals, for example.
 */
final class RecordHasDependentsException extends DomainException
{
    /**
     * @param  array<string, int>  $dependents  counts keyed by plural relation label, e.g. ['contacts' => 3, 'deals' => 1]
     */
    public function __construct(
        public readonly string $entity,
        public readonly array $dependents,
    ) {
        parent::__construct(self::describe($entity, $dependents));
    }

    /**
     * @param  array<string, int>  $dependents
     */
    private static function describe(string $entity, array $dependents): string
    {
        $parts = [];

        foreach ($dependents as $label => $count) {
            $parts[] = sprintf('%d live %s', $count, $count === 1 ? Str::singular($label) : $label);
        }

        return sprintf('Cannot delete this %s: %s depend on it.', $entity, implode(' and ', $parts));
    }
}
