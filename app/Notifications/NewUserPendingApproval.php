<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserPendingApproval extends Notification
{
    public function __construct(public User $user) {}

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
            ->subject('Nueva cuenta para aprobar en Zep')
            ->greeting('¡Hola!')
            ->line("{$this->user->name} ({$this->user->email}) se registró en Zep y está esperando tu aprobación.")
            ->action('Revisar cuentas', route('admin.users.index'))
            ->salutation('Zep');
    }
}
