<?php

declare(strict_types=1);

namespace App\Exceptions;

final readonly class ExceptionMapping
{
    public function __construct(
        public int $status,
        public string $messageKey,
    ) {}
}
