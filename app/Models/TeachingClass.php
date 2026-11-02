<?php

namespace App\Models;

use App\Support\TulabiClock;
use Database\Factories\TeachingClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A class an instructor teaches (table "classes"; "Class" is a reserved word in PHP).
 * instructor_id, status and the publish/cancel columns are not mass-assignable: they
 * change only through App\Support\ClassLifecycle.
 *
 * @property list<int>|null $days
 */
class TeachingClass extends Model
{
    /** @use HasFactory<TeachingClassFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_IN_PERSON = 'in_person';

    public const TYPE_ONLINE = 'online';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** Still shown publicly, but takes no new requests. */
    public const STATUS_CLOSED = 'closed';

    /** Owes nothing and takes no payments (as in rqmana). */
    public const STATUS_CANCELED = 'canceled';

    protected $table = 'classes';

    protected $fillable = [
        'title',
        'description',
        'type',
        'location',
        'meeting_url',
        'starts_on',
        'ends_on',
        'days',
        'start_time',
        'end_time',
        'price',
        'seats',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'days' => 'array',
            'price' => 'decimal:3',
            'seats' => 'integer',
            'published_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Instructor, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Classes a visitor may see: published or closed, of an instructor whose page is public.
     *
     * @param  Builder<TeachingClass>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_CLOSED]);
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_CLOSED], true);
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /** Ended: the last day (or the first, for a one-day class) is before today in Libya. */
    public function hasEnded(): bool
    {
        $last = $this->ends_on ?? $this->starts_on;

        return $last !== null && $last->toDateString() < TulabiClock::today();
    }
}
