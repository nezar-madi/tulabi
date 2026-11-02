<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendRegisterCodeRequest;
use App\Http\Requests\Auth\VerifyRegisterCodeRequest;
use App\Models\User;
use App\Support\Accounts;
use App\Support\LibyanPhone;
use App\Support\PhoneVerifications;
use App\Support\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sign-up in three steps, for students and instructors alike: the phone, the code sent to it,
 * then a password. Everything else is filled in later from the profile. The steps live in the
 * session: `register.phone` (a code was sent) and `register.verified` (the code was right).
 */
class RegisterController extends Controller
{
    private const VERIFIED_MINUTES = 30;

    public function create(Request $request, Recaptcha $recaptcha, PhoneVerifications $verifications): Response
    {
        if ($request->query('for') === 'instructor') {
            $request->session()->put('register.for', 'instructor');
        }

        $verified = $this->verifiedPhone($request);
        $pending = $request->session()->get('register.phone');
        $step = 'phone';
        $wait = 0;

        if ($verified !== null) {
            $step = 'password';
        } elseif (is_string($pending) && ($wait = $verifications->pendingWait($pending)) !== null) {
            $step = 'code';
        }

        $phone = $verified ?? ($step === 'code' ? $pending : null);

        return Inertia::render('Auth/Register', [
            'step' => $step,
            'phone' => $phone === null ? null : LibyanPhone::display($phone),
            'resendIn' => $step === 'code' ? $wait : 0,
            'forInstructor' => $request->session()->get('register.for') === 'instructor',
            'recaptchaSiteKey' => $recaptcha->siteKey(),
        ]);
    }

    public function sendCode(SendRegisterCodeRequest $request, PhoneVerifications $verifications): RedirectResponse
    {
        $phone = (string) $request->validated('phone');

        $request->session()->forget('register.verified');
        $request->session()->put('register.phone', $phone);

        return $this->sent($verifications->send($phone));
    }

    public function resend(Request $request, PhoneVerifications $verifications): RedirectResponse
    {
        $phone = $request->session()->get('register.phone');

        if (! is_string($phone) || User::query()->where('phone', $phone)->exists()) {
            return redirect()->route('register');
        }

        return $this->sent($verifications->send($phone));
    }

    public function verify(VerifyRegisterCodeRequest $request, PhoneVerifications $verifications): RedirectResponse
    {
        $phone = $request->session()->get('register.phone');

        if (! is_string($phone)) {
            return redirect()->route('register');
        }

        $result = $verifications->check($phone, (string) $request->validated('code'));

        if ($result !== 'ok') {
            throw ValidationException::withMessages([
                'code' => $result === 'wrong' ? __('accounts.register.code_wrong') : __('accounts.register.code_expired'),
            ]);
        }

        $request->session()->forget('register.phone');
        $request->session()->put('register.verified', ['phone' => $phone, 'at' => now()->timestamp]);

        return redirect()->route('register');
    }

    /** «غيّر الرقم»: back to the first step. */
    public function restart(Request $request): RedirectResponse
    {
        $request->session()->forget(['register.phone', 'register.verified']);

        return redirect()->route('register');
    }

    public function store(RegisterRequest $request, Accounts $accounts): RedirectResponse
    {
        $phone = $this->verifiedPhone($request);

        if ($phone === null) {
            return redirect()->route('register');
        }

        // Someone may have made an account on this number since the code was checked.
        if (User::query()->where('phone', $phone)->exists()) {
            $request->session()->forget('register.verified');

            throw ValidationException::withMessages(['password' => __('accounts.phone_taken')]);
        }

        $user = $accounts->register(['phone' => $phone, 'password' => $request->validated('password')]);

        $forInstructor = $request->session()->get('register.for') === 'instructor';
        $request->session()->forget('register');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($forInstructor ? 'join' : 'account')
            ->with('status', __('accounts.welcome'));
    }

    /** @param  int  $wait  seconds before another code may be sent; 0 means one was just sent */
    private function sent(int $wait): RedirectResponse
    {
        return $wait > 0
            ? redirect()->route('register')->withErrors(['code' => __('accounts.register.wait', ['seconds' => $wait])])
            : redirect()->route('register')->with('status', __('accounts.register.code_sent'));
    }

    private function verifiedPhone(Request $request): ?string
    {
        $verified = $request->session()->get('register.verified');

        if (! is_array($verified) || ! isset($verified['phone'], $verified['at'])) {
            return null;
        }

        if (now()->timestamp - (int) $verified['at'] > self::VERIFIED_MINUTES * 60) {
            $request->session()->forget('register.verified');

            return null;
        }

        return (string) $verified['phone'];
    }
}
