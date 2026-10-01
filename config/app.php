<?php

return [
    'name' => env('APP_NAME', 'PsaOnline'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:3000'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Phnom_Penh'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Read by UserSeeder. Kept here rather than calling env() in the seeder,
    // because env() returns null once `php artisan config:cache` has run.
    'admin_seed' => [
        'email' => env('ADMIN_SEED_EMAIL', 'admin@example.test'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],
];
