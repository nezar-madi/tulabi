<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Support\Api\AppShape;
use App\Support\EnrollmentPresenter;
use App\Support\Enrollments;
use App\Support\PaymentPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * «فصولي» in the app: every request of the student and its status, one class with its payments,
 * leaving, and «دفعاتي». Same data and rules as the website's Student\StudentClassController
 * (queries start from the signed-in user; StudentEnrollmentRequest keeps others' out).
 */
class StudentClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['teachingClass.instructor.user', 'teachingClass.program'])
            ->latest('requested_at')
            ->latest('id')
            ->get()
            ->map(fn (Enrollment $enrollment) => EnrollmentPresenter::forStudent($enrollment));

        return response()->json(['enrollments' => AppShape::list($enrollments)]);
    }

    public function show(StudentEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        $enrollment->load(['teachingClass.instructor.user', 'payments' => fn ($query) => $query->latest('paid_at')->latest('id')]);

        return response()->json([
            'enrollment' => AppShape::of(EnrollmentPresenter::forStudent($enrollment)),
            'totals' => PaymentPresenter::totals($enrollment),
            'payments' => $enrollment->payments->map(fn (Payment $payment) => self::payment($payment))->values(),
        ]);
    }

    /** Take back a waiting request, or leave a class one was accepted in (as on the website). */
    public function withdraw(StudentEnrollmentRequest $request, Enrollment $enrollment, Enrollments $enrollments): JsonResponse
    {
        if (! $enrollments->withdraw($enrollment)) {
            throw ValidationException::withMessages(['enrollment' => __('enrollments.already_decided')]);
        }

        return response()->json([
            'message' => __('enrollments.withdrawn'),
            'enrollment' => AppShape::of(EnrollmentPresenter::forStudent($enrollment->fresh(['teachingClass.instructor.user']))),
        ]);
    }

    /** «دفعاتي»: every payment and receipt of the student, in every class. */
    public function payments(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payments = Payment::query()
            ->whereHas('enrollment', fn ($query) => $query->where('user_id', $user->getKey()))
            ->with('enrollment.teachingClass.instructor')
            ->latest('paid_at')
            ->latest('id')
            ->get()
            ->map(fn (Payment $payment) => [
                ...self::payment($payment),
                'class_title' => $payment->enrollment->teachingClass->title,
                'instructor' => $payment->enrollment->teachingClass->instructor->display_name,
                'enrollment_id' => $payment->enrollment_id,
            ]);

        return response()->json(['payments' => $payments]);
    }

    /**
     * A payment with its receipt at the API's address (the app opens it with its token).
     *
     * @return array<string, mixed>
     */
    public static function payment(Payment $payment): array
    {
        return [
            ...PaymentPresenter::payment($payment),
            'receipt_url' => route('api.v1.receipts.show', $payment),
        ];
    }
}
