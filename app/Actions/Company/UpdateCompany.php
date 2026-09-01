<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;

final class UpdateCompany
{
    /**
     * @param  array<string, mixed>  $data  validated `UpdateCompanyRequest` shape
     */
    public function handle(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }
}
