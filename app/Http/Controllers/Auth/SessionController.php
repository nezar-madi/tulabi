<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sign in with phone and password. Guesses are limited per phone+IP and per IP (no SMS,
 * so the password is the only lock).
 */
class SessionController extends Controller
{
    private const MAX_ATTEMPTS_PER_PHONE = 5;

    private const MAX_ATTEMPTS_PER_IP = 30;

    private const DECAY_SECONDS = 600;

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $phone = (string) $request->validated('phone');
        $phoneKey = 'login:'.$phone.'|'.$request->ip();
        $ipKey = 'login-ip:'.$request->ip();

        $this->ensureNotLockedOut($phoneKey, self::MAX_ATTEMPTS_PER_PHONE);
        $this->ensureNotLockedOut($ipKey, self::MAX_ATTEMPTS_PER_IP);

        $credentials = ['phone' => $phone, 'password' => (string) $request->validated('password')];
        $guard = Auth::guard('web');
        $provider = $guard->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof User || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($phoneKey, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages(['phone' => __('auth.failed')]);
        }

        // The password was right, so saying "suspended" leaks nothing.
        if (! $user->isActive()) {
            throw ValidationException::withMessages(['phone' => __('accounts.suspended')]);
        }

        RateLimiter::clear($phoneKey);
        $provider->rehashPasswordIfRequired($user, $credentials);

        $guard->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function ensureNotLockedOut(string $key, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'phone' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }
}
