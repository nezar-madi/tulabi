<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The instructor side of an account, made when the admin accepts an application.
 * user_id, status, slug and photo_path are not mass-assignable (slug through
 * App\Support\InstructorSlug, the photo through the profile controller).
 *
 * @property list<string>|null $links
 */
class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /** Photos live on the public disk under this folder. */
    public const PHOTO_DIRECTORY = 'instructors';

    protected $fillable = [
        'display_name',
        'specialty',
        'city',
        'bio',
        'whatsapp',
        'links',
    ];

    protected function casts(): array
    {
        return [
            'links' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TeachingClass, $this> */
    public function classes(): HasMany
    {
        return $this->hasMany(TeachingClass::class);
    }

    /** @return HasMany<Program, $this> */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * Instructors whose page is public: a slug is chosen, teaching is on and the account is active.
     *
     * @param  Builder<Instructor>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->whereNotNull('slug')
            ->where('status', self::STATUS_ACTIVE)
            ->whereHas('user', fn (Builder $user) => $user->where('status', User::STATUS_ACTIVE));
    }

    public function isPublic(): bool
    {
        return $this->slug !== null
            && $this->status === self::STATUS_ACTIVE
            && $this->user?->isActive() === true;
    }

    public function publicUrl(): ?string
    {
        return $this->slug === null ? null : route('instructors.show', ['slug' => $this->slug]);
    }

    /** A path on the current host (php artisan storage:link), so it works on any domain. */
    public function photoUrl(): ?string
    {
        return $this->photo_path === null ? null : '/storage/'.$this->photo_path;
    }
}
