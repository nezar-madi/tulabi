<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The instructor area (/teach, and the app's /api/v1/teach): only accounts with an active
 * instructor side. The user is the route's guard's (the website's session or the app's token).
 */
class EnsureInstructor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isInstructor()) {
            abort(403);
        }

        return $next($request);
    }
}
