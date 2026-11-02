<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\InstructorSite;
use App\Support\TulabiClock;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The public pages: tulabi.ly/{slug} and tulabi.ly/{slug}/classes/{id}, rendered on the server
 * in the look of rqmana's center website (resources/views/site). The instructor also sees their
 * draft classes here (as a preview); nobody else does. The meeting link is never on these pages.
 */
class InstructorPageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $instructor = $this->instructor($slug);
        $site = InstructorSite::site($instructor, $this->isOwner($request, $instructor));

        $courses = $this->openClasses($instructor)
            ->map(fn (TeachingClass $class) => InstructorSite::course($class, $instructor))
            ->all();

        $bio = trim((string) $instructor->bio);

        return view('site.instructor', [
            'site' => $site,
            'courses' => $courses,
            // The first line of the bio under the name (the full bio is in «نبذة»).
            'lead' => $bio === '' ? null : Str::limit(Str::before($bio, "\n"), 220),
            'meta' => [
                'title' => $instructor->display_name.' - '.$instructor->specialty.' | طلابي',
                'description' => Str::limit($bio !== '' ? $bio : $instructor->specialty.'، '.$instructor->city, 160),
                'canonical' => $instructor->publicUrl(),
            ],
        ]);
    }

    public function showClass(Request $request, string $slug, int $class): View
    {
        $instructor = $this->instructor($slug);
        $isOwner = $this->isOwner($request, $instructor);

        /** @var TeachingClass $model */
        $model = $instructor->classes()
            ->whereKey($class)
            ->when(! $isOwner, fn ($query) => $query->publiclyVisible())
            ->when($isOwner, fn ($query) => $query->where('status', '!=', TeachingClass::STATUS_CANCELED))
            ->firstOrFail();

        $user = $request->user('web');
        $enrollment = $user instanceof User && ! $isOwner
            ? $model->enrollments()->where('user_id', $user->getKey())->first()
            : null;

        $course = InstructorSite::course($model, $instructor, $enrollment);
        $related = $this->openClasses($instructor)
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

    /**
     * Published or closed classes that have not ended, soonest first.
     *
     * @return Collection<int, TeachingClass>
     */
    private function openClasses(Instructor $instructor)
    {
        $today = TulabiClock::today();

        return $instructor->classes()
            ->publiclyVisible()
            ->where(fn ($query) => $query
                ->where('ends_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('ends_on')->where('starts_on', '>=', $today)))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();
    }

    private function instructor(string $slug): Instructor
    {
        return Instructor::query()->public()->where('slug', $slug)->with('user')->firstOrFail();
    }

    private function isOwner(Request $request, Instructor $instructor): bool
    {
        $user = $request->user('web');

        return $user instanceof User && $user->getKey() === $instructor->user_id;
    }
}
