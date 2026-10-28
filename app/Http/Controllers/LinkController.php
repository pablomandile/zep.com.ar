<?php

namespace App\Http\Controllers;

use App\Actions\CreateShortLink;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Link;
use App\Services\LinkStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    /**
     * Pantalla principal: el formulario para acortar y los últimos links.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'recentLinks' => $request->user()->links()->latest('id')->limit(5)->get(),
            'aliasRules' => config('shortener.alias'),
        ]);
    }

    public function store(StoreLinkRequest $request, CreateShortLink $create): RedirectResponse
    {
        [$link, $created] = $create->handle($request->user(), $request->validated());

        Inertia::flash('createdLink', $link->toArray());
        Inertia::flash('toast', [
            'type' => $created ? 'success' : 'info',
            'message' => $created ? '¡Listo! Tu link corto está creado.' : 'Ya habías acortado esta URL: es el mismo link.',
        ]);

        return redirect()->route('dashboard');
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');

        $links = $request->user()->links()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('original_url', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")))
            ->when($status === 'active', fn ($q) => $q->available())
            ->when($status === 'inactive', fn ($q) => $q->where(fn ($q) => $q
                ->where('is_active', false)
                ->orWhere('expires_at', '<=', now())))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('links/Index', [
            'links' => $links,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function show(Request $request, Link $link, LinkStats $stats): Response
    {
        Gate::authorize('view', $link);

        $range = (int) $request->query('days', 30);
        $range = in_array($range, [7, 30, 90, 365], true) ? $range : 30;

        return Inertia::render('links/Show', [
            'link' => $link,
            'days' => $range,
            'stats' => $stats->for($link, Carbon::now()->subDays($range - 1)),
        ]);
    }

    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        $link->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link actualizado.']);

        return back();
    }

    public function destroy(Link $link): RedirectResponse
    {
        Gate::authorize('delete', $link);

        $link->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Link eliminado.']);

        return back();
    }
}
