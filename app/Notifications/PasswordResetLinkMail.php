<?php

namespace App\Notifications;

use App\Support\PasswordResetLinks;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The reset link for someone who asked for it and has an email on their account.
 */
class PasswordResetLinkMail extends Notification
{
    public function __construct(private readonly string $url) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('accounts.reset.mail_subject'))
            ->greeting(__('accounts.reset.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('accounts.reset.mail_line'))
            ->action(__('accounts.reset.mail_action'), $this->url)
            ->line(__('accounts.reset.mail_expiry', ['minutes' => PasswordResetLinks::LIFETIME_MINUTES]))
            ->line(__('accounts.reset.mail_ignore'));
    }
}
