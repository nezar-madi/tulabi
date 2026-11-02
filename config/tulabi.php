<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local time zone
    |--------------------------------------------------------------------------
    |
    | Timestamps are stored in UTC (config app.timezone stays UTC). "Today",
    | "this month" and dates a person types mean Libya's calendar; ask
    | App\Support\TulabiClock, never now()->toDateString().
    |
    */

    'timezone' => 'Africa/Tripoli',

    /*
    |--------------------------------------------------------------------------
    | Phone region
    |--------------------------------------------------------------------------
    |
    | Only Libyan mobile numbers are accepted (App\Support\LibyanPhone).
    |
    */

    'phone_region' => 'LY',

    /*
    |--------------------------------------------------------------------------
    | Text messages (phone check at sign-up)
    |--------------------------------------------------------------------------
    |
    | "log" only writes the message to the log, for local work. Production needs a real
    | gateway: add a class implementing App\Support\Sms\SmsSender and map it in
    | AppServiceProvider.
    |
    */

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

];
