<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins must set up an authenticator before using the admin pages: until then every
 * signed-in admin route except logout and the setup screens sends them to setup.
 */
class EnsureAdminTwoFactor
{
    /** Routes reachable before the second factor is set up. */
    private const ALLOWED_ROUTES = [
        'admin.logout',
        'admin.two-factor.setup',
        'admin.two-factor.confirm',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if ($admin instanceof Admin
            && ! $admin->hasTwoFactorEnabled()
            && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                abort(403, 'Two-factor authentication must be set up first.');
            }

            return redirect()->route('admin.two-factor.setup');
        }

        return $next($request);
    }
}
