<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Quote\CreateQuote;
use App\Enums\QuoteStatus;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Collection;
use LogicException;

/**
 * Ten quotes spread across every `QuoteStatus`, so the quotes board never
 * opens with an empty column. Each is created through `CreateQuote` so
 * numbering, the customer-block snapshot and totals all run through the
 * same mechanics a real submission would use; the status a quote settles in
 * (and, for `Expired`, a `valid_until` already in the past) is applied
 * afterward, since seeding a document's final state doesn't need to walk it
 * through every status it passed on the way there.
 */
final class QuoteSeeder
{
    /**
     * @var list<array{dealIndex: int, status: QuoteStatus, issuedDaysAgo: int, validDays: int, items: list<array{description: string, quantity: int, unit_price: string}>}>
     */
    private const array QUOTES = [
        [
            'dealIndex' => 7, 'status' => QuoteStatus::Draft, 'issuedDaysAgo' => 0, 'validDays' => 30,
            'items' => [
                ['description' => 'Konzultantske usluge — analiza postojeće proizvodne linije', 'quantity' => 40, 'unit_price' => '85.00'],
                ['description' => 'Izrada tehničke dokumentacije', 'quantity' => 1, 'unit_price' => '1200.00'],
            ],
        ],
        [
            'dealIndex' => 9, 'status' => QuoteStatus::Draft, 'issuedDaysAgo' => 0, 'validDays' => 30,
            'items' => [
                ['description' => 'Licenca za softver upravljanja zalihama', 'quantity' => 1, 'unit_price' => '3200.00'],
                ['description' => 'Implementacija sustava', 'quantity' => 1, 'unit_price' => '2500.00'],
                ['description' => 'Obuka zaposlenika', 'quantity' => 3, 'unit_price' => '150.00'],
            ],
        ],
        [
            'dealIndex' => 11, 'status' => QuoteStatus::Sent, 'issuedDaysAgo' => 10, 'validDays' => 30,
            'items' => [
                ['description' => 'Nabava informatičke opreme', 'quantity' => 8, 'unit_price' => '650.00'],
                ['description' => 'Instalacija i konfiguracija', 'quantity' => 1, 'unit_price' => '480.00'],
            ],
        ],
        [
            'dealIndex' => 12, 'status' => QuoteStatus::Sent, 'issuedDaysAgo' => 5, 'validDays' => 30,
            'items' => [
                ['description' => 'Izrada web aplikacije — dizajn korisničkog sučelja', 'quantity' => 1, 'unit_price' => '2800.00'],
                ['description' => 'Izrada web aplikacije — razvoj', 'quantity' => 1, 'unit_price' => '6200.00'],
                ['description' => 'Testiranje sustava', 'quantity' => 1, 'unit_price' => '900.00'],
            ],
        ],
        [
            'dealIndex' => 14, 'status' => QuoteStatus::Accepted, 'issuedDaysAgo' => 30, 'validDays' => 30,
            'items' => [
                ['description' => 'Licenca za ERP sustav', 'quantity' => 1, 'unit_price' => '18500.00'],
                ['description' => 'Migracija podataka', 'quantity' => 1, 'unit_price' => '4200.00'],
                ['description' => 'Obuka zaposlenika', 'quantity' => 10, 'unit_price' => '120.00'],
            ],
        ],
        [
            'dealIndex' => 15, 'status' => QuoteStatus::Accepted, 'issuedDaysAgo' => 45, 'validDays' => 30,
            'items' => [
                ['description' => 'Godišnji ugovor o distribuciji — naknada za uspostavu', 'quantity' => 1, 'unit_price' => '5000.00'],
                ['description' => 'Mjesečno održavanje', 'quantity' => 12, 'unit_price' => '190.00'],
            ],
        ],
        [
            'dealIndex' => 18, 'status' => QuoteStatus::Rejected, 'issuedDaysAgo' => 20, 'validDays' => 30,
            'items' => [
                ['description' => 'Nabava ambalažne opreme', 'quantity' => 4, 'unit_price' => '2100.00'],
                ['description' => 'Dostava i montaža', 'quantity' => 1, 'unit_price' => '600.00'],
            ],
        ],
        [
            'dealIndex' => 19, 'status' => QuoteStatus::Rejected, 'issuedDaysAgo' => 50, 'validDays' => 30,
            'items' => [
                ['description' => 'Konzultantske usluge za restrukturiranje logistike', 'quantity' => 60, 'unit_price' => '95.00'],
            ],
        ],
        [
            'dealIndex' => 16, 'status' => QuoteStatus::Expired, 'issuedDaysAgo' => 60, 'validDays' => -10,
            'items' => [
                ['description' => 'Sponzorski paket — logotip i promotivni materijali', 'quantity' => 1, 'unit_price' => '2500.00'],
            ],
        ],
        [
            'dealIndex' => 3, 'status' => QuoteStatus::Expired, 'issuedDaysAgo' => 70, 'validDays' => -15,
            'items' => [
                ['description' => 'Izgradnja skladišnog objekta — pripremni radovi', 'quantity' => 1, 'unit_price' => '45000.00'],
                ['description' => 'Prilagodba korisničkog sučelja nadzornog sustava', 'quantity' => 1, 'unit_price' => '3100.00'],
            ],
        ],
    ];

    private const string TAX_RATE = '0.2500';

    /**
     * @param  Collection<int, Deal>  $deals  in the same order `DealSeeder` created them
     */
    public function run(User $admin, Collection $deals): void
    {
        $dealsList = $deals->values();
        $positionByStatus = [];

        foreach (self::QUOTES as $definition) {
            $status = $definition['status'];
            $positionByStatus[$status->value] = ($positionByStatus[$status->value] ?? 0) + 1;
            $deal = $dealsList->get($definition['dealIndex']);

            if (! $deal instanceof Deal) {
                throw new LogicException('DealSeeder must run before QuoteSeeder.');
            }

            $quote = app(CreateQuote::class)->handle([
                'deal_id' => $deal->id,
                'issue_date' => now()->subDays($definition['issuedDaysAgo'])->toDateString(),
                'valid_until' => now()->addDays($definition['validDays'])->toDateString(),
                'tax_rate' => self::TAX_RATE,
                'items' => $definition['items'],
                'owner_id' => $admin->id,
            ], $admin);

            $quote->forceFill([
                'status' => $status,
                'position' => (string) ($positionByStatus[$status->value] * 1024),
            ])->save();
        }
    }
}
