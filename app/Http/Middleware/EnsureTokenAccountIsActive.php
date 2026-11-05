<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app's side of EnsureAccountIsActive: a suspended account's token is deleted on its next
 * request and the app is told to sign out (401, code account_suspended).
 */
class EnsureTokenAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            $token = $user->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'message' => __('accounts.suspended'),
                'code' => 'account_suspended',
            ], 401);
        }

        return $next($request);
    }
}
