<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Support\Money;

final class CreateDeal
{
    /**
     * @param  array<string, mixed>  $data  validated `StoreDealRequest` shape
     */
    public function handle(array $data, User $creator): Deal
    {
        $value = $data['value'] ?? null;

        return Deal::query()->create([
            'title' => $data['title'],
            'value_minor' => is_string($value) && $value !== '' ? Money::fromDecimalString($value) : null,
            'stage' => $data['stage'] ?? DealStage::New->value,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'company_id' => $data['company_id'],
            'primary_contact_id' => $data['primary_contact_id'] ?? null,
            'owner_id' => $data['owner_id'] ?? $creator->id,
        ]);
    }
}
