<?php

declare(strict_types=1);

namespace App\Http\Requests\Quote;

use App\Models\Quote;
use App\Rules\ValidMoneyAmount;
use App\Rules\ValidTaxRate;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Quote::class) ?? false;
    }

    /**
     * Shape only — present, string, max length, at-least-one-decimal. The
     * customer block is never submitted here: `CreateQuote` derives it from
     * the chosen deal (`SnapshotCustomerBlock`), which is what "snapshot"
     * means — nothing on the form can override it. `items` is `sometimes`
     * rather than `required` because a plain HTML form has no way to submit
     * an explicit empty array — omitting the key entirely is how a
     * zero-item quote (a real case the totals invariant test covers) is
     * expressed; `CreateQuote` treats an absent key the same as `[]`.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'deal_id' => ['required', 'integer', 'exists:deals,id'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['required', 'string', 'max:10', new ValidTaxRate],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['sometimes', 'array'],
            'items.*.description' => ['required', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'string', 'max:20', new ValidMoneyAmount],
        ];
    }
}
