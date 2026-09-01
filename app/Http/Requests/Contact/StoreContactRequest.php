<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Rules\ValidEmailAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Contact::class) ?? false;
    }

    /**
     * Shape only — present, string, max length. `ContactStatus` and
     * `EmailAddress` decide what is actually valid; the partial unique
     * index on `contacts.email` decides whether it's taken.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ContactStatus::class)],
            'email' => ['nullable', 'string', 'max:255', new ValidEmailAddress],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
