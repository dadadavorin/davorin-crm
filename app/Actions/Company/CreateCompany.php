<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;

final class CreateCompany
{
    /**
     * @param  array<string, mixed>  $data  validated `StoreCompanyRequest` shape
     */
    public function handle(array $data, User $creator): Company
    {
        return Company::query()->create([
            ...$data,
            'status' => $data['status'] ?? CompanyStatus::Lead->value,
            'owner_id' => $data['owner_id'] ?? $creator->id,
        ]);
    }
}
