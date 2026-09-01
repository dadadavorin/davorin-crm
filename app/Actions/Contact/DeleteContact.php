<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

/**
 * Unlike `DeleteCompany`, a contact delete is never refused (ADR-0005's
 * asymmetry): `deals.primary_contact_id` is nullable by design, so live
 * deals have it nulled here (one bulk update, never one query per deal)
 * instead of the delete being blocked.
 */
final class DeleteContact
{
    public function handle(Contact $contact): void
    {
        DB::transaction(function () use ($contact): void {
            Deal::query()->where('primary_contact_id', $contact->id)->update(['primary_contact_id' => null]);

            $contact->delete();
        });
    }
}
