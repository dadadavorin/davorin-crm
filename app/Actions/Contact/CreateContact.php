<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Enums\ContactStatus;
use App\Exceptions\DuplicateEmailException;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CreateContact
{
    /**
     * @param  array<string, mixed>  $data  validated `StoreContactRequest` shape
     */
    public function handle(array $data, User $creator): Contact
    {
        try {
            // A unique-index violation aborts the ambient transaction at the
            // database level, not just the query that triggered it — this
            // savepoint (via DB::transaction()) confines that abort to the
            // write itself instead of poisoning a transaction a caller
            // opened around this action.
            return DB::transaction(fn (): Contact => Contact::query()->create([
                ...$data,
                'status' => $data['status'] ?? ContactStatus::New->value,
                'owner_id' => $data['owner_id'] ?? $creator->id,
            ]));
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                $email = $data['email'] ?? null;

                throw new DuplicateEmailException(is_string($email) ? $email : '');
            }

            throw $e;
        }
    }
}
