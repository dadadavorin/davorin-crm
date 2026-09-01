<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Models\Deal;

/**
 * Derives the customer block frozen onto a quote at creation time (T8) —
 * bill-to company name, address, contact name and email, taken from the
 * deal's company and primary contact. Never called again after that: once
 * written, the block is a snapshot, not a live join, so a later company
 * rename or contact change cannot alter an existing quote. `CreateQuote`
 * uses this now; the deal-board shortcut (T9) reuses it unchanged.
 */
final class SnapshotCustomerBlock
{
    /**
     * @return array{bill_to_company_name: string, bill_to_address: string|null, bill_to_contact_name: string|null, bill_to_contact_email: string|null}
     */
    public static function fromDeal(Deal $deal): array
    {
        $contact = $deal->primaryContact;

        return [
            'bill_to_company_name' => $deal->company->name,
            'bill_to_address' => $deal->company->address,
            'bill_to_contact_name' => $contact === null ? null : trim("{$contact->first_name} {$contact->last_name}"),
            'bill_to_contact_email' => $contact?->email?->value,
        ];
    }
}
