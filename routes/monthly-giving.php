<?php

declare(strict_types=1);

use App\Http\Controllers\Public\MonthlyGivingController;
use Illuminate\Support\Facades\Route;

/*
 * The mandate setup redirect flow — see docs/06-UI-UX-FOUNDATION.md §6.
 * Full navigations, never a modal.
 */
Route::get('/donate/monthly/{uuid}/redirecting', [MonthlyGivingController::class, 'redirecting'])->name('donate.monthly.redirecting');
Route::get('/donate/monthly/{uuid}/authorize', [MonthlyGivingController::class, 'authorize'])->name('donate.monthly.authorize');
Route::post('/donate/monthly/{uuid}/authorize', [MonthlyGivingController::class, 'complete'])
    ->middleware('throttle:10,1')
    ->name('donate.monthly.complete');
Route::get('/donate/monthly/{uuid}/return', [MonthlyGivingController::class, 'return'])->name('donate.monthly.return');
