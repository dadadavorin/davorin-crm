<?php

declare(strict_types=1);

use App\Enums\DealStage;

test('the lifecycle progresses new through negotiation', function (): void {
    expect(DealStage::New->canTransitionTo(DealStage::Qualified))->toBeTrue();
    expect(DealStage::Qualified->canTransitionTo(DealStage::Proposal))->toBeTrue();
    expect(DealStage::Proposal->canTransitionTo(DealStage::Negotiation))->toBeTrue();
});

test('negotiation can be won or lost', function (): void {
    expect(DealStage::Negotiation->canTransitionTo(DealStage::Won))->toBeTrue();
    expect(DealStage::Negotiation->canTransitionTo(DealStage::Lost))->toBeTrue();
});

test('the lifecycle cannot skip stages', function (): void {
    expect(DealStage::New->canTransitionTo(DealStage::Proposal))->toBeFalse();
    expect(DealStage::New->canTransitionTo(DealStage::Won))->toBeFalse();
    expect(DealStage::Qualified->canTransitionTo(DealStage::Negotiation))->toBeFalse();
});

test('the lifecycle cannot move backward', function (): void {
    expect(DealStage::Proposal->canTransitionTo(DealStage::Qualified))->toBeFalse();
    expect(DealStage::Negotiation->canTransitionTo(DealStage::Proposal))->toBeFalse();
});

test('won and lost cannot transition anywhere, including to each other', function (): void {
    expect(DealStage::Won->canTransitionTo(DealStage::Negotiation))->toBeFalse();
    expect(DealStage::Won->canTransitionTo(DealStage::Lost))->toBeFalse();
    expect(DealStage::Lost->canTransitionTo(DealStage::Negotiation))->toBeFalse();
    expect(DealStage::Lost->canTransitionTo(DealStage::Won))->toBeFalse();
});

test('won and lost are terminal, nothing else is', function (): void {
    expect(DealStage::Won->isTerminal())->toBeTrue();
    expect(DealStage::Lost->isTerminal())->toBeTrue();

    foreach ([DealStage::New, DealStage::Qualified, DealStage::Proposal, DealStage::Negotiation] as $stage) {
        expect($stage->isTerminal())->toBeFalse();
    }
});

test('label and board order are defined for every case in stage order', function (): void {
    $cases = DealStage::cases();

    foreach ($cases as $stage) {
        expect($stage->label())->not->toBe('');
    }

    $ordered = collect($cases)->sortBy(fn (DealStage $stage): int => $stage->boardOrder())->values();

    expect($ordered->all())->toBe($cases);
});
