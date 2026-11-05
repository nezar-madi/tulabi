<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Classes with «تعليق البدء تلقائيًا» are checked each hour (the date is Libya's, App\Support\TulabiClock).
Schedule::command('classes:check-start')->hourly();

// App sign-in tokens past their lifetime (config sanctum.expiration) are removed each night.
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('03:15');
