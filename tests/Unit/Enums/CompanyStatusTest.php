<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;

test('the lifecycle progresses lead through inactive', function (): void {
    expect(CompanyStatus::Lead->canTransitionTo(CompanyStatus::Prospect))->toBeTrue();
    expect(CompanyStatus::Prospect->canTransitionTo(CompanyStatus::Customer))->toBeTrue();
    expect(CompanyStatus::Customer->canTransitionTo(CompanyStatus::Inactive))->toBeTrue();
});

test('inactive can reopen back to lead', function (): void {
    expect(CompanyStatus::Inactive->canTransitionTo(CompanyStatus::Lead))->toBeTrue();
});

test('the lifecycle cannot skip a stage', function (): void {
    expect(CompanyStatus::Lead->canTransitionTo(CompanyStatus::Customer))->toBeFalse();
    expect(CompanyStatus::Lead->canTransitionTo(CompanyStatus::Inactive))->toBeFalse();
});

test('the lifecycle cannot move backward except inactive to lead', function (): void {
    expect(CompanyStatus::Prospect->canTransitionTo(CompanyStatus::Lead))->toBeFalse();
    expect(CompanyStatus::Customer->canTransitionTo(CompanyStatus::Prospect))->toBeFalse();
});

test('no status is terminal', function (): void {
    foreach (CompanyStatus::cases() as $status) {
        expect($status->isTerminal())->toBeFalse();
    }
});

test('label and board order are defined for every case', function (): void {
    foreach (CompanyStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }

    expect(CompanyStatus::Lead->boardOrder())->toBeLessThan(CompanyStatus::Prospect->boardOrder());
    expect(CompanyStatus::Prospect->boardOrder())->toBeLessThan(CompanyStatus::Customer->boardOrder());
    expect(CompanyStatus::Customer->boardOrder())->toBeLessThan(CompanyStatus::Inactive->boardOrder());
});
