<?php

declare(strict_types=1);

namespace App\Http\Requests\Deal;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Rules\ContactBelongsToCompany;
use App\Rules\ValidMoneyAmount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $deal = $this->route('deal');

        return $deal instanceof Deal && ($this->user()?->can('update', $deal) ?? false);
    }

    /**
     * Shape only — present, string, max length. `DealStage` and `Money`
     * decide what is actually valid; the terminal-stage guard on `Deal`
     * itself decides whether a `stage` change is actually allowed.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:20', new ValidMoneyAmount],
            'stage' => ['required', Rule::enum(DealStage::class)],
            'expected_close_date' => ['nullable', 'date'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'primary_contact_id' => ['nullable', 'integer', 'exists:contacts,id', new ContactBelongsToCompany],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
