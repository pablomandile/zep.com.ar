<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\NewUserPendingApproval;
use Illuminate\Support\Facades\Notification;

/**
 * Lo que pasa después de crear una cuenta, venga del formulario o de Google.
 */
class OnboardNewUser
{
    public function handle(User $user): void
    {
        $user->applyInitialStatus();

        if ($user->isApproved()) {
            return;
        }

        $admins = User::where('is_admin', true)->get();

        if ($admins->isEmpty() && filled(config('shortener.admin_email'))) {
            Notification::route('mail', config('shortener.admin_email'))
                ->notify(new NewUserPendingApproval($user));

            return;
        }

        Notification::send($admins, new NewUserPendingApproval($user));
    }
}
