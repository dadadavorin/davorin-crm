<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Models\Deal;
use App\Support\Money;

final class UpdateDeal
{
    /**
     * @param  array<string, mixed>  $data  validated `UpdateDealRequest` shape
     */
    public function handle(Deal $deal, array $data): Deal
    {
        $value = $data['value'] ?? null;

        $deal->update([
            'title' => $data['title'],
            'value_minor' => is_string($value) && $value !== '' ? Money::fromDecimalString($value) : null,
            'stage' => $data['stage'],
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'company_id' => $data['company_id'],
            'primary_contact_id' => $data['primary_contact_id'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
        ]);

        return $deal;
    }
}
