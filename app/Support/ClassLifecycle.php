<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Status changes of a class:
 *   draft -> published (needs the instructor's page address and a promotional image)
 *   published <-> closed (closed: still shown, takes no new requests)
 *   published/closed -> suspended (automatic, see checkStart) -> published (a new start date)
 *   any -> canceled (final; owes nothing, takes no payments)
 *   draft -> deleted (soft delete; a class that was ever public is canceled instead)
 */
class ClassLifecycle
{
    public function publish(TeachingClass $class): void
    {
        $this->ensure($class, [TeachingClass::STATUS_DRAFT, TeachingClass::STATUS_CLOSED]);

        if ($class->instructor->slug === null) {
            throw ValidationException::withMessages(['class' => __('classes.needs_slug')]);
        }

        // A class shown on the public page needs its promotional image.
        if ($class->image_path === null) {
            throw ValidationException::withMessages(['class' => __('classes.image_required_to_publish')]);
        }

        $class->forceFill([
            'status' => TeachingClass::STATUS_PUBLISHED,
            'published_at' => $class->published_at ?? now(),
        ])->save();
    }

    public function close(TeachingClass $class): void
    {
        $this->ensure($class, [TeachingClass::STATUS_PUBLISHED]);

        $class->forceFill(['status' => TeachingClass::STATUS_CLOSED])->save();
    }

    public function cancel(TeachingClass $class, ?string $reason): void
    {
        $this->ensure($class, [
            TeachingClass::STATUS_DRAFT, TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED, TeachingClass::STATUS_SUSPENDED,
        ]);

        $reason = trim((string) $reason);

        $class->forceFill([
            'status' => TeachingClass::STATUS_CANCELED,
            'canceled_at' => now(),
            'cancel_reason' => $reason === '' ? null : $reason,
        ])->save();
    }

    public function delete(TeachingClass $class): void
    {
        $this->ensure($class, [TeachingClass::STATUS_DRAFT], 'classes.delete_only_drafts');

        $class->delete();
    }

    /**
     * «تعليق البدء تلقائيًا»: a class that has reached its start date (Libya) and has fewer
     * accepted students than its minimum becomes suspended. Returns true when it was suspended.
     */
    public function checkStart(TeachingClass $class): bool
    {
        if (! $this->isUnderfilledAtStart($class)) {
            return false;
        }

        $class->forceFill(['status' => TeachingClass::STATUS_SUSPENDED, 'suspended_at' => now()])->save();

        return true;
    }

    /**
     * Run checkStart over every class (the scheduled command), or one instructor's classes.
     *
     * @return int how many classes were suspended
     */
    public function suspendUnderfilled(?int $instructorId = null): int
    {
        $suspended = 0;

        TeachingClass::query()
            ->whereIn('status', [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED])
            ->where('auto_suspend', true)
            ->where('starts_on', '<=', TulabiClock::today())
            ->when($instructorId !== null, fn ($query) => $query->where('instructor_id', $instructorId))
            ->each(function (TeachingClass $class) use (&$suspended) {
                if ($this->checkStart($class)) {
                    $suspended++;
                }
            });

        return $suspended;
    }

    /**
     * A suspended class starts again on a new date (today or later). The end date and any hand-picked
     * dates move by the same number of days, so the class keeps its length and rhythm.
     */
    public function reactivate(TeachingClass $class, string $newStart): void
    {
        $this->ensure($class, [TeachingClass::STATUS_SUSPENDED]);

        if ($newStart < TulabiClock::today()) {
            throw ValidationException::withMessages(['starts_on' => __('classes.restart_date_past')]);
        }

        $shift = (int) Carbon::parse($class->starts_on->toDateString())->diffInDays(Carbon::parse($newStart), false);

        $moved = fn (?string $date) => $date === null ? null : Carbon::parse($date)->addDays($shift)->toDateString();

        $class->forceFill([
            'status' => TeachingClass::STATUS_PUBLISHED,
            'suspended_at' => null,
            'starts_on' => $newStart,
            'ends_on' => $moved($class->ends_on?->toDateString()),
            'manual_dates' => $class->manual_dates === null ? null : array_map($moved, $class->manual_dates),
        ])->save();
    }

    private function isUnderfilledAtStart(TeachingClass $class): bool
    {
        if (! $class->auto_suspend || ! in_array($class->status, [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED], true)) {
            return false;
        }

        if ($class->starts_on === null || $class->starts_on->toDateString() > TulabiClock::today()) {
            return false;
        }

        $accepted = $class->enrollments()->where('status', Enrollment::STATUS_ACCEPTED)->count();

        return $accepted < (int) $class->min_seats;
    }

    /**
     * @param  list<string>  $from
     */
    private function ensure(TeachingClass $class, array $from, string $message = 'classes.wrong_status'): void
    {
        if (! in_array($class->status, $from, true)) {
            throw ValidationException::withMessages(['class' => __($message)]);
        }
    }
}
