<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Models\TeachingClass;
use App\Support\ClassPresenter;
use App\Support\TulabiClock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The instructor's home: what the page still needs, and the coming classes.
 * Requests and money join in phases 3 and 4.
 */
class DashboardController extends Controller
{
    use FindsOwnClass;

    public function __invoke(Request $request): Response
    {
        $instructor = $this->instructor($request);
        $today = TulabiClock::today();

        $upcoming = $instructor->classes()
            ->whereIn('status', [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED])
            ->where(fn ($query) => $query
                ->where('ends_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('ends_on')->where('starts_on', '>=', $today)))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(fn (TeachingClass $class) => ClassPresenter::forOwner($class));

        return Inertia::render('Teach/Dashboard', [
            'profile' => [
                'display_name' => $instructor->display_name,
                'slug' => $instructor->slug,
                'public_url' => $instructor->publicUrl(),
                'has_photo' => $instructor->photo_path !== null,
                'has_bio' => trim((string) $instructor->bio) !== '',
            ],
            'counts' => $instructor->classes()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'upcoming' => $upcoming,
        ]);
    }
}
