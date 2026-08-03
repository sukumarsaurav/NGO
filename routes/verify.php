<?php

declare(strict_types=1);

use App\Http\Controllers\Public\VerifyDocumentController;
use App\Http\Controllers\Public\VerifyReceiptController;
use Illuminate\Support\Facades\Route;

/*
 * Public, unauthenticated QR-verify pages — see
 * App\Http\Controllers\Public\VerifyDocumentController and VerifyReceiptController.
 */
// Registered before the generic /verify/{uuid} below — otherwise "receipt"
// would itself be matched as the {uuid} wildcard.
Route::get('/verify/receipt/{uuid}', [VerifyReceiptController::class, 'show'])->name('verify.receipt');
Route::get('/verify/{uuid}', [VerifyDocumentController::class, 'show'])->name('verify.show');
