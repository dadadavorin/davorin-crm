<?php

declare(strict_types=1);

use App\Support\Money;
use App\Support\MoneyCast;
use Illuminate\Database\Eloquent\Model;

function moneyCastModel(): Model
{
    return new class extends Model
    {
        protected $casts = ['amount_minor' => MoneyCast::class];

        protected $guarded = [];
    };
}

test('a stored minor-unit integer casts to a Money instance', function (): void {
    $model = moneyCastModel();
    $model->setRawAttributes(['amount_minor' => 4250]);

    expect($model->amount_minor)->toBeInstanceOf(Money::class);
    expect($model->amount_minor->minorUnits)->toBe(4250);
});

test('assigning a Money instance casts back to its minor-unit integer', function (): void {
    $model = moneyCastModel();
    $model->amount_minor = Money::fromMinorUnits(999);

    expect($model->getAttributes()['amount_minor'])->toBe(999);
});

test('null round-trips as null', function (): void {
    $model = moneyCastModel();
    $model->amount_minor = null;

    expect($model->amount_minor)->toBeNull();
    expect($model->getAttributes()['amount_minor'])->toBeNull();
});

test('assigning a non-Money value is rejected', function (): void {
    $model = moneyCastModel();
    $model->amount_minor = 500;
})->throws(InvalidArgumentException::class);
