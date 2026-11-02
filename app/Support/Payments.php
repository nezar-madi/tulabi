<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recording, editing and voiding payments, and receipt numbers.
 *
 * Only an accepted student of a running class can be paid for, and never more than
 * what is still owed. The enrollment row is locked while checking, so two payments
 * recorded at once cannot both use the same remaining amount.
 */
class Payments
{
    /**
     * @param  array{amount: mixed, paid_at: string, method: string, notes?: string|null}  $data
     */
    public function record(Enrollment $enrollment, array $data, User $recordedBy): Payment
    {
        return DB::transaction(function () use ($enrollment, $data, $recordedBy) {
            /** @var Enrollment $fresh */
            $fresh = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();
            $fresh->setRelation('teachingClass', $enrollment->teachingClass);

            if ($fresh->teachingClass->isCanceled()) {
                throw ValidationException::withMessages(['amount' => __('payments.class_canceled')]);
            }

            if (! $fresh->isActiveMember()) {
                throw ValidationException::withMessages(['amount' => __('payments.not_member')]);
            }

            $amount = Currency::toMillis($data['amount']);
            $remaining = $fresh->remainingMillis();

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => __('payments.more_than_remaining', ['remaining' => Currency::format(Currency::fromMillis($remaining))]),
                ]);
            }

            $instructorId = $fresh->teachingClass->instructor_id;
            $notes = trim((string) ($data['notes'] ?? ''));

            $payment = new Payment;
            $payment->forceFill([
                'enrollment_id' => $fresh->getKey(),
                'instructor_id' => $instructorId,
                'amount' => Currency::fromMillis($amount),
                'currency_code' => $fresh->currency_code,
                'paid_at' => $data['paid_at'],
                'method' => $data['method'],
                'receipt_number' => $this->nextReceiptNumber($instructorId),
                'notes' => $notes === '' ? null : $notes,
                'recorded_by' => $recordedBy->getKey(),
            ])->save();

            return $payment;
        });
    }

    /** Only the method and the notes change; the amount and the date are locked. */
    public function update(Payment $payment, string $method, ?string $notes): void
    {
        if ($payment->isVoided()) {
            throw ValidationException::withMessages(['payment' => __('payments.already_voided')]);
        }

        $notes = trim((string) $notes);
        $payment->forceFill(['method' => $method, 'notes' => $notes === '' ? null : $notes])->save();
    }

    /** Void with a reason. Returns false when it was already voided. */
    public function void(Payment $payment, string $reason, User $by): bool
    {
        return Payment::query()
            ->whereKey($payment->getKey())
            ->whereNull('voided_at')
            ->update([
                'voided_at' => now(),
                'void_reason' => trim($reason),
                'voided_by' => $by->getKey(),
            ]) === 1;
    }

    /**
     * The next receipt number of an instructor for today in Libya, e.g. TL-20261102-0001.
     * The counter row is locked, starts again at 1 each day and never goes backwards.
     * Call it inside a transaction.
     */
    public function nextReceiptNumber(int $instructorId): string
    {
        $today = TulabiClock::now()->format('Ymd');

        DB::table('receipt_counters')->insertOrIgnore([
            'instructor_id' => $instructorId,
            'day' => null,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = DB::table('receipt_counters')->where('instructor_id', $instructorId)->lockForUpdate()->first();

        // Same day (or a counter somehow ahead of today): carry on; a new day starts at 1.
        $day = $counter->day !== null && $counter->day >= $today ? $counter->day : $today;
        $next = $counter->day === $day ? (int) $counter->last_number + 1 : 1;

        DB::table('receipt_counters')->where('instructor_id', $instructorId)->update([
            'day' => $day,
            'last_number' => $next,
            'updated_at' => now(),
        ]);

        return 'TL-'.$day.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
