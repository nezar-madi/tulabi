<?php

namespace App\Http\Controllers\Api\V1\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\UpdateProfileRequest;
use App\Http\Requests\Teach\UploadPhotoRequest;
use App\Support\Api\AppShape;
use App\Support\InstructorPhoto;
use App\Support\Teach\ProfileEditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * «صفحتي» in the app: the public page's details and address, and the photo. Same rules as the
 * website (Teach\UpdateProfileRequest, App\Support\Teach\ProfileEditor, App\Support\InstructorPhoto).
 * Every answer brings the page back as the form shows it.
 */
class ProfileController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly ProfileEditor $profile) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->current($request));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $this->profile->update($this->instructor($request), $request->validated());

        return response()->json(['message' => __('instructors.profile_saved'), ...$this->current($request)]);
    }

    /** The photo, as multipart/form-data (`photo`). */
    public function uploadPhoto(UploadPhotoRequest $request, InstructorPhoto $photos): JsonResponse
    {
        try {
            $photos->store($this->instructor($request), $request->file('photo'));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['photo' => __('instructors.photo_unreadable')]);
        }

        return response()->json(['message' => __('instructors.photo_saved'), ...$this->current($request)]);
    }

    public function deletePhoto(InstructorActionRequest $request, InstructorPhoto $photos): JsonResponse
    {
        $photos->delete($this->instructor($request));

        return response()->json(['message' => __('instructors.photo_removed'), ...$this->current($request)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function current(Request $request): array
    {
        return AppShape::of($this->profile->data($this->instructor($request)->fresh()));
    }
}
