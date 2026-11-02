<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the instructor (when they have an email): a student asked to join a class.
 */
class EnrollmentRequested extends Notification
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

        return (new MailMessage)
            ->subject(__('enrollments.mail.requested_subject', ['class' => $class->title]))
            ->greeting(__('enrollments.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('enrollments.mail.requested_line', [
                'student' => $this->enrollment->user?->name,
                'class' => $class->title,
            ]))
            ->action(__('enrollments.mail.requested_action'), route('teach.requests.index'));
    }
}
