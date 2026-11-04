<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A training program of an instructor (rqmana's «برنامج تدريبي»): a catalogue entry that courses
 * belong to. instructor_id and image_path are not mass-assignable.
 */
class Program extends Model
{
    use HasFactory;

    public const NAME_MAX = 120;

    /** Folder of the posters on the public disk (App\Support\ClassImage). */
    public const IMAGE_DIRECTORY = 'program-images';

    protected $fillable = [
        'name',
        'description',
        'learn',
        'requirements',
        'audience',
        'promo_video_url',
        'is_active',
        'is_published',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'learn' => 'array',
            'requirements' => 'array',
            'audience' => 'array',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /** @return BelongsTo<Instructor, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /** @return HasMany<TeachingClass, $this> */
    public function classes(): HasMany
    {
        return $this->hasMany(TeachingClass::class);
    }

    /**
     * What the public page shows: published, not stopped, and with its promotional image or video
     * (one of them, never both).
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('is_active', true)
            ->where(fn ($media) => $media->whereNotNull('image_path')->orWhereNotNull('promo_video_url'));
    }

    public function isPublic(): bool
    {
        return $this->is_published && $this->is_active && $this->hasMedia();
    }

    /** A promotional image or a promotional video: what a program needs to be shown to visitors. */
    public function hasMedia(): bool
    {
        return $this->image_path !== null || $this->promo_video_url !== null;
    }

    /** A path on the current host (php artisan storage:link), so it works on any domain. */
    public function imageUrl(): ?string
    {
        return $this->image_path === null ? null : '/storage/'.$this->image_path;
    }
}
