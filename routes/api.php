<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\Student\StudentClassController;
use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\EnsureTokenAccountIsActive;
use App\Support\InstructorSlug;
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

    // An instructor's public page, as visitors see it (no sign-in needed; a token adds the
    // person's own request to a class). Same rules as tulabi.ly/{slug} (App\Support\PublicCatalog).
    Route::get('/instructors/{slug}', [CatalogController::class, 'instructor'])
        ->where('slug', InstructorSlug::routePattern())
        ->name('instructors.show');
    Route::get('/instructors/{slug}/classes/{class}', [CatalogController::class, 'showClass'])
        ->where(['slug' => InstructorSlug::routePattern(), 'class' => '[0-9]{1,18}'])
        ->name('instructors.classes.show');
    Route::get('/instructors/{slug}/programs/{program}', [CatalogController::class, 'showProgram'])
        ->where(['slug' => InstructorSlug::routePattern(), 'program' => '[0-9]{1,18}'])
        ->name('instructors.programs.show');

    Route::middleware(['auth:sanctum', EnsureTokenAccountIsActive::class])->group(function () {
        Route::get('/me', MeController::class)->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

        // «اطلب الانضمام».
        Route::post('/classes/{class}/join', [CatalogController::class, 'join'])
            ->middleware('throttle:join-class')
            ->name('classes.join');

        // The student side: «فصولي» and «دفعاتي». Every {enrollment} must be the user's own
        // (StudentEnrollmentRequest); a receipt opens for its student and its instructor only.
        Route::get('/student/classes', [StudentClassController::class, 'index'])->name('student.classes.index');
        Route::get('/student/classes/{enrollment}', [StudentClassController::class, 'show'])->name('student.classes.show');
        Route::post('/student/classes/{enrollment}/withdraw', [StudentClassController::class, 'withdraw'])
            ->name('student.classes.withdraw');
        Route::get('/student/payments', [StudentClassController::class, 'payments'])->name('student.payments.index');
        Route::get('/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');
    });
});
