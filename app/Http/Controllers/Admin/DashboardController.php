<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\InstructorApplication;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin's numbers. Classes and enrollments are added with phases 2 and 3.
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'instructors' => Instructor::query()->count(),
                'students' => User::query()->whereDoesntHave('instructor')->count(),
                'pending_applications' => InstructorApplication::query()
                    ->where('status', InstructorApplication::STATUS_PENDING)
                    ->count(),
                'suspended' => User::query()->where('status', User::STATUS_SUSPENDED)->count(),
            ],
        ]);
    }
}
