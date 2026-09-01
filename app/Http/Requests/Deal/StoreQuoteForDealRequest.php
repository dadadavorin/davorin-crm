<?php

declare(strict_types=1);

namespace App\Http\Requests\Deal;

use App\Models\Quote;
use App\Rules\ValidTaxRate;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape only for the deal-board shortcut. Both fields are optional —
 * `CreateQuoteForDeal` supplies a default for whichever one is omitted —
 * and the deal comes from the route, never the request body.
 */
final class StoreQuoteForDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Quote::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'valid_until' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'string', 'max:10', new ValidTaxRate],
        ];
    }
}
