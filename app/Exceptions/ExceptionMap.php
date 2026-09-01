<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * The single table mapping a domain exception class to an HTTP status and
 * a message key. Both renderers (`InertiaExceptionRenderer`,
 * `ProblemJsonExceptionRenderer`) read this instead of deciding on their
 * own, so the two response shapes can never drift apart for the same
 * exception.
 */
final class ExceptionMap
{
    /**
     * @var array<class-string<Throwable>, array{status: int, key: string}>
     */
    private const array MAP = [
        RecordHasDependentsException::class => ['status' => 422, 'key' => 'record_has_dependents'],
        UnknownBoardStatusException::class => ['status' => 422, 'key' => 'unknown_board_status'],
        IllegalStatusTransitionException::class => ['status' => 422, 'key' => 'illegal_status_transition'],
        InvalidBoardNeighbourException::class => ['status' => 422, 'key' => 'invalid_board_neighbour'],
        DuplicateEmailException::class => ['status' => 422, 'key' => 'duplicate_email'],
        QuoteNotEditableException::class => ['status' => 422, 'key' => 'quote_not_editable'],
    ];

    private const array DEFAULT = ['status' => 500, 'key' => 'server_error'];

    public static function resolve(Throwable $e): ExceptionMapping
    {
        foreach (self::MAP as $class => $entry) {
            if ($e instanceof $class) {
                return new ExceptionMapping($entry['status'], $entry['key']);
            }
        }

        return new ExceptionMapping(self::DEFAULT['status'], self::DEFAULT['key']);
    }
}
