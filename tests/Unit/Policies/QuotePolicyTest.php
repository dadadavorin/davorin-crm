<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Quote;
use App\Models\User;
use App\Policies\QuotePolicy;

function quoteUserWith(int $id, UserRole $role): User
{
    $user = new User;
    $user->id = $id;
    $user->role = $role;

    return $user;
}

function quoteOwnedBy(?int $ownerId): Quote
{
    $quote = new Quote;
    $quote->owner_id = $ownerId;

    return $quote;
}

test('reads are never scoped by owner', function (): void {
    $policy = new QuotePolicy;
    $stranger = quoteUserWith(99, UserRole::Member);
    $quote = quoteOwnedBy(1);

    expect($policy->viewAny($stranger))->toBeTrue();
    expect($policy->view($stranger, $quote))->toBeTrue();
});

test('any authenticated user can create or update', function (): void {
    $policy = new QuotePolicy;
    $stranger = quoteUserWith(99, UserRole::Member);
    $quote = quoteOwnedBy(1);

    expect($policy->create($stranger))->toBeTrue();
    expect($policy->update($stranger, $quote))->toBeTrue();
});

test('the owner can delete', function (): void {
    $policy = new QuotePolicy;
    $owner = quoteUserWith(1, UserRole::Member);
    $quote = quoteOwnedBy(1);

    expect($policy->delete($owner, $quote))->toBeTrue();
});

test('an admin can delete a quote they do not own', function (): void {
    $policy = new QuotePolicy;
    $admin = quoteUserWith(2, UserRole::Admin);
    $quote = quoteOwnedBy(1);

    expect($policy->delete($admin, $quote))->toBeTrue();
});

test('a non-owner, non-admin cannot delete', function (): void {
    $policy = new QuotePolicy;
    $stranger = quoteUserWith(3, UserRole::Member);
    $quote = quoteOwnedBy(1);

    expect($policy->delete($stranger, $quote))->toBeFalse();
});
