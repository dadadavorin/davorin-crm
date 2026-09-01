<?php

declare(strict_types=1);

use App\Support\EmailAddress;
use App\Support\EmailAddressCast;
use Illuminate\Database\Eloquent\Model;

function emailCastModel(): Model
{
    return new class extends Model
    {
        protected $casts = ['email' => EmailAddressCast::class];

        protected $guarded = [];
    };
}

test('a stored raw string casts to a normalized EmailAddress instance', function (): void {
    $model = emailCastModel();
    $model->setRawAttributes(['email' => 'Jane@Example.COM']);

    expect($model->email)->toBeInstanceOf(EmailAddress::class);
    expect($model->email->value)->toBe('jane@example.com');
});

test('assigning a raw string normalizes before storing', function (): void {
    $model = emailCastModel();
    $model->email = '  Jane@Example.COM  ';

    expect($model->getAttributes()['email'])->toBe('jane@example.com');
});

test('assigning an EmailAddress instance stores its normalized value', function (): void {
    $model = emailCastModel();
    $model->email = new EmailAddress('Jane@Example.com');

    expect($model->getAttributes()['email'])->toBe('jane@example.com');
});

test('null round-trips as null', function (): void {
    $model = emailCastModel();
    $model->email = null;

    expect($model->email)->toBeNull();
    expect($model->getAttributes()['email'])->toBeNull();
});
