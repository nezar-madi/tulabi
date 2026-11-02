<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Payment;

/**
 * Payments and money totals as sent to pages.
 */
class PaymentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function payment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => Currency::format($payment->amount),
            'currency' => $payment->currency_code,
            'paid_on' => $payment->paidOn(),
            'method' => $payment->method,
            'receipt_number' => $payment->receipt_number,
            'notes' => $payment->notes,
            'voided' => $payment->isVoided(),
            'void_reason' => $payment->void_reason,
            'receipt_url' => route('receipts.show', $payment),
        ];
    }

    /**
     * Fee, paid and remaining of one enrollment.
     *
     * @return array{fee: string|null, paid: string, remaining: string}
     */
    public static function totals(Enrollment $enrollment): array
    {
        return [
            'fee' => $enrollment->fee === null ? null : Currency::format($enrollment->fee),
            'paid' => Currency::format(Currency::fromMillis($enrollment->paidMillis())),
            'remaining' => Currency::format(Currency::fromMillis($enrollment->remainingMillis())),
        ];
    }
}
