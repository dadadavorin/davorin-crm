<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

/**
 * Thirty contacts, two or three per company, spread across every
 * `ContactStatus` so the contacts board never opens with an empty column.
 */
final class ContactSeeder
{
    /**
     * @var list<array{first: string, last: string, title: string, status: ContactStatus}>
     */
    private const array CONTACTS = [
        ['first' => 'Ivan', 'last' => 'Horvat', 'title' => 'Direktor', 'status' => ContactStatus::Active],
        ['first' => 'Ana', 'last' => 'Kovačević', 'title' => 'Voditeljica marketinga', 'status' => ContactStatus::Active],
        ['first' => 'Marko', 'last' => 'Babić', 'title' => 'Voditelj prodaje', 'status' => ContactStatus::New],
        ['first' => 'Petra', 'last' => 'Jurić', 'title' => 'Računovođa', 'status' => ContactStatus::Active],
        ['first' => 'Josip', 'last' => 'Marić', 'title' => 'Voditelj nabave', 'status' => ContactStatus::Active],
        ['first' => 'Ivana', 'last' => 'Novak', 'title' => 'Tehnička direktorica', 'status' => ContactStatus::New],
        ['first' => 'Luka', 'last' => 'Perić', 'title' => 'Voditelj projekta', 'status' => ContactStatus::Active],
        ['first' => 'Marija', 'last' => 'Blažević', 'title' => 'Asistentica uprave', 'status' => ContactStatus::Active],
        ['first' => 'Tomislav', 'last' => 'Vuković', 'title' => 'Direktor', 'status' => ContactStatus::Active],
        ['first' => 'Katarina', 'last' => 'Knežević', 'title' => 'Voditeljica ljudskih resursa', 'status' => ContactStatus::Inactive],
        ['first' => 'Nikola', 'last' => 'Matić', 'title' => 'Voditelj skladišta', 'status' => ContactStatus::Active],
        ['first' => 'Sanja', 'last' => 'Radić', 'title' => 'Financijska analitičarka', 'status' => ContactStatus::Active],
        ['first' => 'Dario', 'last' => 'Đurić', 'title' => 'Voditelj prodaje', 'status' => ContactStatus::New],
        ['first' => 'Lucija', 'last' => 'Barišić', 'title' => 'Marketinška specijalistica', 'status' => ContactStatus::Active],
        ['first' => 'Ante', 'last' => 'Cvitković', 'title' => 'Direktor', 'status' => ContactStatus::Active],
        ['first' => 'Vedrana', 'last' => 'Bošnjak', 'title' => 'Voditeljica nabave', 'status' => ContactStatus::Inactive],
        ['first' => 'Damir', 'last' => 'Vrdoljak', 'title' => 'Tehnički direktor', 'status' => ContactStatus::Active],
        ['first' => 'Iva', 'last' => 'Herceg', 'title' => 'Voditeljica projekta', 'status' => ContactStatus::New],
        ['first' => 'Mislav', 'last' => 'Pavičić', 'title' => 'Voditelj skladišta', 'status' => ContactStatus::Active],
        ['first' => 'Marta', 'last' => 'Grgić', 'title' => 'Računovotkinja', 'status' => ContactStatus::Active],
        ['first' => 'Filip', 'last' => 'Šarić', 'title' => 'Voditelj prodaje', 'status' => ContactStatus::Inactive],
        ['first' => 'Nina', 'last' => 'Kolar', 'title' => 'Asistentica uprave', 'status' => ContactStatus::Active],
        ['first' => 'Hrvoje', 'last' => 'Tomić', 'title' => 'Direktor', 'status' => ContactStatus::Active],
        ['first' => 'Tea', 'last' => 'Vidović', 'title' => 'Specijalistica za korisničku podršku', 'status' => ContactStatus::New],
        ['first' => 'Zoran', 'last' => 'Kovač', 'title' => 'Voditelj nabave', 'status' => ContactStatus::Inactive],
        ['first' => 'Mirna', 'last' => 'Pavlović', 'title' => 'Voditeljica marketinga', 'status' => ContactStatus::Active],
        ['first' => 'Vjeran', 'last' => 'Karlović', 'title' => 'Tehnički direktor', 'status' => ContactStatus::New],
        ['first' => 'Dijana', 'last' => 'Zorić', 'title' => 'Financijska analitičarka', 'status' => ContactStatus::Inactive],
        ['first' => 'Goran', 'last' => 'Lovrić', 'title' => 'Voditelj projekta', 'status' => ContactStatus::New],
        ['first' => 'Petra', 'last' => 'Krznarić', 'title' => 'Voditeljica prodaje', 'status' => ContactStatus::Inactive],
    ];

    /**
     * @param  Collection<int, Company>  $companies
     * @return Collection<int, Contact>
     */
    public function run(User $admin, Collection $companies): Collection
    {
        $positionByStatus = [];
        $companiesList = $companies->values();

        return collect(self::CONTACTS)->map(function (array $definition, int $index) use ($admin, $companiesList, &$positionByStatus): Contact {
            $status = $definition['status'];
            $positionByStatus[$status->value] = ($positionByStatus[$status->value] ?? 0) + 1;
            $company = $companiesList->get($index % $companiesList->count());

            if (! $company instanceof Company) {
                throw new LogicException('CompanySeeder must run before ContactSeeder.');
            }

            $emailLocal = self::transliterate($definition['first']).'.'.self::transliterate($definition['last']);
            $domain = Str::after((string) $company->website, '://www.');

            return Contact::factory()->create([
                'first_name' => $definition['first'],
                'last_name' => $definition['last'],
                'email' => "{$emailLocal}@{$domain}",
                'phone' => '+385 9'.random_int(1, 9).' '.random_int(100, 999).' '.random_int(1000, 9999),
                'job_title' => $definition['title'],
                'status' => $status,
                'company_id' => $company->id,
                'owner_id' => $admin->id,
                'position' => (string) ($positionByStatus[$status->value] * 1024),
            ]);
        });
    }

    private static function transliterate(string $name): string
    {
        return strtolower(strtr($name, [
            'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's', 'đ' => 'dj',
            'Č' => 'c', 'Ć' => 'c', 'Ž' => 'z', 'Š' => 's', 'Đ' => 'dj',
        ]));
    }
}
