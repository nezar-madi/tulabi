<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\JoinClassRequest;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Api\AppShape;
use App\Support\CourseContent;
use App\Support\EnrollmentPresenter;
use App\Support\Enrollments;
use App\Support\InstructorSite;
use App\Support\PublicCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * An instructor's public page inside the app: the instructor, their open classes and published
 * programs, one class, one program, and «اطلب الانضمام». Visitors' rules only (App\Support\PublicCatalog,
 * as on the website); no sign-in needed to look, a token adds the person's own request to a class.
 * The instructor manages their own drafts in the instructor part of the API, not here.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly PublicCatalog $catalog) {}

    public function instructor(string $slug): JsonResponse
    {
        $instructor = $this->catalog->instructor($slug);

        return response()->json([
            'instructor' => $this->site($instructor),
            'classes' => AppShape::list($this->catalog->openClasses($instructor)
                ->map(fn (TeachingClass $class) => InstructorSite::course($class, $instructor))),
            'programs' => AppShape::list($this->catalog->publicPrograms($instructor)),
        ]);
    }

    public function showClass(Request $request, string $slug, int $class): JsonResponse
    {
        $instructor = $this->catalog->instructor($slug);
        $model = $this->catalog->visibleClass($instructor, $class);

        return response()->json([
            'instructor' => $this->site($instructor),
            'class' => AppShape::of(InstructorSite::course($model, $instructor, $this->ownEnrollment($request, $model))),
        ]);
    }

    public function showProgram(string $slug, int $program): JsonResponse
    {
        $instructor = $this->catalog->instructor($slug);
        $model = $this->catalog->visibleProgram($instructor, $program);
        $runs = $this->catalog->openClasses($instructor)
            ->where('program_id', $model->id)
            ->map(fn (TeachingClass $class) => InstructorSite::course($class, $instructor))
            ->values();

        return response()->json([
            'instructor' => $this->site($instructor),
            'program' => AppShape::of(InstructorSite::program($model, $instructor, $runs->count())),
            'classes' => AppShape::list($runs),
        ]);
    }

    /** «اطلب الانضمام»: the same checks as the website (the class takes requests, one request each). */
    public function join(JoinClassRequest $request, TeachingClass $class, Enrollments $enrollments): JsonResponse
    {
        // Only a class visitors can see: a draft or a hidden instructor is "not found", as on the website.
        abort_unless($class->instructor->slug !== null && $class->isPubliclyVisible() && $class->instructor->isPublic(), 404);

        $enrollment = $enrollments->request($request->user(), $class, $request->validated('note'));

        return response()->json([
            'message' => __('enrollments.requested'),
            'enrollment' => AppShape::of(EnrollmentPresenter::forStudent($enrollment->fresh(['teachingClass.instructor.user']))),
        ], 201);
    }

    /**
     * The instructor's page details, without the website's owner flag. Each «من أنا» section keeps
     * its safe HTML (`body`) and gets a plain-text version (`text`) the app can show as it is.
     *
     * @return array<string, mixed>
     */
    private function site(Instructor $instructor): array
    {
        $site = InstructorSite::site($instructor, false);
        unset($site['is_owner']);
        $site['about_sections'] = array_map(
            fn (array $section) => [...$section, 'text' => CourseContent::htmlToText((string) ($section['body'] ?? ''))],
            $site['about_sections'],
        );

        return AppShape::of($site);
    }

    /** The signed-in person's request to this class, when the app sent a token. */
    private function ownEnrollment(Request $request, TeachingClass $class): ?Enrollment
    {
        $user = $request->user('sanctum');

        return $user instanceof User && $user->isActive() && $user->getKey() !== $class->instructor->user_id
            ? $class->enrollments()->where('user_id', $user->getKey())->first()
            : null;
    }
}
