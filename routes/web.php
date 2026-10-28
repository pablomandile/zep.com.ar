<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\RedirectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect()->route('dashboard')
        : Inertia::render('Welcome');
})->name('home');

Route::get('terminos', [LegalController::class, 'terms'])->name('terms');
Route::get('privacidad', [LegalController::class, 'privacy'])->name('privacy');

Route::middleware('auth')->group(function () {
    Route::get('terminos/aceptar', [LegalController::class, 'accept'])->name('terms.accept');
    Route::post('terminos/aceptar', [LegalController::class, 'storeAcceptance'])->name('terms.accept.store');
});

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->get('pendiente', function (Request $request) {
    return $request->user()->isApproved()
        ? redirect()->route('dashboard')
        : Inertia::render('auth/Pending', ['status' => $request->user()->status]);
})->name('pending');

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('dashboard', [LinkController::class, 'create'])->name('dashboard');

    Route::get('links', [LinkController::class, 'index'])->name('links.index');
    Route::post('links', [LinkController::class, 'store'])->middleware('throttle:links')->name('links.store');
    Route::get('links/{link}', [LinkController::class, 'show'])->name('links.show');
    Route::patch('links/{link}', [LinkController::class, 'update'])->name('links.update');
    Route::delete('links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/approve', [Admin\UserController::class, 'approve'])->name('users.approve');
        Route::post('users/{user}/reject', [Admin\UserController::class, 'reject'])->name('users.reject');

        Route::get('links', [Admin\LinkController::class, 'index'])->name('links.index');
        Route::post('links/{link}/toggle', [Admin\LinkController::class, 'toggle'])->name('links.toggle');
    });
});

require __DIR__.'/settings.php';

// zep.com.ar/{code}: va como fallback para quedar detrás de todas las rutas,
// incluidas las que registran los paquetes (Fortify).
Route::get('{code}', RedirectController::class)
    ->where('code', '[A-Za-z0-9_-]+')
    ->fallback()
    ->name('redirect');
