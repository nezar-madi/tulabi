<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Phone and password check, shared by the website's sign-in and the app's (/api/v1/auth/login).
 * Guesses are limited per phone+IP and per IP (no SMS, so the password is the only lock).
 * It only checks: the caller starts the session or issues the token.
 */
class PasswordLogin
{
    private const MAX_ATTEMPTS_PER_PHONE = 5;

    private const MAX_ATTEMPTS_PER_IP = 30;

    private const DECAY_SECONDS = 600;

    /**
     * The account for this phone and password; a validation error on `phone` otherwise
     * (wrong, too many tries, or suspended).
     */
    public function attempt(string $phone, string $password, string $ip): User
    {
        $phoneKey = 'login:'.$phone.'|'.$ip;
        $ipKey = 'login-ip:'.$ip;

        $this->ensureNotLockedOut($phoneKey, self::MAX_ATTEMPTS_PER_PHONE);
        $this->ensureNotLockedOut($ipKey, self::MAX_ATTEMPTS_PER_IP);

        $credentials = ['phone' => $phone, 'password' => $password];
        $provider = Auth::guard('web')->getProvider();
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

        return $user;
    }

    private function ensureNotLockedOut(string $key, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        throw ValidationException::withMessages([
            'phone' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
        ]);
    }
}
