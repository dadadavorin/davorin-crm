<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Policies\CompanyPolicy;

function userWith(int $id, UserRole $role): User
{
    $user = new User;
    $user->id = $id;
    $user->role = $role;

    return $user;
}

function companyOwnedBy(?int $ownerId): Company
{
    $company = new Company;
    $company->owner_id = $ownerId;

    return $company;
}

test('reads are never scoped by owner', function (): void {
    $policy = new CompanyPolicy;
    $stranger = userWith(99, UserRole::Member);
    $company = companyOwnedBy(1);

    expect($policy->viewAny($stranger))->toBeTrue();
    expect($policy->view($stranger, $company))->toBeTrue();
});

test('any authenticated user can create or update', function (): void {
    $policy = new CompanyPolicy;
    $stranger = userWith(99, UserRole::Member);
    $company = companyOwnedBy(1);

    expect($policy->create($stranger))->toBeTrue();
    expect($policy->update($stranger, $company))->toBeTrue();
});

test('the owner can delete', function (): void {
    $policy = new CompanyPolicy;
    $owner = userWith(1, UserRole::Member);
    $company = companyOwnedBy(1);

    expect($policy->delete($owner, $company))->toBeTrue();
});

test('an admin can delete a company they do not own', function (): void {
    $policy = new CompanyPolicy;
    $admin = userWith(2, UserRole::Admin);
    $company = companyOwnedBy(1);

    expect($policy->delete($admin, $company))->toBeTrue();
});

test('a non-owner, non-admin cannot delete', function (): void {
    $policy = new CompanyPolicy;
    $stranger = userWith(3, UserRole::Member);
    $company = companyOwnedBy(1);

    expect($policy->delete($stranger, $company))->toBeFalse();
});
