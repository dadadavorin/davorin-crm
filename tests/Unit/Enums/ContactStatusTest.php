<?php

declare(strict_types=1);

use App\Enums\ContactStatus;

test('the lifecycle progresses new through inactive', function (): void {
    expect(ContactStatus::New->canTransitionTo(ContactStatus::Active))->toBeTrue();
    expect(ContactStatus::Active->canTransitionTo(ContactStatus::Inactive))->toBeTrue();
});

test('inactive can reopen back to active', function (): void {
    expect(ContactStatus::Inactive->canTransitionTo(ContactStatus::Active))->toBeTrue();
});

test('the lifecycle cannot skip from new to inactive', function (): void {
    expect(ContactStatus::New->canTransitionTo(ContactStatus::Inactive))->toBeFalse();
});

test('the lifecycle cannot move backward from active to new', function (): void {
    expect(ContactStatus::Active->canTransitionTo(ContactStatus::New))->toBeFalse();
});

test('no status is terminal', function (): void {
    foreach (ContactStatus::cases() as $status) {
        expect($status->isTerminal())->toBeFalse();
    }
});

test('label and board order are defined for every case', function (): void {
    foreach (ContactStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }

    expect(ContactStatus::New->boardOrder())->toBeLessThan(ContactStatus::Active->boardOrder());
    expect(ContactStatus::Active->boardOrder())->toBeLessThan(ContactStatus::Inactive->boardOrder());
});
