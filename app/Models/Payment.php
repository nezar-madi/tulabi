<?php

namespace App\Models;

use App\Casts\TulabiDateTime;
use App\Support\TulabiClock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment an instructor recorded. Voided, never deleted: the row and its receipt number
 * stay, it stops counting, and the receipt says «ملغى». Amount and date are locked after
 * recording; only the method and the notes can change. Made only by App\Support\Payments.
 */
class Payment extends Model
{
    public const METHOD_CASH = 'cash';

    public const METHOD_TRANSFER = 'transfer';

    public const METHOD_OTHER = 'other';

    /** Methods an instructor can choose by hand (dpay and mypay come with e-payment). */
    public const MANUAL_METHODS = [self::METHOD_CASH, self::METHOD_TRANSFER, self::METHOD_OTHER];

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'paid_at' => TulabiDateTime::class,
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Voided, never deleted.
        static::deleting(fn () => false);
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<Instructor, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /** @param  Builder<Payment>  $query */
    public function scopeNotVoided(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /** The payment date as it was typed, in Libya (Y-m-d). */
    public function paidOn(): string
    {
        return $this->paid_at->copy()->setTimezone(TulabiClock::timezone())->toDateString();
    }
}
