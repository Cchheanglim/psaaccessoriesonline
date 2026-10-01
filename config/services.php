<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Framework defaults (postmark, ses, resend, slack) are merged in
    | automatically; only this application's services are listed here.
    |
    */

    'bakong' => [
        'merchant_id' => env('BAKONG_KHQR_MERCHANT_ID'),
        'merchant_name' => env('BAKONG_KHQR_MERCHANT_NAME', 'PSA ONLINE ACCESSORIES'),
        'city' => env('BAKONG_KHQR_CITY', 'Phnom Penh'),
    ],

];
