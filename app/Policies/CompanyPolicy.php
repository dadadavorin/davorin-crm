<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

/**
 * Reads are never scoped by owner — every authenticated user sees every
 * company. Only delete is owner-or-admin (ADR-0005's dependent-check
 * refusal applies on top of this, not instead of it).
 */
final class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return true;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->role === UserRole::Admin || $user->id === $company->owner_id;
    }
}
