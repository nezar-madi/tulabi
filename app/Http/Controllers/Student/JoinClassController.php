<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\JoinClassRequest;
use App\Models\TeachingClass;
use App\Support\Enrollments;
use Illuminate\Http\RedirectResponse;

/**
 * «اطلب الانضمام» from a public class page.
 */
class JoinClassController extends Controller
{
    /**
     * A visitor who pressed the button lands here after signing in (the auth middleware
     * keeps this URL), and goes back to the class page with the request box open.
     */
    public function create(TeachingClass $class): RedirectResponse
    {
        // Only a class visitors can see: a draft, or a hidden instructor's page address, is never revealed.
        $slug = $class->instructor->slug;
        abort_unless($slug !== null && $class->isPubliclyVisible() && $class->instructor->isPublic(), 404);

        return redirect()->route('classes.show', ['slug' => $slug, 'class' => $class->id, 'request' => 1]);
    }

    public function store(JoinClassRequest $request, TeachingClass $class, Enrollments $enrollments): RedirectResponse
    {
        $enrollments->request($request->user('web'), $class, $request->validated('note'));

        return back()->with('status', __('enrollments.requested'));
    }
}
