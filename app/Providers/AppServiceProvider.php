<?php

namespace App\Providers;

use App\Support\LibyanPhone;
use App\Support\Sms\LogSmsSender;
use App\Support\Sms\SmsSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Map a real gateway here when there is one; "log" is for local work only.
        $this->app->bind(SmsSender::class, fn () => match (config('tulabi.sms.driver')) {
            default => new LogSmsSender,
        });
    }

    public function boot(): void
    {
        $this->configureRateLimits();
    }

    /**
     * Sign-ups, codes and reset requests are limited per IP (and per phone where one is typed). Login has its own limits in the SessionControllers.
     */
    private function configureRateLimits(): void
    {
        // Each code is a message that costs money: limit by IP and by phone.
        RateLimiter::for('register', function (Request $request) {
            $phone = LibyanPhone::normalize((string) $request->input('phone')) ?? 'invalid';

            return [
                Limit::perHour(10)->by('register:'.$request->ip()),
                Limit::perHour(5)->by('register-phone:'.$phone),
            ];
        });

        RateLimiter::for('register-verify', fn (Request $request) => Limit::perHour(30)->by('register-verify:'.$request->ip()));

        RateLimiter::for('join', fn (Request $request) => Limit::perHour(10)->by('join:'.$request->ip()));

        RateLimiter::for('forgot-password', function (Request $request) {
            $phone = LibyanPhone::normalize((string) $request->input('phone')) ?? 'invalid';

            return [
                Limit::perHour(10)->by('forgot-ip:'.$request->ip()),
                Limit::perHour(3)->by('forgot-phone:'.$phone),
            ];
        });

        RateLimiter::for('reset-password', fn (Request $request) => Limit::perHour(30)->by('reset:'.$request->ip()));

        RateLimiter::for('join-class', fn (Request $request) => Limit::perHour(20)
            ->by('join-class:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Creating and saving classes (each save may carry a 4 MB image): plenty for real work, not a flood.
        RateLimiter::for('class-save', fn (Request $request) => Limit::perMinute(30)
            ->by('class-save:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Creating and saving programs (each save may carry a 4 MB image), as for classes.
        RateLimiter::for('program-save', fn (Request $request) => Limit::perMinute(30)
            ->by('program-save:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('instructor-reset-link', fn (Request $request) => Limit::perHour(20)
            ->by('instructor-reset-link:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // The app's API: generous for real use, per account (or per IP before signing in).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by('api:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('account-password', fn (Request $request) => Limit::perMinutes(10, 10)
            ->by('account-password:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
