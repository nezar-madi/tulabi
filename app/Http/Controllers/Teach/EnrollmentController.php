<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\EnrollmentActionRequest;
use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Support\Enrollments;
use App\Support\Teach\ClassMembers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Join requests and the students of each class, for the instructor. Every query starts
 * from the instructor's own classes; every {enrollment} is checked in the FormRequest.
 */
class EnrollmentController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly Enrollments $enrollments) {}

    /** طلبات الانضمام: everything waiting, oldest first, across the instructor's classes. */
    public function requests(Request $request, ClassMembers $members): Response
    {
        return Inertia::render('Teach/Requests', [
            'requests' => $members->pendingRequests($this->instructor($request)),
        ]);
    }

    /** طلاب الفصل. */
    public function students(Request $request, TeachingClass $class, ClassMembers $members): Response
    {
        return Inertia::render('Teach/Classes/Students', [
            ...$members->ofClass($this->ownClass($request, $class)),
            'resetLink' => $request->session()->get('resetLink'),
        ]);
    }

    public function accept(EnrollmentActionRequest $request, Enrollment $enrollment): RedirectResponse
    {
        if (! $this->enrollments->accept($enrollment)) {
            return back()->withErrors(['enrollment' => __('enrollments.already_decided')]);
        }

        return back()->with('status', __('enrollments.accepted', ['name' => $enrollment->user?->name]));
    }

    public function reject(EnrollmentActionRequest $request, Enrollment $enrollment): RedirectResponse
    {
        if (! $this->enrollments->reject($enrollment, $request->validated('reason'))) {
            return back()->withErrors(['enrollment' => __('enrollments.already_decided')]);
        }

        return back()->with('status', __('enrollments.rejected'));
    }

    public function remove(EnrollmentActionRequest $request, Enrollment $enrollment): RedirectResponse
    {
        if (! $this->enrollments->remove($enrollment, $request->validated('reason'))) {
            return back()->withErrors(['enrollment' => __('enrollments.already_decided')]);
        }

        return back()->with('status', __('enrollments.removed'));
    }

    /** A password reset link for one of the instructor's students (rules in ClassMembers::resetLink). */
    public function resetLink(EnrollmentActionRequest $request, Enrollment $enrollment, ClassMembers $members): RedirectResponse
    {
        return back()->with('resetLink', $members->resetLink($enrollment, $this->instructor($request)));
    }
}
