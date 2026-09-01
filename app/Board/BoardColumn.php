<?php

declare(strict_types=1);

namespace App\Board;

/**
 * One column of a rendered board: the cards `BoardBuilder` decided to
 * include (capped at its column limit) plus enough of a count to tell the
 * frontend more exist.
 */
final readonly class BoardColumn
{
    /**
     * @param  list<array<string, mixed>>  $cards
     */
    public function __construct(
        public string $status,
        public string $label,
        public array $cards,
        public int $total,
        public bool $hasMore,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'label' => $this->label,
            'cards' => $this->cards,
            'total' => $this->total,
            'has_more' => $this->hasMore,
        ];
    }
}
