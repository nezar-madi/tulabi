<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Support\Accounts;
use App\Support\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Recaptcha $recaptcha): Response
    {
        return Inertia::render('Auth/Register', [
            'recaptchaSiteKey' => $recaptcha->siteKey(),
        ]);
    }

    public function store(RegisterRequest $request, Accounts $accounts): RedirectResponse
    {
        $user = $accounts->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('status', __('accounts.welcome'));
    }
}
