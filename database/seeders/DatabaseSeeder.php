<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. All demo data is owned by the
     * admin account, so a reviewer logging in with the handed-over
     * credentials never sees an empty CRM.
     *
     * A no-op once a user exists, so the dev container can run this on
     * every boot the same way it runs migrations — populating a fresh
     * volume without re-seeding (and failing on duplicate emails) on every
     * later restart.
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $admin = User::factory()->create([
            'name' => config('seed.admin.name'),
            'email' => config('seed.admin.email'),
            'password' => config('seed.admin.password'),
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => config('seed.member.name'),
            'email' => config('seed.member.email'),
            'password' => config('seed.member.password'),
            'role' => UserRole::Member,
        ]);

        $companies = (new CompanySeeder)->run($admin);
        $contacts = (new ContactSeeder)->run($admin, $companies);
        $deals = (new DealSeeder)->run($admin, $companies, $contacts);
        (new QuoteSeeder)->run($admin, $deals);
    }
}
