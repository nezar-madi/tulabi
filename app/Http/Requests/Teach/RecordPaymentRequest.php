<?php

namespace App\Http\Requests\Teach;

use App\Models\Payment;
use App\Support\TulabiClock;
use Illuminate\Validation\Rule;

/**
 * A payment for an enrollment in one of the instructor's classes (ownership in the parent).
 */
class RecordPaymentRequest extends EnrollmentActionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000', 'decimal:0,3'],
            'paid_at' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:'.TulabiClock::EARLIEST_MONEY_DATE,
                'before_or_equal:'.TulabiClock::today(),
            ],
            'method' => ['required', Rule::in(Payment::MANUAL_METHODS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['paid_at.before_or_equal' => __('payments.date_future')];
    }
}
