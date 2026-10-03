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
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
        // Used when the main model is rate-limited or overloaded (it has its own free allowance).
        'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-flash-latest'),
    ],

];
