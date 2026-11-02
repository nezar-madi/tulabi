<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TwoFactorChallengeController;
use App\Http\Controllers\Admin\TwoFactorSetupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureAdminTwoFactor;
use Illuminate\Support\Facades\Route;

// Loaded with prefix /admin and names admin.* (bootstrap/app.php).

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    Route::get('/two-factor', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor', [TwoFactorChallengeController::class, 'store'])->name('two-factor.verify');
});

Route::middleware(['auth:admin', EnsureAdminTwoFactor::class])->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/two-factor/setup', [TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
    Route::post('/two-factor/setup', [TwoFactorSetupController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/two-factor/recovery-codes', [TwoFactorSetupController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('/applications/{application}/accept', [ApplicationController::class, 'accept'])->name('applications.accept');
    Route::post('/applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');

    Route::get('/instructors', [UserController::class, 'instructors'])->name('instructors.index');
    Route::get('/students', [UserController::class, 'students'])->name('students.index');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('/users/{user}/reset-link', [UserController::class, 'resetLink'])->name('users.reset-link');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
