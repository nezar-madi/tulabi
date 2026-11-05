<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Middleware\EnsureTokenAccountIsActive;
use Illuminate\Support\Facades\Route;

/*
| The mobile app's API (the app is the main product; owner's decision 2026-11-05).
| Every route is /api/v1/..., answers JSON (ForceJsonResponse), checks the app's version
| (CheckAppVersion) and is limited by the "api" limiter. Signed-in routes take a Sanctum
| token ("Authorization: Bearer ..."). A breaking change goes into /api/v2, never into v1.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/meta', MetaController::class)->name('meta');

    // Signing in and up: the same rules and limits as the website.
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:forgot-password')
        ->name('auth.forgot-password');
    Route::post('/auth/register/code', [RegisterController::class, 'sendCode'])
        ->middleware('throttle:register')
        ->name('auth.register.code');
    Route::post('/auth/register/verify', [RegisterController::class, 'verify'])
        ->middleware('throttle:register-verify')
        ->name('auth.register.verify');
    Route::post('/auth/register', [RegisterController::class, 'register'])
        ->middleware('throttle:register-verify')
        ->name('auth.register');

    Route::middleware(['auth:sanctum', EnsureTokenAccountIsActive::class])->group(function () {
        Route::get('/me', MeController::class)->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
    });
});
