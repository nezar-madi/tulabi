<?php

namespace App\Http\Middleware;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $this->user($request),
                'admin' => fn () => $request->routeIs('admin.*') ? $request->user('admin')?->only(['name', 'email']) : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function user(Request $request): ?array
    {
        $user = $request->user('web');

        if (! $user instanceof User) {
            return null;
        }

        $isInstructor = $user->isInstructor();

        return [
            'name' => $user->name,
            'email' => $user->email,
            'isInstructor' => $isInstructor,
            'instructorUrl' => $isInstructor ? $user->instructor->publicUrl() : null,
            // Join requests waiting for this instructor (badge in the instructor menu).
            'pendingRequests' => $isInstructor ? Enrollment::query()
                ->where('status', Enrollment::STATUS_REQUESTED)
                ->whereHas('teachingClass', fn ($query) => $query
                    ->where('instructor_id', $user->instructor->getKey())
                    ->where('status', '!=', TeachingClass::STATUS_CANCELED))
                ->count() : 0,
        ];
    }
}
