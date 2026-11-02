<?php

namespace App\Notifications;

use App\Models\InstructorApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the admin's notification address that someone asked to teach.
 */
class InstructorApplicationSubmitted extends Notification
{
    public function __construct(private readonly InstructorApplication $application) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->application->user;

        return (new MailMessage)
            ->subject(__('instructors.mail.submitted_subject', ['name' => $user->name]))
            ->greeting(__('instructors.mail.submitted_greeting'))
            ->line(__('instructors.mail.submitted_line', [
                'name' => $user->name,
                'specialty' => $this->application->specialty,
                'city' => $this->application->city,
            ]))
            ->action(__('instructors.mail.submitted_action'), route('admin.applications.index'));
    }
}
