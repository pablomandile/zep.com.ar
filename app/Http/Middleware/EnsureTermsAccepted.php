<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frena a quien no aceptó la versión vigente de los términos y la privacidad.
 *
 * Cubre lo que no pasa por la casilla del registro: el alta con Google y
 * cualquier cambio futuro de versión (`config('legal.version')`).
 *
 * Va en todo el grupo `web` y no ruta por ruta: así una ruta nueva nace
 * cubierta, y lo que se exceptúa está a la vista en una sola lista.
 */
class EnsureTermsAccepted
{
    /**
     * Lo que se puede hacer sin aceptar: leer los textos, salir, borrar la
     * cuenta (es un derecho que no depende de aceptar nada), verificar el
     * email y seguir un link corto (no es una pantalla de la app).
     *
     * @var list<string>
     */
    public const EXEMPT = [
        'terms',
        'terms.*',
        'privacy',
        'home',
        'logout',
        'profile.destroy',
        'verification.*',
        'redirect',
        'storage.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->hasAcceptedCurrentTerms() || $this->exempt($request)) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Antes de seguir tenés que aceptar los términos y la política de privacidad.',
            ], 409);
        }

        // `guest()` guarda la URL pedida, si era un GET, para volver ahí al aceptar.
        return redirect()->guest(route('terms.accept'));
    }

    private function exempt(Request $request): bool
    {
        $name = $request->route()?->getName();

        // Sin nombre: rutas de paquetes (.well-known, sanctum), no pantallas.
        return $name === null || Str::is(self::EXEMPT, $name);
    }
}
