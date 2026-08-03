<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentsController;
use App\Http\Controllers\Portal\DonationsController;
use App\Http\Controllers\Portal\NoticesController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\SubscriptionsController;
use Illuminate\Support\Facades\Route;

/*
 * Member/donor self-service. Separate from Filament's /admin and /manager
 * panels entirely — see docs/06-UI-UX-FOUNDATION.md §9 (portal IA).
 */
Route::middleware(['auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/download', [DocumentsController::class, 'download'])->name('documents.download');

    Route::get('/donations', [DonationsController::class, 'index'])->name('donations.index');
    Route::get('/donations/receipts/{receipt}', [DonationsController::class, 'downloadReceipt'])->name('donations.receipt');
    Route::post('/donations/pan', [DonationsController::class, 'updatePan'])->name('donations.pan');
    Route::get('/donations/annual-statement/{financialYear}', [DonationsController::class, 'downloadAnnualStatement'])
        ->where('financialYear', '\d{4}-\d{2}')
        ->name('donations.annual-statement');

    Route::get('/notices', [NoticesController::class, 'index'])->name('notices.index');
    Route::get('/notices/{notice}', [NoticesController::class, 'show'])->name('notices.show');

    Route::get('/subscriptions', [SubscriptionsController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{subscription}/pause', [SubscriptionsController::class, 'pause'])->name('subscriptions.pause');
    Route::post('/subscriptions/{subscription}/resume', [SubscriptionsController::class, 'resume'])->name('subscriptions.resume');
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionsController::class, 'cancel'])->name('subscriptions.cancel');
});
