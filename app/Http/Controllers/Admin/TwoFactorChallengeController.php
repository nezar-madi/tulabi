<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\TwoFactor\AdminTwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Second step of the admin login: the password was right, now the authenticator code
 * (or a one-time recovery code) is needed before the account is signed in.
 */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'admin_two_factor_login';

    public const PENDING_SECONDS = 600;

    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 600;

    public function __construct(private readonly AdminTwoFactor $twoFactor) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return $this->expired($request);
        }

        return Inertia::render('Admin/TwoFactor/Challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $this->pendingUser($request);
        if ($user === null) {
            return $this->expired($request);
        }

        $useRecovery = trim((string) ($data['recovery_code'] ?? '')) !== '';
        $field = $useRecovery ? 'recovery_code' : 'code';

        $key = 'admin-two-factor:'.$user->getKey().'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $field => __('accounts.too_many', ['minutes' => max(1, (int) ceil($seconds / 60))]),
            ]);
        }

        $valid = $useRecovery
            ? $this->twoFactor->useRecoveryCode($user, (string) $data['recovery_code'])
            : $this->twoFactor->verifyCode($user, (string) ($data['code'] ?? ''));

        if (! $valid) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                $field => $useRecovery ? __('accounts.two_factor.invalid_recovery') : __('accounts.two_factor.invalid_code'),
            ]);
        }

        RateLimiter::clear($key);
        $remember = (bool) ($request->session()->get(self::SESSION_KEY)['remember'] ?? false);
        $request->session()->forget(self::SESSION_KEY);

        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();

        if ($useRecovery) {
            $left = count($user->recoveryCodeHashes());
            $request->session()->flash('status', __('accounts.two_factor.used_recovery', ['count' => $left]));
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    private function pendingUser(Request $request): ?Admin
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_array($pending)
            || ! isset($pending['id'], $pending['expires_at'])
            || (int) $pending['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        $user = Admin::query()->find($pending['id']);

        return $user instanceof Admin && $user->hasTwoFactorEnabled() ? $user : null;
    }

    private function expired(Request $request): RedirectResponse
    {
        $hadPending = $request->session()->has(self::SESSION_KEY);
        $request->session()->forget(self::SESSION_KEY);

        $redirect = redirect()->route('admin.login');

        return $hadPending
            ? $redirect->withErrors(['email' => __('accounts.two_factor.expired')])
            : $redirect;
    }
}
