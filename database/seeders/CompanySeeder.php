<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Twelve companies spread across every `CompanyStatus`, so the companies
 * board never opens with an empty column. Croatian names and addresses
 * throughout — line items and customer blocks derived from these further
 * down the seed chain are what exercise the quote PDF's embedded font.
 */
final class CompanySeeder
{
    /**
     * @var list<array{name: string, status: CompanyStatus, industry: string, address: string, domain: string}>
     */
    private const array COMPANIES = [
        ['name' => 'Zagrebačka Digitalna Rješenja d.o.o.', 'status' => CompanyStatus::Lead, 'industry' => 'Informacijske tehnologije', 'address' => 'Ilica 42, 10000 Zagreb', 'domain' => 'zadigitalna.hr'],
        ['name' => 'Istarska Maslinarska Zadruga', 'status' => CompanyStatus::Lead, 'industry' => 'Poljoprivreda', 'address' => 'Flanatička 5, 52100 Pula', 'domain' => 'istarska-maslina.hr'],
        ['name' => 'Šibenska Elektronika d.o.o.', 'status' => CompanyStatus::Lead, 'industry' => 'Elektronika', 'address' => 'Kralja Zvonimira 9, 22000 Šibenik', 'domain' => 'sibenska-elektronika.hr'],
        ['name' => 'Riječka Logistika d.o.o.', 'status' => CompanyStatus::Prospect, 'industry' => 'Logistika', 'address' => 'Riva 12, 51000 Rijeka', 'domain' => 'rijecka-logistika.hr'],
        ['name' => 'Đakovačka Tekstilna Manufaktura d.o.o.', 'status' => CompanyStatus::Prospect, 'industry' => 'Tekstilna industrija', 'address' => 'J. J. Strossmayera 10, 31400 Đakovo', 'domain' => 'djakovacka-tekstil.hr'],
        ['name' => 'Međimurska Vinarija d.o.o.', 'status' => CompanyStatus::Prospect, 'industry' => 'Vinogradarstvo', 'address' => 'Glavna ulica 21, 40000 Čakovec', 'domain' => 'medjimurska-vinarija.hr'],
        ['name' => 'Jadranska Brodogradnja d.d.', 'status' => CompanyStatus::Customer, 'industry' => 'Brodogradnja', 'address' => 'Liburnijska 18, 51000 Rijeka', 'domain' => 'jadranska-brodogradnja.hr'],
        ['name' => 'Slavonska Poljoprivredna Zadruga', 'status' => CompanyStatus::Customer, 'industry' => 'Poljoprivreda', 'address' => 'Europska avenija 7, 31000 Osijek', 'domain' => 'slavonska-poljoprivreda.hr'],
        ['name' => 'Dubrovačka Turistička Agencija d.o.o.', 'status' => CompanyStatus::Customer, 'industry' => 'Turizam', 'address' => 'Stradun 3, 20000 Dubrovnik', 'domain' => 'dubrovacka-turisticka.hr'],
        ['name' => 'Osječka Pekarska Industrija d.d.', 'status' => CompanyStatus::Customer, 'industry' => 'Prehrambena industrija', 'address' => 'Vukovarska 45, 31000 Osijek', 'domain' => 'osjecka-pekarska.hr'],
        ['name' => 'Splitska Ribarska Zadruga', 'status' => CompanyStatus::Inactive, 'industry' => 'Ribarstvo', 'address' => 'Poljička cesta 8, 21000 Split', 'domain' => 'splitska-ribarska.hr'],
        ['name' => 'Karlovačka Strojogradnja d.o.o.', 'status' => CompanyStatus::Inactive, 'industry' => 'Strojarstvo', 'address' => 'Trg bana Jelačića 2, 47000 Karlovac', 'domain' => 'karlovacka-strojogradnja.hr'],
    ];

    /**
     * @return Collection<int, Company>
     */
    public function run(User $admin): Collection
    {
        $positionByStatus = [];

        return collect(self::COMPANIES)->map(function (array $definition) use ($admin, &$positionByStatus): Company {
            $status = $definition['status'];
            $positionByStatus[$status->value] = ($positionByStatus[$status->value] ?? 0) + 1;

            return Company::factory()->create([
                'name' => $definition['name'],
                'status' => $status,
                'industry' => $definition['industry'],
                'website' => "https://www.{$definition['domain']}",
                'email' => "info@{$definition['domain']}",
                'phone' => '+385 '.random_int(1, 5).' '.random_int(100, 999).' '.random_int(1000, 9999),
                'address' => $definition['address'],
                'notes' => null,
                'owner_id' => $admin->id,
                'position' => (string) ($positionByStatus[$status->value] * 1024),
            ]);
        });
    }
}
