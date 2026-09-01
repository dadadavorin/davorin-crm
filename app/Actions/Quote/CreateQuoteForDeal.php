<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Date;

/**
 * The deal-board shortcut (T9): a thin wrapper around `CreateQuote` (T8)
 * that fills in what the shortcut never asks for — the deal itself, today
 * as the issue date, and a zero-item quote — so numbering, the customer
 * block snapshot and totals all run through the exact same mechanics as a
 * standalone create. Never touches the deal itself.
 */
final class CreateQuoteForDeal
{
    private const string DEFAULT_TAX_RATE = '0.2500';

    private const int DEFAULT_VALIDITY_DAYS = 30;

    public function __construct(private readonly CreateQuote $createQuote) {}

    /**
     * @param  array<string, mixed>  $data  validated `StoreQuoteForDealRequest` shape
     */
    public function handle(Deal $deal, array $data, User $creator): Quote
    {
        return $this->createQuote->handle([
            'deal_id' => $deal->id,
            'issue_date' => Date::now()->toDateString(),
            'valid_until' => $data['valid_until'] ?? self::defaultValidUntil(),
            'tax_rate' => $data['tax_rate'] ?? self::DEFAULT_TAX_RATE,
            'items' => [],
        ], $creator);
    }

    public static function defaultTaxRate(): string
    {
        return self::DEFAULT_TAX_RATE;
    }

    public static function defaultValidUntil(): string
    {
        return Date::now()->addDays(self::DEFAULT_VALIDITY_DAYS)->toDateString();
    }
}
