<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Los términos, la política de privacidad y su aceptación.
 *
 * Los textos viven en `resources/legal/*.md` y se convierten acá: una sola
 * fuente, que se edita como texto y no como plantilla de Vue.
 */
class LegalController extends Controller
{
    private const DOCUMENTS = [
        'terms' => 'Términos y condiciones',
        'privacy' => 'Política de privacidad',
    ];

    public function terms(): Response
    {
        return $this->document('terms');
    }

    public function privacy(): Response
    {
        return $this->document('privacy');
    }

    public function accept(Request $request): Response
    {
        return Inertia::render('auth/AcceptTerms', [
            'firstTime' => $request->user()?->terms_version === null,
            'version' => $this->readableVersion(),
        ]);
    }

    public function storeAcceptance(Request $request): RedirectResponse
    {
        $request->validate(
            ['terms' => ['accepted']],
            ['terms.accepted' => 'Para seguir tenés que aceptar los términos y la política de privacidad.'],
        );

        $request->user()->acceptTerms();

        return redirect()->intended(route('dashboard'));
    }

    private function document(string $which): Response
    {
        $markdown = (string) file_get_contents(resource_path("legal/{$which}.md"));
        $markdown = str_replace(
            [':contact', ':domain'],
            [(string) config('legal.contact_email'), (string) config('shortener.domain')],
            $markdown,
        );

        return Inertia::render('legal/Document', [
            'title' => self::DOCUMENTS[$which],
            'version' => $this->readableVersion(),
            // Texto propio, del repo: no hay entrada de usuario que escapar.
            'html' => Str::markdown($markdown, ['html_input' => 'escape']),
        ]);
    }

    private function readableVersion(): string
    {
        $version = config('legal.version');

        // El locale de la app ya es `es`: «27 de octubre de 2026».
        return (new Carbon(is_string($version) ? $version : 'now'))
            ->translatedFormat('j \d\e F \d\e Y');
    }
}
