<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The framework default allows every origin. This store's pages are served
    | from the same origin as the API, so no cross-origin access is needed
    | unless CORS_ALLOWED_ORIGINS names one explicitly (comma-separated, full
    | origin including scheme, e.g. https://admin.example.com).
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
