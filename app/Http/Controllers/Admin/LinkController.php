<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));

        $links = Link::query()
            ->with('user:id,name,email')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('original_url', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"))))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/Links', [
            'links' => $links,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Activa o desactiva un link de cualquier usuario (por ejemplo, uno abusivo).
     */
    public function toggle(Link $link): RedirectResponse
    {
        $link->update(['is_active' => ! $link->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $link->is_active ? "/{$link->code} reactivado." : "/{$link->code} desactivado.",
        ]);

        return back();
    }
}
