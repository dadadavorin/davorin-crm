<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Deal;
use App\Models\User;
use App\Policies\DealPolicy;

function dealUserWith(int $id, UserRole $role): User
{
    $user = new User;
    $user->id = $id;
    $user->role = $role;

    return $user;
}

function dealOwnedBy(?int $ownerId): Deal
{
    $deal = new Deal;
    $deal->owner_id = $ownerId;

    return $deal;
}

test('reads are never scoped by owner', function (): void {
    $policy = new DealPolicy;
    $stranger = dealUserWith(99, UserRole::Member);
    $deal = dealOwnedBy(1);

    expect($policy->viewAny($stranger))->toBeTrue();
    expect($policy->view($stranger, $deal))->toBeTrue();
});

test('any authenticated user can create or update', function (): void {
    $policy = new DealPolicy;
    $stranger = dealUserWith(99, UserRole::Member);
    $deal = dealOwnedBy(1);

    expect($policy->create($stranger))->toBeTrue();
    expect($policy->update($stranger, $deal))->toBeTrue();
});

test('the owner can delete', function (): void {
    $policy = new DealPolicy;
    $owner = dealUserWith(1, UserRole::Member);
    $deal = dealOwnedBy(1);

    expect($policy->delete($owner, $deal))->toBeTrue();
});

test('an admin can delete a deal they do not own', function (): void {
    $policy = new DealPolicy;
    $admin = dealUserWith(2, UserRole::Admin);
    $deal = dealOwnedBy(1);

    expect($policy->delete($admin, $deal))->toBeTrue();
});

test('a non-owner, non-admin cannot delete', function (): void {
    $policy = new DealPolicy;
    $stranger = dealUserWith(3, UserRole::Member);
    $deal = dealOwnedBy(1);

    expect($policy->delete($stranger, $deal))->toBeFalse();
});
