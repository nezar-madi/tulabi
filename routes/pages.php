<?php

use App\Http\Controllers\InstructorPageController;
use App\Support\InstructorSlug;
use Illuminate\Support\Facades\Route;

// Loaded last (bootstrap/app.php), with the web middleware. The slug pattern leaves out
// every reserved word (App\Support\InstructorSlug::RESERVED), so these never catch the
// site's own paths. Ids are capped at 18 digits so a longer number is a 404 (the controller
// takes an int; a number past PHP_INT_MAX would be a TypeError, a 500).

Route::get('/{slug}', [InstructorPageController::class, 'show'])
    ->where('slug', InstructorSlug::routePattern())
    ->name('instructors.show');

Route::get('/{slug}/programs', [InstructorPageController::class, 'programs'])
    ->where('slug', InstructorSlug::routePattern())
    ->name('programs.index');

Route::get('/{slug}/programs/{program}', [InstructorPageController::class, 'showProgram'])
    ->where('slug', InstructorSlug::routePattern())
    ->where('program', '[0-9]{1,18}')
    ->name('programs.show');

Route::get('/{slug}/classes/{class}', [InstructorPageController::class, 'showClass'])
    ->where('slug', InstructorSlug::routePattern())
    ->where('class', '[0-9]{1,18}')
    ->name('classes.show');
