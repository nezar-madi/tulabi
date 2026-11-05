<?php

namespace App\Support\Teach;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\PasswordResetLink;
use App\Models\Payment;
use App\Models\TeachingClass;
use App\Support\ClassPresenter;
use App\Support\EnrollmentPresenter;
use App\Support\PasswordResetLinks;
use App\Support\PaymentPresenter;
use App\Support\TulabiClock;
use App\Support\WhatsApp;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Join requests, a class's students and one student's payments, as the instructor sees them
 * (the website's Teach pages and the app's /api/v1/teach). Every query starts from the
 * instructor's own classes; the callers check that a {class} or {enrollment} is theirs.
 */
class ClassMembers
{
    public function __construct(private readonly PasswordResetLinks $links) {}

    /**
     * «طلبات الانضمام»: everything waiting, oldest first, across the instructor's classes.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pendingRequests(Instructor $instructor): Collection
    {
        return Enrollment::query()
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
    }

    /**
     * «طلاب الفصل»: waiting first, then accepted, then the rest, each with its money.
     *
     * @return array{item: array<string, mixed>, enrollments: Collection<int, array<string, mixed>>}
     */
    public function ofClass(TeachingClass $class): array
    {
        $enrollments = $class->enrollments()
            ->with(['user', 'teachingClass', 'payments'])
            ->orderByRaw('case status when ? then 0 when ? then 1 else 2 end', [Enrollment::STATUS_REQUESTED, Enrollment::STATUS_ACCEPTED])
            ->oldest('requested_at')
            ->get()
            ->map(fn (Enrollment $enrollment) => $this->member($enrollment));

        return [
            'item' => [
                'id' => $class->id,
                'title' => $class->title,
                'status' => $class->status,
                'seats' => $class->seats,
                'price' => ClassPresenter::forPublic($class)['price'],
                'meeting_share_url' => ClassPresenter::meetingShareUrl($class),
            ],
            'enrollments' => $enrollments,
        ];
    }

    /**
     * One enrollment as a row of «طلاب الفصل» (after an action, the app gets the row back).
     *
     * @return array<string, mixed>
     */
    public function member(Enrollment $enrollment): array
    {
        $enrollment->loadMissing(['user', 'teachingClass', 'payments']);

        return [
            ...EnrollmentPresenter::forInstructor($enrollment),
            'totals' => PaymentPresenter::totals($enrollment),
        ];
    }

    /**
     * One student's payments in a class: totals, whether a payment can be recorded, the list.
     *
     * @return array<string, mixed>
     */
    public function payments(Enrollment $enrollment): array
    {
        $enrollment->load(['user', 'teachingClass', 'payments' => fn ($query) => $query->latest('paid_at')->latest('id')]);

        return [
            'enrollment' => EnrollmentPresenter::forInstructor($enrollment),
            'totals' => PaymentPresenter::totals($enrollment),
            'canPay' => $enrollment->isActiveMember() && $enrollment->remainingMillis() > 0,
            'payments' => $enrollment->payments->map(fn (Payment $payment) => PaymentPresenter::payment($payment))->values(),
            'methods' => Payment::MANUAL_METHODS,
            'today' => TulabiClock::today(),
        ];
    }

    /**
     * A password reset link for one of the instructor's students (spec: students who forgot
     * their password ask their instructor). Only for students accepted in this class, and never
     * for an account that is itself an instructor (the admin does those).
     *
     * @return array{name: string, url: string, whatsapp: string|null}
     */
    public function resetLink(Enrollment $enrollment, Instructor $instructor): array
    {
        $user = $enrollment->user;

        if ($user === null || ! $enrollment->isActiveMember() || ! $user->isActive() || $user->instructor()->exists()) {
            throw ValidationException::withMessages(['enrollment' => __('enrollments.reset_only_students')]);
        }

        $url = $this->links->issue($user, PasswordResetLink::ISSUER_INSTRUCTOR, $instructor->getKey());

        return [
            'name' => $user->name,
            'url' => $url,
            'whatsapp' => WhatsApp::link($user->phone, __('accounts.reset.whatsapp', ['name' => $user->name, 'url' => $url])),
        ];
    }

    private function acceptedCount(int $classId): int
    {
        return Enrollment::query()
            ->where('class_id', $classId)
            ->where('status', Enrollment::STATUS_ACCEPTED)
            ->count();
    }
}
