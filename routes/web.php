<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstructorApplicationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Student\JoinClassController;
use App\Http\Controllers\Student\StudentClassController;
use App\Http\Controllers\Teach\ClassController;
use App\Http\Controllers\Teach\DashboardController as TeachDashboardController;
use App\Http\Controllers\Teach\EnrollmentController;
use App\Http\Controllers\Teach\PaymentController;
use App\Http\Controllers\Teach\ProfileController;
use App\Http\Middleware\EnsureInstructor;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest:web')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'sendCode'])->middleware('throttle:register');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->middleware('throttle:register')->name('register.resend');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:register-verify')->name('register.verify');
    Route::post('/register/restart', [RegisterController::class, 'restart'])->name('register.restart');
    Route::post('/register/password', [RegisterController::class, 'store'])->middleware('throttle:register-verify')->name('register.password');

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

// «انضم كمدرّس»: a visitor is sent to sign up by phone first, then comes back here to apply.
Route::get('/join', [InstructorApplicationController::class, 'create'])->name('join');
Route::post('/join', [InstructorApplicationController::class, 'store'])->middleware(['auth:web', 'throttle:join']);

Route::middleware(['auth:web', AuthenticateSession::class])->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:account-password')
        ->name('account.password');

    // «فصولي» for students. Every {enrollment} must be the user's own (StudentEnrollmentRequest).
    Route::get('/account/classes', [StudentClassController::class, 'index'])->name('student.classes.index');
    Route::get('/account/classes/{enrollment}', [StudentClassController::class, 'show'])->name('student.classes.show');
    Route::post('/account/classes/{enrollment}/withdraw', [StudentClassController::class, 'withdraw'])
        ->name('student.classes.withdraw');
    Route::get('/account/payments', [StudentClassController::class, 'payments'])->name('student.payments.index');

    // Receipts (PDF): the instructor who recorded the payment and the student it is for.
    Route::get('/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');

    // «اطلب الانضمام» (the GET brings a visitor back to the class page after signing in).
    Route::get('/classes/{class}/request', [JoinClassController::class, 'create'])->name('classes.request.create');
    Route::post('/classes/{class}/request', [JoinClassController::class, 'store'])
        ->middleware('throttle:join-class')
        ->name('classes.request');
});

// The instructor area. Every {class} is checked to belong to the signed-in instructor.
Route::middleware(['auth:web', AuthenticateSession::class, EnsureInstructor::class])
    ->prefix('teach')
    ->name('teach.')
    ->group(function () {
        Route::get('/', TeachDashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');
        Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');

        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
        Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
        Route::get('/classes/{class}/edit', [ClassController::class, 'edit'])->name('classes.edit');
        Route::put('/classes/{class}', [ClassController::class, 'update'])->name('classes.update');
        Route::post('/classes/{class}/publish', [ClassController::class, 'publish'])->name('classes.publish');
        Route::post('/classes/{class}/close', [ClassController::class, 'close'])->name('classes.close');
        Route::post('/classes/{class}/cancel', [ClassController::class, 'cancel'])->name('classes.cancel');
        Route::delete('/classes/{class}', [ClassController::class, 'destroy'])->name('classes.destroy');
        Route::get('/classes/{class}/students', [EnrollmentController::class, 'students'])->name('classes.students');

        // Every {enrollment} must be in one of the instructor's classes (EnrollmentActionRequest).
        Route::get('/requests', [EnrollmentController::class, 'requests'])->name('requests.index');
        Route::post('/enrollments/{enrollment}/accept', [EnrollmentController::class, 'accept'])->name('enrollments.accept');
        Route::post('/enrollments/{enrollment}/reject', [EnrollmentController::class, 'reject'])->name('enrollments.reject');
        Route::post('/enrollments/{enrollment}/remove', [EnrollmentController::class, 'remove'])->name('enrollments.remove');
        Route::post('/enrollments/{enrollment}/reset-link', [EnrollmentController::class, 'resetLink'])
            ->middleware('throttle:instructor-reset-link')
            ->name('enrollments.reset-link');

        // Payments: voided, never deleted; only method and notes change (PaymentRequest checks ownership).
        Route::get('/enrollments/{enrollment}/payments', [PaymentController::class, 'index'])->name('enrollments.payments');
        Route::post('/enrollments/{enrollment}/payments', [PaymentController::class, 'store'])->name('enrollments.payments.store');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::post('/payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
    });

// The public instructor pages (tulabi.ly/{slug}) are in routes/pages.php, loaded last.
