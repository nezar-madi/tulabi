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

];
