<?php

namespace App\Support;

use App\Models\Program;

/**
 * The shapes a program is sent to the instructor's pages in.
 */
class ProgramPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forOwner(Program $program): array
    {
        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'learn' => $program->learn ?? [],
            'requirements' => $program->requirements ?? [],
            'audience' => $program->audience ?? [],
            'promo_video_url' => $program->promo_video_url,
            'image_url' => $program->imageUrl(),
            'is_active' => $program->is_active,
            'is_published' => $program->is_published,
            'is_featured' => $program->is_featured,
            'classes_count' => $program->classes_count ?? $program->classes()->count(),
            'public_url' => $program->instructor->slug === null
                ? null
                : route('programs.show', ['slug' => $program->instructor->slug, 'program' => $program->id]),
        ];
    }
}
