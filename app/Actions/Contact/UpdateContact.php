<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Exceptions\DuplicateEmailException;
use App\Models\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class UpdateContact
{
    /**
     * @param  array<string, mixed>  $data  validated `UpdateContactRequest` shape
     */
    public function handle(Contact $contact, array $data): Contact
    {
        try {
            // See `CreateContact` — the savepoint confines a unique-index
            // abort to this write instead of poisoning an ambient transaction.
            DB::transaction(fn (): bool => $contact->update($data));
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                $email = $data['email'] ?? null;

                throw new DuplicateEmailException(is_string($email) ? $email : '');
            }

            throw $e;
        }

        return $contact;
    }
}
