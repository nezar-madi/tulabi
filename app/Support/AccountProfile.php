<?php

namespace App\Support;

use App\Models\InstructorApplication;
use App\Models\User;

/**
 * «حسابي»: the account's details and where the instructor application stands. Shared by the
 * website's AccountController and the app's /api/v1/account.
 */
class AccountProfile
{
    /**
     * @return array<string, mixed>
     */
    public function show(User $user): array
    {
        $application = $user->instructorApplications()->latest('id')->first();

        return [
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
        ];
    }

    /**
     * Name and email (Account\UpdateProfileRequest). A new email is unverified again.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): void
    {
        $email = $data['email'] ?? null;

        $user->fill(['name' => trim($data['name']), 'email' => $email === '' ? null : $email]);

        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();
    }

    /** A new password ends every app sign-in (the website's end through AuthenticateSession). */
    public function updatePassword(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();
    }
}
