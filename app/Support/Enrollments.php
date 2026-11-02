<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Notifications\EnrollmentDecided;
use App\Notifications\EnrollmentRequested;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Places in classes:
 *   (none) / rejected / withdrawn / canceled -> requested   (the student asks)
 *   requested -> accepted | rejected                        (the instructor decides)
 *   accepted -> canceled                                    (the instructor removes the student)
 *   requested | accepted -> withdrawn                       (the student leaves)
 * Every change is a conditional update, so two clicks at once change it only once.
 */
class Enrollments
{
    public function request(User $user, TeachingClass $class, ?string $note): Enrollment
    {
        if (! $class->acceptsRequests() || ! $class->instructor->isPublic()) {
            throw ValidationException::withMessages(['enrollment' => __('enrollments.not_open')]);
        }

        if ($class->instructor->user_id === $user->getKey()) {
            throw ValidationException::withMessages(['enrollment' => __('enrollments.own_class')]);
        }

        $note = self::text($note);

        $enrollment = DB::transaction(function () use ($user, $class, $note) {
            /** @var Enrollment|null $existing */
            $existing = Enrollment::query()
                ->where('class_id', $class->getKey())
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null && in_array($existing->status, [Enrollment::STATUS_REQUESTED, Enrollment::STATUS_ACCEPTED], true)) {
                throw ValidationException::withMessages(['enrollment' => __('enrollments.already_'.$existing->status)]);
            }

            $enrollment = $existing ?? new Enrollment;
            $enrollment->forceFill([
                'class_id' => $class->getKey(),
                'user_id' => $user->getKey(),
                'status' => Enrollment::STATUS_REQUESTED,
                'currency_code' => $class->currency_code,
                'note' => $note,
                'decision_reason' => null,
                'requested_at' => now(),
                'decided_at' => null,
                'withdrawn_at' => null,
            ])->save();

            return $enrollment;
        });

        $this->notify($class->instructor->user, new EnrollmentRequested($enrollment));

        return $enrollment;
    }

    /**
     * Accept. The fee is the class price the first time; a student accepted before keeps
     * the fee they had. Returns false when the request was no longer waiting.
     */
    public function accept(Enrollment $enrollment): bool
    {
        $class = $enrollment->teachingClass;

        if ($class->isCanceled()) {
            throw ValidationException::withMessages(['enrollment' => __('enrollments.class_canceled')]);
        }

        $updated = Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->where('status', Enrollment::STATUS_REQUESTED)
            ->update([
                'status' => Enrollment::STATUS_ACCEPTED,
                'fee' => $enrollment->fee ?? $class->price,
                'decided_at' => now(),
                'accepted_at' => $enrollment->accepted_at ?? now(),
                'decision_reason' => null,
            ]);

        if ($updated !== 1) {
            return false;
        }

        $enrollment->refresh();
        $this->notify($enrollment->user, new EnrollmentDecided($enrollment));

        return true;
    }

    public function reject(Enrollment $enrollment, ?string $reason): bool
    {
        $updated = $this->move($enrollment, Enrollment::STATUS_REQUESTED, Enrollment::STATUS_REJECTED, [
            'decided_at' => now(),
            'decision_reason' => self::text($reason),
        ]);

        if ($updated) {
            $enrollment->refresh();
            $this->notify($enrollment->user, new EnrollmentDecided($enrollment));
        }

        return $updated;
    }

    /** The instructor takes an accepted student out of the class. */
    public function remove(Enrollment $enrollment, ?string $reason): bool
    {
        return $this->move($enrollment, Enrollment::STATUS_ACCEPTED, Enrollment::STATUS_CANCELED, [
            'decided_at' => now(),
            'decision_reason' => self::text($reason),
        ]);
    }

    /** The student takes back a request or leaves the class. */
    public function withdraw(Enrollment $enrollment): bool
    {
        $updated = Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->whereIn('status', [Enrollment::STATUS_REQUESTED, Enrollment::STATUS_ACCEPTED])
            ->update(['status' => Enrollment::STATUS_WITHDRAWN, 'withdrawn_at' => now()]);

        return $updated === 1;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function move(Enrollment $enrollment, string $from, string $to, array $extra): bool
    {
        return Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->where('status', $from)
            ->update(['status' => $to, ...$extra]) === 1;
    }

    /** Email only reaches people who gave one; a mail failure must not undo the change. */
    private function notify(?User $user, Notification $notification): void
    {
        if ($user === null || $user->email === null) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
