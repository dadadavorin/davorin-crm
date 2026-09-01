<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded accounts
    |--------------------------------------------------------------------------
    |
    | Credentials for the two accounts the database seeder creates. There is
    | no public registration, so these are the only way in until an admin
    | creates further users.
    |
    */

    'admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Admin'),
        'email' => env('SEED_ADMIN_EMAIL', 'admin@davorincrm.test'),
        'password' => env('SEED_ADMIN_PASSWORD', 'password'),
    ],

    'member' => [
        'name' => env('SEED_MEMBER_NAME', 'Member'),
        'email' => env('SEED_MEMBER_EMAIL', 'member@davorincrm.test'),
        'password' => env('SEED_MEMBER_PASSWORD', 'password'),
    ],

];
