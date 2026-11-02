<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\InstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Support\Accounts;
use App\Support\InstructorApplications;
use App\Support\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «انضم كمدرّس»: a visitor makes an account and applies in one form; someone signed in
 * only adds the teaching details. The admin then accepts or rejects.
 */
class InstructorApplicationController extends Controller
{
    public function create(Request $request, Recaptcha $recaptcha): Response|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user('web');

        if ($user !== null && ($user->instructor()->exists() || $user->pendingInstructorApplication() !== null)) {
            return redirect()->route('account');
        }

        $lastRejected = $user?->instructorApplications()
            ->where('status', InstructorApplication::STATUS_REJECTED)
            ->latest('id')
            ->first();

        return Inertia::render('Join/Create', [
            'signedIn' => $user !== null,
            'recaptchaSiteKey' => $user === null ? $recaptcha->siteKey() : '',
            'previous' => $lastRejected?->only(['specialty', 'city', 'bio']),
        ]);
    }

    public function store(
        InstructorApplicationRequest $request,
        Accounts $accounts,
        InstructorApplications $applications,
    ): RedirectResponse {
        $data = $request->validated();
        $user = $request->user('web');

        if ($user === null) {
            $user = DB::transaction(function () use ($accounts, $applications, $data) {
                $user = $accounts->register($data);
                $applications->submit($user, $data);

                return $user;
            });

            Auth::login($user);
            $request->session()->regenerate();
        } else {
            $applications->submit($user, $data);
        }

        return redirect()->route('account')->with('status', __('instructors.submitted'));
    }
}
