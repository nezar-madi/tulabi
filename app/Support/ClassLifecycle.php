<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Status changes of a class:
 *   draft -> published (needs the page address, a promotional image, not ended, no clash)
 *   published <-> closed (closed: still shown, takes no new requests)
 *   published/closed -> suspended (automatic, once at the start date, see checkStart) -> published (a new start date)
 *   published/closed/suspended, not ended -> canceled (final; owes nothing, takes no payments)
 *   draft -> deleted (soft delete, its image file removed; a class that was ever public is canceled instead)
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

        // An ended class is not opened to visitors again.
        if ($class->hasEnded()) {
            throw ValidationException::withMessages(['class' => __('classes.publish_ended')]);
        }

        // A draft is not checked while saved; it may not go live over another class's sessions.
        if ($class->status === TeachingClass::STATUS_DRAFT) {
            $this->ensureNoClash($class, 'class');
        }

        $class->forceFill([
            'status' => TeachingClass::STATUS_PUBLISHED,
            'published_at' => $class->published_at ?? now(),
            // Published on or after its start date: the instructor starts it as it is, no automatic check.
            'start_checked_at' => $class->start_checked_at
                ?? ($class->starts_on->toDateString() <= TulabiClock::today() ? now() : null),
        ])->save();
    }

    public function close(TeachingClass $class): void
    {
        $this->ensure($class, [TeachingClass::STATUS_PUBLISHED]);

        $class->forceFill(['status' => TeachingClass::STATUS_CLOSED])->save();
    }

    public function cancel(TeachingClass $class, ?string $reason): void
    {
        // A draft was never public: it is deleted, not canceled.
        $this->ensure($class, [
            TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED, TeachingClass::STATUS_SUSPENDED,
        ], $class->status === TeachingClass::STATUS_DRAFT ? 'classes.cancel_not_draft' : 'classes.wrong_status');

        // A class that already ran is not canceled: that would erase what its students still owe.
        // One student's balance is let go by removing that student instead.
        if ($class->hasEnded()) {
            throw ValidationException::withMessages(['class' => __('classes.cancel_ended')]);
        }

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

        // A draft was never public: its image file goes with it.
        app(ClassImage::class)->delete($class);
        $class->delete();
    }

    /**
     * «تعليق البدء تلقائيًا»: checked once, when a class reaches its start date (Libya). With fewer
     * accepted students than its minimum it becomes suspended; either way the check is recorded and
     * never runs again for that start (a student leaving mid-course does not stop the class).
     * Returns true when it was suspended.
     */
    public function checkStart(TeachingClass $class): bool
    {
        if (! $this->isDueForStartCheck($class)) {
            return false;
        }

        $underfilled = $class->enrollments()->where('status', Enrollment::STATUS_ACCEPTED)->count() < (int) $class->min_seats;

        $class->forceFill([
            'start_checked_at' => now(),
            ...($underfilled ? ['status' => TeachingClass::STATUS_SUSPENDED, 'suspended_at' => now()] : []),
        ])->save();

        return $underfilled;
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
            ->whereNull('start_checked_at')
            ->where('starts_on', '<=', TulabiClock::today())
            ->when($instructorId !== null, fn ($query) => $query->where('instructor_id', $instructorId))
            // By id, not by page: each check stamps the class, which takes it out of this query.
            ->lazyById()
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

        // A weekly class starts on one of its days (the hand-picked dates move with the start).
        if ($class->schedule_type === TeachingClass::SCHEDULE_WEEKLY
            && ! in_array(Carbon::parse($newStart)->dayOfWeek, $class->days ?? [], true)) {
            throw ValidationException::withMessages(['starts_on' => __('classes.start_not_meeting_day', ['days' => ClassText::days($class->days)])]);
        }

        $class->forceFill([
            'status' => TeachingClass::STATUS_PUBLISHED,
            'suspended_at' => null,
            'starts_on' => $newStart,
            'ends_on' => $moved($class->ends_on?->toDateString()),
            'manual_dates' => $class->manual_dates === null ? null : array_map($moved, $class->manual_dates),
            // Restarting is the instructor's decision to run it with the students it has: no second check.
            'start_checked_at' => now(),
            // The accepted students see that the dates moved.
            'schedule_changed_at' => now(),
        ]);

        // The new dates may not land on another active class's sessions.
        $this->ensureNoClash($class, 'starts_on');

        $class->save();
    }

    private function ensureNoClash(TeachingClass $class, string $errorKey): void
    {
        $clash = ScheduleClash::find($class->instructor, [
            'starts_on' => $class->starts_on->toDateString(),
            'ends_on' => $class->ends_on?->toDateString(),
            'schedule_type' => $class->schedule_type,
            'days' => $class->days,
            'manual_dates' => $class->manual_dates,
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
        ], $class->getKey());

        if ($clash !== null) {
            throw ValidationException::withMessages([$errorKey => ScheduleClash::message($clash)]);
        }
    }

    /** Auto-suspend on, public, its start date reached, not ended, and not checked yet. */
    private function isDueForStartCheck(TeachingClass $class): bool
    {
        if (! $class->auto_suspend || $class->start_checked_at !== null
            || ! in_array($class->status, [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED], true)) {
            return false;
        }

        return $class->starts_on !== null
            && $class->starts_on->toDateString() <= TulabiClock::today()
            && ! $class->hasEnded();
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
