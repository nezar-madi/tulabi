<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Instructor;
use App\Models\InstructorApplication;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InstructorApplicationSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Instructor applications: submit, accept (adds the instructor side to the account), reject.
 */
class InstructorApplications
{
    /**
     * @param  array{specialty: string, city: string, bio?: string|null}  $data
     */
    public function submit(User $user, array $data): InstructorApplication
    {
        if ($user->instructor()->exists()) {
            throw ValidationException::withMessages(['specialty' => __('instructors.already_instructor')]);
        }

        if ($user->pendingInstructorApplication() !== null) {
            throw ValidationException::withMessages(['specialty' => __('instructors.already_pending')]);
        }

        $application = new InstructorApplication([
            'specialty' => trim($data['specialty']),
            'city' => trim($data['city']),
            'bio' => self::nullableText($data['bio'] ?? null),
        ]);
        $application->forceFill([
            'user_id' => $user->getKey(),
            'status' => InstructorApplication::STATUS_PENDING,
        ])->save();

        $this->notifyAdmin($application);

        return $application;
    }

    /**
     * Accept and make the instructor. Returns null when the application was already decided.
     */
    public function accept(InstructorApplication $application, Admin $admin): ?Instructor
    {
        return DB::transaction(function () use ($application, $admin) {
            /** @var InstructorApplication|null $fresh */
            $fresh = InstructorApplication::query()->whereKey($application->getKey())->lockForUpdate()->first();

            if ($fresh === null || ! $fresh->isPending()) {
                return null;
            }

            $fresh->forceFill([
                'status' => InstructorApplication::STATUS_ACCEPTED,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $user = $fresh->user;

            $instructor = new Instructor([
                'display_name' => $user->name,
                'specialty' => $fresh->specialty,
                'city' => $fresh->city,
                'bio' => $fresh->bio,
                'whatsapp' => $user->phone,
            ]);
            $instructor->forceFill([
                'user_id' => $user->getKey(),
                'status' => Instructor::STATUS_ACTIVE,
            ])->save();

            $application->setRawAttributes($fresh->getAttributes(), true);

            return $instructor;
        });
    }

    /**
     * Reject with a reason. Returns false when the application was already decided.
     */
    public function reject(InstructorApplication $application, Admin $admin, string $reason): bool
    {
        $updated = InstructorApplication::query()
            ->whereKey($application->getKey())
            ->where('status', InstructorApplication::STATUS_PENDING)
            ->update([
                'status' => InstructorApplication::STATUS_REJECTED,
                'rejection_reason' => trim($reason),
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
            ]);

        return $updated === 1;
    }

    /**
     * The welcome message the admin sends with the WhatsApp button after accepting.
     */
    public static function welcomeMessage(User $user): string
    {
        return __('instructors.welcome_whatsapp', [
            'name' => $user->name,
            'url' => route('login'),
        ]);
    }

    /**
     * Tell the admin by email, when an address is set. A mail failure must not lose the application.
     */
    private function notifyAdmin(InstructorApplication $application): void
    {
        $email = Setting::get(Setting::APPLICATIONS_NOTIFY_EMAIL);

        if ($email === null || $email === '') {
            return;
        }

        try {
            Notification::route('mail', $email)->notify(new InstructorApplicationSubmitted($application));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
