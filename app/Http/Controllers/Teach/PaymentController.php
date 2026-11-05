<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\EnrollmentActionRequest;
use App\Http\Requests\Teach\PaymentRequest;
use App\Http\Requests\Teach\RecordPaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Support\Payments;
use App\Support\Teach\ClassMembers;
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

    public function index(EnrollmentActionRequest $request, Enrollment $enrollment, ClassMembers $members): Response
    {
        return Inertia::render('Teach/Payments', $members->payments($enrollment));
    }

    public function store(RecordPaymentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $payment = $this->payments->record($enrollment, $request->validated(), $request->user());

        return back()->with('status', __('payments.recorded', ['number' => $payment->receipt_number]));
    }

    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->update($payment, (string) $request->validated('method'), $request->validated('notes'));

        return back()->with('status', __('payments.updated'));
    }

    public function void(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        if (! $this->payments->void($payment, (string) $request->validated('reason'), $request->user())) {
            return back()->withErrors(['payment' => __('payments.already_voided')]);
        }

        return back()->with('status', __('payments.voided'));
    }
}
