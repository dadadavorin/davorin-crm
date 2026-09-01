<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;
use App\Policies\ContactPolicy;

function contactUserWith(int $id, UserRole $role): User
{
    $user = new User;
    $user->id = $id;
    $user->role = $role;

    return $user;
}

function contactOwnedBy(?int $ownerId): Contact
{
    $contact = new Contact;
    $contact->owner_id = $ownerId;

    return $contact;
}

test('reads are never scoped by owner', function (): void {
    $policy = new ContactPolicy;
    $stranger = contactUserWith(99, UserRole::Member);
    $contact = contactOwnedBy(1);

    expect($policy->viewAny($stranger))->toBeTrue();
    expect($policy->view($stranger, $contact))->toBeTrue();
});

test('any authenticated user can create or update', function (): void {
    $policy = new ContactPolicy;
    $stranger = contactUserWith(99, UserRole::Member);
    $contact = contactOwnedBy(1);

    expect($policy->create($stranger))->toBeTrue();
    expect($policy->update($stranger, $contact))->toBeTrue();
});

test('the owner can delete', function (): void {
    $policy = new ContactPolicy;
    $owner = contactUserWith(1, UserRole::Member);
    $contact = contactOwnedBy(1);

    expect($policy->delete($owner, $contact))->toBeTrue();
});

test('an admin can delete a contact they do not own', function (): void {
    $policy = new ContactPolicy;
    $admin = contactUserWith(2, UserRole::Admin);
    $contact = contactOwnedBy(1);

    expect($policy->delete($admin, $contact))->toBeTrue();
});

test('a non-owner, non-admin cannot delete', function (): void {
    $policy = new ContactPolicy;
    $stranger = contactUserWith(3, UserRole::Member);
    $contact = contactOwnedBy(1);

    expect($policy->delete($stranger, $contact))->toBeFalse();
});
