<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRejected extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu cuenta de Zep no fue habilitada')
            ->greeting('Hola')
            ->line('Tu cuenta en Zep no fue habilitada para acortar links.')
            ->line('Si creés que es un error, respondé este mail.')
            ->salutation('Zep');
    }
}
