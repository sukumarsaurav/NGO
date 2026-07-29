<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\ProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Member/donor self-service. Separate from Filament's /admin and /manager
 * panels entirely — see docs/06-UI-UX-FOUNDATION.md §9 (portal IA).
 */
Route::middleware(['auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
