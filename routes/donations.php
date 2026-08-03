<?php

declare(strict_types=1);

use App\Http\Controllers\Public\DonationPageController;
use Illuminate\Support\Facades\Route;

/*
 * Public donation flow. The actual payment logic lives in the DonationForm
 * and DonationPending Livewire components — these routes are page shells.
 * See docs/06-UI-UX-FOUNDATION.md §5-6.
 */
Route::get('/donate', [DonationPageController::class, 'show'])->name('donate.show');
Route::get('/donate/success/{donation}', [DonationPageController::class, 'success'])->name('donate.success');
Route::get('/donate/pending/{donation}', [DonationPageController::class, 'pending'])->name('donate.pending');
Route::get('/donate/failed/{donation}', [DonationPageController::class, 'failed'])->name('donate.failed');
