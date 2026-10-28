<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountApproved;
use App\Notifications\AccountRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status');
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->withCount('links')
            ->when(UserStatus::tryFrom($status), fn ($q, $s) => $q->where('status', $s))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            // Pendientes primero: es lo que el admin viene a resolver.
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [UserStatus::Pending->value])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => ['status' => $status, 'search' => $search],
            'pendingCount' => User::where('status', UserStatus::Pending)->count(),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $user->approve($request->user());
        $user->notify(new AccountApproved);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} ya puede usar Zep."]);

        return back();
    }

    /**
     * Rechaza una cuenta pendiente o revoca una aprobada: pierde el acceso y sus tokens.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No podés revocar tu propia cuenta.']);

            return back();
        }

        $user->reject($request->user());
        $user->notify(new AccountRejected);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Se quitó el acceso a {$user->name}."]);

        return back();
    }
}
