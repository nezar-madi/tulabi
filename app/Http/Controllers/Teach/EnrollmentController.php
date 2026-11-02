<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\EnrollmentActionRequest;
use App\Models\Enrollment;
use App\Models\PasswordResetLink;
use App\Models\TeachingClass;
use App\Support\ClassPresenter;
use App\Support\EnrollmentPresenter;
use App\Support\Enrollments;
use App\Support\PasswordResetLinks;
use App\Support\WhatsApp;
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
    public function requests(Request $request): Response
    {
        $instructor = $this->instructor($request);

        $pending = Enrollment::query()
            ->where('status', Enrollment::STATUS_REQUESTED)
            ->whereHas('teachingClass', fn ($query) => $query
                ->where('instructor_id', $instructor->getKey())
                ->where('status', '!=', TeachingClass::STATUS_CANCELED))
            ->with(['user', 'teachingClass'])
            ->oldest('requested_at')
            ->oldest('id')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                ...EnrollmentPresenter::forInstructor($enrollment),
                'accepted_count' => $this->acceptedCount($enrollment->class_id),
            ]);

        return Inertia::render('Teach/Requests', [
            'requests' => $pending,
        ]);
    }

    /** طلاب الفصل. */
    public function students(Request $request, TeachingClass $class): Response
    {
        $class = $this->ownClass($request, $class);

        $enrollments = $class->enrollments()
            ->with(['user', 'teachingClass'])
            ->orderByRaw('case status when ? then 0 when ? then 1 else 2 end', [Enrollment::STATUS_REQUESTED, Enrollment::STATUS_ACCEPTED])
            ->oldest('requested_at')
            ->get()
            ->map(fn (Enrollment $enrollment) => EnrollmentPresenter::forInstructor($enrollment));

        return Inertia::render('Teach/Classes/Students', [
            'item' => [
                'id' => $class->id,
                'title' => $class->title,
                'status' => $class->status,
                'seats' => $class->seats,
                'price' => ClassPresenter::forPublic($class)['price'],
            ],
            'enrollments' => $enrollments,
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

    /**
     * A password reset link for one of the instructor's students (spec: students who
     * forgot their password ask their instructor). Only for students accepted in this
     * class, and never for an account that is itself an instructor (the admin does those).
     */
    public function resetLink(EnrollmentActionRequest $request, Enrollment $enrollment, PasswordResetLinks $links): RedirectResponse
    {
        $user = $enrollment->user;

        if ($user === null || ! $enrollment->isActiveMember() || ! $user->isActive() || $user->instructor()->exists()) {
            return back()->withErrors(['enrollment' => __('enrollments.reset_only_students')]);
        }

        $url = $links->issue($user, PasswordResetLink::ISSUER_INSTRUCTOR, $this->instructor($request)->getKey());

        return back()->with('resetLink', [
            'name' => $user->name,
            'url' => $url,
            'whatsapp' => WhatsApp::link($user->phone, __('accounts.reset.whatsapp', ['name' => $user->name, 'url' => $url])),
        ]);
    }

    private function acceptedCount(int $classId): int
    {
        return Enrollment::query()
            ->where('class_id', $classId)
            ->where('status', Enrollment::STATUS_ACCEPTED)
            ->count();
    }
}
