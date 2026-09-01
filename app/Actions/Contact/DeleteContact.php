<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Models\Contact;

/**
 * Unlike `DeleteCompany`, a contact delete is never refused (ADR-0005's
 * asymmetry): `deals.primary_contact_id` is nullable by design, so deals
 * (T7) null it instead of blocking here.
 */
final class DeleteContact
{
    public function handle(Contact $contact): void
    {
        $contact->delete();
    }
}
