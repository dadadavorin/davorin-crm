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
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
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
    }
}
