<?php

use App\Http\Controllers\InstructorPageController;
use App\Support\InstructorSlug;
use Illuminate\Support\Facades\Route;

// Loaded last (bootstrap/app.php), with the web middleware. The slug pattern leaves out
// every reserved word (App\Support\InstructorSlug::RESERVED), so these never catch the
// site's own paths.

Route::get('/{slug}', [InstructorPageController::class, 'show'])
    ->where('slug', InstructorSlug::routePattern())
    ->name('instructors.show');

Route::get('/{slug}/programs', [InstructorPageController::class, 'programs'])
    ->where('slug', InstructorSlug::routePattern())
    ->name('programs.index');

Route::get('/{slug}/programs/{program}', [InstructorPageController::class, 'showProgram'])
    ->where('slug', InstructorSlug::routePattern())
    ->whereNumber('program')
    ->name('programs.show');

Route::get('/{slug}/classes/{class}', [InstructorPageController::class, 'showClass'])
    ->where('slug', InstructorSlug::routePattern())
    ->whereNumber('class')
    ->name('classes.show');
