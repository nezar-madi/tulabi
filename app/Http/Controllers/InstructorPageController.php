<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\ClassPresenter;
use App\Support\ProfileLinks;
use App\Support\TulabiClock;
use App\Support\WhatsApp;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public pages: tulabi.ly/{slug} and tulabi.ly/{slug}/classes/{id}. The instructor
 * also sees their draft classes here (as a preview); nobody else does.
 */
class InstructorPageController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $instructor = $this->instructor($slug);
        $today = TulabiClock::today();

        $classes = $instructor->classes()
            ->publiclyVisible()
            ->where(fn ($query) => $query
                ->where('ends_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('ends_on')->where('starts_on', '>=', $today)))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get()
            ->map(fn (TeachingClass $class) => ClassPresenter::forPublic($class));

        return Inertia::render('Public/Instructor', [
            'instructor' => $this->present($instructor),
            'classes' => $classes,
            'isOwner' => $this->isOwner($request, $instructor),
        ]);
    }

    public function showClass(Request $request, string $slug, int $class): Response
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

        return Inertia::render('Public/Class', [
            'instructor' => $this->present($instructor),
            'item' => ClassPresenter::forPublic($model),
            'isOwner' => $isOwner,
            'acceptsRequests' => $model->acceptsRequests(),
            // The visitor's own place in this class, if any (status only).
            'enrollment' => $enrollment === null ? null : [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
            ],
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function present(Instructor $instructor): array
    {
        return [
            'slug' => $instructor->slug,
            'display_name' => $instructor->display_name,
            'specialty' => $instructor->specialty,
            'city' => $instructor->city,
            'bio' => $instructor->bio,
            'photo_url' => $instructor->photoUrl(),
            'links' => ProfileLinks::present($instructor->links),
            'whatsapp' => WhatsApp::link(
                $instructor->whatsapp,
                __('instructors.whatsapp_hello', ['name' => $instructor->display_name]),
            ),
        ];
    }
}
