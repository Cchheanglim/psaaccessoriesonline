<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    // Google Gemini (AI Studio) for the customer chat assistant on chat.html.
    // Set GEMINI_API_KEY in .env (and in Render's environment). The key stays on the server.
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
    ],

];
