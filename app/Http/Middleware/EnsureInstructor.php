<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The instructor area (/teach): only accounts with an active instructor side.
 */
class EnsureInstructor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user instanceof User || ! $user->isInstructor()) {
            abort(403);
        }

        return $next($request);
    }
}
