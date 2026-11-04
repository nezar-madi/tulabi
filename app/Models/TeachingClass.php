<?php

namespace App\Models;

use App\Support\TulabiClock;
use Database\Factories\TeachingClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** How a course meets: every day, on chosen weekdays (days), or on dates picked by hand (manual_dates). */
    public const SCHEDULE_DAILY = 'daily';

    public const SCHEDULE_WEEKLY = 'weekly';

    public const SCHEDULE_MANUAL = 'manual';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** Still shown publicly, but takes no new requests. */
    public const STATUS_CLOSED = 'closed';

    /** Owes nothing and takes no payments (as in rqmana). */
    public const STATUS_CANCELED = 'canceled';

    /**
     * «معلّق»: reached its start date with fewer accepted students than min_seats (auto_suspend).
     * Hidden from visitors and takes no requests until the instructor picks a new start date.
     */
    public const STATUS_SUSPENDED = 'suspended';

    /** Folder of the promotional images on the public disk (App\Support\ClassImage). */
    public const IMAGE_DIRECTORY = 'class-images';

    /** Days a changed meeting link (or schedule) is marked «تم التحديث» for the students. */
    public const MEETING_URL_NEW_DAYS = 7;

    /** The fields whose change accepted students are told about: when and where the class meets. */
    public const SCHEDULE_FIELDS = [
        'starts_on', 'ends_on', 'schedule_type', 'days', 'manual_dates', 'start_time', 'end_time', 'type', 'location', 'map_url',
    ];

    protected $table = 'classes';

    protected $fillable = [
        'program_id',
        'title',
        'description',
        'learn',
        'requirements',
        'audience',
        'promo_video_url',
        'auto_suspend',
        'min_seats',
        'type',
        'location',
        'map_url',
        'meeting_url',
        'starts_on',
        'ends_on',
        'schedule_type',
        'days',
        'manual_dates',
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
            'manual_dates' => 'array',
            'learn' => 'array',
            'requirements' => 'array',
            'audience' => 'array',
            'auto_suspend' => 'boolean',
            'min_seats' => 'integer',
            'suspended_at' => 'datetime',
            'price' => 'decimal:3',
            'seats' => 'integer',
            'published_at' => 'datetime',
            'canceled_at' => 'datetime',
            'meeting_url_changed_at' => 'datetime',
            'start_checked_at' => 'datetime',
            'schedule_changed_at' => 'datetime',
        ];
    }

    /** The dates, hours or place changed in the last few days (shown to accepted students). */
    public function scheduleIsNew(): bool
    {
        return $this->schedule_changed_at !== null
            && $this->schedule_changed_at->gt(now()->subDays(self::MEETING_URL_NEW_DAYS));
    }

    /**
     * The price is fixed once a student has joined: someone is waiting or accepted, or was
     * accepted before (their fee was set from it).
     */
    public function priceIsLocked(): bool
    {
        // A list loads it for every class at once: withCount(['enrollments as price_lock_count' => ...]).
        if (array_key_exists('price_lock_count', $this->attributes)) {
            return (int) $this->attributes['price_lock_count'] > 0;
        }

        return $this->exists && self::lockingPrice($this->enrollments())->exists();
    }

    /**
     * The enrollments that fix the price: waiting or accepted, or accepted before.
     *
     * @template TQuery of \Illuminate\Contracts\Database\Query\Builder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public static function lockingPrice($query)
    {
        return $query->where(fn ($inner) => $inner
            ->whereIn('status', [Enrollment::STATUS_REQUESTED, Enrollment::STATUS_ACCEPTED])
            ->orWhereNotNull('accepted_at'));
    }

    /** Accepted students right now. */
    public function hasAcceptedStudents(): bool
    {
        return $this->exists && $this->enrollments()->where('status', Enrollment::STATUS_ACCEPTED)->exists();
    }

    /** The meeting link was changed in the last few days. */
    public function meetingUrlIsNew(): bool
    {
        return $this->meeting_url !== null && $this->meeting_url_changed_at !== null
            && $this->meeting_url_changed_at->gt(now()->subDays(self::MEETING_URL_NEW_DAYS));
    }

    /** A path on the current host (php artisan storage:link), so it works on any domain. */
    public function imageUrl(): ?string
    {
        return $this->image_path === null ? null : '/storage/'.$this->image_path;
    }

    /** @return BelongsTo<Instructor, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /** Takes new join requests: published and not ended. */
    public function acceptsRequests(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && ! $this->hasEnded();
    }

    /**
     * Classes a visitor may see: published or closed, with their promotional image or video (one
     * of them, never both), of an instructor whose page is public.
     *
     * @param  Builder<TeachingClass>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_CLOSED])
            ->where(fn ($media) => $media->whereNotNull('image_path')->orWhereNotNull('promo_video_url'));
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_CLOSED], true) && $this->hasMedia();
    }

    /** A promotional image or a promotional video: what a class needs to be shown to visitors. */
    public function hasMedia(): bool
    {
        return $this->image_path !== null || $this->promo_video_url !== null;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
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
