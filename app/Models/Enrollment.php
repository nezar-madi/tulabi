<?php

namespace App\Models;

use App\Support\Currency;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A student's place in a class, from the request on. Nothing is mass-assignable:
 * every change goes through App\Support\Enrollments.
 */
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    /** Removed by the instructor after being accepted. */
    public const STATUS_CANCELED = 'canceled';

    /** The student took back the request or left the class. */
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:3',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
            'accepted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TeachingClass, $this> */
    public function teachingClass(): BelongsTo
    {
        return $this->belongsTo(TeachingClass::class, 'class_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Paid so far, voided payments left out, in dirhams (1/1000 dinar). */
    public function paidMillis(): int
    {
        $payments = $this->relationLoaded('payments')
            ? $this->payments->whereNull('voided_at')
            : $this->payments()->notVoided()->get(['amount']);

        return (int) $payments->sum(fn (Payment $payment) => Currency::toMillis($payment->amount));
    }

    /**
     * Still owed, in dirhams. Only an accepted student of a running class owes anything:
     * a canceled class, a removed or withdrawn student owe nothing more.
     */
    public function remainingMillis(): int
    {
        if (! $this->isActiveMember() || $this->fee === null) {
            return 0;
        }

        return max(0, Currency::toMillis($this->fee) - $this->paidMillis());
    }

    /**
     * The status people see: a canceled class cancels every open place in it, without
     * changing the rows (as rqmana's billingStopped()), so it owes nothing.
     */
    public function displayStatus(): string
    {
        if ($this->teachingClass?->isCanceled() && in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_ACCEPTED], true)) {
            return self::STATUS_CANCELED;
        }

        return $this->status;
    }

    /** Accepted in a class that still runs: sees the meeting link, owes the fee. */
    public function isActiveMember(): bool
    {
        return $this->displayStatus() === self::STATUS_ACCEPTED;
    }
}
