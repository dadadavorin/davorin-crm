<?php

declare(strict_types=1);

namespace App\Http\Requests\Quote;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Rules\ValidMoneyAmount;
use App\Rules\ValidTaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quote = $this->route('quote');

        return $quote instanceof Quote && ($this->user()?->can('update', $quote) ?? false);
    }

    /**
     * Shape only. `deal_id` and the customer block are absent on purpose —
     * a quote's deal association and the block snapshotted from it are set
     * once, at creation, never re-derived. `items` is `sometimes` because an
     * edit that only changes `notes` (always legal, even once `Sent`)
     * shouldn't have to resubmit the item set; `Quote::booted()` and
     * `QuoteItem::booted()` decide whether a change that *does* touch items,
     * money fields or the customer block is actually allowed.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(QuoteStatus::class)],
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
