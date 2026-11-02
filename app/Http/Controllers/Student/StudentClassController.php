<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\EnrollmentPresenter;
use App\Support\Enrollments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «فصولي» for the student: every request and its status. Queries start from the
 * signed-in user, so nobody sees another student's enrollments.
 */
class StudentClassController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user('web');

        $enrollments = $user->enrollments()
            ->with(['teachingClass.instructor.user'])
            ->latest('requested_at')
            ->latest('id')
            ->get()
            ->map(fn (Enrollment $enrollment) => EnrollmentPresenter::forStudent($enrollment));

        return Inertia::render('Student/Classes/Index', [
            'enrollments' => $enrollments,
        ]);
    }

    public function show(StudentEnrollmentRequest $request, Enrollment $enrollment): Response
    {
        return Inertia::render('Student/Classes/Show', [
            'enrollment' => EnrollmentPresenter::forStudent($enrollment->load('teachingClass.instructor.user')),
        ]);
    }

    public function withdraw(StudentEnrollmentRequest $request, Enrollment $enrollment, Enrollments $enrollments): RedirectResponse
    {
        if (! $enrollments->withdraw($enrollment)) {
            return back()->withErrors(['enrollment' => __('enrollments.already_decided')]);
        }

        return back()->with('status', __('enrollments.withdrawn'));
    }
}
