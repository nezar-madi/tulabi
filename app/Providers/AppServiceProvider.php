<?php

namespace App\Providers;

use App\Support\LibyanPhone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimits();
    }

    /**
     * Without SMS there is no phone check, so sign-ups and reset requests are limited per IP
     * (and per phone where one is typed). Login has its own limits in the SessionControllers.
     */
    private function configureRateLimits(): void
    {
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by('register:'.$request->ip()));

        RateLimiter::for('join', fn (Request $request) => Limit::perHour(10)->by('join:'.$request->ip()));

        RateLimiter::for('forgot-password', function (Request $request) {
            $phone = LibyanPhone::normalize((string) $request->input('phone')) ?? 'invalid';

            return [
                Limit::perHour(10)->by('forgot-ip:'.$request->ip()),
                Limit::perHour(3)->by('forgot-phone:'.$phone),
            ];
        });

        RateLimiter::for('reset-password', fn (Request $request) => Limit::perHour(30)->by('reset:'.$request->ip()));

        RateLimiter::for('account-password', fn (Request $request) => Limit::perMinutes(10, 10)
            ->by('account-password:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
