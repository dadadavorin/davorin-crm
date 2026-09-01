<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;

test('the lifecycle progresses draft to sent', function (): void {
    expect(QuoteStatus::Draft->canTransitionTo(QuoteStatus::Sent))->toBeTrue();
});

test('sent can be accepted, rejected or expired', function (): void {
    expect(QuoteStatus::Sent->canTransitionTo(QuoteStatus::Accepted))->toBeTrue();
    expect(QuoteStatus::Sent->canTransitionTo(QuoteStatus::Rejected))->toBeTrue();
    expect(QuoteStatus::Sent->canTransitionTo(QuoteStatus::Expired))->toBeTrue();
});

test('draft cannot skip straight to a terminal status', function (): void {
    expect(QuoteStatus::Draft->canTransitionTo(QuoteStatus::Accepted))->toBeFalse();
    expect(QuoteStatus::Draft->canTransitionTo(QuoteStatus::Rejected))->toBeFalse();
    expect(QuoteStatus::Draft->canTransitionTo(QuoteStatus::Expired))->toBeFalse();
});

test('sent cannot move backward to draft', function (): void {
    expect(QuoteStatus::Sent->canTransitionTo(QuoteStatus::Draft))->toBeFalse();
});

test('accepted, rejected and expired cannot transition anywhere, including to each other', function (): void {
    foreach ([QuoteStatus::Accepted, QuoteStatus::Rejected, QuoteStatus::Expired] as $terminal) {
        foreach (QuoteStatus::cases() as $target) {
            expect($terminal->canTransitionTo($target))->toBeFalse();
        }
    }
});

test('accepted, rejected and expired are terminal, nothing else is', function (): void {
    expect(QuoteStatus::Accepted->isTerminal())->toBeTrue();
    expect(QuoteStatus::Rejected->isTerminal())->toBeTrue();
    expect(QuoteStatus::Expired->isTerminal())->toBeTrue();

    expect(QuoteStatus::Draft->isTerminal())->toBeFalse();
    expect(QuoteStatus::Sent->isTerminal())->toBeFalse();
});

test('label and board order are defined for every case in status order', function (): void {
    $cases = QuoteStatus::cases();

    foreach ($cases as $status) {
        expect($status->label())->not->toBe('');
    }

    $ordered = collect($cases)->sortBy(fn (QuoteStatus $status): int => $status->boardOrder())->values();

    expect($ordered->all())->toBe($cases);
});
