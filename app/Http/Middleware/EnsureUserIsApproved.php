<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las cuentas nuevas quedan pendientes hasta que el admin las aprueba.
 * Mientras tanto (o si fueron rechazadas) solo ven la pantalla de espera.
 */
class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            if ($request->expectsJson()) {
                abort(403, 'Tu cuenta todavía no fue aprobada.');
            }

            return redirect()->route('pending');
        }

        return $next($request);
    }
}
