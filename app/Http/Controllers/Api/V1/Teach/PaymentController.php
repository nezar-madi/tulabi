<?php

namespace App\Http\Controllers\Api\V1\Teach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teach\EnrollmentActionRequest;
use App\Http\Requests\Teach\PaymentRequest;
use App\Http\Requests\Teach\RecordPaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Support\Api\AppShape;
use App\Support\PaymentPresenter;
use App\Support\Payments;
use App\Support\Teach\ClassMembers;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * A student's payments in the app: the list, record, change method and notes, void with a
 * reason. Never delete; amount and date are fixed once recorded (void and record again).
 * Same rules as the website (App\Support\Payments; the requests check ownership).
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly Payments $payments,
        private readonly ClassMembers $members,
    ) {}

    public function index(EnrollmentActionRequest $request, Enrollment $enrollment): JsonResponse
    {
        return response()->json(AppShape::of(json_decode((string) json_encode($this->members->payments($enrollment)), true)));
    }

    public function store(RecordPaymentRequest $request, Enrollment $enrollment): JsonResponse
    {
        $payment = $this->payments->record($enrollment, $request->validated(), $request->user());

        return $this->answer($payment, __('payments.recorded', ['number' => $payment->receipt_number]), 201);
    }

    public function update(PaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->payments->update($payment, (string) $request->validated('method'), $request->validated('notes'));

        return $this->answer($payment, __('payments.updated'));
    }

    public function void(PaymentRequest $request, Payment $payment): JsonResponse
    {
        if (! $this->payments->void($payment, (string) $request->validated('reason'), $request->user())) {
            throw ValidationException::withMessages(['payment' => __('payments.already_voided')]);
        }

        return $this->answer($payment, __('payments.voided'));
    }

    /** The payment as it is now and the student's totals after it. */
    private function answer(Payment $payment, string $message, int $status = 200): JsonResponse
    {
        $payment = $payment->fresh();
        $enrollment = $payment->enrollment()->with(['teachingClass', 'payments'])->first();

        return response()->json([
            'message' => $message,
            'payment' => PaymentPresenter::payment($payment),
            'totals' => PaymentPresenter::totals($enrollment),
        ], $status);
    }
}
