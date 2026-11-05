<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\InstructorSite;
use App\Support\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The public pages: tulabi.ly/{slug} and tulabi.ly/{slug}/classes/{id}, rendered on the server
 * in the look of rqmana's center website (resources/views/site). The instructor also sees their
 * draft classes here (as a preview); nobody else does. The meeting link is never on these pages.
 * What is visible is App\Support\PublicCatalog (the app's catalogue uses the same).
 */
class InstructorPageController extends Controller
{
    public function __construct(private readonly PublicCatalog $catalog) {}

    public function show(Request $request, string $slug): View
    {
        $instructor = $this->catalog->instructor($slug);
        $site = InstructorSite::site($instructor, $this->isOwner($request, $instructor));

        $courses = $this->catalog->openClasses($instructor)
            ->map(fn (TeachingClass $class) => InstructorSite::course($class, $instructor))
            ->all();

        $bio = trim((string) $instructor->bio);

        return view('site.instructor', [
            'site' => $site,
            'courses' => $courses,
            'programs' => $this->catalog->publicPrograms($instructor, limit: 3),
            'programsTotal' => $instructor->programs()->public()->count(),
            // The first line of the bio under the name (the full bio is in «نبذة»).
            'lead' => $bio === '' ? null : Str::limit(Str::before($bio, "\n"), 220),
            'meta' => [
                'title' => $instructor->display_name.' - '.$instructor->specialty.' | طلابي',
                'description' => Str::limit($bio !== '' ? $bio : $instructor->specialty.'، '.$instructor->city, 160),
                'canonical' => $instructor->publicUrl(),
            ],
        ]);
    }

    /** The published programs: tulabi.ly/{slug}/programs. */
    public function programs(Request $request, string $slug): View
    {
        $instructor = $this->catalog->instructor($slug);

        return view('site.programs.index', [
            'site' => InstructorSite::site($instructor, $this->isOwner($request, $instructor)),
            'programs' => $this->catalog->publicPrograms($instructor),
            'meta' => [
                'title' => __('site.programs.title').' - '.$instructor->display_name.' | طلابي',
                'description' => __('site.programs.intro'),
                'canonical' => route('programs.index', ['slug' => $instructor->slug]),
            ],
        ]);
    }

    /** One program with its open courses. The owner also sees a program that is not published yet. */
    public function showProgram(Request $request, string $slug, int $program): View
    {
        $instructor = $this->catalog->instructor($slug);
        $isOwner = $this->isOwner($request, $instructor);

        $model = $this->catalog->visibleProgram($instructor, $program, $isOwner);

        $runs = $this->catalog->openClasses($instructor)
            ->where('program_id', $model->id)
            ->map(fn (TeachingClass $class) => InstructorSite::course($class, $instructor))
            ->values()
            ->all();

        $site = InstructorSite::site($instructor, $isOwner);
        $site['is_owner'] = $isOwner;

        return view('site.programs.show', [
            'site' => $site,
            'program' => InstructorSite::program($model, $instructor, count($runs)),
            'runs' => $runs,
            'ownerBar' => $model->isPublic() ? null : __('site.programs.hidden_bar'),
            'ownerEditUrl' => route('teach.programs.edit', $model),
            'meta' => [
                'title' => $model->name.' - '.$instructor->display_name.' | طلابي',
                'description' => InstructorSite::program($model, $instructor)['summary'],
                'canonical' => route('programs.show', ['slug' => $instructor->slug, 'program' => $model->id]),
            ],
        ]);
    }

    public function showClass(Request $request, string $slug, int $class): View
    {
        $instructor = $this->catalog->instructor($slug);
        $isOwner = $this->isOwner($request, $instructor);

        // Visitors see a class until it ends (the owner still sees an ended one).
        $model = $this->catalog->visibleClass($instructor, $class, $isOwner);

        $user = $request->user('web');
        $enrollment = $user instanceof User && ! $isOwner
            ? $model->enrollments()->where('user_id', $user->getKey())->first()
            : null;

        $course = InstructorSite::course($model, $instructor, $enrollment);
        $related = $this->catalog->openClasses($instructor)
            ->reject(fn (TeachingClass $other) => $other->id === $model->id)
            ->take(3)
            ->map(fn (TeachingClass $other) => InstructorSite::course($other, $instructor))
            ->values()
            ->all();

        return view('site.class', [
            'site' => InstructorSite::site($instructor, $isOwner),
            'course' => $course,
            'related' => $related,
            'meta' => [
                'title' => $model->title.' - '.$instructor->display_name.' | طلابي',
                'description' => Str::limit(trim(strip_tags((string) $model->description)) ?: $model->title, 160),
                'canonical' => $course['url'],
            ],
        ]);
    }

    private function isOwner(Request $request, Instructor $instructor): bool
    {
        $user = $request->user('web');

        return $user instanceof User && $user->getKey() === $instructor->user_id;
    }
}
