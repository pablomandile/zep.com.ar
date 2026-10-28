<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public const ABILITIES = ['links:read', 'links:write'];

    public function index(Request $request): Response
    {
        return Inertia::render('settings/ApiTokens', [
            'tokens' => $request->user()->tokens()
                ->latest('id')
                ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']),
            'abilities' => self::ABILITIES,
            'apiBase' => url('/api/v1'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['in:'.implode(',', self::ABILITIES)],
        ], [], ['name' => 'nombre', 'abilities' => 'permisos']);

        $token = $request->user()->createToken($data['name'], $data['abilities']);

        // El token en texto plano solo se muestra esta vez.
        Inertia::flash('plainToken', $token->plainTextToken);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token creado. Copialo ahora: no se vuelve a mostrar.']);

        return back();
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token revocado.']);

        return back();
    }
}
