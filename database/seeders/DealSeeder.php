<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use LogicException;

/**
 * Twenty deals spread across every `DealStage`, including `Won` and `Lost`,
 * so the deals board never opens with an empty column.
 */
final class DealSeeder
{
    /**
     * @var list<array{title: string, stage: DealStage, valueMinor: int, closeOffsetDays: int, hasContact: bool}>
     */
    private const array DEALS = [
        ['title' => 'Implementacija CRM sustava', 'stage' => DealStage::New, 'valueMinor' => 1_850_000, 'closeOffsetDays' => 60, 'hasContact' => true],
        ['title' => 'Nabava uredske opreme', 'stage' => DealStage::New, 'valueMinor' => 420_000, 'closeOffsetDays' => 45, 'hasContact' => false],
        ['title' => 'Godišnji ugovor o održavanju', 'stage' => DealStage::New, 'valueMinor' => 960_000, 'closeOffsetDays' => 75, 'hasContact' => true],
        ['title' => 'Proširenje skladišnog prostora', 'stage' => DealStage::Qualified, 'valueMinor' => 4_500_000, 'closeOffsetDays' => 90, 'hasContact' => true],
        ['title' => 'Digitalizacija poslovanja', 'stage' => DealStage::Qualified, 'valueMinor' => 2_300_000, 'closeOffsetDays' => 50, 'hasContact' => true],
        ['title' => 'Nabava dostavnih vozila', 'stage' => DealStage::Qualified, 'valueMinor' => 6_750_000, 'closeOffsetDays' => 40, 'hasContact' => false],
        ['title' => 'Konzultantske usluge za optimizaciju procesa', 'stage' => DealStage::Qualified, 'valueMinor' => 780_000, 'closeOffsetDays' => 35, 'hasContact' => true],
        ['title' => 'Modernizacija proizvodne linije', 'stage' => DealStage::Proposal, 'valueMinor' => 8_900_000, 'closeOffsetDays' => 30, 'hasContact' => true],
        ['title' => 'Marketinška kampanja za novo tržište', 'stage' => DealStage::Proposal, 'valueMinor' => 540_000, 'closeOffsetDays' => 25, 'hasContact' => true],
        ['title' => 'Uvođenje sustava za upravljanje zalihama', 'stage' => DealStage::Proposal, 'valueMinor' => 1_320_000, 'closeOffsetDays' => 28, 'hasContact' => false],
        ['title' => 'Izgradnja skladišnog objekta', 'stage' => DealStage::Proposal, 'valueMinor' => 15_000_000, 'closeOffsetDays' => 120, 'hasContact' => true],
        ['title' => 'Nabava informatičke opreme', 'stage' => DealStage::Negotiation, 'valueMinor' => 650_000, 'closeOffsetDays' => 14, 'hasContact' => true],
        ['title' => 'Razvoj web aplikacije', 'stage' => DealStage::Negotiation, 'valueMinor' => 1_100_000, 'closeOffsetDays' => 18, 'hasContact' => true],
        ['title' => 'Obnova voznog parka', 'stage' => DealStage::Negotiation, 'valueMinor' => 5_400_000, 'closeOffsetDays' => 21, 'hasContact' => false],
        ['title' => 'Uvođenje ERP sustava', 'stage' => DealStage::Won, 'valueMinor' => 3_200_000, 'closeOffsetDays' => -20, 'hasContact' => true],
        ['title' => 'Ugovor o distribuciji', 'stage' => DealStage::Won, 'valueMinor' => 2_750_000, 'closeOffsetDays' => -35, 'hasContact' => true],
        ['title' => 'Sponzorski ugovor', 'stage' => DealStage::Won, 'valueMinor' => 300_000, 'closeOffsetDays' => -10, 'hasContact' => true],
        ['title' => 'Godišnji ugovor o konzultantskim uslugama', 'stage' => DealStage::Won, 'valueMinor' => 1_450_000, 'closeOffsetDays' => -60, 'hasContact' => true],
        ['title' => 'Nabava ambalažne opreme', 'stage' => DealStage::Lost, 'valueMinor' => 890_000, 'closeOffsetDays' => -15, 'hasContact' => false],
        ['title' => 'Restrukturiranje logistike', 'stage' => DealStage::Lost, 'valueMinor' => 2_100_000, 'closeOffsetDays' => -40, 'hasContact' => true],
    ];

    /**
     * @param  Collection<int, Company>  $companies
     * @param  Collection<int, Contact>  $contacts
     * @return Collection<int, Deal>
     */
    public function run(User $admin, Collection $companies, Collection $contacts): Collection
    {
        $positionByStage = [];
        $companiesList = $companies->values();
        $contactsByCompany = $contacts->groupBy('company_id');

        return collect(self::DEALS)->map(function (array $definition, int $index) use ($admin, $companiesList, $contactsByCompany, &$positionByStage): Deal {
            $stage = $definition['stage'];
            $positionByStage[$stage->value] = ($positionByStage[$stage->value] ?? 0) + 1;
            $company = $companiesList->get(($index + 3) % $companiesList->count());

            if (! $company instanceof Company) {
                throw new LogicException('CompanySeeder must run before DealSeeder.');
            }

            $primaryContact = null;
            if ($definition['hasContact']) {
                /** @var Collection<int, Contact>|null $companyContacts */
                $companyContacts = $contactsByCompany->get($company->id);
                $primaryContact = $companyContacts?->first();
            }

            return Deal::factory()->create([
                'title' => $definition['title'],
                'value_minor' => Money::fromMinorUnits($definition['valueMinor']),
                'stage' => $stage,
                'expected_close_date' => now()->addDays($definition['closeOffsetDays'])->toDateString(),
                'company_id' => $company->id,
                'primary_contact_id' => $primaryContact?->id,
                'owner_id' => $admin->id,
                'position' => (string) ($positionByStage[$stage->value] * 1024),
            ]);
        });
    }
}
