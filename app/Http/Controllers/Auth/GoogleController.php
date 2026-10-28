<?php

namespace App\Http\Controllers\Auth;

use App\Actions\OnboardNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Si el email ya tiene cuenta, se vincula con Google; si no, se crea una
     * cuenta nueva que queda pendiente de aprobación como cualquier registro.
     */
    public function callback(OnboardNewUser $onboard): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se pudo iniciar sesión con Google. Probá de nuevo.']);

            return redirect()->route('login');
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $google->getId(),
                'avatar' => $google->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $google->getName() ?: $google->getEmail(),
                'email' => $google->getEmail(),
                'google_id' => $google->getId(),
                'avatar' => $google->getAvatar(),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $onboard->handle($user);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
