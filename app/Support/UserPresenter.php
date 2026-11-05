<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;

/**
 * The signed-in account as the app sees it (/api/v1/me), and the counts both the website's
 * menu and the app show.
 */
class UserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forApp(User $user): array
    {
        $isInstructor = $user->isInstructor();
        $instructor = $isInstructor ? $user->instructor : null;

        return [
            'id' => $user->id,
            'name' => $user->hasName() ? $user->name : null,
            'phone' => LibyanPhone::display($user->phone),
            'email' => $user->email,
            'is_instructor' => $isInstructor,
            // A pending «انضم كمدرّس» application (the app shows «طلبك قيد المراجعة»).
            'instructor_application_pending' => ! $isInstructor && $user->pendingInstructorApplication() !== null,
            'instructor' => $instructor === null ? null : [
                'slug' => $instructor->slug,
                'display_name' => $instructor->display_name,
                'public_url' => $instructor->publicUrl(),
                'pending_requests' => self::pendingRequests($user),
            ],
        ];
    }

    /** Join requests waiting for this instructor (0 for anyone else). */
    public static function pendingRequests(User $user): int
    {
        if (! $user->isInstructor()) {
            return 0;
        }

        return Enrollment::query()
            ->where('status', Enrollment::STATUS_REQUESTED)
            ->whereHas('teachingClass', fn ($query) => $query
                ->where('instructor_id', $user->instructor->getKey())
                ->where('status', '!=', TeachingClass::STATUS_CANCELED))
            ->count();
    }
}
