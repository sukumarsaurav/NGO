<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin seed credentials
    |--------------------------------------------------------------------------
    |
    | Used once by AdminUserSeeder to create the first super-admin. Left unset
    | in .env.example on purpose — AdminUserSeeder refuses to run without both,
    | rather than falling back to a guessable default password.
    |
    */

    'admin_seed_email' => env('ADMIN_SEED_EMAIL'),
    'admin_seed_password' => env('ADMIN_SEED_PASSWORD'),

];
