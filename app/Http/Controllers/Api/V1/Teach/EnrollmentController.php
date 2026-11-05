<?php

namespace App\Http\Controllers\Api\V1\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\EnrollmentActionRequest;
use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Support\Api\AppShape;
use App\Support\Enrollments;
use App\Support\Teach\ClassMembers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Join requests and a class's students in the app: accept, reject, remove, and a password link
 * for a student. Same rules as the website (EnrollmentActionRequest: the enrollment must be in one
 * of the instructor's classes; App\Support\Enrollments does the work). Each action answers with the
 * enrollment's new row, so the app can update its list without reloading it.
 */
class EnrollmentController extends Controller
{
    use FindsOwnClass;

    public function __construct(
        private readonly Enrollments $enrollments,
        private readonly ClassMembers $members,
    ) {}

    public function requests(Request $request): JsonResponse
    {
        return response()->json(['requests' => AppShape::list($this->members->pendingRequests($this->instructor($request)))]);
    }

    public function students(Request $request, TeachingClass $class): JsonResponse
    {
        $data = $this->members->ofClass($this->ownClass($request, $class));

        return response()->json([
            'class' => AppShape::of($data['item']),
            'enrollments' => AppShape::list($data['enrollments']),
        ]);
    }

    public function accept(EnrollmentActionRequest $request, Enrollment $enrollment): JsonResponse
    {
        $this->decided($this->enrollments->accept($enrollment));

        return $this->answer($enrollment, __('enrollments.accepted', ['name' => $enrollment->user?->name]));
    }

    public function reject(EnrollmentActionRequest $request, Enrollment $enrollment): JsonResponse
    {
        $this->decided($this->enrollments->reject($enrollment, $request->validated('reason')));

        return $this->answer($enrollment, __('enrollments.rejected'));
    }

    public function remove(EnrollmentActionRequest $request, Enrollment $enrollment): JsonResponse
    {
        $this->decided($this->enrollments->remove($enrollment, $request->validated('reason')));

        return $this->answer($enrollment, __('enrollments.removed'));
    }

    /** A one-time link to set a new password, to send to the student (whatsapp is a ready wa.me link). */
    public function resetLink(EnrollmentActionRequest $request, Enrollment $enrollment): JsonResponse
    {
        return response()->json(['reset_link' => $this->members->resetLink($enrollment, $this->instructor($request))]);
    }

    private function decided(bool $done): void
    {
        if (! $done) {
            throw ValidationException::withMessages(['enrollment' => __('enrollments.already_decided')]);
        }
    }

    private function answer(Enrollment $enrollment, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'enrollment' => AppShape::of($this->members->member($enrollment->fresh())),
        ]);
    }
}
