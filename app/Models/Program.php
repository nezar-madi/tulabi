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
        'is_active',
        'is_published',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
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

    /** What the public page shows: published and not stopped. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('is_active', true);
    }

    public function isPublic(): bool
    {
        return $this->is_published && $this->is_active;
    }

    /** A path on the current host (php artisan storage:link), so it works on any domain. */
    public function imageUrl(): ?string
    {
        return $this->image_path === null ? null : '/storage/'.$this->image_path;
    }
}
