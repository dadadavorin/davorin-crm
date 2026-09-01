<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when the `contacts.email` partial unique index (live rows only)
 * rejects an insert or update — translated from SQLSTATE 23505 rather than
 * a pre-write existence check, per `CONVENTIONS.md` §4.
 */
final class DuplicateEmailException extends DomainException
{
    public function __construct(public readonly string $email)
    {
        parent::__construct("Cannot save this contact: \"{$email}\" is already in use by another contact.");
    }
}
