<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\EnrollmentActionRequest;
use App\Http\Requests\Teach\PaymentRequest;
use App\Http\Requests\Teach\RecordPaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Support\EnrollmentPresenter;
use App\Support\PaymentPresenter;
use App\Support\Payments;
use App\Support\TulabiClock;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A student's payments in one of the instructor's classes: record, edit method and
 * notes, void with a reason. Never delete.
 */
class PaymentController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly Payments $payments) {}

    public function index(EnrollmentActionRequest $request, Enrollment $enrollment): Response
    {
        $enrollment->load(['user', 'teachingClass', 'payments' => fn ($query) => $query->latest('paid_at')->latest('id')]);

        return Inertia::render('Teach/Payments', [
            'enrollment' => EnrollmentPresenter::forInstructor($enrollment),
            'totals' => PaymentPresenter::totals($enrollment),
            'canPay' => $enrollment->isActiveMember() && $enrollment->remainingMillis() > 0,
            'payments' => $enrollment->payments->map(fn (Payment $payment) => PaymentPresenter::payment($payment))->values(),
            'methods' => Payment::MANUAL_METHODS,
            'today' => TulabiClock::today(),
        ]);
    }

    public function store(RecordPaymentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $payment = $this->payments->record($enrollment, $request->validated(), $request->user('web'));

        return back()->with('status', __('payments.recorded', ['number' => $payment->receipt_number]));
    }

    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->update($payment, (string) $request->validated('method'), $request->validated('notes'));

        return back()->with('status', __('payments.updated'));
    }

    public function void(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        if (! $this->payments->void($payment, (string) $request->validated('reason'), $request->user('web'))) {
            return back()->withErrors(['payment' => __('payments.already_voided')]);
        }

        return back()->with('status', __('payments.voided'));
    }
}
