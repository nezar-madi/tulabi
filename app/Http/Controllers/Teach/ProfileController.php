<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\UpdateProfileRequest;
use App\Http\Requests\Teach\UploadPhotoRequest;
use App\Support\AboutSections;
use App\Support\InstructorPhoto;
use App\Support\InstructorSlug;
use App\Support\LibyanPhone;
use App\Support\ProfileLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * «صفحتي»: the instructor's public page details, photo and address.
 */
class ProfileController extends Controller
{
    use FindsOwnClass;

    public function edit(Request $request): Response
    {
        $instructor = $this->instructor($request);

        return Inertia::render('Teach/Profile', [
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
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $instructor = $this->instructor($request);
        $data = $request->validated();

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

        return back()->with('status', __('instructors.profile_saved'));
    }

    public function uploadPhoto(UploadPhotoRequest $request, InstructorPhoto $photos): RedirectResponse
    {
        try {
            $photos->store($this->instructor($request), $request->file('photo'));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['photo' => __('instructors.photo_unreadable')]);
        }

        return back()->with('status', __('instructors.photo_saved'));
    }

    public function deletePhoto(InstructorActionRequest $request, InstructorPhoto $photos): RedirectResponse
    {
        $photos->delete($this->instructor($request));

        return back()->with('status', __('instructors.photo_removed'));
    }
}
