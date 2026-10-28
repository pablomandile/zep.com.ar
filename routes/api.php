<?php

use App\Http\Controllers\Api\V1\LinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    // Solo los tokens del admin: un token de otra cuenta recibe 403.
    ->middleware(['auth:sanctum', 'approved', 'admin', 'throttle:api'])
    ->name('api.')
    ->group(function () {
        Route::middleware('abilities:links:read')->group(function () {
            Route::get('links', [LinkController::class, 'index'])->name('links.index');
            Route::get('links/{code}', [LinkController::class, 'show'])->name('links.show');
            Route::get('links/{code}/stats', [LinkController::class, 'stats'])->name('links.stats');
        });

        Route::middleware('abilities:links:write')->group(function () {
            Route::post('links', [LinkController::class, 'store'])->name('links.store');
            Route::patch('links/{code}', [LinkController::class, 'update'])->name('links.update');
            Route::delete('links/{code}', [LinkController::class, 'destroy'])->name('links.destroy');
        });
    });
