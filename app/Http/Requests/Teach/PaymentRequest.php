<?php

namespace App\Http\Requests\Teach;

use App\Models\Payment;
use Illuminate\Validation\Rule;

/**
 * Edit or void one of the instructor's own payments.
 */
class PaymentRequest extends InstructorRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return parent::authorize()
            && $payment instanceof Payment
            && $payment->instructor_id === $this->instructor()?->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->routeIs('teach.payments.void')
            ? ['reason' => ['required', 'string', 'min:3', 'max:500']]
            : [
                'method' => ['required', Rule::in(Payment::MANUAL_METHODS)],
                'notes' => ['nullable', 'string', 'max:500'],
            ];
    }
}
