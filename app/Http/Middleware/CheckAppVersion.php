<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An app older than tulabi.app.min_version (X-App-Version header) is told to update (426),
 * so an API change never meets an app that cannot handle it. No header (a browser, a test
 * tool) passes; the app always sends it. /api/v1/meta always answers, so the app can learn
 * the versions before anything else.
 */
class CheckAppVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->header('X-App-Version');

        if (is_string($version) && $version !== '' && ! $request->routeIs('api.v1.meta')
            && $this->isOlder($version, (string) config('tulabi.app.min_version'))) {
            return response()->json([
                'message' => __('api.update_required'),
                'code' => 'update_required',
                'min_version' => config('tulabi.app.min_version'),
                'store_urls' => config('tulabi.app.store_urls'),
            ], 426);
        }

        return $next($request);
    }

    private function isOlder(string $version, string $minimum): bool
    {
        // Only plain versions (1.2.3) are compared; anything else is treated as current.
        if (! preg_match('/^\d+(\.\d+){0,3}$/', $version)) {
            return false;
        }

        return version_compare($version, $minimum, '<');
    }
}
