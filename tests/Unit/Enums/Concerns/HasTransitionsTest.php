<?php

declare(strict_types=1);

use Tests\Fixtures\FixtureStatus;

test('an allowed transition is permitted', function (): void {
    expect(FixtureStatus::Draft->canTransitionTo(FixtureStatus::Active))->toBeTrue();
});

test('a disallowed transition is rejected', function (): void {
    expect(FixtureStatus::Draft->canTransitionTo(FixtureStatus::Archived))->toBeFalse();
});

test('a same-status transition is rejected unless explicitly allowed', function (): void {
    expect(FixtureStatus::Draft->canTransitionTo(FixtureStatus::Draft))->toBeFalse();
});

test('a state with no allowed transitions is terminal', function (): void {
    expect(FixtureStatus::Archived->isTerminal())->toBeTrue();
});

test('a state with allowed transitions is not terminal', function (): void {
    expect(FixtureStatus::Draft->isTerminal())->toBeFalse();
    expect(FixtureStatus::Active->isTerminal())->toBeFalse();
});

test('a terminal state permits no transitions at all', function (): void {
    expect(FixtureStatus::Archived->canTransitionTo(FixtureStatus::Draft))->toBeFalse();
    expect(FixtureStatus::Archived->canTransitionTo(FixtureStatus::Active))->toBeFalse();
    expect(FixtureStatus::Archived->canTransitionTo(FixtureStatus::Archived))->toBeFalse();
});

test('label and board order are defined for every case', function (): void {
    expect(FixtureStatus::Draft->label())->toBe('Draft');
    expect(FixtureStatus::Draft->boardOrder())->toBe(1);
    expect(FixtureStatus::Archived->boardOrder())->toBeGreaterThan(FixtureStatus::Draft->boardOrder());
});
