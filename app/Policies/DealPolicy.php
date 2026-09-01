<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Deal;
use App\Models\User;

/**
 * Reads are never scoped by owner — every authenticated user sees every
 * deal. Only delete is owner-or-admin, the same shape as `CompanyPolicy`
 * and `ContactPolicy`. Reopening a terminal deal goes through `update`.
 */
final class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deal $deal): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deal $deal): bool
    {
        return true;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->role === UserRole::Admin || $user->id === $deal->owner_id;
    }
}
