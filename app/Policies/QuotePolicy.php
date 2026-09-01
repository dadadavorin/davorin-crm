<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Quote;
use App\Models\User;

/**
 * Reads are never scoped by owner — every authenticated user sees every
 * quote. Only delete is owner-or-admin, the same shape as `DealPolicy`.
 * Reopening a terminal quote goes through `update`.
 */
final class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quote $quote): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Quote $quote): bool
    {
        return true;
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $user->role === UserRole::Admin || $user->id === $quote->owner_id;
    }
}
