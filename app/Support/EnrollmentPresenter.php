<?php

namespace App\Support;

use App\Models\Enrollment;

/**
 * The shapes an enrollment is sent to pages in. The meeting link is added only for
 * a student who is accepted in a class that still runs.
 */
class EnrollmentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forStudent(Enrollment $enrollment): array
    {
        $class = $enrollment->teachingClass;
        $instructor = $class->instructor;

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->displayStatus(),
            'fee' => self::money($enrollment->fee),
            'note' => $enrollment->note,
            'decision_reason' => $enrollment->decision_reason,
            'requested_at' => $enrollment->requested_at?->toIso8601String(),
            'decided_at' => $enrollment->decided_at?->toIso8601String(),
            'class' => [
                ...ClassPresenter::forPublic($class),
                'meeting_url' => $enrollment->isActiveMember() ? $class->meeting_url : null,
                'meeting_url_new' => $enrollment->isActiveMember() && $class->meetingUrlIsNew(),
                'meeting_url_changed_at' => $enrollment->isActiveMember() && $class->meetingUrlIsNew()
                    ? $class->meeting_url_changed_at->toIso8601String()
                    : null,
                'cancel_reason' => $class->isCanceled() ? $class->cancel_reason : null,
                'url' => $instructor->isPublic() && $class->isPubliclyVisible()
                    ? route('classes.show', ['slug' => $instructor->slug, 'class' => $class->id])
                    : null,
            ],
            'instructor' => [
                'display_name' => $instructor->display_name,
                'photo_url' => $instructor->photoUrl(),
                'url' => $instructor->publicUrl(),
                'whatsapp' => WhatsApp::link($instructor->whatsapp, __('enrollments.whatsapp_to_instructor', [
                    'name' => $instructor->display_name,
                    'class' => $class->title,
                ])),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forInstructor(Enrollment $enrollment): array
    {
        $user = $enrollment->user;
        $class = $enrollment->teachingClass;

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->displayStatus(),
            'fee' => self::money($enrollment->fee),
            'note' => $enrollment->note,
            'decision_reason' => $enrollment->decision_reason,
            'requested_at' => $enrollment->requested_at?->toIso8601String(),
            'accepted_at' => $enrollment->accepted_at?->toIso8601String(),
            'student' => $user === null ? null : [
                'name' => $user->name,
                'phone' => LibyanPhone::display($user->phone),
                'whatsapp' => WhatsApp::link($user->phone, __('enrollments.whatsapp_to_student', [
                    'name' => $user->name,
                    'class' => $class->title,
                ])),
            ],
            'class' => [
                'id' => $class->id,
                'title' => $class->title,
                'seats' => $class->seats,
            ],
        ];
    }

    private static function money(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = number_format((float) $value, Currency::DECIMALS, '.', '');

        return rtrim(rtrim($text, '0'), '.');
    }
}
