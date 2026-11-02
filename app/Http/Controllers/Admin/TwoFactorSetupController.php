<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\TwoFactor\AdminTwoFactor;
use App\Support\TwoFactor\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Set up the authenticator for an admin account (required before using the admin pages),
 * show the recovery codes once, and make new recovery codes later.
 */
class TwoFactorSetupController extends Controller
{
    /** The new secret waits here until a code from it is confirmed. */
    public const SECRET_KEY = 'admin_two_factor_setup_secret';

    private const CODES_KEY = 'admin_two_factor_recovery_codes';

    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 600;

    public function __construct(private readonly AdminTwoFactor $twoFactor) {}

    public function show(Request $request): Response
    {
        $user = $this->user($request);

        if ($user->hasTwoFactorEnabled()) {
            return Inertia::render('Admin/TwoFactor/Setup', [
                'enabled' => true,
                'recoveryCodes' => $request->session()->get(self::CODES_KEY),
                'recoveryCodesLeft' => count($user->recoveryCodeHashes()),
            ]);
        }

        $secret = $request->session()->get(self::SECRET_KEY);
        if (! is_string($secret) || $secret === '') {
            $secret = Totp::generateSecret();
            $request->session()->put(self::SECRET_KEY, $secret);
        }

        $issuer = AdminTwoFactor::issuer();

        return Inertia::render('Admin/TwoFactor/Setup', [
            'enabled' => false,
            'secret' => $secret,
            'otpauthUrl' => Totp::provisioningUri($secret, (string) $user->email, $issuer),
            'issuer' => $issuer,
            'account' => (string) $user->email,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        $secret = $request->session()->get(self::SECRET_KEY);
        if (! is_string($secret) || $secret === '') {
            return redirect()->route('admin.two-factor.setup');
        }

        $key = $this->ensureNotLockedOut($request, $user);
        $step = Totp::verify($secret, $data['code']);

        if ($step === null) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages(['code' => __('accounts.two_factor.setup_invalid')]);
        }

        RateLimiter::clear($key);
        $codes = $this->twoFactor->enable($user, $secret, $step);
        $request->session()->forget(self::SECRET_KEY);
        $request->session()->flash(self::CODES_KEY, $codes);

        return redirect()->route('admin.two-factor.setup');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        $key = $this->ensureNotLockedOut($request, $user);
        if (! $this->twoFactor->verifyCode($user, $data['code'])) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages(['code' => __('accounts.two_factor.invalid_code')]);
        }

        RateLimiter::clear($key);
        $request->session()->flash(self::CODES_KEY, $this->twoFactor->regenerateRecoveryCodes($user));

        return redirect()->route('admin.two-factor.setup');
    }

    private function user(Request $request): Admin
    {
        $user = $request->user('admin');
        abort_unless($user instanceof Admin, 403);

        return $user;
    }

    private function ensureNotLockedOut(Request $request, Admin $user): string
    {
        $key = 'admin-two-factor-setup:'.$user->getKey().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'code' => __('accounts.too_many', ['minutes' => max(1, (int) ceil($seconds / 60))]),
            ]);
        }

        return $key;
    }
}
