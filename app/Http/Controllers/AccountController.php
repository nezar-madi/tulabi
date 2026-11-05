<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Support\AccountProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «حسابي»: the account's details, password, and where the instructor application stands
 * (App\Support\AccountProfile, shared with the app).
 */
class AccountController extends Controller
{
    public function show(Request $request, AccountProfile $account): Response
    {
        return Inertia::render('Account/Show', $account->show($request->user()));
    }

    public function update(UpdateProfileRequest $request, AccountProfile $account): RedirectResponse
    {
        $account->update($request->user(), $request->validated());

        return redirect()->route('account')->with('status', __('accounts.profile_saved'));
    }

    public function updatePassword(UpdatePasswordRequest $request, AccountProfile $account): RedirectResponse
    {
        // Other devices signed in with the old password are signed out by AuthenticateSession;
        // this session takes the new password hash at the end of the request. The app's
        // sign-ins (tokens) end too.
        $account->updatePassword($request->user(), (string) $request->validated('password'));
        $request->session()->regenerate();

        return redirect()->route('account')->with('status', __('accounts.password_saved'));
    }
}
