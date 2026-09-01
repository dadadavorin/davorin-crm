<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Contact;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A deal's primary contact must belong to the deal's own company — a
 * contact from a different company would misrepresent who the deal is
 * actually with. Reads the sibling `company_id` field via `DataAwareRule`.
 */
final class ContactBelongsToCompany implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $companyId = $this->data['company_id'] ?? null;

        if ($companyId === null) {
            return;
        }

        $belongs = Contact::query()->whereKey($value)->where('company_id', $companyId)->exists();

        if (! $belongs) {
            $fail('The :attribute must belong to the selected company.');
        }
    }
}
