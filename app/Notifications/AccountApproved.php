<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
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
            ->subject('Tu cuenta de Zep fue aprobada')
            ->greeting('¡Hola!')
            ->line('Tu cuenta ya está habilitada. Desde ahora podés acortar links y crear tokens para la API.')
            ->action('Empezar a acortar', route('dashboard'))
            ->salutation('Zep');
    }
}
