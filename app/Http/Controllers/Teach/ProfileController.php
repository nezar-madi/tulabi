<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\UpdateProfileRequest;
use App\Http\Requests\Teach\UploadPhotoRequest;
use App\Support\InstructorPhoto;
use App\Support\Teach\ProfileEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * «صفحتي»: the instructor's public page details, photo and address (the details are
 * App\Support\Teach\ProfileEditor, shared with the app).
 */
class ProfileController extends Controller
{
    use FindsOwnClass;

    public function edit(Request $request, ProfileEditor $profile): Response
    {
        return Inertia::render('Teach/Profile', $profile->data($this->instructor($request)));
    }

    public function update(UpdateProfileRequest $request, ProfileEditor $profile): RedirectResponse
    {
        $profile->update($this->instructor($request), $request->validated());

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
