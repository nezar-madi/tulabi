<?php

namespace App\Support;

use App\Models\TeachingClass;
use Illuminate\Validation\ValidationException;

/**
 * Status changes of a class:
 *   draft -> published (needs the instructor's page address)
 *   published <-> closed (closed: still shown, takes no new requests)
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
        $this->ensure($class, [TeachingClass::STATUS_DRAFT, TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED]);

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
     * @param  list<string>  $from
     */
    private function ensure(TeachingClass $class, array $from, string $message = 'classes.wrong_status'): void
    {
        if (! in_array($class->status, $from, true)) {
            throw ValidationException::withMessages(['class' => __($message)]);
        }
    }
}
