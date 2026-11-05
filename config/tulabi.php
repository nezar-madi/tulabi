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

    /*
    |--------------------------------------------------------------------------
    | The mobile app (Flutter) and its API (/api/v1)
    |--------------------------------------------------------------------------
    |
    | The app sends its version in the X-App-Version header. An app older than
    | min_version gets 426 «حدّث التطبيق» with the store links (raise it when an
    | API change would break old apps). latest_version is shown as an optional update.
    |
    */

    'app' => [
        'min_version' => env('APP_MIN_VERSION', '1.0.0'),
        'latest_version' => env('APP_LATEST_VERSION', '1.0.0'),
        'store_urls' => [
            'android' => env('APP_STORE_URL_ANDROID'),
            'ios' => env('APP_STORE_URL_IOS'),
        ],
    ],

];
