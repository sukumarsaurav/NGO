<?php

declare(strict_types=1);

use App\Http\Controllers\Public\CampaignController;
use App\Http\Controllers\Public\FundraiserRequestController;
use Illuminate\Support\Facades\Route;

/*
 * Category pages live at /causes/{category}, campaign detail pages at
 * /campaigns/{slug} — two separate namespaces so neither can shadow the
 * other. See docs/07-SEO.md §1.
 */
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/causes/{category}', [CampaignController::class, 'showCategory'])->name('campaigns.category');
Route::get('/monthly-giving', [CampaignController::class, 'monthlyGiving'])->name('campaigns.monthly-giving');

/*
 * Rate limited — public forms attract spam. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Fundraiser requests".
 */
Route::get('/start-fundraiser', [FundraiserRequestController::class, 'show'])->name('fundraiser.show');
Route::post('/start-fundraiser', [FundraiserRequestController::class, 'store'])
    ->middleware('throttle:3,60')
    ->name('fundraiser.store');

Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
