<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    private const MAX_ATTEMPTS_PER_ACCOUNT = 5;

    private const MAX_ATTEMPTS_PER_IP = 20;

    private const DECAY_SECONDS = 600;

    public function create(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));

        // Admin accounts control every account, so guesses are limited per account and IP
        // and per IP across all accounts.
        $accountKey = 'admin-login:'.Str::transliterate($credentials['email']).'|'.$request->ip();
        $ipKey = 'admin-login-ip:'.$request->ip();
        $this->ensureNotLockedOut($accountKey, self::MAX_ATTEMPTS_PER_ACCOUNT);
        $this->ensureNotLockedOut($ipKey, self::MAX_ATTEMPTS_PER_IP);

        $guard = Auth::guard('admin');
        $provider = $guard->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof Admin || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($accountKey, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($accountKey);
        $provider->rehashPasswordIfRequired($user, $credentials);
        $remember = $request->boolean('remember');

        // The password alone is not enough once an authenticator is set up: the account
        // waits (not signed in) until the code is entered on the challenge page.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();
            $request->session()->put(TwoFactorChallengeController::SESSION_KEY, [
                'id' => $user->getKey(),
                'remember' => $remember,
                'expires_at' => now()->addSeconds(TwoFactorChallengeController::PENDING_SECONDS)->getTimestamp(),
            ]);

            return redirect()->route('admin.two-factor.challenge');
        }

        // Not set up yet: signed in, but EnsureAdminTwoFactor sends every page to setup.
        $guard->login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function ensureNotLockedOut(string $key, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
