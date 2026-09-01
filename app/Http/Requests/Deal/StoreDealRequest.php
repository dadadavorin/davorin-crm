<?php

declare(strict_types=1);

namespace App\Http\Requests\Deal;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Rules\ContactBelongsToCompany;
use App\Rules\ValidMoneyAmount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Deal::class) ?? false;
    }

    /**
     * Shape only — present, string, max length. `DealStage` and `Money`
     * decide what is actually valid; `ContactBelongsToCompany` is the one
     * cross-field shape check (a deal's primary contact must belong to its
     * own company).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:20', new ValidMoneyAmount],
            'stage' => ['nullable', Rule::enum(DealStage::class)],
            'expected_close_date' => ['nullable', 'date'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'primary_contact_id' => ['nullable', 'integer', 'exists:contacts,id', new ContactBelongsToCompany],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
