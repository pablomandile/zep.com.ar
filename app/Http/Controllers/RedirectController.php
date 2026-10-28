<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\ClickRecorder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends Controller
{
    /**
     * La ruta más usada de la app: zep.com.ar/{code}.
     *
     * Usa 302 y no 301: el navegador cachea los 301 y esos clics dejarían de contarse.
     */
    public function __invoke(Request $request, string $code, ClickRecorder $clicks): Response
    {
        $link = Link::where('code', $code)->first();

        abort_if($link === null, 404);

        if (! $link->isAvailable()) {
            return Inertia::render('links/Unavailable', [
                'reason' => $link->isExpired() ? 'expired' : 'inactive',
            ])->toResponse($request)->setStatusCode(410);
        }

        $clicks->record($link, $request);

        return redirect()->away($link->original_url, 302)
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
