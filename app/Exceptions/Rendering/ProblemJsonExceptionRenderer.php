<?php

declare(strict_types=1);

namespace App\Exceptions\Rendering;

use App\Exceptions\DomainException;
use App\Exceptions\ExceptionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renders a domain exception as an RFC 9457 `application/problem+json`
 * body, for the `/api/v1/*` JSON surface (the board move endpoint —
 * see ADR-0006).
 */
final class ProblemJsonExceptionRenderer
{
    public function render(DomainException $e, Request $request): JsonResponse
    {
        $mapping = ExceptionMap::resolve($e);

        return response()->json([
            'type' => 'about:blank',
            'title' => $mapping->messageKey,
            'status' => $mapping->status,
            'detail' => $e->getMessage(),
        ], $mapping->status, ['Content-Type' => 'application/problem+json']);
    }
}
