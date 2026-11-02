<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\PasswordResetLink;
use App\Models\User;
use App\Notifications\PasswordResetLinkMail;
use App\Support\PasswordResetLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * «نسيت كلمة المرور» (an email link for accounts that have an email) and the page every
 * reset link opens, whoever made it.
 */
class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetLinks $links) {}

    public function request(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * The answer is the same whether the phone exists or has an email, so the page
     * can't be used to find out who has an account.
     */
    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = User::query()->where('phone', $request->validated('phone'))->first();

        if ($user !== null && $user->isActive() && $user->email !== null) {
            $url = $this->links->issue($user, PasswordResetLink::ISSUER_EMAIL);

            try {
                $user->notify(new PasswordResetLinkMail($url));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('password.request')->with('status', __('accounts.reset.sent'));
    }

    public function show(string $token): Response
    {
        $link = $this->links->find($token);

        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'valid' => $link !== null,
            'name' => $link?->user->name,
        ]);
    }

    public function update(ResetPasswordRequest $request, string $token): RedirectResponse
    {
        $user = $this->links->consume($token, (string) $request->validated('password'));

        if ($user === null) {
            return redirect()->route('password.reset', ['token' => $token]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('status', __('accounts.reset.done'));
    }
}
