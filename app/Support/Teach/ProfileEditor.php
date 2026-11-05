<?php

namespace App\Support\Teach;

use App\Models\Instructor;
use App\Support\AboutSections;
use App\Support\InstructorSlug;
use App\Support\LibyanPhone;
use App\Support\ProfileLinks;
use Illuminate\Validation\ValidationException;

/**
 * «صفحتي»: the instructor's public page details and address. Shared by the website's
 * Teach\ProfileController and the app's /api/v1/teach/profile (the photo is App\Support\InstructorPhoto).
 */
class ProfileEditor
{
    /**
     * What the form shows, with its suggestions and limits.
     *
     * @return array<string, mixed>
     */
    public function data(Instructor $instructor): array
    {
        return [
            'profile' => [
                'display_name' => $instructor->display_name,
                'specialty' => $instructor->specialty,
                'city' => $instructor->city,
                'sections' => $instructor->aboutSections(),
                'whatsapp' => $instructor->whatsapp === null ? '' : (string) LibyanPhone::display($instructor->whatsapp),
                'links' => ProfileLinks::clean($instructor->links),
                'slug' => $instructor->slug,
                'photo_url' => $instructor->photoUrl(),
                'public_url' => $instructor->publicUrl(),
            ],
            'defaultSectionTitle' => AboutSections::DEFAULT_TITLE,
            'sectionSuggestions' => AboutSections::SUGGESTIONS,
            'baseUrl' => rtrim(url('/'), '/').'/',
            'slugRules' => [
                'min' => InstructorSlug::MIN_LENGTH,
                'max' => InstructorSlug::MAX_LENGTH,
            ],
        ];
    }

    /**
     * Save the validated details (Teach\UpdateProfileRequest).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Instructor $instructor, array $data): void
    {
        $sections = AboutSections::clean($data['sections']);

        $instructor->fill([
            'display_name' => trim($data['display_name']),
            'specialty' => trim($data['specialty']),
            'city' => trim($data['city']),
            'bio_sections' => $sections,
            // The plain text of «من أنا»: the page's lead line, its description and the setup step.
            'bio' => AboutSections::plain($sections),
            'whatsapp' => $data['whatsapp'] ?? null,
            'links' => ProfileLinks::clean($data['links'] ?? []),
        ]);
        $instructor->forceFill(['slug' => $data['slug'] ?? null]);

        // Classes are shown at /{slug}/classes/..., so a published class keeps the address.
        if ($instructor->isDirty('slug') && $instructor->slug === null
            && $instructor->classes()->publiclyVisible()->exists()) {
            throw ValidationException::withMessages(['slug' => __('instructors.slug_needed_for_classes')]);
        }

        $instructor->save();
    }
}
