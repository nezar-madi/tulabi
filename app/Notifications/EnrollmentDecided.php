<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the student (when they have an email): the request was accepted or rejected.
 */
class EnrollmentDecided extends Notification
{
    public function __construct(private readonly Enrollment $enrollment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $class = $this->enrollment->teachingClass;
        $accepted = $this->enrollment->status === Enrollment::STATUS_ACCEPTED;
        $key = $accepted ? 'accepted' : 'rejected';

        $mail = (new MailMessage)
            ->subject(__("enrollments.mail.{$key}_subject", ['class' => $class->title]))
            ->greeting(__('enrollments.mail.greeting', ['name' => $notifiable->name]))
            ->line(__("enrollments.mail.{$key}_line", [
                'class' => $class->title,
                'instructor' => $class->instructor->display_name,
            ]));

        if (! $accepted && $this->enrollment->decision_reason !== null) {
            $mail->line(__('enrollments.mail.reason', ['reason' => $this->enrollment->decision_reason]));
        }

        return $mail->action(__('enrollments.mail.open'), route('student.classes.show', $this->enrollment));
    }
}
