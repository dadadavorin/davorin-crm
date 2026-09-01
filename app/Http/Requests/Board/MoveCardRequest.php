<?php

declare(strict_types=1);

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape only for a board move — which status the card is dropping into and,
 * optionally, the ids of the cards it lands between. Whether the status
 * value names a real enum case, whether the transition is allowed, and
 * whether the neighbour ids are actually in the target column are business
 * rules `MoveCardAction` decides, not this request. Authorization happens
 * in the controller, once the target model is resolved from the route.
 */
final class MoveCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:255'],
            'before_id' => ['nullable', 'integer'],
            'after_id' => ['nullable', 'integer'],
        ];
    }
}
