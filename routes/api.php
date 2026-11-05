<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\Student\StudentClassController;
use App\Http\Controllers\Api\V1\Teach\EnrollmentController as TeachEnrollmentController;
use App\Http\Controllers\Api\V1\Teach\HomeController;
use App\Http\Controllers\Api\V1\Teach\PaymentController as TeachPaymentController;
use App\Http\Controllers\Api\V1\Teach\ProgramController as TeachProgramController;
use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\EnsureInstructor;
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

        // The instructor side (EnsureInstructor: an active instructor account). Every {class},
        // {enrollment} and {payment} must be the instructor's own (FormRequests, then the controller).
        Route::middleware(EnsureInstructor::class)->prefix('teach')->name('teach.')->group(function () {
            Route::get('/home', [HomeController::class, 'home'])->name('home');
            Route::get('/students', [HomeController::class, 'students'])->name('students.index');
            Route::get('/payments', [HomeController::class, 'payments'])->name('payments.index');

            Route::get('/requests', [TeachEnrollmentController::class, 'requests'])->name('requests.index');
            Route::get('/classes/{class}/students', [TeachEnrollmentController::class, 'students'])->name('classes.students');
            Route::post('/enrollments/{enrollment}/accept', [TeachEnrollmentController::class, 'accept'])->name('enrollments.accept');
            Route::post('/enrollments/{enrollment}/reject', [TeachEnrollmentController::class, 'reject'])->name('enrollments.reject');
            Route::post('/enrollments/{enrollment}/remove', [TeachEnrollmentController::class, 'remove'])->name('enrollments.remove');
            Route::post('/enrollments/{enrollment}/reset-link', [TeachEnrollmentController::class, 'resetLink'])
                ->middleware('throttle:instructor-reset-link')
                ->name('enrollments.reset-link');

            Route::get('/enrollments/{enrollment}/payments', [TeachPaymentController::class, 'index'])->name('enrollments.payments');
            Route::post('/enrollments/{enrollment}/payments', [TeachPaymentController::class, 'store'])->name('enrollments.payments.store');
            Route::put('/payments/{payment}', [TeachPaymentController::class, 'update'])->name('payments.update');
            Route::post('/payments/{payment}/void', [TeachPaymentController::class, 'void'])->name('payments.void');

            // «البرامج التدريبية». Saving with a new image is multipart, so it goes by POST.
            Route::get('/programs', [TeachProgramController::class, 'index'])->name('programs.index');
            Route::get('/programs/rules', [TeachProgramController::class, 'rules'])->name('programs.rules');
            Route::post('/programs', [TeachProgramController::class, 'store'])->middleware('throttle:program-save')->name('programs.store');
            Route::get('/programs/{program}', [TeachProgramController::class, 'show'])->whereNumber('program')->name('programs.show');
            Route::match(['put', 'post'], '/programs/{program}', [TeachProgramController::class, 'update'])
                ->whereNumber('program')
                ->middleware('throttle:program-save')
                ->name('programs.update');
            Route::delete('/programs/{program}', [TeachProgramController::class, 'destroy'])->whereNumber('program')->name('programs.destroy');
        });
    });
});
