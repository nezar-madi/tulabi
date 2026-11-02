<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstructorApplicationController;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest:web')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');

    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])
        ->middleware('throttle:forgot-password')
        ->name('password.email');
});

// Reset links work signed in or not (an instructor may open a student's link to check it).
Route::get('/reset/{token}', [PasswordResetController::class, 'show'])
    ->middleware('throttle:reset-password')
    ->name('password.reset');
Route::post('/reset/{token}', [PasswordResetController::class, 'update'])
    ->middleware('throttle:reset-password')
    ->name('password.update');

// «انضم كمدرّس»: visitors make an account in the same form; signed-in users just apply.
Route::get('/join', [InstructorApplicationController::class, 'create'])->name('join');
Route::post('/join', [InstructorApplicationController::class, 'store'])->middleware('throttle:join');

Route::middleware(['auth:web', AuthenticateSession::class])->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:account-password')
        ->name('account.password');
});
