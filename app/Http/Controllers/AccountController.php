<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Support\LibyanPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «حسابي»: the account's details, password, and where the instructor application stands.
 */
class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $application = $user->instructorApplications()->latest('id')->first();

        return Inertia::render('Account/Show', [
            'profile' => [
                'name' => $user->hasName() ? $user->name : '',
                'phone' => LibyanPhone::display($user->phone),
                'email' => $user->email,
            ],
            'instructor' => $user->instructor?->only(['display_name', 'specialty', 'city', 'status']),
            'application' => $application === null ? null : [
                'status' => $application->status,
                'specialty' => $application->specialty,
                'created_at' => $application->created_at?->toIso8601String(),
                'rejection_reason' => $application->status === InstructorApplication::STATUS_REJECTED
                    ? $application->rejection_reason
                    : null,
            ],
            'enrollmentCounts' => $user->enrollments()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $email = $data['email'] ?? null;

        $user->fill(['name' => trim($data['name']), 'email' => $email === '' ? null : $email]);

        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();

        return redirect()->route('account')->with('status', __('accounts.profile_saved'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['password' => (string) $request->validated('password')])->save();

        // Other devices signed in with the old password are signed out by AuthenticateSession;
        // this session takes the new password hash at the end of the request.
        $request->session()->regenerate();

        return redirect()->route('account')->with('status', __('accounts.password_saved'));
    }
}
